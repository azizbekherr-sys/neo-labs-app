@if(session('success'))
  <div class="alert alert-success d-flex align-items-start" role="status" aria-live="polite"><i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i><div>{{ session('success') }}</div></div>
@endif
@if($errors->any())
  <div class="alert alert-danger" role="alert" aria-live="assertive" id="form-errors">
    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>Ma’lumotlarni tekshiring:</div>
    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
  </div>
@endif
