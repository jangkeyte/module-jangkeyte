@php
    $folder = $object ? ($object . 's/') : '';
    $cleanName = trim((string) ($name ?? ''));
    $isPlaceholder = $cleanName === '' || in_array($cleanName, ['default.png', 'default.jpg', 'default_photo.jpg'], true);
    $relPath = 'storage/uploads/' . $folder . $cleanName;
    $imgStyle = $style ?? 'width:40px;height:40px;object-fit:cover;';
@endphp
@if(! $isPlaceholder && file_exists(public_path($relPath)))
    <img src="{{ asset($relPath) }}" alt="{{ $alt ?? $cleanName }}" class="{{ $class ?? '' }}" style="{{ $imgStyle }}">
@else
    <span class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle {{ $class ?? '' }}" style="{{ $imgStyle }}">
        <i class="bi bi-person-fill"></i>
    </span>
@endif
