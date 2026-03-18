@extends('home')

@section('content')
<div>
    <form action="{{ route('register.post') }}" method="post">
        @csrf

        @error('login')
        <div class="alert-error" style="color:red;margin-bottom:8px">{{ $message }}</div>
        @enderror

        <div>
            <label for="name">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Ваше ім'я" required
                autocomplete="name" class="name1">
        </div>

        <div>
            <label for="surname">Surname</label>
            <input type="text" name="surname" value="{{ old('surname') }}" placeholder="Ваше прізвище"
                autocomplete="surname" class="name1">
        </div>

        <div>
            <label for="login">login</label>
            <input type="text" name="login" placeholder="login" autocomplete="username" required class="name1">
        </div>

        <div>
            <label for="pass">password</label>
            <input type="password" name="pass" placeholder="Пароль" autocomplete="current-password" required
                class="name1">
        </div>

        <div>
            <button type="submit">Зареєструватися</button>
        </div>
    </form>

</div>
@endsection