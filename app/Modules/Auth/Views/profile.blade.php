@extends('auth::layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="profile-heading mb-4">
            <div class="eyebrow">Account</div>
            <h1 class="fw-bold mb-1">Profile settings</h1>
            <p class="text-muted mb-0">Keep your personal details up to date.</p>
        </div>

        <div class="card">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="rounded-circle border-2 border-primary" width="72" height="72">
                    <div>
                        <h2 class="h5 mb-1">{{ $user->name }}</h2>
                        <div class="text-muted small">{{ $user->email }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full name</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="dob" class="form-label">Date of birth</label>
                            <input type="date" name="dob" id="dob" class="form-control" value="{{ old('dob', optional($user->dob)->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="nid_scan" class="form-label">Scan a new NID</label>
                            <input type="file" id="nid_scan" class="form-control nid-scan-input" accept="image/*" capture="environment" data-target="nid_no">
                            <div class="form-text nid-scan-status">Choose a clear NID photo to extract its number.</div>
                            <label for="nid_no" class="form-label mt-3">Extracted NID number</label>
                            <input type="text" name="nid_no" id="nid_no" class="form-control" value="{{ old('nid_no', $user->nid_no) }}" inputmode="numeric" required>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea name="address" id="address" class="form-control" rows="3" required>{{ old('address', $user->address) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label for="photo" class="form-label">Profile photo</label>
                            <input type="file" name="photo" id="photo" class="form-control" accept="image/jpeg,image/png">
                            <div class="form-text">JPG or PNG, up to 2 MB.</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">Save profile</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection