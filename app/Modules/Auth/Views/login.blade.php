@extends('auth::layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold mb-4 text-center">Welcome back</h2>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" name="email" id="email" class="form-control form-control-lg" value="{{ old('email') }}" placeholder="you@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="••••••••" required>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">Login</button>
                </form>

                <div class="mt-4 text-center">
                    <a href="{{ route('password.request') }}" class="text-decoration-none">Forgot your password?</a>
                </div>
                <div class="mt-2 text-center">
                    <span>Need an account?</span>
                    <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">Create one</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
