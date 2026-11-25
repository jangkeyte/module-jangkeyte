@extends('JangKeyte::layout.guest')

@section('heading', __('Sign In'))

@section('main_content')
    <div class="min-vh-100 d-flex align-items-center justify-content-center bg-body-tertiary py-5">
        <div class="card shadow-sm border-0" style="width: 100%; max-width: 420px;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}" class="text-decoration-none">
                        <h4 class="fw-bold text-primary mb-1">{{ setting('site.name', config('app.name', 'JangKeyte Admin')) }}</h4>
                    </a>
                    <p class="text-muted small mb-0">{{ __('Sign in to start your session') }}</p>
                </div>

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="username" class="form-label">{{ __('Username or Email') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="{{ __('Username') }}" class="form-control @error('username') is-invalid @enderror" autofocus required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">{{ __('Password') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" id="password" name="password" placeholder="{{ __('Password') }}" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                            <label class="form-check-label small" for="remember">{{ __('Remember Me') }}</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i> {{ __('Sign In') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
