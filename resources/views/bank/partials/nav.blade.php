<nav class="bank-nav" aria-label="Bank navigation">
    <a href="{{ route('bank.cash-accounts') }}" class="{{ request()->routeIs('bank.cash-accounts') ? 'is-active' : '' }}">Клиентские счета</a>
    <a href="{{ route('bank.operational-accounts') }}" class="{{ request()->routeIs('bank.operational-accounts') ? 'is-active' : '' }}">Операционные счета</a>
    <a href="{{ route('bank.deposit') }}" class="{{ request()->routeIs('bank.deposit') ? 'is-active' : '' }}">Депозиты</a>
    <a href="{{ route('bank.invest') }}" class="{{ request()->routeIs('bank.invest') ? 'is-active' : '' }}">Инвестиции</a>
    <a href="{{ route('bank.exchange') }}" class="{{ request()->routeIs('bank.exchange') ? 'is-active' : '' }}">Обмен фиат/крипта</a>
    <a href="{{ route('bank.clearing') }}" class="{{ request()->routeIs('bank.clearing') ? 'is-active' : '' }}">Клиринг проектов</a>
    <a href="{{ route('bank.payments') }}" class="{{ request()->routeIs('bank.payments') ? 'is-active' : '' }}">Платежи</a>
    <a href="{{ route('bank.reconciliation') }}" class="{{ request()->routeIs('bank.reconciliation') ? 'is-active' : '' }}">Сверка</a>
    <a href="{{ route('blockchain-monitor.index') }}">Blockchain Monitor</a>
</nav>
