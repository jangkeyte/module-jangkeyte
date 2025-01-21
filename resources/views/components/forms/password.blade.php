@if(isset($name))
    {{ html()->password($name)->class('form-control input-sm' . ($errors->has($name) ? ' is-invalid' : ''))->required($required)->disabled($disabled)->autofocus($autofocus ?? '') }}
    @empty($hidden)
        {{ html()->label(isset($label) ? $label . (isset($required) ? ' *' : '' ) : '')->for($name) }}          
    @endempty
    @if($errors->has($name))
        {{ html()->div($errors->first($name))->class('text-danger') }}
    @endif
@else
    {{ html()->password()->class('form-control input-sm text-danger' . ($errors->has($name) ? ' is-invalid' : '')) }}
@endif