@extends('JangKeyte::layout.app')

@section('heading', 'Dashboard')

@section('main_content')

<div class="container g-0">
    <div class="row">
        <div class="col-md-12 g-0">
            @isset($user)
            <div class="list-group list-group-flush mt-3" id="accordion">
                <div class="card list-group-item-primary">
                    <div class="card-header clearfix">
                        <h4 class="mb-0 float-start">
                            <a class="collapsed" data-bs-toggle="collapse" data-parent="#accordion" href="#userDetail" role="button" aria-expanded="false" aria-controls="userDetail">
                                Thông tin chi tiết người dùng
                            </a>
                        </h4>
                        <div class="btn-group float-end">
                            <a href="{{ route('user.update', $user->id) }}" class="btn btn-success btn-sm"><i class="fa fa-edit"></i> Sửa</a>
                            <a href="{!! url()->previous() !!}" class="btn btn-warning btn-sm"><i class="fa fa-arrow-left"></i> Quay lại</a>
                        </div>
                    </div>
                    <div id="userDetail" class="bg-light collapse show">
                        <div class="row g-0">
                            <div class="col-md-3 text-center mt-md-4 my-2">
                                @include('Authetication::templates.image', array('image' => $user->photo, 'object' => 'user', 'class' => 'img-thumbnail rounded-circle w-75'))
                            </div>
                            <div class="col-md-9">
                                <table class="table table-responsive table-striped table-bordered table-hover">
                                    <tbody>
                                        <tr>
                                            <td class="key">Mã định danh</td>
                                            <td>{!! $user->uid !!}</td>
                                        </tr>
                                        <tr>
                                            <td class="key" style="width:30%">Tên nhân viên</td>
                                            <td>{!! $user->name !!}</td>
                                        </tr>
                                        <tr>
                                            <td class="key">Tên tài khoản</td>
                                            <td>{!! $user->username !!}</td>
                                        </tr>
                                        <tr>
                                            <td class="key">Địa chỉ email</td>
                                            <td>{!! $user->email !!}</td>
                                        </tr>
                                        <tr>
                                            <td class="key">Ngày tạo</td>
                                            <td>{!! $user->created_at !!}</td>
                                        </tr>
                                        <tr>
                                            <td class="key">Vai trò</td>
                                            <td>
                                                @include('Authetication::user.elements.role_list')
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="key">Quyền hạn</td>
                                            <td>
                                                @include('Authetication::user.elements.permission_list')
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card list-group-item-primary">
                    <div class="card-header clearfix">
                        <h4 class="mb-0 float-start">
                            <a data-bs-toggle="collapse" data-parent="#accordion" href="#collapse2" class="collapsed" aria-expanded="false">
                                Quyền hạn được cấp
                            </a>
                        </h4>
                        <div class="btn-group float-end">
                            <!-- <a href="/user/update/{!! $user->id !!}" class="btn btn-primary btn-sm"><i class="bx bxs-plus-square"></i> Thêm mới</a> -->
                        </div>
                    </div>
                    <div id="collapse2" class="panel-collapse collapse bg-light" aria-expanded="false">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <!--<div class="row"><div class="col-md-12"><h5>Quá trình chăm sóc cây xanh</h5></div></div>-->
                                    <table class="table table-responsive table-striped table-bordered table-hover">
                                        <tbody>
                                            <tr>
                                                <th class="w10 text-center">TT</th>
                                                <th class="text-center">Quyền hạn</th>
                                                <th class="text-center">Nội dung</th>
                                                <th class="text-center">Ngày cấp</th>
                                                <th class="w10 text-center">Sửa</th>
                                                <th class="w10 text-center">Xóa</th>
                                            </tr>
                                        </tbody>
                                            @isset($permissions)
                                                @foreach($permissions as $permission)
                                                    @if($user->hasPermissionTo($permission))                                           
                                                    <tr>
                                                        <td class="text-center">{!! $loop->index + 1 !!}</td>
                                                        <td>{!! $permission->name !!}</td>
                                                        <td>{!! $permission->description !!}</td>
                                                        <td class="text-center">{!! $permission->created_at !!}</td>
                                                        <td class="text-center"><a href="#" data-bs-toggle="modal" data-bs-target="#modifyObjectModal"><i class="fa fa-edit"></i></a></td>
                                                        <td class="text-center"><a href="#"><i class="fa fa-trash" aria-hidden="true"></i></a></td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                            @endisset   
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @else
                <h1>Không tồn tại người dùng này</h1>
            @endisset

            @section('modals')
                {{-- @include('Authetication::log.elements.modal') --}}
            @stop
        </div>
    </div>            
</div>

@stop