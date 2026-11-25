@extends('JangKeyte::layout.app')

@section('heading', $role->exists ? __('Edit Role') : __('Create Role'))

@section('button')
    <a href="{{ route('admin_role') }}" class="btn btn-outline-secondary btn-sm ms-2">
        <i class="bi bi-arrow-left"></i> {{ __('View All') }}
    </a>
@endsection

@section('main_content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $role->exists ? route('admin_role_update', $role->id) : route('admin_role_store') }}">
                @csrf
                @if($role->exists)
                    @method('PUT')
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Name') }} *</label>
                        <input type="text" name="name" value="{{ old('name', $role->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Slug') }} *</label>
                        <input type="text" name="slug" value="{{ old('slug', $role->slug) }}" class="form-control @error('slug') is-invalid @enderror" required>
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Badge Color') }}</label>
                        <input type="text" name="code" value="{{ old('code', $role->code ?: 'primary') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description', $role->description) }}</textarea>
                    </div>
                </div>

                <div class="card border mb-4">
                    <div class="card-header bg-light fw-semibold">{{ __('Permission Matrix') }}</div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($groupedPermissions as $group => $perms)
                                <div class="col-md-6 col-xl-4">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="fw-bold border-bottom pb-2 mb-2">{{ $group }}</h6>
                                        @foreach($perms as $perm)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="perm_{{ $perm->slug }}" name="permission[]" value="{{ $perm->slug }}" @checked(in_array($perm->slug, old('permission', $selectedPermissions), true))>
                                                <label class="form-check-label small" for="perm_{{ $perm->slug }}">
                                                    {{ $perm->name }} <code class="text-muted">({{ $perm->slug }})</code>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ $role->exists ? __('Update') : __('Create') }}</button>
            </form>
        </div>
    </div>
@endsection
