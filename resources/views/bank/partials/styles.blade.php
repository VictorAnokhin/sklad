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

    .bank-panel {
        background: rgba(15, 23, 42, 0.72);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 8px;
        padding: 18px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22);
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

    .bank-table {
        margin: 0;
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
    }

    @media (max-width: 640px) {
        .bank-grid {
            grid-template-columns: 1fr;
        }

        .bank-table-header {
            flex-direction: column;
        }
    }
</style>
