@isset($name)
@php $permissions = $permissions->pluck('slug')->toArray() @endphp
<div class="form-group d-flex justify-content-around text-center clearfix">
    <div class="icheck-success d-inline">
        <input type="checkbox" id="permission-{{ $name }}-browse" name="permission[]" value="browse-{{$name}}" @if(in_array('browse-'.$name, $permissions)) checked="" @endif >
        <label for="permission-{{ $name }}-browse"></label>
    </div>
    <div class="icheck-primary d-inline">
        <input type="checkbox" id="permission-{{ $name }}-read" name="permission[]" value="read-{{$name}}" @if(in_array('read-'.$name, $permissions)) checked="" @endif >
        <label for="permission-{{ $name }}-read"></label>
    </div>
    <div class="icheck-amethyst d-inline">
        <input type="checkbox" id="permission-{{ $name }}-add" name="permission[]" value="add-{{$name}}" @if(in_array('add-'.$name, $permissions)) checked="" @endif >
        <label for="permission-{{ $name }}-add"></label>
    </div>
    <div class="icheck-warning d-inline">
        <input type="checkbox" id="permission-{{ $name }}-edit" name="permission[]" value="edit-{{$name}}" @if(in_array('edit-'.$name, $permissions)) checked="" @endif >
        <label for="permission-{{ $name }}-edit"></label>
    </div>
    <div class="icheck-danger d-inline">
        <input type="checkbox" id="permission-{{ $name }}-delete" name="permission[]" value="delete-{{$name}}" @if(in_array('delete-'.$name, $permissions)) checked="" @endif >
        <label for="permission-{{ $name }}-delete"></label>
    </div>
</div>
@endisset
