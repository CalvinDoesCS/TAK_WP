@extends('layouts/horizontalLayout')

@section('title', __('My Profile'))

@section('content')
<x-breadcrumb
    :title="__('My Profile')"
    :breadcrumbs="[
        ['name' => __('Dashboard'), 'url' => route('employee.dashboard')],
        ['name' => __('My Profile'), 'url' => '']
    ]"
    :homeUrl="route('employee.dashboard')"/>

<div class="row">
    <!-- Profile Card -->
    <div class="col-md-4 mb-6">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar avatar-xl mx-auto mb-4">
                    @if($user->profile_picture)
                        <img src="{{ $user->getProfilePicture() }}" alt="Profile" class="rounded-circle">
                    @else
                        <span class="avatar-initial rounded-circle bg-label-primary">{{ $user->getInitials() }}</span>
                    @endif
                </div>
                <h5 class="card-title mb-1">{{ $user->getFullName() }}</h5>
                <p class="text-muted mb-3">{{ $user->designation->name ?? __('Employee') }}</p>

                <div class="d-flex justify-content-center gap-2 mb-4">
                    <span class="badge bg-label-primary">{{ $user->code }}</span>
                    <span class="badge bg-label-success">{{ ucfirst($user->status->value ?? $user->status) }}</span>
                </div>

                <div class="text-start">
                    <p class="mb-2"><i class="bx bx-envelope me-2"></i>{{ $user->email }}</p>
                    <p class="mb-2"><i class="bx bx-phone me-2"></i>{{ $user->phone }}</p>
                    @if($user->date_of_joining)
                        <p class="mb-2"><i class="bx bx-calendar me-2"></i>{{ __('Joined') }}: {{ $user->date_of_joining->format('d M, Y') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Form -->
    <div class="col-md-8">
        <div class="card mb-6">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Update Profile') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('employee.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">{{ __('First Name') }}</label>
                            <input type="text" class="form-control" name="first_name" value="{{ $user->first_name }}" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">{{ __('Last Name') }}</label>
                            <input type="text" class="form-control" name="last_name" value="{{ $user->last_name }}" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">{{ __('Phone') }}</label>
                            <input type="text" class="form-control" name="phone" value="{{ $user->phone }}" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">{{ __('Alternate Number') }}</label>
                            <input type="text" class="form-control" name="alternate_number" value="{{ $user->alternate_number }}">
                        </div>
                        <div class="col-12 mb-4">
                            <label class="form-label">{{ __('Profile Picture') }}</label>
                            <input type="file" class="form-control" name="profile_picture" accept="image/*">
                            <small class="text-muted">{{ __('Max size: 2MB. Supported formats: JPEG, PNG, JPG, GIF') }}</small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Update Profile') }}</button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Change Password') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('employee.profile.change-password') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-12 mb-4">
                            <label class="form-label">{{ __('Current Password') }}</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">{{ __('New Password') }}</label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">{{ __('Confirm Password') }}</label>
                            <input type="password" class="form-control" name="password_confirmation" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning">{{ __('Change Password') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection