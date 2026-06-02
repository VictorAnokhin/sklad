@php
    $text = (string) ($text ?? '');
@endphp

@once
<style>
    .report-info-hint {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        margin-left: 5px;
        border: 1px solid rgba(251, 191, 36, 0.55);
        border-radius: 50%;
        color: #fbbf24;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        cursor: help;
        vertical-align: middle;
    }

    .report-info-hint:hover,
    .report-info-hint:focus {
        background: rgba(251, 191, 36, 0.16);
        border-color: rgba(251, 191, 36, 0.85);
        outline: none;
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.bootstrap || !bootstrap.Tooltip) {
        return;
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
        if (!bootstrap.Tooltip.getInstance(element)) {
            new bootstrap.Tooltip(element);
        }
    });
});
</script>
@endpush
@endonce

<span class="report-info-hint" tabindex="0" role="button" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $text }}">i</span>
