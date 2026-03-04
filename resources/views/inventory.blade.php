<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LuraPos — Inventario</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        [x-cloak] { display: none !important; }
        .stock-low  { @apply bg-yellow-100 text-yellow-700; }
        .stock-zero { @apply bg-red-100 text-red-700; }
        .stock-ok   { @apply bg-green-100 text-green-700; }
        .input-base {
            @apply w-full border border-gray-200 rounded-lg px-3 py-2 text-sm
                   focus:outline-none focus:ring-2 focus:ring-zinc-300 focus:border-zinc-400
                   transition-colors;
        }
        /* Scanner pulse */
        @keyframes pulse-green {
            0%,100% { box-shadow: 0 0 0 0 rgba(22,163,74,.4); }
            50%      { box-shadow: 0 0 0 8px rgba(22,163,74,0); }
        }
        .scanning { animation: pulse-green 1s ease infinite; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">

<!-- ════════════════════════════ NAVBAR ════════════════════════════ -->
<nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img src="{{ asset('/assets/img/logoSF.png') }}" alt="Logo" class="h-7 w-auto">
            <span class="text-sm font-bold text-gray-700 hidden sm:block">Inventario</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('fotos.view') }}"
               class="flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700
                      bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors">
                <i class="fa-solid fa-camera"></i>
                <span class="hidden sm:inline">Fotos</span>
            </a>
            <a href="{{ url('/') }}"
               class="flex items-center gap-1.5 text-xs font-semibold text-white
                      bg-zinc-700 hover:bg-zinc-800 px-3 py-1.5 rounded-lg transition-colors">
                <i class="fa-solid fa-cash-register"></i>
                <span class="hidden sm:inline">Ir al POS</span>
            </a>
        </div>
    </div>
</nav>

