{{-- Backward-compatible lightweight view. The /dashboard route uses admin.overview. --}}
@extends('admin.layouts.app')
@section('title', 'Umumiy ko‘rinish')
@section('content')
  <header class="admin-page-header"><div><h1 class="h3 mb-1">Admin panel</h1><p>Boshqaruv bo‘limlari alohida sahifalarga ajratildi.</p></div></header>
  <div class="admin-card"><div class="admin-card-body"><a class="btn btn-primary" href="{{ route('dashboard') }}">Umumiy ko‘rinishga o‘tish</a></div></div>
@endsection
