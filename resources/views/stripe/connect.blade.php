@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Connect Stripe Account</div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="stripe-connect-instructions">
                        <h5>How it works:</h5>
                        <ol>
                            <li>Click the button below to connect with Stripe</li>
                            <li>You'll be redirected to Stripe's secure platform</li>
                            <li>Complete the quick onboarding process</li>
                            <li>You'll be returned to our site when finished</li>
                        </ol>

                        <div class="stripe-connect-button text-center mt-5">
                            <a href="{{ route('stripe.connect') }}" class="btn btn-primary btn-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-credit-card mr-2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                                Connect with Stripe Express
                            </a>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    By connecting your Stripe account, you agree to our 
                                    <a href="#">Terms of Service</a> and Stripe's 
                                    <a href="https://stripe.com/connect-account/legal" target="_blank">Connected Account Agreement</a>.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection