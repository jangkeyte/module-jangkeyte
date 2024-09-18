@if($user->roles->count() > 0)
    @foreach($user->roles as $role)
        <span class="badge text-bg-{!! $role->code !!}">{!! $role->name !!}</span>
    @endforeach
@else
<span class="badge bg-light text-dark">Chưa phân quyền</span>
@endif