<!-- ══════════════════════ LAYOUT PRINCIPAL ══════════════════════ -->
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    <!-- ════════════ SECCIÓN 1: ENTRADA DE MERCANCÍA ════════════ -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <!-- Header sección -->
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <i class="fa-solid fa-truck-ramp-box text-emerald-600 text-sm"></i>
                </div>
                <div>
                    <h2 class="font-bold text-gray-800 text-sm leading-tight">Entrada de mercancía</h2>
                    <p class="text-xs text-gray-400">Registra unidades que llegaron al almacén</p>
                </div>
            </div>
            <button id="btnToggleEntry"
                    onclick="toggleEntrySection()"
                    class="text-xs font-semibold text-gray-400 hover:text-gray-600 flex items-center gap-1">
                <i id="entryChevron" class="fa-solid fa-chevron-up transition-transform"></i>
            </button>
        </div>

        <div id="entrySection">
            <div class="px-5 py-4 grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Formulario de entrada -->
                <div class="space-y-4">
                    <!-- Búsqueda de producto -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            Producto <span class="font-normal text-gray-400">(código o nombre · también con scanner)</span>
                        </label>
                        <div class="relative">
                            <input id="entrySearch"
                                   type="text"
                                   placeholder="Escanea o escribe..."
                                   autocomplete="off"
                                   class="input-base pr-10"
                                   oninput="onEntrySearchInput()"
                                   onkeydown="onEntrySearchKey(event)">
                            <i class="fa-solid fa-barcode absolute right-3 top-1/2 -translate-y-1/2 text-gray-300"></i>
                            <!-- Autocomplete dropdown -->
                            <div id="entryDropdown"
                                 class="absolute z-30 w-full bg-white border border-gray-200 rounded-lg shadow-lg
                                        mt-1 max-h-48 overflow-y-auto hidden">
                            </div>
                        </div>
                    </div>

                    <!-- Producto seleccionado -->
                    <div id="entryProductCard" class="hidden rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p id="entryProductName" class="font-bold text-gray-800 text-sm truncate"></p>
                                <p id="entryProductCode" class="text-xs text-gray-500 font-mono"></p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs text-gray-500">Stock actual</p>
                                <p id="entryProductStock" class="text-lg font-black text-emerald-700"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Cantidad -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            Cantidad a agregar
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    onclick="adjustEntryQty(-1)"
                                    class="w-10 h-10 rounded-lg border border-gray-200 hover:bg-gray-50
                                           flex items-center justify-center text-gray-500 font-bold text-lg shrink-0">
                                −
                            </button>
                            <input id="entryQty" type="number" value="1" min="1"
                                   class="input-base text-center font-bold text-lg h-10">
                            <button type="button"
                                    onclick="adjustEntryQty(1)"
                                    class="w-10 h-10 rounded-lg border border-gray-200 hover:bg-gray-50
                                           flex items-center justify-center text-gray-500 font-bold text-lg shrink-0">
                                +
                            </button>
                            <div class="flex gap-1 ml-1">
                                <button type="button" onclick="setEntryQty(5)"
                                        class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200 font-semibold text-gray-600">
                                    +5
                                </button>
                                <button type="button" onclick="setEntryQty(10)"
                                        class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200 font-semibold text-gray-600">
                                    +10
                                </button>
                                <button type="button" onclick="setEntryQty(24)"
                                        class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200 font-semibold text-gray-600">
                                    +24
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notas + precios opcionales -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Notas (opcional)</label>
                        <input id="entryNotes" type="text" placeholder="Ej: Factura #123, proveedor ACME…"
                               class="input-base">
                    </div>

                    <div id="entryPricesSection" class="hidden grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                                Actualizar P. Costo
                                <span class="font-normal text-gray-400">(opcional)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                <input id="entryCosto" type="number" step="0.01" min="0"
                                       placeholder="0.00" class="input-base pl-6">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                                Actualizar P. Venta
                                <span class="font-normal text-gray-400">(opcional)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                <input id="entryVenta" type="number" step="0.01" min="0"
                                       placeholder="0.00" class="input-base pl-6">
                            </div>
                        </div>
                    </div>

                    <button id="btnUpdatePrice"
                            type="button"
                            onclick="togglePrices()"
                            class="text-xs text-zinc-500 hover:text-zinc-700 font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-tag text-xs"></i>
                        <span id="priceToggleLabel">Actualizar precios al registrar</span>
                    </button>

                    <!-- Botón registrar -->
                    <button id="btnRegisterEntry"
                            onclick="registerEntry()"
                            disabled
                            class="w-full py-3 rounded-xl font-bold text-sm transition-colors
                                   bg-gray-100 text-gray-400 cursor-not-allowed"
                            data-enabled-cls="bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer"
                            data-disabled-cls="bg-gray-100 text-gray-400 cursor-not-allowed">
                        <i class="fa-solid fa-plus mr-2"></i>Selecciona un producto primero
                    </button>
                </div>

                <!-- Log de entradas de hoy -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">
                            Entradas de hoy
                        </p>
                        <button onclick="loadTodayEntries()"
                                class="text-xs text-zinc-500 hover:text-zinc-700 font-semibold">
                            <i class="fa-solid fa-rotate-right mr-1"></i>Actualizar
                        </button>
                    </div>
                    <div id="entryLog"
                         class="space-y-1 max-h-72 overflow-y-auto pr-1">
                        <p class="text-xs text-gray-400 text-center py-4">Cargando…</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════ SECCIÓN 2: LISTA DE PRODUCTOS ════════════ -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <!-- Header con filtros -->
        <div class="px-5 py-4 border-b border-gray-100">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Búsqueda -->
                <div class="relative flex-1 min-w-48">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
                    <input id="prodSearch" type="text" placeholder="Buscar por código o nombre…"
                           class="input-base pl-8" oninput="applyFilters()">
                </div>
                <!-- Departamento -->
                <select id="prodCategory" class="input-base w-auto min-w-32" onchange="applyFilters()">
                    <option value="">Todos los deptos.</option>
                    @foreach ($categories as $cat)
                        <option value="{{ strtolower($cat) }}">{{ $cat }}</option>
                    @endforeach
                </select>
                <!-- Stock -->
                <select id="prodStock" class="input-base w-auto min-w-36" onchange="applyFilters()">
                    <option value="">Todo el stock</option>
                    <option value="zero">Sin existencia</option>
                    <option value="low">Stock bajo (≤ mínimo)</option>
                    <option value="ok">Con existencia</option>
                </select>
                <!-- Contador -->
                <span id="prodCount" class="text-xs text-gray-400 whitespace-nowrap shrink-0"></span>
                <!-- Botón nuevo -->
                <button onclick="openProductModal(null)"
                        class="flex items-center gap-1.5 text-xs font-bold text-white
                               bg-zinc-700 hover:bg-zinc-800 px-3 py-2 rounded-lg transition-colors shrink-0">
                    <i class="fa-solid fa-plus"></i>
                    Nuevo producto
                </button>
            </div>
        </div>

        <!-- Tabla -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-100">
                        <th class="px-4 py-3 text-left cursor-pointer hover:text-gray-700 select-none"
                            onclick="sortTable('id')">
                            # <span id="sort-id" class="text-gray-300">↕</span>
                        </th>
                        <th class="px-4 py-3 text-left">Código</th>
                        <th class="px-4 py-3 text-left cursor-pointer hover:text-gray-700 select-none"
                            onclick="sortTable('name')">
                            Nombre <span id="sort-name" class="text-gray-300">↕</span>
                        </th>
                        <th class="px-4 py-3 text-left">Depto.</th>
                        <th class="px-4 py-3 text-center cursor-pointer hover:text-gray-700 select-none"
                            onclick="sortTable('stock')">
                            Existencia <span id="sort-stock" class="text-gray-300">↕</span>
                        </th>
                        <th class="px-4 py-3 text-center text-xs">Mín / Máx</th>
                        <th class="px-4 py-3 text-right cursor-pointer hover:text-gray-700 select-none"
                            onclick="sortTable('price')">
                            P. Venta <span id="sort-price" class="text-gray-300">↕</span>
                        </th>
                        <th class="px-4 py-3 text-right">P. Mayoreo</th>
                        <th class="px-4 py-3 text-right">P. Costo</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="prodTableBody" class="divide-y divide-gray-50">
                    <tr>
                        <td colspan="10" class="py-10 text-center text-gray-400 text-sm">
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i>Cargando productos…
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div id="prodEmpty" class="hidden py-14 flex flex-col items-center text-gray-300">
            <i class="fa-solid fa-box-open text-5xl mb-3"></i>
            <p class="text-base font-semibold text-gray-400">No se encontraron productos</p>
            <p class="text-sm mt-1">Ajusta los filtros o agrega uno nuevo</p>
        </div>
    </div>

