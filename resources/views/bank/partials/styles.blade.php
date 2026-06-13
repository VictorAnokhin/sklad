<style>
    .bank-page {
        max-width: 1280px;
        margin: 0 auto 48px;
        color: rgba(255, 255, 255, 0.88);
    }

    .bank-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }

    .bank-nav a {
        display: inline-flex;
        align-items: center;
        min-height: 38px;
        padding: 7px 12px;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        background: rgba(15, 23, 42, 0.72);
        color: rgba(226, 232, 240, 0.92);
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .bank-nav a.is-active {
        background: #fbbf24;
        border-color: #fbbf24;
        color: #111827;
    }

    .bank-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 12px;
    }

    .bank-hero {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        align-items: stretch;
        margin-bottom: 12px;
        padding: 22px;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.92), rgba(20, 83, 45, 0.36));
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22);
    }

    .bank-hero h1 {
        margin: 0;
        color: #fff;
        font-size: 1.65rem;
        line-height: 1.18;
    }

    .bank-hero p {
        max-width: 760px;
        margin: 10px 0 0;
        color: rgba(226, 232, 240, 0.78);
    }

    .bank-hero__metrics {
        display: grid;
        min-width: 260px;
        gap: 10px;
    }

    .bank-hero__metrics div {
        display: grid;
        gap: 4px;
        padding: 12px;
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.36);
        border: 1px solid rgba(148, 163, 184, 0.16);
    }

    .bank-hero__metrics span {
        color: rgba(203, 213, 225, 0.72);
        font-size: 0.82rem;
    }

    .bank-hero__metrics strong {
        color: #fff;
        font-size: 1.1rem;
    }

    .bank-panel {
        background: rgba(15, 23, 42, 0.72);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 8px;
        padding: 18px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22);
    }

    .bank-panel--accent {
        border-color: rgba(34, 197, 94, 0.34);
        background: rgba(20, 83, 45, 0.28);
    }

    .bank-panel--button {
        display: block;
        width: 100%;
        text-align: left;
        color: inherit;
        cursor: pointer;
    }

    .bank-panel--button:hover,
    .bank-panel--button.is-active {
        border-color: rgba(251, 191, 36, 0.55);
        background: rgba(120, 53, 15, 0.28);
    }

    .bank-panel--button .bank-label,
    .bank-panel--button .bank-value,
    .bank-panel--button .bank-meta {
        display: block;
    }

    .bank-table-panel {
        padding: 0;
        overflow: hidden;
    }

    .bank-table-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 18px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    }

    .bank-table-header--search {
        align-items: end;
    }

    .bank-label {
        color: rgba(148, 163, 184, 0.9);
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0;
        margin-bottom: 8px;
    }

    .bank-value {
        color: #fff;
        font-size: 1.55rem;
        font-weight: 800;
        line-height: 1.15;
    }

    .bank-meta {
        color: rgba(203, 213, 225, 0.72);
        font-size: 0.84rem;
        margin-top: 5px;
        overflow-wrap: anywhere;
    }

    .bank-mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.84rem;
    }

    .bank-pill {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        padding: 3px 9px;
        border-radius: 999px;
        background: rgba(34, 197, 94, 0.12);
        color: #bbf7d0;
        border: 1px solid rgba(34, 197, 94, 0.2);
        font-weight: 800;
    }

    .bank-pill--company {
        background: rgba(59, 130, 246, 0.12);
        color: #bfdbfe;
        border-color: rgba(59, 130, 246, 0.28);
    }

    .bank-pill--person {
        background: rgba(245, 158, 11, 0.12);
        color: #fde68a;
        border-color: rgba(245, 158, 11, 0.28);
    }

    .bank-pill--currency {
        background: rgba(34, 197, 94, 0.12);
        color: #bbf7d0;
        border-color: rgba(34, 197, 94, 0.2);
    }

    .bank-status {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: 2px 8px;
        border-radius: 8px;
        background: rgba(16, 185, 129, 0.12);
        color: #a7f3d0;
        border: 1px solid rgba(16, 185, 129, 0.22);
        font-weight: 700;
        font-size: 0.82rem;
    }

    .bank-currency-strip {
        padding: 0;
        margin-bottom: 12px;
        overflow: hidden;
    }

    .bank-currency-list {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1px;
        background: rgba(148, 163, 184, 0.12);
    }

    .bank-currency-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px;
        background: rgba(15, 23, 42, 0.82);
    }

    .bank-currency-item span {
        color: rgba(203, 213, 225, 0.74);
        font-weight: 800;
    }

    .bank-currency-item strong {
        color: #fff;
        text-align: right;
    }

    .bank-empty {
        grid-column: 1 / -1;
        padding: 18px;
        background: rgba(15, 23, 42, 0.82);
        color: rgba(203, 213, 225, 0.72);
    }

    .bank-table {
        margin: 0;
    }

    .bank-table-scroll {
        max-height: 620px;
        overflow: auto;
    }

    .bank-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #111827;
        box-shadow: inset 0 -1px 0 rgba(148, 163, 184, 0.22);
    }

    .bank-table__num {
        width: 56px;
        min-width: 56px;
        text-align: right;
        color: rgba(203, 213, 225, 0.72);
    }

    .bank-table__account {
        width: 300px;
        min-width: 300px;
    }

    .bank-table--client-accounts {
        min-width: 1120px;
    }

    .bank-table--projects,
    .bank-table--persons {
        min-width: 1120px;
    }

    .bank-table--nested {
        margin: 0;
        background: rgba(2, 6, 23, 0.18);
    }

    .bank-table--nested thead th {
        position: static;
        background: rgba(15, 23, 42, 0.86);
    }

    .bank-table__wide {
        width: 340px;
        min-width: 340px;
    }

    .bank-accordion-row {
        cursor: pointer;
    }

    .bank-accordion-row.is-open {
        background: rgba(30, 41, 59, 0.72);
    }

    .bank-row-button {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        max-width: 100%;
        padding: 0;
        border: 0;
        background: transparent;
        color: inherit;
        text-align: left;
    }

    .bank-row-button strong,
    .bank-row-button small {
        display: block;
    }

    .bank-row-button small {
        margin-top: 3px;
        color: rgba(203, 213, 225, 0.66);
    }

    .bank-row-caret {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 8px;
        background: rgba(148, 163, 184, 0.12);
        color: #fbbf24;
        font-size: 1.1rem;
        line-height: 1;
        transition: transform 0.16s ease;
        flex: 0 0 auto;
    }

    .bank-accordion-row.is-open .bank-row-caret {
        transform: rotate(90deg);
    }

    .bank-detail-block {
        padding: 12px 14px;
        background: rgba(2, 6, 23, 0.34);
        border-top: 1px solid rgba(148, 163, 184, 0.12);
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    }

    .bank-inline-balance {
        display: inline-flex;
        margin: 2px 4px 2px 0;
        padding: 3px 8px;
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.74);
        border: 1px solid rgba(148, 163, 184, 0.16);
        color: rgba(226, 232, 240, 0.9);
        font-size: 0.84rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .bank-account-link {
        padding: 0;
        border: 0;
        background: transparent;
        color: #bfdbfe;
        font-weight: 800;
        text-align: left;
        text-decoration: underline;
        text-decoration-color: rgba(96, 165, 250, 0.4);
        text-underline-offset: 3px;
    }

    .bank-account-link:hover {
        color: #fff;
    }

    .bank-search {
        display: grid;
        gap: 6px;
        min-width: min(360px, 100%);
        margin: 0;
    }

    .bank-search span {
        color: rgba(148, 163, 184, 0.9);
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .bank-search input {
        min-height: 38px;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.24);
        background: rgba(2, 6, 23, 0.46);
        color: rgba(255, 255, 255, 0.92);
        padding: 7px 10px;
        outline: none;
    }

    .bank-search input:focus {
        border-color: rgba(251, 191, 36, 0.62);
        box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.12);
    }

    .bank-modal[hidden] {
        display: none;
    }

    .bank-modal {
        position: fixed;
        inset: 0;
        z-index: 1100;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .bank-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(2, 6, 23, 0.76);
        backdrop-filter: blur(4px);
    }

    .bank-modal__dialog {
        position: relative;
        width: min(820px, 100%);
        max-height: min(780px, calc(100vh - 40px));
        overflow: auto;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: #0f172a;
        box-shadow: 0 24px 70px rgba(0, 0, 0, 0.46);
    }

    .bank-modal__header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    }

    .bank-modal__header h2 {
        margin: 0;
        color: #fff;
        font-size: 1.25rem;
    }

    .bank-modal__close {
        width: 36px;
        height: 36px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.72);
        color: #fff;
        font-size: 1.4rem;
        line-height: 1;
    }

    .bank-requisites-form {
        padding: 18px 20px 20px;
    }

    .bank-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .bank-requisites-form label {
        display: grid;
        gap: 6px;
        margin: 0;
    }

    .bank-requisites-form label span {
        color: rgba(148, 163, 184, 0.9);
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .bank-requisites-form input,
    .bank-requisites-form textarea {
        width: 100%;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.24);
        background: rgba(2, 6, 23, 0.46);
        color: rgba(255, 255, 255, 0.92);
        padding: 8px 10px;
        outline: none;
    }

    .bank-requisites-form input[readonly] {
        color: rgba(226, 232, 240, 0.72);
        background: rgba(15, 23, 42, 0.52);
    }

    .bank-requisites-form input:focus,
    .bank-requisites-form textarea:focus {
        border-color: rgba(251, 191, 36, 0.62);
        box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.12);
    }

    .bank-form-full {
        margin-top: 12px !important;
    }

    .bank-modal__actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 16px;
    }

    .bank-placeholder h2 {
        color: #fff;
        font-size: 1.55rem;
        margin: 0 0 10px;
    }

    .bank-placeholder p {
        color: rgba(226, 232, 240, 0.78);
        max-width: 760px;
    }

    .bank-placeholder-list {
        display: grid;
        gap: 8px;
        margin-top: 16px;
        color: rgba(203, 213, 225, 0.78);
    }

    @media (max-width: 980px) {
        .bank-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .bank-hero {
            flex-direction: column;
        }

        .bank-hero__metrics {
            min-width: 0;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .bank-currency-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .bank-grid {
            grid-template-columns: 1fr;
        }

        .bank-table-header {
            flex-direction: column;
        }

        .bank-hero__metrics,
        .bank-currency-list {
            grid-template-columns: 1fr;
        }

        .bank-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
