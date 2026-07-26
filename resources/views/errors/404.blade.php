<x-layouts.index :title="__('site.errors.not_found_title')" :description="__('site.errors.not_found_description')" robots="noindex, nofollow">
  <main style="min-height:55vh;display:flex;align-items:center;background:#fff;padding:48px 0;">
    <div class="container" style="text-align:center;">
      <p style="font-size:5rem;font-weight:800;color:#69ce79;margin:0;">404</p>
      <h1>{{ __('site.errors.not_found_title') }}</h1>
      <p style="color:#64748b;">{{ __('site.errors.not_found_text') }}</p>
      <a class="btn" href="{{ route('home') }}">{{ __('site.errors.home') }}</a>
    </div>
  </main>
</x-layouts.index>