</div><!-- /max-w-7xl -->


<!-- ══════════════════════ MODAL PRODUCTO ══════════════════════ -->
<div id="productModal"
     class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90dvh] overflow-y-auto">

        <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 id="modalTitle" class="font-bold text-gray-800">Nuevo producto</h2>
            <button onclick="closeProductModal()"
                    class="w-8 h-8 rounded-full hover:bg-gray-100 text-gray-400
                           flex items-center justify-center text-xl leading-none">
                &times;
            </button>
        </div>

        <form id="productForm" class="px-6 py-5 space-y-4" onsubmit="submitProductForm(event)">
            <input type="hidden" id="formProductId">

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        Código <span class="text-red-500">*</span>
                    </label>
                    <input id="fCodigo" name="codigo" type="text" required class="input-base"
                           placeholder="Ej: 7501234567890">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        Departamento
                    </label>
                    <input id="fDpto" name="dpto" type="text" list="dpto-list-modal" class="input-base"
                           placeholder="Ej: FERRETERIA">
                    <datalist id="dpto-list-modal">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}">
                        @endforeach
                    </datalist>
                </div>

                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        Nombre / Descripción <span class="text-red-500">*</span>
                    </label>
                    <input id="fProducto" name="producto" type="text" required class="input-base"
                           placeholder="Nombre del producto">
                </div>

                <!-- Stock -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Existencia</label>
                    <input id="fExistencia" name="existencia" type="number" min="0" value="0"
                           class="input-base">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Inv. mín.</label>
                        <input id="fInvMin" name="inv_min" type="number" min="0" value="1"
                               class="input-base">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Inv. máx.</label>
                        <input id="fInvMax" name="inv_max" type="number" min="0" value="10"
                               class="input-base">
                    </div>
                </div>

                <!-- Precios -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        P. Costo <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                        <input id="fPcosto" name="p_costo" type="number" step="0.01" min="0" required
                               value="0" class="input-base pl-6">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        P. Venta <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                        <input id="fPventa" name="p_venta" type="number" step="0.01" min="0" required
                               value="0" class="input-base pl-6">
                    </div>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">P. Mayoreo</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                        <input id="fPmayoreo" name="p_mayoreo" type="number" step="0.01" min="0"
                               value="0" class="input-base pl-6">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeProductModal()"
                        class="px-4 py-2 text-sm font-semibold rounded-lg border border-gray-200
                               hover:bg-gray-50 text-gray-600 transition-colors">
                    Cancelar
                </button>
                <button type="submit" id="formSubmitBtn"
                        class="px-5 py-2 text-sm font-bold rounded-lg bg-zinc-700 hover:bg-zinc-800
                               text-white transition-colors">
                    <i class="fa-solid fa-floppy-disk mr-1.5"></i>Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast -->
