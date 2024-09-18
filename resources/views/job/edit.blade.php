@extends('JangKeyte::layout.app')

@section('heading', __('Edit Job'))

@section('button')
<a href="{{ route('job_index') }}" class="btn btn-primary btn-sm ms-2"><i class="bi bi-folder-check"></i> {{ __('View All') }}</a>
@endsection

@section('main_content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('job_update') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-3">
                            <div class="alert alert-primary d-flex align-items-center m-2" role="alert">
                                <div>
                                    <h5>{{ __('Update job') }}</h5>
                                    <span>{{ __('You can make any changes on the form below and click [Save] button to update job information.') }}</span>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="id" value="{{ $item->id }}">                        
                        
                        <div class='row mb-3'>
                            <label for='title' class='col-sm-2 col-form-label'>{{ __('Title') }}</label>
                            <div class='col-sm-10'>
                                <input type='text' name='title' class='form-control @error('title') is-invalid @enderror' value='{{ old('title', $item->title) }}'/>
                                @error('title') <div class='invalid-feedback'>{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class='row mb-3'>
                            <label for='name' class='col-sm-2 col-form-label'>{{ __('Name') }}</label>
                            <div class='col-sm-10'>
                                <input type='text' name='name' class='form-control @error('name') is-invalid @enderror' value='{{ old('name', $item->name) }}'/>
                                @error('name') <div class='invalid-feedback'>{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class='row mb-3'>
                            <label for='image' class='col-sm-2 col-form-label'>{{ __('Image') }}</label>
                            <div class='col-sm-10'>
                                <input type='file' name='image' class='form-control @error('image') is-invalid @enderror' value='{{ old('image', $item->image) }}'/>
                                @error('image') <div class='invalid-feedback'>{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class='row mb-3'>
                            <label for='birthday' class='col-sm-2 col-form-label'>{{ __('Birthday') }}</label>
                            <div class='col-sm-10'>
                                <input type='date' name='birthday' class='form-control @error('birthday') is-invalid @enderror' value='{{ old('birthday', $item->birthday ?? date('Y-m-d')) }}'/>
                                @error('birthday') <div class='invalid-feedback'>{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class='row mb-3'>
                            <label for='age' class='col-sm-2 col-form-label'>{{ __('Age') }}</label>
                            <div class='col-sm-10'>
                                <input type='number' name='age' class='form-control @error('age') is-invalid @enderror' value='{{ old('age', $item->age) }}'/>
                                @error('age') <div class='invalid-feedback'>{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class='row mb-3'>
                            <label for='sexual' class='col-sm-2 col-form-label'>{{ __('Sexual') }}</label>
                            <div class='col-sm-10'>
                                <div class='form-check form-switch mt-2'>
                                    <input class='form-check-input' type='checkbox' role='switch' name='sexual'>
                                    <label class='form-check-label' for='sexual'>sexual</label>
                                </div>
                                @error('sexual') <div class='invalid-feedback'>{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class='row mb-3'>
                            <label for='description' class='col-sm-2 col-form-label'>{{ __('Description') }}</label>
                            <div class='col-sm-10'>
                                <textarea name='description' class='form-control @error('description') is-invalid @enderror' rows='4'>{{ old('description', $item->description) }}</textarea>
                                @error('description') <div class='invalid-feedback'>{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class='row mb-3'>
                            <label for='start_time' class='col-sm-2 col-form-label'>{{ __('Start Time') }}</label>
                            <div class='col-sm-10'>
                                <input type='datetime' name='start_time' class='form-control @error('start_time') is-invalid @enderror' value='{{ old('start_time', $item->start_time ?? date('Y-m-d h:i:s')) }}'/>
                                @error('start_time') <div class='invalid-feedback'>{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-2"></label>
                            <div class="col-sm-10">
                                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection