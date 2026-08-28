@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Welcome {{ Auth::user()->name }}!</h1>
    <p>You are logged in!</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-danger">Logout</button>
    </form>
</div>
@endsection
