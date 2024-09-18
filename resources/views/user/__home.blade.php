@extends('JangKeyte::layout.app')

@section('heading', 'Dashboard')

@section('button')
<a href="{{ route('user.create') }}" class="btn btn-primary btn-sm ms-2"><i class="bi bi-plus"></i> {{ __('Add New') }}</a>
@endsection

@section('main_content')

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header ui-sortable-handle" style="cursor: move;">
                    <h3 class="card-title">
                    <i class="ion ion-clipboard mr-1"></i>
                        {{ __('Users List') }}
                    </h3>
                    <div class="card-tools">
                        {{ $users->links() }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="">                
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Hình ảnh</th>
                                    <th>Tên người dùng</th>
                                    <th>Địa chỉ email</th>
                                    <th>Uid</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                <tr>
                                    <td class="text-center">#{{ $loop->index + 1 }}</td>
                                    <td style="width:5%" class="text-center">
                                        <x-authetication::htmls.image :name="$user->photo" object="user" :alt="$user->name" class="rounded-circle showdow w-100"/>
                                    </td>
                                    <td><a href="{{ route('user.detail', $user->id) }}">{!! $user->name !!}</a> <br> @include('Authetication::user.elements.role_list')</td>
                                    <td>{!! $user->email !!}</td>
                                    <td>{!! $user->uid !!}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>  

@stop