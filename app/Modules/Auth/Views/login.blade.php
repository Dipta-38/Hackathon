@extends("layouts.app")

@section("content")
<div class="container">
    <h2>Login</h2>
    <form method="POST" action="{{ route("login") }}">
        @csrf
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="{{ old("email") }}" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="remember" class="form-check-input">
            <label class="form-check-label">Remember Me</label>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
    <p class="mt-3"><a href="{{ route("password.request") }}">Forgot Password?</a></p>
    <p>Don"t have an account? <a href="{{ route("register") }}">Register</a></p>
</div>
@endsection