<div id="invToast"
     class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 hidden
            px-5 py-2.5 rounded-full text-sm font-semibold text-white shadow-xl
            transition-all duration-300">
</div>


<script>
/* ═══════════════════════════════════════════════════════════════
   CONSTANTES
═══════════════════════════════════════════════════════════════ */
const API    = '/api/pos';
const CSRF   = document.querySelector('meta[name="csrf-token"]').content;

/* ── Estado global ── */
let allProducts    = [];
let filteredProds  = [];
let sortState      = { field: 'id', order: 'asc' };
let selectedEntry  = null;   // producto seleccionado para entrada
let showPrices     = false;

/* ═══════════════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════════════ */
async function apiFetch(endpoint, opts = {}) {
    const res = await fetch(API + endpoint, {
        ...opts,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept'      : 'application/json',
            ...(opts.headers || {}),
        },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);
    return data;
}

function toast(msg, ok = true) {
    const el = document.getElementById('invToast');
    el.textContent  = msg;
    el.className    = `fixed bottom-6 left-1/2 -translate-x-1/2 z-50
        px-5 py-2.5 rounded-full text-sm font-semibold text-white shadow-xl
        ${ok ? 'bg-emerald-600' : 'bg-red-600'}`;
    el.classList.remove('hidden');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.add('hidden'), 3000);
}

function stockBadge(stock, min) {
    if (stock <= 0)   return `<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">${stock}</span>`;
    if (stock <= min) return `<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">${stock}</span>`;
    return                    `<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">${stock}</span>`;
}

function fmt(n) { return Number(n).toFixed(2); }

/* ═══════════════════════════════════════════════════════════════
   CARGA DE PRODUCTOS
═══════════════════════════════════════════════════════════════ */
async function loadProducts() {
    try {
        const data = await apiFetch('/products');
        allProducts = data;
        applyFilters();
    } catch(e) {
        document.getElementById('prodTableBody').innerHTML =
            `<tr><td colspan="10" class="py-8 text-center text-red-400 text-sm">Error al cargar: ${e.message}</td></tr>`;
    }
}

/* ═══════════════════════════════════════════════════════════════
   FILTROS Y TABLA
═══════════════════════════════════════════════════════════════ */
function applyFilters() {
    const search   = document.getElementById('prodSearch').value.toLowerCase();
    const category = document.getElementById('prodCategory').value.toLowerCase();
    const stockF   = document.getElementById('prodStock').value;

    filteredProds = allProducts.filter(p => {
        const matchSearch   = p.name.toLowerCase().includes(search) || p.code.toLowerCase().includes(search);
        const matchCat      = !category || (p.category || '').toLowerCase() === category;
        let   matchStock    = true;
        if (stockF === 'zero') matchStock = p.stock <= 0;
        else if (stockF === 'low') matchStock = p.stock > 0 && p.stock <= p.inv_min;
        else if (stockF === 'ok')  matchStock = p.stock > 0;
        return matchSearch && matchCat && matchStock;
    });

    sortProducts();
    renderTable();
}

function sortProducts() {
    const { field, order } = sortState;
    filteredProds.sort((a, b) => {
        let va, vb;
        if (field === 'id')    { va = a.id;    vb = b.id; }
        if (field === 'name')  { va = a.name;  vb = b.name; }
        if (field === 'stock') { va = a.stock; vb = b.stock; }
        if (field === 'price') { va = a.price; vb = b.price; }
        if (va === vb) return 0;
        const cmp = va > vb ? 1 : -1;
        return order === 'asc' ? cmp : -cmp;
    });
}

function sortTable(field) {
    if (sortState.field === field) {
        sortState.order = sortState.order === 'asc' ? 'desc' : 'asc';
    } else {
        sortState.field = field;
        sortState.order = 'asc';
    }
    ['id','name','stock','price'].forEach(f => {
        const el = document.getElementById(`sort-${f}`);
        if (el) el.textContent = f === field ? (sortState.order === 'asc' ? '↑' : '↓') : '↕';
    });
    sortProducts();
    renderTable();
}

