@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Login</h2>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
        <input type="password" name="password" placeholder="Password" required>
        <label>
            <input type="checkbox" name="remember"> Remember Me
        </label>
        <button type="submit">Login</button>
    </form>
    <p><a href="{{ route('password.request') }}">Forgot Password?</a></p>
    <p>Don't have an account? <a href="{{ route('register') }}">Register</a></p>
</div>
@endsection