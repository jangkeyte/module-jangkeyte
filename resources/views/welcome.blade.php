@extends('JangKeyte::layout.guest')

@section('heading', 'JangKeyte Modular Starter')

@section('main_content')
    <div class="min-vh-100 d-flex align-items-center justify-content-center bg-body-tertiary py-5">
        <div class="container" style="max-width: 820px;">
            <div class="card border-0 shadow-sm p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 border-bottom pb-4 mb-4">
                    <div>
                        <span class="badge text-bg-primary mb-2">JangKeyte Core v2.1.0</span>
                        <h2 class="fw-bold mb-1">{{ setting('site.name', config('app.name', 'Laravel')) }}</h2>
                        <p class="text-muted mb-0">{{ __('Visual Module & CRUD Studio, RBAC, Media Library & Dynamic Settings') }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                <i class="bi bi-speedometer2 me-1"></i> {{ __('Dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-1"></i> {{ __('Sign In') }}
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100 bg-white">
                            <i class="bi bi-shield-check fs-3 text-success"></i>
                            <h6 class="fw-bold mt-2 mb-1">Auth & RBAC Ready</h6>
                            <p class="text-muted small mb-0">Users, Roles, Cached Permissions & AdminLTE 4 layout pre-configured.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100 bg-white">
                            <i class="bi bi-boxes fs-3 text-primary"></i>
                            <h6 class="fw-bold mt-2 mb-1">Auto-Discovered Modules</h6>
                            <p class="text-muted small mb-0">Zero-boilerplate PSR-4 modules managed via <code>modules_statuses.json</code>.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100 bg-white">
                            <i class="bi bi-terminal fs-3 text-warning"></i>
                            <h6 class="fw-bold mt-2 mb-1">1-Command CRUD</h6>
                            <p class="text-muted small mb-0">Scaffolds Migration, Model, Service, Repo, Controller, Views, Menu & RBAC.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-dark text-light rounded p-3">
                    <div class="small text-secondary mb-1"># Generate a complete new module & CRUD in 1 command:</div>
                    <code class="text-info d-block">php artisan jangkeyte:crud Trip --module=TripBooking --fields="title:string,destination:string,seats:integer,departure_date:date,is_active:boolean"</code>
                </div>
            </div>
        </div>
    </div>
@endsection
