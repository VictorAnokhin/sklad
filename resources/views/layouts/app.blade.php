@extends('home')

@section('title', $doc === 'ZOUT' ? 'Замовлення' : 'Закупки');

@section('content')

<div class="document">
  <section style="width:15%;max-width:60px">
    @include('partials.filter')
  </section>
  <section style="width:85%">
    @include('partials.panel')
  </section>
</div>

@if(session('success'))
<div class="alert alert-success" style="color:green;padding:6px">
  {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="alert-error" style="color:red;padding:6px">
  @foreach($errors->all() as $err)
  <div>{{ $err }}</div>
  @endforeach
</div>
@endif

@yield('contentbody')



@endsection