@extends('JangKeyte::layout.app')

@section('heading', 'Dashboard')

@section('main_content')

<div class="container">

    <div class="col-md-12">
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title">Quản lý quyền hạn</h3>
            </div>
            <div class="card-body">                                
                <div class="row">
                    <div class="col-md-4 col-6">
                        <h4>{{ __('Đối tượng') }}</h4>
                    </div>
                    <div class="col-md-8 col-6">                                        
                        <div class="form-group d-flex justify-content-around clearfix">
                            <div>{{ __('Browse') }}</div>
                            <div>{{ __('Read') }}</div>
                            <div>{{ __('Add') }}</div>
                            <div>{{ __('Edit') }}</div>
                            <div>{{ __('Delete') }}</div>
                        </div>
                    </div>
                </div>
                <!--
                @foreach($objects as $key => $value)
                <div class="row">
                    <div class="col-md-4 col-6">
                        {{ $value }}
                    </div>
                    <div class="col-md-8 col-6">
                        @include('JangKeyte::components.forms.checkbox', array('name' => $key, 'roles' => $user->role))
                    </div>
                </div>
                @endforeach
                -->
            </div>
        </div>
    </div>
</div>
@stop