function renderTable() {
    const body  = document.getElementById('prodTableBody');
    const empty = document.getElementById('prodEmpty');
    const count = document.getElementById('prodCount');

    count.textContent = `${filteredProds.length} producto${filteredProds.length !== 1 ? 's' : ''}`;

    if (!filteredProds.length) {
        body.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }
    empty.classList.add('hidden');

    body.innerHTML = filteredProds.map(p => `
        <tr class="hover:bg-gray-50 transition-colors group" data-id="${p.id}">
            <td class="px-4 py-3 text-xs text-gray-400">${p.id}</td>
            <td class="px-4 py-3 font-mono text-xs text-gray-600">${p.code}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-800 max-w-xs">
                <span class="block truncate" title="${p.name}">${p.name}</span>
            </td>
            <td class="px-4 py-3 text-xs text-gray-500">${p.category || '—'}</td>
            <td class="px-4 py-3 text-center">${stockBadge(p.stock, p.inv_min)}</td>
            <td class="px-4 py-3 text-center text-xs text-gray-400">
                ${p.inv_min} / ${p.inv_max || '—'}
            </td>
            <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800">
                $${fmt(p.price)}
            </td>
            <td class="px-4 py-3 text-right text-sm text-gray-500">
                ${p.wholesale > 0 ? '$' + fmt(p.wholesale) : '<span class="text-gray-300">—</span>'}
            </td>
            <td class="px-4 py-3 text-right text-xs text-gray-400">$${fmt(p.cost)}</td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                    <!-- Entrada rápida -->
                    <button title="Entrada de mercancía"
                            onclick="quickEntry(${p.id})"
                            class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600
                                   flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-plus text-xs"></i>
                    </button>
                    <!-- Editar -->
                    <button title="Editar producto"
                            onclick="openProductModal(${p.id})"
                            class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600
                                   flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </button>
                    <!-- Eliminar -->
                    <button title="Eliminar producto"
                            onclick="confirmDelete(${p.id}, '${p.name.replace(/'/g,"\\\'")}')"
                            class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 text-red-500
                                   flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-trash text-xs"></i>
                    </button>
                </div>
            </td>
        </tr>`).join('');
}

/* ═══════════════════════════════════════════════════════════════
   ENTRADA DE MERCANCÍA
═══════════════════════════════════════════════════════════════ */
function toggleEntrySection() {
    const section  = document.getElementById('entrySection');
    const chevron  = document.getElementById('entryChevron');
    const isHidden = section.classList.contains('hidden');
    section.classList.toggle('hidden', !isHidden);
    chevron.classList.toggle('fa-chevron-up',   isHidden);
    chevron.classList.toggle('fa-chevron-down', !isHidden);
}

/* Búsqueda de producto para entrada */
let _entryTimer = null;
function onEntrySearchInput() {
    const val = document.getElementById('entrySearch').value.trim();
    clearTimeout(_entryTimer);
    if (val.length < 1) { closeEntryDropdown(); return; }
    _entryTimer = setTimeout(() => searchEntryProducts(val), 200);
}

function onEntrySearchKey(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const dd    = document.getElementById('entryDropdown');
        const first = dd.querySelector('[data-entry-item]');
        if (first) first.click();
    }
    if (e.key === 'Escape') closeEntryDropdown();
}

function searchEntryProducts(q) {
    const results = allProducts.filter(p =>
        p.code.toLowerCase().includes(q.toLowerCase()) ||
        p.name.toLowerCase().includes(q.toLowerCase())
    ).slice(0, 8);

    renderEntryDropdown(results);
}

function renderEntryDropdown(items) {
    const dd = document.getElementById('entryDropdown');
    if (!items.length) { dd.classList.add('hidden'); return; }

    dd.innerHTML = items.map(p => `
        <div data-entry-item="${p.id}"
             class="px-3 py-2.5 hover:bg-gray-50 cursor-pointer flex items-center justify-between gap-3 border-b border-gray-50 last:border-0"
             onclick="selectEntryProduct(${p.id})">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate">${p.name}</p>
                <p class="text-xs text-gray-400 font-mono">${p.code}</p>
            </div>
            <div class="text-right shrink-0">
                ${stockBadge(p.stock, p.inv_min)}
            </div>
        </div>`).join('');
    dd.classList.remove('hidden');
}

function closeEntryDropdown() {
    document.getElementById('entryDropdown').classList.add('hidden');
}

function selectEntryProduct(id) {
    selectedEntry = allProducts.find(p => p.id === id);
    if (!selectedEntry) return;

    closeEntryDropdown();
    document.getElementById('entrySearch').value = selectedEntry.name;

    /* Card del producto */
    document.getElementById('entryProductName').textContent  = selectedEntry.name;
    document.getElementById('entryProductCode').textContent  = selectedEntry.code;
    document.getElementById('entryProductStock').textContent = selectedEntry.stock;
    document.getElementById('entryProductCard').classList.remove('hidden');

    /* Rellenar precios actuales como placeholder */
    document.getElementById('entryCosto').placeholder = fmt(selectedEntry.cost);
    document.getElementById('entryVenta').placeholder = fmt(selectedEntry.price);

    /* Activar botón */
    const btn = document.getElementById('btnRegisterEntry');
    btn.disabled = false;
    btn.className = `w-full py-3 rounded-xl font-bold text-sm transition-colors
                     bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer`;
    btn.innerHTML = `<i class="fa-solid fa-check mr-2"></i>Registrar entrada`;

    /* Foco en cantidad */
    document.getElementById('entryQty').focus();
    document.getElementById('entryQty').select();
}

/* Llamado desde el botón + verde de la tabla */
function quickEntry(productId) {
    selectEntryProduct(productId);
    document.getElementById('entrySection').classList.remove('hidden');
    document.getElementById('entrySection').scrollIntoView({ behavior: 'smooth' });
    document.getElementById('entryChevron').className = 'fa-solid fa-chevron-up transition-transform';
}

function adjustEntryQty(delta) {
    const input = document.getElementById('entryQty');
    const val   = parseInt(input.value) || 1;
    input.value = Math.max(1, val + delta);
}

function setEntryQty(n) {
    document.getElementById('entryQty').value = n;
}

function togglePrices() {
    showPrices = !showPrices;
    document.getElementById('entryPricesSection').classList.toggle('hidden', !showPrices);
    document.getElementById('priceToggleLabel').textContent = showPrices
        ? 'Ocultar precios'
        : 'Actualizar precios al registrar';
}

async function registerEntry() {
    if (!selectedEntry) return;

    const qty   = parseInt(document.getElementById('entryQty').value) || 0;
    const notes = document.getElementById('entryNotes').value.trim();
    const costo = parseFloat(document.getElementById('entryCosto').value) || 0;
    const venta = parseFloat(document.getElementById('entryVenta').value) || 0;

    if (qty < 1) { toast('La cantidad debe ser mayor a 0', false); return; }

    const btn = document.getElementById('btnRegisterEntry');
    const origHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Registrando…';

    try {
        const body = { quantity: qty, notes: notes || undefined };
        if (showPrices && costo > 0) body.p_costo = costo;
        if (showPrices && venta > 0) body.p_venta = venta;

        const res = await apiFetch(`/products/${selectedEntry.id}/entrada`, {
            method: 'POST',
            body: JSON.stringify(body),
        });

        /* Actualizar estado local */
        const idx = allProducts.findIndex(p => p.id === selectedEntry.id);
        if (idx >= 0) {
            allProducts[idx] = res.data;
            applyFilters();
        }

        toast(`✓ ${res.message} — ${selectedEntry.name}`);
        resetEntryForm();
        loadTodayEntries();

    } catch(e) {
        toast(e.message, false);
        btn.disabled  = false;
        btn.innerHTML = origHTML;
    }
}

function resetEntryForm() {
    selectedEntry = null;
    document.getElementById('entrySearch').value      = '';
    document.getElementById('entryQty').value         = '1';
    document.getElementById('entryNotes').value       = '';
    document.getElementById('entryCosto').value       = '';
    document.getElementById('entryVenta').value       = '';
    document.getElementById('entryProductCard').classList.add('hidden');
    document.getElementById('entryPricesSection').classList.add('hidden');
    showPrices = false;
    document.getElementById('priceToggleLabel').textContent = 'Actualizar precios al registrar';

    const btn = document.getElementById('btnRegisterEntry');
    btn.disabled  = true;
    btn.className = `w-full py-3 rounded-xl font-bold text-sm transition-colors
                     bg-gray-100 text-gray-400 cursor-not-allowed`;
    btn.innerHTML = `<i class="fa-solid fa-plus mr-2"></i>Selecciona un producto primero`;
}

/* Log de entradas de hoy */
async function loadTodayEntries() {
    const log   = document.getElementById('entryLog');
    const today = new Date().toLocaleDateString('sv-SE');
    log.innerHTML = '<p class="text-xs text-gray-400 text-center py-3">Cargando…</p>';

    try {
        const items = await apiFetch(`/inventory-movements?type=entrada&date=${today}`);
        renderEntryLog(items);
    } catch {
        log.innerHTML = '<p class="text-xs text-gray-400 text-center py-3">Sin entradas registradas hoy</p>';
    }
}

function renderEntryLog(items) {
    const log = document.getElementById('entryLog');
    if (!items || !items.length) {
        log.innerHTML = '<p class="text-xs text-gray-400 text-center py-3">Sin entradas hoy</p>';
        return;
    }
    log.innerHTML = items.map(m => `
        <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-100">
            <div class="w-7 h-7 rounded-full bg-emerald-200 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-arrow-down text-emerald-700 text-xs"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-800 truncate">${m.product_name || '—'}</p>
                <p class="text-xs text-gray-400">${m.notes || 'Entrada'}</p>
            </div>
            <div class="text-right shrink-0">
                <p class="text-sm font-black text-emerald-700">+${m.quantity}</p>
                <p class="text-xs text-gray-400">${new Date(m.movement_date || m.created_at).toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'})}</p>
            </div>
        </div>`).join('');
}

/* ═══════════════════════════════════════════════════════════════
   MODAL PRODUCTO (crear / editar)
═══════════════════════════════════════════════════════════════ */
function openProductModal(productId) {
    const modal = document.getElementById('productModal');
    const title = document.getElementById('modalTitle');
    const idInp = document.getElementById('formProductId');

    if (productId) {
        const p = allProducts.find(x => x.id === productId);
        if (!p) return;
        title.textContent             = 'Editar producto';
        idInp.value                   = p.id;
        document.getElementById('fCodigo').value    = p.code;
        document.getElementById('fProducto').value  = p.name;
        document.getElementById('fDpto').value      = p.category || '';
        document.getElementById('fExistencia').value= p.stock;
        document.getElementById('fInvMin').value    = p.inv_min;
        document.getElementById('fInvMax').value    = p.inv_max || 10;
        document.getElementById('fPcosto').value    = p.cost;
        document.getElementById('fPventa').value    = p.price;
        document.getElementById('fPmayoreo').value  = p.wholesale;
    } else {
        title.textContent = 'Nuevo producto';
        idInp.value       = '';
        document.getElementById('productForm').reset();
        document.getElementById('fExistencia').value = 0;
        document.getElementById('fInvMin').value     = 1;
        document.getElementById('fInvMax').value     = 10;
        document.getElementById('fPcosto').value     = 0;
        document.getElementById('fPventa').value     = 0;
        document.getElementById('fPmayoreo').value   = 0;
    }

    modal.classList.replace('hidden', 'flex');
}

function closeProductModal() {
    document.getElementById('productModal').classList.replace('flex', 'hidden');
}

async function submitProductForm(e) {
    e.preventDefault();
    const id  = document.getElementById('formProductId').value;
    const btn = document.getElementById('formSubmitBtn');
    const orig = btn.innerHTML;

    btn.disabled  = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i>Guardando…';

    const body = {
        codigo    : document.getElementById('fCodigo').value.trim(),
        producto  : document.getElementById('fProducto').value.trim(),
        dpto      : document.getElementById('fDpto').value.trim() || null,
        existencia: parseInt(document.getElementById('fExistencia').value)  || 0,
        inv_min   : parseInt(document.getElementById('fInvMin').value)      || 1,
        inv_max   : parseInt(document.getElementById('fInvMax').value)      || 10,
        p_costo   : parseFloat(document.getElementById('fPcosto').value)    || 0,
        p_venta   : parseFloat(document.getElementById('fPventa').value)    || 0,
        p_mayoreo : parseFloat(document.getElementById('fPmayoreo').value)  || 0,
    };

    try {
        let res;
        if (id) {
            res = await apiFetch(`/products/${id}`, {
                method: 'PUT',
                body: JSON.stringify(body),
            });
            const idx = allProducts.findIndex(p => p.id == id);
            if (idx >= 0) allProducts[idx] = res.data;
        } else {
            res = await apiFetch('/products', {
                method: 'POST',
                body: JSON.stringify(body),
            });
            allProducts.push(res.data);
        }

        applyFilters();
        closeProductModal();
        toast(id ? '✓ Producto actualizado' : '✓ Producto creado');

    } catch(err) {
        toast(err.message, false);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = orig;
    }
}

