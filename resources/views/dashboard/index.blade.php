@extends('JangKeyte::layout.app')

@section('heading', __('Dashboard'))

@section('main_content')
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center p-4">
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $stats['users'] ?? 0 }}</h3>
                        <span class="text-muted">{{ __('Users') }}</span>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3">
                        <i class="bi bi-people fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center p-4">
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $stats['roles'] ?? 0 }}</h3>
                        <span class="text-muted">{{ __('Roles') }}</span>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-3">
                        <i class="bi bi-shield-lock fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center p-4">
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $stats['permissions'] ?? 0 }}</h3>
                        <span class="text-muted">{{ __('Permissions') }}</span>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3">
                        <i class="bi bi-key fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center p-4">
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $stats['modules'] ?? 0 }}</h3>
                        <span class="text-muted">{{ __('Active Modules') }}</span>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 text-info p-3">
                        <i class="bi bi-boxes fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-lightning-charge text-warning me-1"></i> {{ __('Quick Start — Generate Your Next CRUD Module') }}
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">
                        {{ __('Run a single Artisan command to scaffold a full PSR-4 Domain Module, Database Migration, Model, Filter, Repository, Service, FormRequest, RESTful Controller, Blade Views, Sidebar Menu & RBAC Permissions:') }}
                    </p>
                    <div class="bg-dark text-light rounded p-3 mb-3">
                        <code class="text-info small">php artisan jangkeyte:crud Trip --module=TripBooking --fields="title:string,destination:string,seats:integer,departure_date:date,image:file,is_active:boolean"</code>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @can('browse-module')
                            <a href="{{ route('admin.modules.index') }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-cpu me-1"></i> {{ __('Open Module Studio') }}
                            </a>
                        @endcan
                        @can('browse-user')
                            <a href="{{ route('admin_user') }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-people me-1"></i> {{ __('Manage Users') }}
                            </a>
                        @endcan
                        @can('browse-role')
                            <a href="{{ route('admin_role') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-shield-lock me-1"></i> {{ __('Manage Roles & Permissions') }}
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-boxes text-primary me-1"></i> {{ __('Discovered Domain Modules') }}
                </div>
                <div class="card-body">
                    @php $discoveredModules = app(\JangKeyte\Support\ModuleLoader::class)->allModules(); @endphp
                    @if(empty($discoveredModules))
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            <span class="small">{{ __('No domain modules created yet. Folder modules/ is clean and ready.') }}</span>
                        </div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($discoveredModules as $mod)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <span class="fw-semibold">{{ $mod['name'] }}</span>
                                        <div class="text-muted small"><code>{{ $mod['provider'] }}</code></div>
                                    </div>
                                    @if($mod['enabled'])
                                        <span class="badge text-bg-success">{{ __('Enabled') }}</span>
                                    @else
                                        <span class="badge text-bg-secondary">{{ __('Disabled') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
