@if($errors->any())
<div class="alert alert-danger operations-errors" role="alert" tabindex="-1"><strong>Periksa kembali isian berikut.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
<script type="application/json" id="operationsValidationErrors">@json($errors->messages())</script>
@endif
