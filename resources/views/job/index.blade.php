@extends('JangKeyte::layout.app')

@section('heading', __('Jobs'))

@section('button')
<a href="{{ route('job_edit') }}" class="btn btn-primary btn-sm ms-2"><i class="bi bi-plus"></i> {{ __('Add New') }}</a>
@endsection

@section('main_content')
    <div class="row mb-3">
        <div class="alert alert-primary d-flex align-items-center" role="alert">
            <div>
                <h5>{{ __('Meeting jobs') }}</h5>
                <span>{{ __('Below is a list of all jobs. You can add and edit jobs, change their status and see all infomation for each job.') }}</span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Title') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Image') }}</th>
                                    <th>{{ __('Birthday') }}</th>
                                    <th>{{ __('Age') }}</th>
                                    <th>{{ __('Sexual') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Start_time') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($jobs as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->title }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td><img src='{{ asset('storage/uploads/jobs/' . ($item->image ?? 'default.png')) }}' alt='' style='height:100px'></td>
                                    <td>{{ $item->birthday }}</td>
                                    <td>{{ $item->age }}</td>
                                    <td>{{ $item->sexual }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ $item->start_time }}</td>
                                    <td class="py-1">
                                        <a href="{{ route('job_delete', $item->id) }}" class="btn btn-danger btn-sm" onClick="return confirm('{{ __('Are you sure?') }}');">{{ __('Delete') }}</a>
                                        <a href="{{ route('job_edit', $item->id) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection