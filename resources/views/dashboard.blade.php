<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard de Ventas') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ── Filtros ───────────────────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <form id="filtroForm" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[140px]">
                        <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">Sucursal</label>
                        <select id="branchSelect" name="branch_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[130px]">
                        <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">Desde</label>
                        <input type="date" id="dateFrom" name="date_from" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex-1 min-w-[130px]">
                        <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">Hasta</label>
                        <input type="date" id="dateTo" name="date_to" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="setPreset('today')"   class="preset-btn text-xs px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50">Hoy</button>
                        <button type="button" onclick="setPreset('week')"    class="preset-btn text-xs px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50">7 días</button>
                        <button type="button" onclick="setPreset('month')"   class="preset-btn text-xs px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50">Este mes</button>
                        <button type="submit" class="text-sm px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium shadow-sm">Aplicar</button>
                    </div>
                </form>
            </div>

            {{-- ── Tarjetas fila 1: Financieros ─────────────────────────────── --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="stat-card">
                    <div class="stat-icon bg-blue-100 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <p class="stat-label">Ventas del período</p>
                        <p class="stat-value" id="ventasDia">$0.00</p>
                        <p class="stat-sub">Total facturado</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-emerald-100 text-emerald-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                    <div>
                        <p class="stat-label">Margen Bruto</p>
                        <p class="stat-value" id="margenBruto">$0.00</p>
                        <p class="stat-sub" id="margenPct">0% del total</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-violet-100 text-violet-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <div>
                        <p class="stat-label">Costo de Ventas</p>
                        <p class="stat-value" id="costoVentas">$0.00</p>
                        <p class="stat-sub">Mercancía vendida</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-amber-100 text-amber-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <div>
                        <p class="stat-label">Ticket Promedio</p>
                        <p class="stat-value" id="ticketPromedio">$0.00</p>
                        <p class="stat-sub">Por transacción</p>
                    </div>
                </div>
            </div>

            {{-- ── Tarjetas fila 2: Operativos ──────────────────────────────── --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="stat-card">
                    <div class="stat-icon bg-indigo-100 text-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <div>
                        <p class="stat-label">Transacciones</p>
                        <p class="stat-value" id="transacciones">0</p>
                        <p class="stat-sub">Ventas completadas</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-teal-100 text-teal-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2z" /></svg>
                    </div>
                    <div>
                        <p class="stat-label">SKUs vendidos</p>
                        <p class="stat-value" id="skusVendidos">0</p>
                        <p class="stat-sub">Productos distintos</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-cyan-100 text-cyan-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    </div>
                    <div>
                        <p class="stat-label">Valor Inventario</p>
                        <p class="stat-value" id="valorInventario">$0.00</p>
                        <p class="stat-sub">Al costo actual</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-red-100 text-red-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div>
                        <p class="stat-label">Stock Bajo</p>
                        <p class="stat-value" id="stockBajo">0</p>
                        <p class="stat-sub">Productos a resurtir</p>
                    </div>
                </div>
            </div>

            {{-- ── Gráficas fila 1 ──────────────────────────────────────────── --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Ventas por día (2/3) --}}
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-800">Ventas por día</h3>
                            <p class="text-xs text-gray-400">Total facturado diario</p>
                        </div>
                        <span class="text-xs bg-blue-50 text-blue-700 font-medium px-2 py-1 rounded-full" id="labelPeriodo"></span>
                    </div>
                    <canvas id="chartVentas" height="120"></canvas>
                </div>

                {{-- Métodos de pago (1/3) --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-800">Métodos de pago</h3>
                        <p class="text-xs text-gray-400">Distribución del período</p>
                    </div>
                    <canvas id="chartMetodoPago"></canvas>
                </div>
            </div>

            {{-- ── Gráficas fila 2 ──────────────────────────────────────────── --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Top productos (2/3) --}}
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-800">Top 8 productos por venta</h3>
                        <p class="text-xs text-gray-400">Ordenado por ingresos</p>
                    </div>
                    <canvas id="chartTop" height="160"></canvas>
                </div>

                {{-- Ventas por categoría (1/3) --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-800">Ventas por categoría</h3>
                        <p class="text-xs text-gray-400">Ingresos por departamento</p>
                    </div>
                    <canvas id="chartCategoria"></canvas>
                </div>
            </div>

            {{-- ── KPIs de rendimiento ──────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5" id="kpiSection">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="font-semibold text-gray-800">Indicadores de Rendimiento por Producto</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Solo productos con ventas en el período y stock disponible · <span id="kpiPeriodoLabel"></span></p>
                    </div>
                    <div class="flex gap-2 text-xs">
                        <button onclick="sortKpi('tos')"   id="sortTos"   class="kpi-sort-btn px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 font-medium">↕ TOS</button>
                        <button onclick="sortKpi('st')"    id="sortSt"    class="kpi-sort-btn px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 font-medium">↕ ST%</button>
                        <button onclick="sortKpi('gmroi')" id="sortGmroi" class="kpi-sort-btn active px-3 py-1.5 rounded-lg border font-medium">↕ GMROI</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5 text-xs">
                    <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                        <p class="font-bold text-blue-800">TOS — Rotación de Stock</p>
                        <p class="text-blue-700 mt-1">Veces que rota el inventario/año. <strong>&gt;4</strong> excelente · <strong>2–4</strong> normal · <strong>&lt;2</strong> lento.</p>
                    </div>
                    <div class="bg-purple-50 rounded-xl p-3 border border-purple-100">
                        <p class="font-bold text-purple-800">ST% — Sell Through mensual</p>
                        <p class="text-purple-700 mt-1">% del stock que se vende/mes. <strong>&gt;30%</strong> muy activo · <strong>15–30%</strong> normal · <strong>&lt;15%</strong> lento.</p>
                    </div>
                    <div class="bg-green-50 rounded-xl p-3 border border-green-100">
                        <p class="font-bold text-green-800">GMROI — Retorno sobre Inventario</p>
                        <p class="text-green-700 mt-1">$ de margen por $1 invertido/año. <strong>&gt;2.0</strong> excelente · <strong>1–2</strong> aceptable · <strong>&lt;1</strong> riesgo.</p>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th class="px-3 py-2.5 font-semibold">Producto</th>
                                <th class="px-3 py-2.5 font-semibold text-center">Categoría</th>
                                <th class="px-3 py-2.5 font-semibold text-center">Stock</th>
                                <th class="px-3 py-2.5 font-semibold text-center">Vendido</th>
                                <th class="px-3 py-2.5 font-semibold text-center">Margen $</th>
                                <th class="px-3 py-2.5 font-semibold text-center" title="Turn Over Stock – rotaciones/año">TOS</th>
                                <th class="px-3 py-2.5 font-semibold text-center" title="Sell Through mensual">ST%</th>
                                <th class="px-3 py-2.5 font-semibold text-center" title="Gross Margin Return on Inventory Investment">GMROI</th>
                            </tr>
                        </thead>
                        <tbody id="tablaKpis" class="divide-y divide-gray-50">
                            <tr><td colspan="8" class="text-center py-8 text-gray-400">Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── Detalle de ventas ────────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Detalle de ventas por ticket</h3>
                <div class="overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wide text-xs">
                            <tr>
                                <th class="px-3 py-2.5 font-semibold">Fecha</th>
                                <th class="px-3 py-2.5 font-semibold">Productos</th>
                                <th class="px-3 py-2.5 font-semibold text-center">Cant.</th>
                                <th class="px-3 py-2.5 font-semibold text-right">Total</th>
                                <th class="px-3 py-2.5 font-semibold text-center">Pago</th>
                            </tr>
                        </thead>
                        <tbody id="tablaVentasDetalle" class="divide-y divide-gray-50">
                            <tr><td colspan="5" class="text-center py-6 text-gray-400">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- JS --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <style>
        /* ── Stat cards ─────────────────────────────────────── */
        .stat-card {
            display: flex; gap: 14px; align-items: center;
            background: #fff; border-radius: 16px; padding: 16px 18px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            border: 1px solid #f1f5f9;
        }
        .stat-icon {
            flex-shrink: 0; width: 42px; height: 42px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }
        .stat-label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
        .stat-value { font-size: 22px; font-weight: 800; color: #111827; line-height: 1.15; margin-top: 2px; }
        .stat-sub   { font-size: 10px; color: #9ca3af; margin-top: 2px; }

        /* ── KPI badges ─────────────────────────────────────── */
        .kpi-sort-btn { transition: all .15s; }
        .kpi-sort-btn.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
        .kpi-badge { display:inline-block; padding:2px 7px; border-radius:9999px; font-weight:700; font-size:10px; }
        .kpi-green  { background:#d1fae5; color:#065f46; }
        .kpi-yellow { background:#fef9c3; color:#854d0e; }
        .kpi-red    { background:#fee2e2; color:#991b1b; }
        .kpi-gray   { background:#f3f4f6; color:#6b7280; }

        /* ── Preset buttons ─────────────────────────────────── */
        .preset-btn { transition: all .15s; }
        .preset-btn.active { background: #eff6ff; color: #2563eb; border-color: #93c5fd; }
    </style>
    <script>
        let chartVentas, chartTop, chartMetodoPago, chartCategoria;
        let kpiData = [];
        let kpiSortField = 'gmroi';
        let kpiSortAsc   = false;

        // ── Paletas ────────────────────────────────────────────────────────────
        const PALETTE = ['#3b82f6','#10b981','#8b5cf6','#f59e0b','#ef4444','#06b6d4','#f97316','#ec4899'];
        const METHOD_COLORS = {
            efectivo: '#10b981', tarjeta: '#3b82f6', transferencia: '#8b5cf6',
            mixto: '#f59e0b', credito: '#ef4444',
        };
        const fmt  = v => '$' + parseFloat(v ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const trunc = (s, n) => s && s.length > n ? s.substring(0, n - 1) + '…' : s;

        // ── Preset de fechas ───────────────────────────────────────────────────
        function setPreset(p) {
            const today = new Date();
            let from = new Date(), to = new Date();
            if (p === 'week')  from.setDate(today.getDate() - 6);
            if (p === 'month') from = new Date(today.getFullYear(), today.getMonth(), 1);
            document.getElementById('dateFrom').value = from.toISOString().slice(0,10);
            document.getElementById('dateTo').value   = to.toISOString().slice(0,10);
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            document.querySelector(`[onclick="setPreset('${p}')"]`)?.classList.add('active');
            cargarDashboard(document.getElementById('dateFrom').value, document.getElementById('dateTo').value);
        }

        // ── Carga de sucursales ────────────────────────────────────────────────
        function cargarSucursales() {
            fetch("{{ url('/api/pos/branches') }}", { credentials: 'include' })
                .then(r => r.json())
                .then(branches => {
                    const sel = document.getElementById('branchSelect');
                    sel.innerHTML = '<option value="">Todas</option>';
                    branches.forEach(b => {
                        const o = document.createElement('option');
                        o.value = b.id;
                        o.textContent = b.name ?? b.branch_name ?? 'Sucursal';
                        sel.appendChild(o);
                    });
                });
        }

        // ── Dashboard principal ────────────────────────────────────────────────
        function cargarDashboard(dateFrom, dateTo) {
            const branchId = document.getElementById('branchSelect').value;
            let url = "{{ url('/dashboard/data') }}?";
            if (branchId)           url += `branch_id=${branchId}&`;
            if (dateFrom && dateTo) url += `date_from=${dateFrom}&date_to=${dateTo}`;

            fetch(url, { credentials: 'include' })
                .then(r => r.json())
                .then(data => {
                    // ── Tarjetas ─────────────────────────────────────────────
                    document.getElementById('ventasDia').textContent      = fmt(data.total_sales);
                    document.getElementById('margenBruto').textContent    = fmt(data.total_margin);
                    document.getElementById('margenPct').textContent      = `${data.margin_pct ?? 0}% del total`;
                    document.getElementById('costoVentas').textContent    = fmt(data.total_cost);
                    document.getElementById('ticketPromedio').textContent = fmt(data.average_ticket);
                    document.getElementById('transacciones').textContent  = data.transactions_count;
                    document.getElementById('skusVendidos').textContent   = data.unique_skus ?? 0;
                    document.getElementById('stockBajo').textContent      = data.low_stock_products;
                    // valor inventario viene en los kpis (stock × costo)
                    const valInv = (data.kpis ?? []).reduce((s, p) => s + (p.stock * (p.margen_bruto / (p.vendido || 1) + 0)), 0);
                    // Calculamos valor inventario desde la suma de (stock × costo) — aproximado desde kpis
                    const invCost = (data.kpis ?? []).reduce((s, p) => {
                        // costo unitario ≈ (venta_total - margen_bruto) / vendido
                        const cUnit = p.vendido > 0 ? (p.venta_total - p.margen_bruto) / p.vendido : 0;
                        return s + (p.stock * cUnit);
                    }, 0);
                    document.getElementById('valorInventario').textContent = fmt(invCost);

                    // Etiqueta período
                    document.getElementById('labelPeriodo').textContent = `${data.kpi_periodo ?? 1} día(s)`;

                    // ── Destruir gráficas anteriores ──────────────────────────
                    [chartVentas, chartTop, chartMetodoPago, chartCategoria].forEach(c => c?.destroy());

                    // ── Chart: Ventas por día ─────────────────────────────────
                    const ctxV = document.getElementById('chartVentas').getContext('2d');
                    const gradV = ctxV.createLinearGradient(0, 0, 0, 280);
                    gradV.addColorStop(0, 'rgba(59,130,246,.35)');
                    gradV.addColorStop(1, 'rgba(59,130,246,0)');
                    chartVentas = new Chart(ctxV, {
                        type: 'line',
                        data: {
                            labels: data.daily_sales.map(s => s.date),
                            datasets: [{
                                label: 'Ventas ($)',
                                data: data.daily_sales.map(s => s.total),
                                borderColor: '#3b82f6',
                                backgroundColor: gradV,
                                borderWidth: 2.5,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4,
                                pointBackgroundColor: '#3b82f6',
                                pointHoverRadius: 6,
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: { label: ctx => ' ' + fmt(ctx.parsed.y) }
                                }
                            },
                            scales: {
                                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                                y: {
                                    grid: { color: '#f3f4f6' },
                                    ticks: { font: { size: 11 }, callback: v => '$' + v.toLocaleString('es-MX') }
                                }
                            }
                        }
                    });

                    // ── Chart: Top productos (barras horizontales) ────────────
                    const topLabels = data.top_products.map(p => trunc(p.name, 30));
                    const topData   = data.top_products.map(p => p.revenue);
                    chartTop = new Chart(document.getElementById('chartTop'), {
                        type: 'bar',
                        data: {
                            labels: topLabels,
                            datasets: [{
                                label: 'Ingresos ($)',
                                data: topData,
                                backgroundColor: topLabels.map((_, i) => PALETTE[i % PALETTE.length] + 'cc'),
                                borderColor:     topLabels.map((_, i) => PALETTE[i % PALETTE.length]),
                                borderWidth: 1.5,
                                borderRadius: 6,
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: ctx => ' ' + fmt(ctx.parsed.x) } }
                            },
                            scales: {
                                x: {
                                    grid: { color: '#f3f4f6' },
                                    ticks: { font: { size: 10 }, callback: v => '$' + v.toLocaleString('es-MX') }
                                },
                                y: { grid: { display: false }, ticks: { font: { size: 11 } } }
                            }
                        }
                    });

                    // ── Chart: Métodos de pago (doughnut) ─────────────────────
                    const methodLabels = data.sales_by_method.map(m => m.payment_method);
                    const methodData   = data.sales_by_method.map(m => m.total);
                    const methodColors = methodLabels.map(l => METHOD_COLORS[l] ?? '#94a3b8');
                    chartMetodoPago = new Chart(document.getElementById('chartMetodoPago'), {
                        type: 'doughnut',
                        data: {
                            labels: methodLabels,
                            datasets: [{
                                data: methodData,
                                backgroundColor: methodColors.map(c => c + 'cc'),
                                borderColor: methodColors,
                                borderWidth: 2,
                                hoverOffset: 6,
                            }]
                        },
                        options: {
                            cutout: '62%',
                            plugins: {
                                legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 14 } },
                                tooltip: { callbacks: { label: ctx => ' ' + fmt(ctx.parsed) } }
                            }
                        }
                    });

                    // ── Chart: Ventas por categoría (doughnut) ────────────────
                    const catLabels = (data.sales_by_category ?? []).map(c => c.category);
                    const catData   = (data.sales_by_category ?? []).map(c => c.total);
                    chartCategoria = new Chart(document.getElementById('chartCategoria'), {
                        type: 'doughnut',
                        data: {
                            labels: catLabels,
                            datasets: [{
                                data: catData,
                                backgroundColor: catLabels.map((_, i) => PALETTE[i % PALETTE.length] + 'cc'),
                                borderColor:     catLabels.map((_, i) => PALETTE[i % PALETTE.length]),
                                borderWidth: 2,
                                hoverOffset: 6,
                            }]
                        },
                        options: {
                            cutout: '62%',
                            plugins: {
                                legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } },
                                tooltip: { callbacks: { label: ctx => ' ' + fmt(ctx.parsed) } }
                            }
                        }
                    });

                    // ── Tabla detalle ─────────────────────────────────────────
                    const tbody = document.getElementById('tablaVentasDetalle');
                    if (!data.sales_detail?.length) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-400">Sin ventas en el período</td></tr>';
                    } else {
                        tbody.innerHTML = data.sales_detail.map(s => `
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2.5 text-gray-700">${s.sale_date}</td>
                                <td class="px-3 py-2.5 text-gray-600 text-xs">${s.products}</td>
                                <td class="px-3 py-2.5 text-center">${s.total_quantity}</td>
                                <td class="px-3 py-2.5 text-right font-semibold">${fmt(s.total_amount)}</td>
                                <td class="px-3 py-2.5 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                          style="background:${(METHOD_COLORS[s.payment_method]??'#94a3b8')}22;color:${METHOD_COLORS[s.payment_method]??'#64748b'}">
                                        ${s.payment_method}
                                    </span>
                                </td>
                            </tr>`).join('');
                    }

                    // ── KPIs ──────────────────────────────────────────────────
                    kpiData = data.kpis ?? [];
                    document.getElementById('kpiPeriodoLabel').textContent =
                        `${data.kpi_periodo ?? '?'} día(s) · anualizado a 365 días`;
                    renderKpis();
                });
        }

        // ── KPI helpers ────────────────────────────────────────────────────────
        function kpiBadge(value, thresholds, decimals = 2, suffix = '') {
            if (value === null || value === undefined)
                return '<span class="kpi-badge kpi-gray">—</span>';
            const [hi, lo] = thresholds;
            const cls = value >= hi ? 'kpi-green' : value >= lo ? 'kpi-yellow' : 'kpi-red';
            return `<span class="kpi-badge ${cls}">${value.toFixed(decimals)}${suffix}</span>`;
        }

        function renderKpis() {
            const tbody = document.getElementById('tablaKpis');
            if (!kpiData.length) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-gray-400">Sin datos para el período seleccionado</td></tr>';
                return;
            }
            const sorted = [...kpiData].sort((a, b) => {
                const va = a[kpiSortField] ?? -Infinity;
                const vb = b[kpiSortField] ?? -Infinity;
                return kpiSortAsc ? va - vb : vb - va;
            });
            tbody.innerHTML = sorted.map(p => `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-3 py-2.5 font-medium text-gray-800">${trunc(p.name, 40)}</td>
                    <td class="px-3 py-2.5 text-center text-gray-500">${p.category}</td>
                    <td class="px-3 py-2.5 text-center">${p.stock}</td>
                    <td class="px-3 py-2.5 text-center">${p.vendido}</td>
                    <td class="px-3 py-2.5 text-center font-medium">${fmt(p.margen_bruto)}</td>
                    <td class="px-3 py-2.5 text-center">${kpiBadge(p.tos,   [4, 2])}</td>
                    <td class="px-3 py-2.5 text-center">${kpiBadge(p.st,    [30, 15], 1, '%')}</td>
                    <td class="px-3 py-2.5 text-center">${kpiBadge(p.gmroi, [2, 1])}</td>
                </tr>`).join('');
        }

        function sortKpi(field) {
            if (kpiSortField === field) kpiSortAsc = !kpiSortAsc;
            else { kpiSortField = field; kpiSortAsc = false; }
            document.querySelectorAll('.kpi-sort-btn').forEach(b => b.classList.remove('active'));
            document.getElementById({ tos:'sortTos', st:'sortSt', gmroi:'sortGmroi' }[field])?.classList.add('active');
            renderKpis();
        }

        // ── Inicio ────────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            cargarSucursales();
            const today = new Date().toISOString().slice(0, 10);
            document.getElementById('dateFrom').value = today;
            document.getElementById('dateTo').value   = today;
            document.querySelector('[onclick="setPreset(\'today\')"]')?.classList.add('active');
            cargarDashboard(today, today);
        });

        document.getElementById('branchSelect').addEventListener('change', () => {
            cargarDashboard(document.getElementById('dateFrom').value, document.getElementById('dateTo').value);
        });

        document.getElementById('filtroForm').addEventListener('submit', e => {
            e.preventDefault();
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            cargarDashboard(document.getElementById('dateFrom').value, document.getElementById('dateTo').value);
        });
    </script>

</x-app-layout>
