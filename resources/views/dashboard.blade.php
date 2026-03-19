@extends('home')

@section('title')
Dashboard — {{ session('name1') }}
@endsection

@section('content')
<div>
    <div class="flex-shrink-0 p-3" style="width: 280px;">
        <a href="{{ route('dashboard') }}"
            class="d-flex align-items-center pb-3 mb-3 link-body-emphasis text-decoration-none border-bottom">
            <span class="fs-5 fw-semibold">Головне меню</span>
        </a>
        <ul class="list-unstyled ps-0">
            <li class="mb-1">
                <span class="fs-5 fw-semibold"> Документи </span>
                <div>
                    <ul>
                        <li><a href="{{ route('document.index') }}"
                                class="link-body-emphasis d-inline-flex text-decoration-none rounded">Всі документи</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="border-top my-3"></li>
            <li class="mb-1">
                <span class="fs-5 fw-semibold"> Довідники </span>
                <div>
                    <ul>
                        <li><a href="{{ route('client.index') }}"
                                class="link-body-emphasis d-inline-flex text-decoration-none rounded">Клієнти /
                                Постачальники</a></li>
                        <li><a href="{{ route('goods.index') }}"
                                class="link-body-emphasis d-inline-flex text-decoration-none rounded">Товари</a></li>
                        <li><a href="{{ route('money.index') }}"
                                class="link-body-emphasis d-inline-flex text-decoration-none rounded">Каси</a></li>
                    </ul>
                </div>
            </li>
            <li class="border-top my-3"></li>
            <li class="mb-1">
                <span class="fs-5 fw-semibold"> Налаштування </span>
                <div>
                    <ul>
                        <li><a href="{{ route('admin.index') }}"
                                class="link-body-emphasis d-inline-flex text-decoration-none rounded">Адмін панель</a>
                        </li>
                        <li><a href="{{ route('kurs.index') }}"
                                class="link-body-emphasis d-inline-flex text-decoration-none rounded">Курси валют</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="border-top my-3"></li>
            <li class="mb-1">
                <span class="fs-5 fw-semibold"> Акаунт </span>
                <div>
                    <ul>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                @csrf
                                <a href="#" onclick="document.getElementById('logout-form').submit(); return false;"
                                    class="link-body-emphasis d-inline-flex text-decoration-none rounded w-100">Вийти</a>
                            </form>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</div>
@endsection