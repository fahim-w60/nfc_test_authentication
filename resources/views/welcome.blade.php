<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="stripe-connection-status mb-4">
                        {{-- @if(auth()->user()->stripe_account_id) --}}
                            {{-- <div class="alert alert-success d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle mr-2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <span>Your Stripe account is successfully connected!</span>
                            </div>
                            
                            <div class="connection-details">
                                <h5>Stripe Account Details</h5>
                                <ul class="list-group">
                                    <li class="list-group-item">Connected Account ID: </li>
                                    <li class="list-group-item">Connection Status: Active</li>
                                </ul>
                            </div> --}}
                        {{-- @else --}}
                            <div class="alert alert-warning">
                                <h5 class="alert-heading">Stripe Account Not Connected</h5>
                                <p>You need to connect your Stripe account to receive payments.</p>
                                <hr>
                                <a href="{{ route('stripe.connect') }}" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-credit-card mr-2">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                        <line x1="1" y1="10" x2="23" y2="10"></line>
                                    </svg>
                                    Connect with Stripe Express
                                </a>
                            </div>
                        {{-- @endif --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>