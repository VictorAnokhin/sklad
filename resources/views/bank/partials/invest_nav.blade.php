<nav class="bank-nav" aria-label="Инвестиции и активы">
    <a href="{{ route('bank.invest') }}" class="{{ request()->routeIs('bank.invest*') ? 'is-active' : '' }}">Инвестиции</a>
    <a href="{{ route('bank.assets') }}" class="{{ request()->routeIs('bank.assets*') ? 'is-active' : '' }}">Активы</a>
</nav>