/* ═══════════════════════════════════════════════════════════════
   ELIMINAR
═══════════════════════════════════════════════════════════════ */
async function confirmDelete(id, name) {
    const result = await Swal.fire({
        title            : '¿Eliminar producto?',
        html             : `<span class="text-sm text-gray-500"><b>${name}</b> será eliminado permanentemente.</span>`,
        icon             : 'warning',
        showCancelButton : true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText : 'Cancelar',
        confirmButtonColor: '#dc2626',
        reverseButtons   : true,
    });

    if (!result.isConfirmed) return;

    try {
        await apiFetch(`/products/${id}`, { method: 'DELETE' });
        allProducts = allProducts.filter(p => p.id !== id);
        applyFilters();
        toast('Producto eliminado');
    } catch(e) {
        toast(e.message, false);
    }
}

/* ═══════════════════════════════════════════════════════════════
   LECTOR DE CÓDIGO DE BARRAS (entrada de mercancía)
═══════════════════════════════════════════════════════════════ */
(function initScanner() {
    let buffer = '', lastKey = 0, timer = null;
    const MAX_INTERVAL = 60, DONE = 80;

    document.addEventListener('keydown', (e) => {
        const now      = Date.now();
        const interval = now - lastKey;
        lastKey        = now;

        if (interval > MAX_INTERVAL && buffer) buffer = '';
        if (['Shift','Control','Alt','Meta','CapsLock','Tab','Escape'].includes(e.key)) return;

        const active    = document.activeElement;
        const isInput   = active && ['INPUT','TEXTAREA','SELECT'].includes(active.tagName);
        const isEntry   = active === document.getElementById('entrySearch');
        const isFilter  = active === document.getElementById('prodSearch');

        if (e.key === 'Enter') {
            clearTimeout(timer);
            if (isEntry && buffer.length >= 4) {
                e.preventDefault();
                processScanEntry(buffer);
            }
            buffer = '';
            return;
        }

        if (e.key.length !== 1) return;

        /* Si el foco está en la búsqueda de entrada → scanner escribe ahí directamente */
        if (isEntry || isFilter) { buffer = ''; return; }

        /* Sin foco en inputs → captura global (scanner libre) */
        if (!isInput) {
            buffer += e.key;
            clearTimeout(timer);
            timer = setTimeout(() => {
                if (buffer.length >= 4) {
                    /* Enfocar el campo de entrada y procesar */
                    document.getElementById('entrySearch').value = buffer;
                    processScanEntry(buffer);
                }
                buffer = '';
            }, DONE);
        }
    });

    function processScanEntry(code) {
        code = code.trim();
        const found = allProducts.find(p => p.code.trim() === code);
        if (found) {
            selectEntryProduct(found.id);
            /* Scroll suave a la sección de entrada si está oculta */
            document.getElementById('entrySection').classList.remove('hidden');
            document.getElementById('entryChevron').className = 'fa-solid fa-chevron-up transition-transform';
        } else {
            /* Buscar coincidencia parcial */
            searchEntryProducts(code);
            document.getElementById('entrySearch').focus();
        }
    }
})();

/* ═══════════════════════════════════════════════════════════════
   INIT
═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', async () => {
    await loadProducts();
    loadTodayEntries();

    /* Cerrar dropdown al hacer clic afuera */
    document.addEventListener('click', e => {
        if (!e.target.closest('#entrySearch') && !e.target.closest('#entryDropdown')) {
            closeEntryDropdown();
        }
    });

    /* Cerrar modal al hacer clic en overlay */
    document.getElementById('productModal').addEventListener('click', e => {
        if (e.target === document.getElementById('productModal')) closeProductModal();
    });
});
</script>
</body>
</html>
