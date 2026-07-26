@extends('admin.layouts.app')
@section('title', 'Mahsulotni tahrirlash')

@section('content')
  <header class="admin-page-header">
    <div>
      <span class="admin-eyebrow"><i class="bi bi-box-seam" aria-hidden="true"></i>Mahsulotlar</span>
      <h1 class="h3 mb-1">{{ $product->name_uz ?: $product->name }}</h1>
      <p>Mahsulot kontenti, sotuv rejimi va nashr sozlamalarini tahrirlang.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('dashboard.products.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Orqaga</a>
  </header>

  @if($errors->any())
    <div class="alert alert-danger" role="alert" tabindex="-1" data-validation-summary>
      <strong>Ma’lumotlarni tekshiring.</strong>
      <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
  @endif

  <form class="admin-card" method="POST" action="{{ route('dashboard.products.update', $product) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="admin-card-body">
      @include('admin.products.partials.form-fields', [
        'product' => $product,
        'formPrefix' => 'edit-product',
      ])
    </div>
    <div class="admin-card-body border-top d-flex justify-content-end gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('dashboard.products.index') }}">Bekor qilish</a>
      <button class="btn btn-primary" type="submit"><i class="bi bi-save me-2" aria-hidden="true"></i>O‘zgarishlarni saqlash</button>
    </div>
  </form>
@endsection
