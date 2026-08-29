@extends('auth::layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold mb-4 text-center">Create your account</h2>

                <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Full name</label>
                        <input type="text" name="name" id="name" class="form-control form-control-lg" value="{{ old('name') }}" placeholder="Jane Doe" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" name="email" id="email" class="form-control form-control-lg" value="{{ old('email') }}" placeholder="you@example.com" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="dob" class="form-label">Date of birth</label>
                                <input type="date" name="dob" id="dob" class="form-control form-control-lg" value="{{ old('dob') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nid_scan" class="form-label">Scan your NID</label>
                                <input type="file" id="nid_scan" class="form-control form-control-lg nid-scan-input" accept="image/*" capture="environment" data-target="nid_no" required>
                                <div class="form-text nid-scan-status">Use a clear photo of the front of your NID.</div>
                                <label for="nid_no" class="form-label mt-3">Extracted NID number</label>
                                <input type="text" name="nid_no" id="nid_no" class="form-control form-control-lg" value="{{ old('nid_no') }}" inputmode="numeric" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea name="address" id="address" class="form-control form-control-lg" rows="3" placeholder="House, street, city, district" required>{{ old('address') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="photo" class="form-label">Profile photo</label>
                        <input type="file" name="photo" id="photo" class="form-control form-control-lg" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="Minimum 8 characters" required>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control form-control-lg" placeholder="Repeat your password" required>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100">Register</button>
                </form>

                <div class="mt-4 text-center">
                    <span>Already have an account?</span>
                    <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
