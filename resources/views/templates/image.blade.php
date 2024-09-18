@if(isset($image) && file_exists(public_path('/storage/uploads/' . (isset($object) ? $object . 's/' : '') . $image))) 
    <img src="{!! asset('/storage/uploads/' . (isset($object) ? $object . 's/' : '') . '/' . $image) !!}" title="{!! $user->name !!}" alt="{!! $user->name !!}" class="{{ $class ?? 'w-100' }}">
@else 
    <svg xmlns="http://www.w3.org/2000/svg" height="auto" width="10%" viewBox="0 0 448 512" title="{!! asset('/storage/uploads/' . (isset($object) ? $object . 's/' : '') . '' . $image) !!}">
        <path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/></svg>
@endif