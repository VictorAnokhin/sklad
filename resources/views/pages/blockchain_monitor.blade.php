@extends('home')

@section('title')
Blockchain Monitor
@endsection

@section('header_actions')
<div class="bc-monitor-actions">
    <button type="button" class="bc-btn bc-btn--muted" id="bc-refresh-btn">Refresh</button>
    <button type="button" class="bc-btn bc-btn--primary" id="bc-sync-btn">Sync now</button>
</div>
@endsection

@section('content')
<div class="bc-monitor" data-summary-url="{{ route('blockchain-monitor.summary') }}" data-events-url="{{ route('blockchain-monitor.events') }}" data-sync-url="{{ route('blockchain-monitor.sync') }}">
    <section class="bc-status-grid">
        <div class="bc-panel bc-status-panel">
            <div class="bc-panel-label">Source</div>
            <div class="bc-panel-value" id="bc-source-label">--</div>
            <div class="bc-panel-meta" id="bc-source-meta">Loading...</div>
        </div>
        <div class="bc-panel bc-status-panel">
            <div class="bc-panel-label">Listener</div>
            <div class="bc-panel-value">
                <span class="bc-status-dot" id="bc-status-dot"></span>
                <span id="bc-listener-status">--</span>
            </div>
            <div class="bc-panel-meta" id="bc-listener-meta">--</div>
        </div>
        <div class="bc-panel bc-status-panel">
            <div class="bc-panel-label">Events</div>
            <div class="bc-panel-value" id="bc-events-total">0</div>
            <div class="bc-panel-meta" id="bc-events-by-type">--</div>
        </div>
        <div class="bc-panel bc-status-panel">
            <div class="bc-panel-label">Pools / Packages</div>
            <div class="bc-panel-value"><span id="bc-pools-total">0</span> / <span id="bc-packages-total">0</span></div>
            <div class="bc-panel-meta" id="bc-last-checkpoint">checkpoint --</div>
        </div>
    </section>

    <section class="bc-panel bc-filters">
        <select id="bc-network-filter" aria-label="Network filter">
            <option value="">All networks</option>
        </select>
        <select id="bc-event-filter" aria-label="Event type filter">
            <option value="">All events</option>
        </select>
        <select id="bc-pool-filter" aria-label="Pool filter">
            <option value="">All pools</option>
        </select>
        <input id="bc-search-filter" type="search" placeholder="tx, wallet, pool, package">
        <select id="bc-limit-filter" aria-label="Rows limit">
            <option value="50">50 rows</option>
            <option value="100" selected>100 rows</option>
            <option value="250">250 rows</option>
        </select>
    </section>

    <section class="bc-panel bc-table-panel">
        <div class="bc-table-header">
            <div>
                <div class="bc-panel-label">Confirmed events</div>
                <div class="bc-panel-meta" id="bc-table-meta">Waiting for data...</div>
            </div>
            <div class="bc-panel-meta" id="bc-action-status"></div>
        </div>
        <div class="bc-table-wrap">
            <table class="bc-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Network</th>
                        <th>Amount</th>
                        <th>Pool</th>
                        <th>Owner</th>
                        <th>Tx</th>
                    </tr>
                </thead>
                <tbody id="bc-events-body">
                    <tr><td colspan="7" class="bc-empty">Loading events...</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<style>
    .bc-monitor {
        max-width: 1280px;
        margin: 0 auto 48px;
        color: rgba(255, 255, 255, 0.88);
    }

    .bc-monitor-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .bc-status-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 12px;
    }

    .bc-panel {
        background: rgba(15, 23, 42, 0.72);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 8px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22);
    }

    .bc-status-panel {
        min-height: 124px;
        padding: 18px;
    }

    .bc-panel-label {
        color: rgba(148, 163, 184, 0.9);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .bc-panel-value {
        color: #fff;
        font-size: 1.45rem;
        font-weight: 700;
        line-height: 1.2;
        word-break: break-word;
    }

    .bc-panel-meta {
        color: rgba(203, 213, 225, 0.72);
        font-size: 0.86rem;
        margin-top: 8px;
        overflow-wrap: anywhere;
    }

    .bc-status-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #94a3b8;
        margin-right: 8px;
    }

    .bc-status-dot.is-healthy {
        background: #22c55e;
        box-shadow: 0 0 14px rgba(34, 197, 94, 0.7);
    }

    .bc-status-dot.is-stale,
    .bc-status-dot.is-waiting_for_events {
        background: #f59e0b;
        box-shadow: 0 0 14px rgba(245, 158, 11, 0.55);
    }

    .bc-status-dot.is-not_configured {
        background: #ef4444;
        box-shadow: 0 0 14px rgba(239, 68, 68, 0.55);
    }

    .bc-filters {
        display: grid;
        grid-template-columns: minmax(120px, 0.8fr) minmax(140px, 0.8fr) minmax(180px, 1.4fr) minmax(180px, 1.4fr) minmax(100px, 0.7fr);
        gap: 10px;
        padding: 14px;
        margin-bottom: 12px;
    }

    .bc-filters select,
    .bc-filters input {
        width: 100%;
        min-height: 42px;
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.72);
        color: #fff;
        padding: 9px 11px;
        outline: none;
    }

    .bc-btn {
        min-height: 40px;
        border-radius: 8px;
        padding: 0 15px;
        border: 1px solid transparent;
        font-weight: 700;
        cursor: pointer;
    }

    .bc-btn--primary {
        background: #fbbf24;
        color: #111827;
        border-color: #fbbf24;
    }

    .bc-btn--muted {
        background: rgba(15, 23, 42, 0.72);
        color: #e5e7eb;
        border-color: rgba(148, 163, 184, 0.28);
    }

    .bc-btn:disabled {
        opacity: 0.55;
        cursor: progress;
    }

    .bc-table-panel {
        padding: 0;
        overflow: hidden;
    }

    .bc-table-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 18px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    }

    .bc-table-wrap {
        overflow-x: auto;
    }

    .bc-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 980px;
    }

    .bc-table th,
    .bc-table td {
        padding: 12px 14px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        vertical-align: top;
        font-size: 0.9rem;
    }

    .bc-table th {
        color: rgba(148, 163, 184, 0.95);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0;
        font-weight: 800;
        background: rgba(2, 6, 23, 0.36);
    }

    .bc-mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.82rem;
        color: rgba(226, 232, 240, 0.9);
    }

    .bc-pill {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        padding: 3px 9px;
        border-radius: 999px;
        background: rgba(59, 130, 246, 0.14);
        color: #bfdbfe;
        border: 1px solid rgba(59, 130, 246, 0.22);
        font-size: 0.8rem;
        font-weight: 700;
    }

    .bc-empty {
        text-align: center;
        color: rgba(203, 213, 225, 0.68);
        padding: 32px;
    }

    @media (max-width: 980px) {
        .bc-status-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .bc-filters {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .bc-status-grid,
        .bc-filters {
            grid-template-columns: 1fr;
        }

        .bc-table-header {
            flex-direction: column;
        }
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('.bc-monitor');
    if (!root) return;

    const urls = {
        summary: root.dataset.summaryUrl,
        events: root.dataset.eventsUrl,
        sync: root.dataset.syncUrl,
    };

    const nodes = {
        sourceLabel: document.getElementById('bc-source-label'),
        sourceMeta: document.getElementById('bc-source-meta'),
        listenerStatus: document.getElementById('bc-listener-status'),
        listenerMeta: document.getElementById('bc-listener-meta'),
        statusDot: document.getElementById('bc-status-dot'),
        eventsTotal: document.getElementById('bc-events-total'),
        eventsByType: document.getElementById('bc-events-by-type'),
        poolsTotal: document.getElementById('bc-pools-total'),
        packagesTotal: document.getElementById('bc-packages-total'),
        lastCheckpoint: document.getElementById('bc-last-checkpoint'),
        network: document.getElementById('bc-network-filter'),
        eventType: document.getElementById('bc-event-filter'),
        pool: document.getElementById('bc-pool-filter'),
        search: document.getElementById('bc-search-filter'),
        limit: document.getElementById('bc-limit-filter'),
        tableMeta: document.getElementById('bc-table-meta'),
        actionStatus: document.getElementById('bc-action-status'),
        body: document.getElementById('bc-events-body'),
        refresh: document.getElementById('bc-refresh-btn'),
        sync: document.getElementById('bc-sync-btn'),
    };

    function shortHash(value) {
        if (!value || value.length < 18) return value || '--';
        return value.slice(0, 10) + '...' + value.slice(-8);
    }

    function formatDate(value) {
        if (!value) return '--';
        return new Intl.DateTimeFormat(undefined, {
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        }).format(new Date(value));
    }

    function formatLag(seconds) {
        if (seconds === null || seconds === undefined) return 'no events yet';
        if (seconds < 60) return seconds + 's lag';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'm lag';
        return Math.floor(seconds / 3600) + 'h lag';
    }

    function setOptions(select, options, label, getValue, getText) {
        const current = select.value;
        select.innerHTML = '<option value="">' + label + '</option>' + options.map((item) => {
            const value = getValue(item);
            const text = getText(item);
            return '<option value="' + escapeHtml(value) + '">' + escapeHtml(text) + '</option>';
        }).join('');
        if ([...select.options].some((option) => option.value === current)) {
            select.value = current;
        }
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    }

    async function fetchJson(url, options) {
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json', ...(options?.headers || {}) },
            ...options,
        });
        const json = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(json.message || 'Request failed');
        }
        return json;
    }

    async function loadSummary() {
        const data = await fetchJson(urls.summary);
        nodes.sourceLabel.textContent = data.source?.label || '--';
        nodes.sourceMeta.textContent = data.source?.bridge_ready
            ? 'External bridge connected'
            : 'Current adapter: Laravel polling, 5 min schedule';

        const status = data.listener?.status || 'unknown';
        nodes.listenerStatus.textContent = status.replaceAll('_', ' ');
        nodes.statusDot.className = 'bc-status-dot is-' + status;
        nodes.listenerMeta.textContent = [
            data.listener?.network || 'network --',
            formatLag(data.listener?.lag_seconds),
            data.listener?.last_event_at ? 'last ' + formatDate(data.listener.last_event_at) : '',
        ].filter(Boolean).join(' | ');

        nodes.eventsTotal.textContent = data.metrics?.events_total ?? 0;
        nodes.poolsTotal.textContent = data.metrics?.pools_total ?? 0;
        nodes.packagesTotal.textContent = data.metrics?.packages_total ?? 0;
        nodes.lastCheckpoint.textContent = 'checkpoint ' + (data.listener?.last_checkpoint ?? '--');

        const counts = data.metrics?.events_by_type || {};
        nodes.eventsByType.textContent = Object.keys(counts).length
            ? Object.entries(counts).map(([key, value]) => key + ': ' + value).join(' | ')
            : '--';

        setOptions(nodes.network, data.filters?.networks || [], 'All networks', (item) => item, (item) => item);
        setOptions(nodes.eventType, data.filters?.event_types || [], 'All events', (item) => item, (item) => item);
        setOptions(
            nodes.pool,
            data.filters?.pools || [],
            'All pools',
            (item) => item.pool_object_id,
            (item) => (item.name || item.pool_object_id) + ' | ' + shortHash(item.pool_object_id)
        );

        nodes.sync.disabled = !(data.actions?.sync_now?.enabled);
    }

    async function loadEvents() {
        const params = new URLSearchParams();
        if (nodes.network.value) params.set('network', nodes.network.value);
        if (nodes.eventType.value) params.set('event_type', nodes.eventType.value);
        if (nodes.pool.value) params.set('pool_object_id', nodes.pool.value);
        if (nodes.search.value.trim()) params.set('q', nodes.search.value.trim());
        params.set('limit', nodes.limit.value || '100');

        nodes.tableMeta.textContent = 'Loading...';
        const data = await fetchJson(urls.events + '?' + params.toString());
        const events = data.data || [];
        nodes.tableMeta.textContent = events.length + ' rows from ' + (data.meta?.source_kind || 'source');

        if (!events.length) {
            nodes.body.innerHTML = '<tr><td colspan="7" class="bc-empty">No events match the current filters.</td></tr>';
            return;
        }

        nodes.body.innerHTML = events.map((event) => `
            <tr>
                <td>${escapeHtml(formatDate(event.event_at))}<div class="bc-panel-meta">#${escapeHtml(event.checkpoint ?? '--')}</div></td>
                <td><span class="bc-pill">${escapeHtml(event.event_type)}</span></td>
                <td>${escapeHtml(event.network)}</td>
                <td>${escapeHtml(event.amount_usdc || '0')}<div class="bc-panel-meta">shares ${escapeHtml(event.pool_shares || '0')}</div></td>
                <td><span class="bc-mono" title="${escapeHtml(event.pool_object_id)}">${escapeHtml(shortHash(event.pool_object_id))}</span></td>
                <td><span class="bc-mono" title="${escapeHtml(event.owner_address)}">${escapeHtml(shortHash(event.owner_address))}</span></td>
                <td><span class="bc-mono" title="${escapeHtml(event.tx_digest)}">${escapeHtml(shortHash(event.tx_digest))}</span><div class="bc-panel-meta">seq ${escapeHtml(event.event_seq)}</div></td>
            </tr>
        `).join('');
    }

    async function reloadAll() {
        nodes.actionStatus.textContent = '';
        try {
            await loadSummary();
            await loadEvents();
        } catch (error) {
            nodes.actionStatus.textContent = error.message;
            nodes.body.innerHTML = '<tr><td colspan="7" class="bc-empty">' + escapeHtml(error.message) + '</td></tr>';
        }
    }

    let searchTimer = null;
    [nodes.network, nodes.eventType, nodes.pool, nodes.limit].forEach((node) => node.addEventListener('change', loadEvents));
    nodes.search.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadEvents, 250);
    });

    nodes.refresh.addEventListener('click', reloadAll);
    nodes.sync.addEventListener('click', async function () {
        nodes.sync.disabled = true;
        nodes.actionStatus.textContent = 'Sync is running...';
        try {
            const data = await fetchJson(urls.sync, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    network: nodes.network.value || undefined,
                }),
            });
            nodes.actionStatus.textContent = data.output || 'Sync complete.';
            await reloadAll();
        } catch (error) {
            nodes.actionStatus.textContent = error.message;
        } finally {
            nodes.sync.disabled = false;
        }
    });

    reloadAll();
});
</script>
@endpush
@endsection
