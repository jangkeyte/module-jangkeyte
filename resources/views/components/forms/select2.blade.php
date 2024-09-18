<div class="form-group" data-select2-id="47">
    <label>Minimal (.select2-danger)</label>
    <select class="form-control test-sl2 select2 select2-danger select2-hidden-accessible">
        <option selected="selected" data-select2-id="14">Alabama</option>
        <option data-select2-id="49">Alaska</option>
        <option data-select2-id="50">California</option>
        <option data-select2-id="51">Delaware</option>
        <option data-select2-id="52">Tennessee</option>
        <option data-select2-id="53">Texas</option>
        <option data-select2-id="54">Washington</option>
    </select>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('.test-sl2').select2();
});
</script>
@endpush