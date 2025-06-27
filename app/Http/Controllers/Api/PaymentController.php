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
                'currency' => 'required|string|size:3',
                'account' => 'required|string',
            ]);

            // Get authenticated merchant
            $merchant = $request->account;
            // if (!$merchant || !$merchant->stripe_account_id) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Invalid merchant account'
            //     ], 400);
            // }

            Stripe::setApiKey(config('services.stripe.secret'));

            // Platform fee (€0.12)
            $applicationFeeAmount = 12; // 12 cents in EUR

            // Create payment intent
            $paymentIntent = PaymentIntent::create([

                'amount' => $request->amount,
                'currency' => $request->currency,
                'application_fee_amount' => $applicationFeeAmount,
                'transfer_data' => [
                    'destination' => $merchant,
                ],
                'payment_method_types' => ['card_present'],  // Only NFC tap-to-pay, no Google Pay popup
                'capture_method' => 'automatic', // Automatically capture after successful tap
                'metadata' => [
                    'merchant_id' => $merchant,
                    'merchant_stripe_account' => $merchant,
                ]
            ]);

            // Save transaction
            Transaction::create([
                'user_id' => 7, // or find the corresponding user ID if needed
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $request->amount,
                'platform_fee' => $applicationFeeAmount,
                'currency' => $request->currency,
                'status' => $paymentIntent->status,
                'payment_method_type' => 'card_present',
                'metadata' => [
                    'merchant_stripe_account' => $merchant,
                    'stripe_account_id' => $merchant
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

    public function createTerminalReader(Request $request)
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Create a new Terminal reader
            $reader = \Stripe\Terminal\Reader::create([
                'registration_code' => $request->registration_code,
                'label' => $request->label ?? 'NFC Reader',
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'reader_id' => $reader->id,
                    'label' => $reader->label,
                    'status' => $reader->status
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reader creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function processTerminalPayment(Request $request)
    {
        try {
            $request->validate([
                'payment_intent_id' => 'required|string',
                'reader_id' => 'required|string'
            ]);

            Stripe::setApiKey(config('services.stripe.secret'));

            // Get the payment intent
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);
            
            // Get the reader instance first
            $reader = \Stripe\Terminal\Reader::retrieve($request->reader_id);
            
            // Process the payment intent with the reader
            $reader = $reader->processPaymentIntent([
                'payment_intent' => $paymentIntent->id
            ]);

            // Wait for payment processing (in production, use webhooks)
            $maxAttempts = 10;
            $attempts = 0;
            while ($reader->action->status === 'processing' && $attempts < $maxAttempts) {
                sleep(1);
                $reader = \Stripe\Terminal\Reader::retrieve($reader->id);
                $attempts++;
            }

            if ($reader->action->status === 'succeeded') {
                // Payment was successful and automatically captured
                $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

                // Update transaction status
                Transaction::where('payment_intent_id', $paymentIntent->id)
                    ->update([
                        'status' => $paymentIntent->status,
                        'metadata' => array_merge(
                            (array) json_decode(Transaction::where('payment_intent_id', $paymentIntent->id)->value('metadata')),
                            ['reader_id' => $reader->id]
                        )
                    ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => $paymentIntent->status,
                        'amount' => $paymentIntent->amount,
                        // 'currency' => $paymentIntent->currency
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment processing failed or timed out'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terminal payment processing failed: ' . $e->getMessage()
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
