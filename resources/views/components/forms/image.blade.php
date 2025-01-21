@if(isset($name))
<div class="form-group">
    <div class="row">
        <div class="col-12">
        {{ html()->label($label ?? '')->for($name) }} 
        {{ html()->file($name)->class('form-control d-none')->acceptImage()->required($required) }}
        {{-- Form::file($name, array('class' => 'form-control d-none', 'onchange' => 'readURL(this)')); --}}
        <a class="btn btn-light d-block" onclick="document.getElementById('{{ $name }}').click()">Chọn {!! $label ?? 'hình ảnh' !!}</a>
        @if($errors->has($name))
            {{ html()->div($errors->first($name))->class('invalid-feedback') }}
        @endif
        </div>
    </div>
</div>
@else
    {{ html()->text()->class('form-control input-sm text-danger')->value('Lỗi dữ liệu: tên input form không tồn tại.')->disabled(true) }}
    {{ html()->label('Không xác định') }}
@endif

@push('scripts')
<script>
function readURL(input) {
    var url = input.value;
    var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
    if (input.files && input.files[0] && (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg")) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#demo').attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        alert("Hình ảnh không hợp lệ, chỉ chấp nhận định dạng png, jpg, jpeg!!!");
    }
}
</script>
@endpush