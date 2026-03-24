@extends('home')

@section('title')
Dashboard — {{ session('name1') }}
@endsection

@section('content')
<div>
    user-id : {{ session('id') }} <br>
    idfirma : {{ session('fid') }} <br>
    doc : {{ session('doc') }} <br>
    balans : {{ session('balans') }} <br>
    name : {{ session('name1') }} <br>
    login: {{ session('login') }} <br>

</div>
@endsection