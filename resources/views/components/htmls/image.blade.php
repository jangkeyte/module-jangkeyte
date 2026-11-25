@php
    $folder = $object ? ($object . 's/') : '';
    $relPath = 'storage/uploads/' . $folder . ($name ?? '');
    $imgStyle = $style ?? 'width:40px;height:40px;object-fit:cover;';
@endphp
@if(!empty($name) && file_exists(public_path($relPath)))
    <img src="{{ asset($relPath) }}" alt="{{ $alt ?? $name }}" class="{{ $class ?? '' }}" style="{{ $imgStyle }}">
@else
    <span class="d-inline-flex align-items-center justify-content-center bg-secondary-subtle text-secondary rounded-circle" style="{{ $imgStyle }}">
        <i class="bi bi-person-fill"></i>
    </span>
@endif
