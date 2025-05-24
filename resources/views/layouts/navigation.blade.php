&lt;nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    &lt;div class="container">
        &lt;a class="navbar-brand" href="{{ route('dashboard') }}">
            {{ config('app.name', 'Laravel') }}
        &lt;/a>
        &lt;button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            &lt;span class="navbar-toggler-icon">&lt;/span>
        &lt;/button>

        &lt;div class="collapse navbar-collapse" id="navbarSupportedContent">
            &lt;ul class="navbar-nav me-auto">
                @auth
                    &lt;li class="nav-item">
                        &lt;a class="nav-link" href="{{ route('dashboard') }}">Dashboard&lt;/a>
                    &lt;/li>
                @endauth
            &lt;/ul>

            &lt;ul class="navbar-nav ms-auto">
                @guest
                    &lt;li class="nav-item">
                        &lt;a class="nav-link" href="{{ route('auth.stripe.login') }}">Login with Stripe&lt;/a>
                    &lt;/li>
                @else
                    &lt;li class="nav-item">
                        &lt;a class="nav-link" href="{{ route('auth.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Logout
                        &lt;/a>
                        &lt;form id="logout-form" action="{{ route('auth.logout') }}" method="POST" class="d-none">
                            @csrf
                        &lt;/form>
                    &lt;/li>
                @endguest
            &lt;/ul>
        &lt;/div>
    &lt;/div>
&lt;/nav>
