<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function createPaymentIntent(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1',
                'currency' => 'required|string|size:3'
            ]);

            // Get authenticated merchant
            $merchant = Auth::user();
            if (!$merchant || !$merchant->stripe_account_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid merchant account'
                ], 400);
            }

            Stripe::setApiKey(config('services.stripe.secret'));

            // Platform fee (€0.12)
            $applicationFeeAmount = 12; // 12 cents in EUR

            // Create payment intent
            $paymentIntent = PaymentIntent::create([

                'amount' => $request->amount,
                'currency' => $request->currency,
                'application_fee_amount' => $applicationFeeAmount,
                'transfer_data' => [
                    'destination' => $merchant->stripe_account_id,
                ],
                'payment_method_types' => ['card_present'],
                'capture_method' => 'manual', // For tap-to-pay, we'll capture later
                'metadata' => [
                    'merchant_id' => $merchant->id,
                    'merchant_stripe_account' => $merchant->stripe_account_id
                ]
            ]);

            // Save transaction
            Transaction::create([
                'user_id' => $merchant->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $request->amount,
                'platform_fee' => $applicationFeeAmount,
                'currency' => $request->currency,
                'status' => $paymentIntent->status,
                'payment_method_type' => 'card_present',
                'metadata' => [
                    'merchant_stripe_account' => $merchant->stripe_account_id
                ]
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment intent creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function processNfcPayment(Request $request)
    {
        try {
            $request->validate([
                'payment_intent_id' => 'required|string',
                'nfc_data' => 'required|array',
                'nfc_data.id' => 'required|string',
                'nfc_data.techTypes' => 'required|array'
            ]);

            Stripe::setApiKey(config('services.stripe.secret'));

            // Get the payment intent
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);
            
            // Verify payment intent is still valid
            if ($paymentIntent->status !== 'requires_payment_method') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment intent status: ' . $paymentIntent->status
                ], 400);
            }

            // In a real implementation, you would use Stripe Terminal SDK to process the NFC data
            // For now, we'll simulate the card read success and confirm the payment
            $paymentIntent->confirm([
                'payment_method_data' => [
                    'type' => 'card_present',
                    'card_present' => [
                        'nfc_data' => $request->nfc_data['id']
                    ]
                ]
            ]);

            // If confirmation successful, capture the payment
            if ($paymentIntent->status === 'requires_capture') {
                $paymentIntent = $paymentIntent->capture();
            }

            // Update transaction status
            Transaction::where('payment_intent_id', $paymentIntent->id)
                ->update([
                    'status' => $paymentIntent->status,
                    'metadata' => array_merge(
                        (array) json_decode(Transaction::where('payment_intent_id', $paymentIntent->id)->value('metadata')),
                        ['nfc_tag_id' => $request->nfc_data['id']]
                    )
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $paymentIntent->status,
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'NFC payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function capturePayment(Request $request)
    {
        try {
            $request->validate([
                'payment_intent_id' => 'required|string'
            ]);

            Stripe::setApiKey(config('services.stripe.secret'));

            // Capture the payment
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);
            $paymentIntent->capture();

            // Update transaction status
            Transaction::where('payment_intent_id', $paymentIntent->id)
                ->update(['status' => $paymentIntent->status]);

            return response()->json([
                'success' => true,
                'message' => 'Payment captured successfully',
                'data' => [
                    'payment_intent' => $paymentIntent->id,
                    'amount' => $paymentIntent->amount,
                    'status' => $paymentIntent->status
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment capture failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTransactions(Request $request)
    {
        try {
            $merchant = Auth::user();
            $transactions = Transaction::where('user_id', $merchant->id)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'total_amount' => $transactions->sum('amount'),
                    'total_platform_fees' => $transactions->sum('platform_fee')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions: ' . $e->getMessage()
            ], 500);
        }
    }
}
