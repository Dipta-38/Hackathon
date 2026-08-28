@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Forgot Password</h2>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <input type="email" name="email" placeholder="Your Email" required>
        <button type="submit">Send Reset Link</button>
    </form>
    <p><a href="{{ route('login') }}">Back to Login</a></p>
</div>
@endsection