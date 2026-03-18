{{-- top_reklama.blade.php --}}
{{-- Replace with actual logo/header HTML from your top_reklama.php --}}

<div class="d-flex flex-column flex-md-row align-items-center pb-3 mb-4 border-bottom">
  <a href="/" class="d-flex align-items-center link-body-emphasis text-decoration-none">
    <span class="fs-4">Pricing example</span>
  </a>
  <nav class="d-inline-flex mt-2 mt-md-0 ms-md-auto">
    <a class="py-2 link-body-emphasis text-decoration-none" id="btn_login" style='cursor:pointer'>
      <img src="{{ asset('img/door-open.png') }}" alt="{{ config('app.name') }}" style="height:40px">{{ session('name1')
      ?? '' }}
    </a>
  </nav>
</div>