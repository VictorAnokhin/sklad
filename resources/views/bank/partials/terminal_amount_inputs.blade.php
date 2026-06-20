<style>
    .bank-terminal-amount-input {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        text-align: right;
        letter-spacing: 0;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const formatTerminalAmount = (minorUnits, decimals = 2, allowNegative = false) => {
        const sign = allowNegative && minorUnits < 0 ? '-' : '';
        const absolute = Math.abs(minorUnits);
        const factor = 10 ** decimals;

        return sign + (absolute / factor).toFixed(decimals);
    };

    const parseTerminalAmount = (value, decimals = 2) => {
        const normalized = String(value || '').replace(/\s/g, '').replace(',', '.');
        const amount = parseFloat(normalized);

        return Number.isFinite(amount) ? Math.round(amount * (10 ** decimals)) : 0;
    };

    const bindTerminalAmountInput = (input) => {
        if (!input || input.dataset.terminalAmountBound === '1') {
            return;
        }

        input.dataset.terminalAmountBound = '1';
        input.classList.add('bank-terminal-amount-input');
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('autocomplete', 'off');

        const decimals = Math.max(0, parseInt(input.dataset.terminalDecimals || '2', 10) || 2);
        const allowNegative = input.dataset.terminalNegative === '1';

        const syncValue = (minorUnits, emit = true) => {
            input.dataset.terminalAmountMinor = String(minorUnits);
            input.value = formatTerminalAmount(minorUnits, decimals, allowNegative);
            if (emit) {
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };

        const getMinorUnits = () => parseInt(input.dataset.terminalAmountMinor || '0', 10) || 0;
        const getDigits = () => String(Math.abs(getMinorUnits()));

        const appendDigit = (digit) => {
            const currentDigits = input.dataset.terminalAmountFresh === '1' ? '' : getDigits();
            const nextDigits = (currentDigits + digit).replace(/^0+(?=\d)/, '');
            const sign = allowNegative && getMinorUnits() < 0 ? -1 : 1;

            syncValue(sign * parseInt(nextDigits || '0', 10));
            input.dataset.terminalAmountFresh = '0';
        };

        const removeLastDigit = () => {
            const sign = allowNegative && getMinorUnits() < 0 ? -1 : 1;
            const nextDigits = getDigits().slice(0, -1);

            syncValue(sign * parseInt(nextDigits || '0', 10));
            input.dataset.terminalAmountFresh = '0';
        };

        const toggleSign = () => {
            if (!allowNegative) {
                return;
            }
            syncValue(-getMinorUnits());
            input.dataset.terminalAmountFresh = '0';
        };

        syncValue(parseTerminalAmount(input.value, decimals), false);

        input.addEventListener('focus', () => {
            input.dataset.terminalAmountFresh = '1';
            syncValue(parseTerminalAmount(input.value, decimals), false);
            input.select();
        });

        input.addEventListener('beforeinput', (event) => {
            if (event.inputType === 'insertText' && /^\d$/.test(event.data || '')) {
                event.preventDefault();
                appendDigit(event.data);
                return;
            }

            if (event.inputType === 'insertText' && event.data === '-') {
                event.preventDefault();
                toggleSign();
                return;
            }

            if (event.inputType === 'deleteContentBackward') {
                event.preventDefault();
                removeLastDigit();
                return;
            }

            if (event.inputType === 'deleteContentForward') {
                event.preventDefault();
                syncValue(0);
                input.dataset.terminalAmountFresh = '0';
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }

            if (/^\d$/.test(event.key)) {
                event.preventDefault();
                appendDigit(event.key);
                return;
            }

            if (event.key === '-' || event.key === 'Minus') {
                event.preventDefault();
                toggleSign();
                return;
            }

            if (event.key === 'Backspace') {
                event.preventDefault();
                removeLastDigit();
                return;
            }

            if (event.key === 'Delete') {
                event.preventDefault();
                syncValue(0);
                input.dataset.terminalAmountFresh = '0';
            }
        });

        input.addEventListener('paste', (event) => {
            event.preventDefault();
            const text = event.clipboardData?.getData('text') || '';
            const sign = allowNegative && text.trim().startsWith('-') ? -1 : 1;
            const digits = text.replace(/\D/g, '');

            syncValue(sign * parseInt(digits || '0', 10));
            input.dataset.terminalAmountFresh = '0';
        });

        input.addEventListener('input', () => {
            syncValue(parseTerminalAmount(input.value, decimals), false);
            input.dataset.terminalAmountFresh = '0';
        });
    };

    document.querySelectorAll('[data-terminal-amount]').forEach(bindTerminalAmountInput);
});
</script>
