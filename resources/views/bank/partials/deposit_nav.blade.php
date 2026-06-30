<nav class="bank-nav" aria-label="Депозиты и пулы">
    <a href="{{ route('bank.deposit') }}" class="{{ request()->routeIs('bank.deposit') ? 'is-active' : '' }}">Депозиты</a>
    <a href="{{ route('bank.pools') }}" class="{{ request()->routeIs('bank.pools*') ? 'is-active' : '' }}">Пулы</a>
    <a href="{{ route('bank.pool-movements') }}" class="{{ request()->routeIs('bank.pool-movements*') ? 'is-active' : '' }}">Движение средств</a>
</nav>
