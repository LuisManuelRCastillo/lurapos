<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>LuraPos – POS</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    * { box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

    /* Scrollbar delgada */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

    /* Tarjeta de producto */
    .product-card { transition: transform .15s, box-shadow .15s; }
    .product-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.12); }
    .product-card:active { transform: scale(.97); }

    /* Categoría activa */
    .cat-btn { transition: background .15s, color .15s; }
    .cat-btn.active { background: #52525b !important; color: #fff !important; }

    /* Flash de scanner */
    @keyframes scanFlash {
        0%   { box-shadow: 0 0 0 0   rgba(82,82,91,.8); border-color: #52525b; }
        60%  { box-shadow: 0 0 0 10px rgba(82,82,91,0);  border-color: #52525b; }
        100% { box-shadow: 0 0 0 0   rgba(82,82,91,0);   border-color: transparent; }
    }
    .scan-flash { animation: scanFlash .6s ease forwards; }

    /* Clamp de texto en tarjetas */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Ocultar scrollbar en pills mobile */
    .pills-scroll { scrollbar-width: none; }
    .pills-scroll::-webkit-scrollbar { display: none; }

    /* ── Panel de Checkout ── */
    #checkoutPanel {
        transition: transform .3s cubic-bezier(.4,0,.2,1);
        transform: translateX(100%);
    }
    #checkoutPanel.open { transform: translateX(0); }

    /* Botones método de pago */
    .co-pay-btn {
        display: flex; flex-direction: column; align-items: center;
        padding: .65rem .5rem; border-radius: .75rem;
        border: 2px solid #e5e7eb; font-size: .72rem; font-weight: 700;
        cursor: pointer; transition: all .15s; background: white; color: #6b7280;
        gap: .25rem;
    }
    .co-pay-btn:hover  { border-color: #52525b; color: #52525b; background: #fafafa; }
    .co-pay-btn.active { border-color: #52525b; color: #3f3f46;  background: #f4f4f5; }

    /* Botones de monto rápido */
    .co-quick-btn {
        padding: .45rem .7rem; border-radius: .5rem;
        background: #f3f4f6; font-size: .8rem; font-weight: 700;
        cursor: pointer; border: 1.5px solid transparent; transition: all .15s;
    }
    .co-quick-btn:hover  { background: #f4f4f5; border-color: #52525b; color: #3f3f46; }
    .co-quick-btn.exact  { background: #f4f4f5; border-color: #52525b; color: #3f3f46; }
</style>
</head>

<body class="bg-gray-100 overflow-hidden" style="height:100dvh">

<!--
╔══════════════════════════════════════════════════════════════╗
║  LAYOUT DESKTOP (md+):  [Categorías] | [Productos] | [Carrito] ║
║  LAYOUT MOBILE:  header + pills + grid + botón flotante       ║
╚══════════════════════════════════════════════════════════════╝
-->
<div class="flex h-full">

    <!-- ══════════ SIDEBAR IZQUIERDO – Categorías (solo desktop) ══════════ -->
    <aside class="hidden md:flex flex-col w-44 lg:w-52 shrink-0 bg-white border-r border-gray-200">
        <!-- Logo -->
        <div class="p-3 border-b border-gray-100 flex items-center justify-center">
            <img src="{{ asset('/assets/img/logoSF.png') }}" alt="Logo" class="h-10 object-contain">
        </div>
        <!-- Lista de categorías -->
        <div id="categoriesDesktop"
             class="flex-1 overflow-y-auto py-2 px-2 space-y-0.5">
            <!-- JS las inserta aquí -->
        </div>
        <!-- Sucursal -->
        <div id="branchesContainer" class="p-2 border-t border-gray-100 text-xs text-gray-500">
        </div>
    </aside>


    <!-- ══════════ PANEL CENTRAL – Búsqueda + Productos ══════════ -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-3 py-2 flex items-center gap-2 shrink-0">
            <!-- Logo mobile -->
            <img src="{{ asset('/assets/img/logoSF.png') }}" alt="Logo"
                 class="h-8 object-contain md:hidden shrink-0">

            <!-- Buscador -->
            <div class="relative flex-1">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 select-none">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </span>
                <input id="searchInput" type="text"
                       placeholder="Buscar producto o escanear código..."
                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:border-transparent">
            </div>

            <!-- Botón carrito mobile -->
            <button id="cartBtnMobile"
                    class="md:hidden relative bg-zinc-600 text-white p-2.5 rounded-lg shrink-0">
                <i class="fa-solid fa-cart-shopping"></i>
                <span id="cartCountMobile"
                      class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-xs
                             font-bold rounded-full w-5 h-5 flex items-center justify-center hidden">0</span>
            </button>
        </header>

        <!-- Pills de categorías (solo mobile) -->
        <div class="md:hidden pills-scroll flex gap-2 overflow-x-auto px-3 py-2 bg-white border-b border-gray-100 shrink-0">
            <div id="categoriesMobile" class="flex gap-2"></div>
        </div>

        <!-- Grid de productos -->
        <div id="productsContainer"
             class="flex-1 overflow-y-auto p-3
                    grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5
                    gap-3 content-start auto-rows-max">
            <!-- JS los inserta aquí -->
        </div>
    </main>


    <!-- ══════════ PANEL DERECHO – Carrito (solo desktop) ══════════ -->
    <aside class="hidden md:flex flex-col w-72 lg:w-80 shrink-0 bg-white border-l border-gray-200">
        <!-- Encabezado -->
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between shrink-0">
            <h2 class="font-bold text-gray-800">
                <i class="fa-solid fa-cart-shopping mr-1 text-zinc-600"></i> Carrito
            </h2>
            <span id="cartBadge"
                  class="hidden bg-zinc-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">0</span>
        </div>

        <!-- Items -->
        <div id="cartItemsDesktop"
             class="flex-1 overflow-y-auto px-3 py-2 space-y-2">
            <p class="text-gray-400 text-center py-8 text-sm">Carrito vacío</p>
        </div>

        <!-- Resumen y acciones -->
        <div class="shrink-0 border-t border-gray-100 p-4 bg-gray-50 space-y-2">
            <div id="cartSummaryDesktop" class="text-sm text-gray-600"></div>
            <button id="checkoutBtn"
                    class="w-full bg-zinc-600 hover:bg-zinc-700 active:bg-zinc-800
                           text-white font-bold py-3 rounded-xl transition-colors text-base">
                <i class="fa-solid fa-cash-register mr-1"></i> Cobrar
            </button>
            <button id="clearCartBtn"
                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-500 text-sm
                           py-2 rounded-xl transition-colors">
                <i class="fa-solid fa-trash-can mr-1"></i> Vaciar carrito
            </button>
        </div>
    </aside>
</div>


<!-- ══════════ MODAL CARRITO MOBILE ══════════ -->
<div id="cartModalMobile"
     class="fixed inset-0 bg-black/50 z-40 hidden items-end md:hidden">
    <div class="bg-white w-full rounded-t-2xl max-h-[80dvh] flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
            <h2 class="font-bold text-lg">
                <i class="fa-solid fa-cart-shopping mr-1 text-zinc-600"></i> Carrito
            </h2>
            <button id="closeCartMobile" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <div id="cartItemsMobile" class="flex-1 overflow-y-auto px-4 py-3 space-y-2"></div>
        <div class="shrink-0 px-4 pb-6 pt-3 border-t border-gray-100 space-y-2">
            <div id="cartSummaryMobile" class="text-base font-bold text-gray-800 mb-2"></div>
            <button id="checkoutMobileBtn"
                    class="w-full bg-zinc-600 hover:bg-zinc-700 text-white font-bold py-3 rounded-xl">
                <i class="fa-solid fa-cash-register mr-1"></i> Cobrar
            </button>
        </div>
    </div>
</div>


<!-- ══════════ OVERLAY + PANEL CHECKOUT ══════════ -->
<div id="checkoutOverlay"
     class="fixed inset-0 bg-black/50 z-40 hidden"
     onclick="closeCheckout()"></div>

<div id="checkoutPanel"
     class="fixed inset-y-0 right-0 z-50 w-full md:w-[420px] bg-white shadow-2xl flex flex-col">

    <!-- Cabecera naranja con total -->
    <div class="shrink-0 bg-zinc-600 text-white px-5 pt-5 pb-4">
        <div class="flex items-start justify-between mb-3">
            <div>
                <p class="text-sm font-medium opacity-80">
                    <i class="fa-solid fa-cash-register mr-1"></i> Cobrar
                </p>
                <p id="coTotal" class="text-4xl font-black tracking-tight">$0.00</p>
            </div>
            <button onclick="closeCheckout()"
                    class="w-9 h-9 rounded-full bg-white/20 hover:bg-white/30
                           text-xl font-bold flex items-center justify-center shrink-0 mt-1">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        <!-- Mini-resumen de artículos -->
        <p id="coSubtitle" class="text-xs opacity-70"></p>
    </div>

    <!-- Lista de items del carrito -->
    <div id="coItems"
         class="flex-1 overflow-y-auto px-4 py-3 space-y-1 min-h-0"></div>

    <!-- Método de pago -->
    <div class="shrink-0 px-4 py-3 border-t border-gray-100">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">
            Método de pago
        </p>
        <div class="grid grid-cols-3 gap-2">
            <button class="co-pay-btn active" data-method="efectivo" onclick="selectPayMethod(this)">
                <i class="fa-solid fa-money-bill-wave text-lg"></i> Efectivo
            </button>
            <button class="co-pay-btn" data-method="tarjeta" onclick="selectPayMethod(this)">
                <i class="fa-solid fa-credit-card text-lg"></i> Tarjeta
            </button>
            <button class="co-pay-btn" data-method="transferencia" onclick="selectPayMethod(this)">
                <i class="fa-solid fa-mobile-screen text-lg"></i> Transfer
            </button>
        </div>
    </div>

    <!-- Sección efectivo: montos rápidos + input + cambio -->
    <div id="coCashSection" class="shrink-0 px-4 pb-3 border-t border-gray-100">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mt-3 mb-2">
            Monto recibido
        </p>
        <div id="coQuickAmounts" class="flex flex-wrap gap-1.5 mb-3"></div>
        <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-lg">$</span>
            <input id="coAmount" type="number" min="0" step="0.01"
                   class="w-full pl-9 pr-4 py-3 border-2 border-gray-300 rounded-xl
                          text-2xl font-black focus:outline-none focus:border-zinc-400
                          text-center transition-colors"
                   placeholder="0.00"
                   oninput="updateChange()">
        </div>
        <div id="coChange" class="hidden mt-3 py-2 px-4 rounded-xl bg-gray-50 text-center">
            <span class="text-xs text-gray-500 block">Cambio</span>
            <span id="coChangeAmt" class="text-2xl font-black text-zinc-600">$0.00</span>
        </div>
    </div>

    <!-- Botón confirmar -->
    <div class="shrink-0 px-4 pb-6 pt-2">
        <button id="coConfirmBtn" onclick="confirmSale()"
                class="w-full bg-zinc-600 hover:bg-zinc-700 active:bg-zinc-800
                       text-white font-black text-lg py-4 rounded-2xl transition-colors
                       flex items-center justify-center gap-2">
            <i class="fa-solid fa-check"></i> CONFIRMAR VENTA
        </button>
        <p class="text-center text-xs text-gray-400 mt-2">
            <i class="fa-solid fa-keyboard mr-1"></i> Enter para confirmar
        </p>
    </div>
</div>

<!-- ══════════ INDICADOR DE SCANNER ══════════ -->
<div id="scannerIndicator"
     class="hidden fixed top-4 left-1/2 -translate-x-1/2 z-50
            bg-gray-900 text-white text-sm font-mono px-5 py-2.5
            rounded-full shadow-2xl items-center gap-3 pointer-events-none">
    <i class="fa-solid fa-barcode text-zinc-400 text-sm shrink-0"></i>
    <span id="scannerText">Escaneando…</span>
</div>


<script>
/* ═══════════════════════════════════════════════════════════
   ESTADO GLOBAL
═══════════════════════════════════════════════════════════ */
const API_URL  = "/api/pos";
const CSRF     = document.querySelector('meta[name="csrf-token"]').content;

let products         = [];
let categories       = [];
let cart             = JSON.parse(localStorage.getItem('cart') || '[]');
let selectedCategory = 'Todas';
let searchTerm       = '';

/* ═══════════════════════════════════════════════════════════
   API HELPER
═══════════════════════════════════════════════════════════ */
async function api(endpoint, opts = {}) {
    const res = await fetch(API_URL + endpoint, {
        ...opts,
        headers: {
            'Content-Type' : 'application/json',
            'X-CSRF-TOKEN' : CSRF,
            'Accept'       : 'application/json',
            ...(opts.headers || {})
        }
    });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
}

/* ═══════════════════════════════════════════════════════════
   CARGA DE DATOS
═══════════════════════════════════════════════════════════ */
async function loadBranches() {
    try {
        const data = await api('/branches');
        renderBranches(data);
    } catch(e) { console.warn('Branches:', e); }
}

async function loadCategories() {
    try {
        const data = await api('/categories');
        categories = ['Todas', ...data];
        renderCategories();
    } catch(e) { console.warn('Categories:', e); }
}

async function loadProducts() {
    const params = new URLSearchParams();
    if (searchTerm)                    params.append('search',   searchTerm);
    if (selectedCategory !== 'Todas') params.append('category', selectedCategory);
    try {
        products = await api(`/products?${params}`);
        renderProducts();
    } catch(e) { console.warn('Products:', e); }
}

/* ═══════════════════════════════════════════════════════════
   RENDER SUCURSALES
═══════════════════════════════════════════════════════════ */
function renderBranches(branches) {
    const c = document.getElementById('branchesContainer');
    if (!c) return;
    c.innerHTML = '';
    branches.forEach(b => {
        const btn = document.createElement('button');
        btn.textContent = b.name;
        btn.className = 'block w-full text-left px-2 py-1.5 rounded hover:bg-zinc-50 text-xs text-gray-600 font-medium';
        btn.onclick = () => {
            localStorage.setItem('selectedBranchId',   b.id);
            localStorage.setItem('selectedBranchName', b.name);
            c.querySelectorAll('button').forEach(x => x.classList.remove('bg-zinc-100','text-zinc-700'));
            btn.classList.add('bg-zinc-100','text-zinc-700');
        };
        c.appendChild(btn);
    });
}

/* ═══════════════════════════════════════════════════════════
   RENDER CATEGORÍAS  (sidebar desktop + pills mobile)
═══════════════════════════════════════════════════════════ */
function renderCategories() {
    // Desktop
    const desk = document.getElementById('categoriesDesktop');
    desk.innerHTML = '';
    categories.forEach(cat => {
        const btn = document.createElement('button');
        btn.textContent = cat;
        btn.className = `cat-btn w-full text-left px-3 py-2 rounded-lg text-sm font-medium
                         hover:bg-zinc-50 hover:text-zinc-700 text-gray-700
                         ${selectedCategory === cat ? 'active' : ''}`;
        btn.onclick = () => { selectedCategory = cat; loadProducts(); renderCategories(); };
        desk.appendChild(btn);
    });

    // Mobile
    const mob = document.getElementById('categoriesMobile');
    mob.innerHTML = '';
    categories.forEach(cat => {
        const btn = document.createElement('button');
        btn.textContent = cat;
        btn.className = `cat-btn shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold
                         bg-gray-100 text-gray-700 hover:bg-zinc-100
                         ${selectedCategory === cat ? 'active' : ''}`;
        btn.onclick = () => { selectedCategory = cat; loadProducts(); renderCategories(); };
        mob.appendChild(btn);
    });
}

/* ═══════════════════════════════════════════════════════════
   RENDER PRODUCTOS
═══════════════════════════════════════════════════════════ */
function renderProducts() {
    const c = document.getElementById('productsContainer');
    c.innerHTML = '';

    if (!products.length) {
        c.innerHTML = `
            <div class="col-span-full flex flex-col items-center justify-center py-20 text-gray-400">
                <i class="fa-solid fa-magnifying-glass text-6xl mb-4"></i>
                <p class="text-lg font-semibold">Sin resultados</p>
                <p class="text-sm mt-1">Intenta con otro término o categoría</p>
            </div>`;
        return;
    }

    products.forEach(p => {
        const div = document.createElement('div');
        div.dataset.pid = p.id;
        div.className = 'product-card bg-white rounded-xl shadow-sm border-2 border-transparent p-3 cursor-pointer select-none';

        const stockClass = p.stock <= 5 ? 'bg-red-100 text-red-700' : 'bg-zinc-100 text-zinc-700';
        const imgHtml = p.image
            ? `<img src="/storage/${p.image}"
                    class="w-full h-20 object-contain mb-2 rounded-lg bg-gray-50"
                    onerror="this.replaceWith(iconFallback())">`
            : `<div class="w-full h-16 flex items-center justify-center mb-2 text-gray-300">
                   <i class="fa-solid fa-screwdriver-wrench text-4xl"></i>
               </div>`;

        div.innerHTML = `
            ${imgHtml}
            <p class="text-xs text-gray-400 truncate leading-none mb-0.5">${p.code}</p>
            <h3 class="font-semibold text-xs leading-snug line-clamp-2 text-gray-800 mb-2"
                style="min-height:2.6em">${p.name}</h3>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-zinc-600">$${Number(p.price).toFixed(2)}</span>
                <span class="text-xs px-1.5 py-0.5 rounded-full font-medium ${stockClass}">${p.stock}</span>
            </div>`;

        div.onclick = () => addToCart(p);
        c.appendChild(div);
    });
}

function iconFallback() {
    const d = document.createElement('div');
    d.className = 'w-full h-16 flex items-center justify-center mb-2 text-gray-300';
    d.innerHTML = '<i class="fa-solid fa-screwdriver-wrench text-4xl"></i>';
    return d;
}

/* ═══════════════════════════════════════════════════════════
   CARRITO
═══════════════════════════════════════════════════════════ */
function addToCart(product) {
    const ex = cart.find(i => i.id === product.id);
    if (ex) ex.quantity++;
    else cart.push({ ...product, quantity: 1 });
    saveCart();
    renderCart();
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

function makeCartRow(item) {
    const total = Number(item.price) * item.quantity;
    const div   = document.createElement('div');
    div.className = 'flex items-center gap-2 bg-gray-50 rounded-lg p-2';
    div.innerHTML = `
        <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold truncate text-gray-800">${item.name}</p>
            <p class="text-xs text-gray-400">$${Number(item.price).toFixed(2)} c/u</p>
        </div>
        <div class="flex items-center gap-1 shrink-0">
            <button class="btn-minus w-6 h-6 rounded-md bg-gray-200 hover:bg-red-100
                           text-sm font-bold flex items-center justify-center leading-none">
                    <i class="fa-solid fa-minus text-xs"></i>
                </button>
            <span class="w-5 text-center text-sm font-bold">${item.quantity}</span>
            <button class="btn-plus w-6 h-6 rounded-md bg-zinc-100 hover:bg-zinc-200
                           text-sm font-bold text-zinc-700 flex items-center justify-center leading-none">
                    <i class="fa-solid fa-plus text-xs"></i>
                </button>
        </div>
        <span class="text-xs font-bold text-gray-700 w-14 text-right shrink-0">$${total.toFixed(2)}</span>`;

    div.querySelector('.btn-minus').onclick = () => {
        if (item.quantity > 1) item.quantity--;
        else cart = cart.filter(i => i.id !== item.id);
        saveCart(); renderCart();
    };
    div.querySelector('.btn-plus').onclick = () => {
        item.quantity++;
        saveCart(); renderCart();
    };
    return div;
}

function renderCart() {
    const total = cart.reduce((s, i) => s + Number(i.price) * i.quantity, 0);
    const count = cart.reduce((s, i) => s + i.quantity, 0);

    // ── Desktop panel ──
    const desk    = document.getElementById('cartItemsDesktop');
    const deskSum = document.getElementById('cartSummaryDesktop');
    const badge   = document.getElementById('cartBadge');

    desk.innerHTML = '';
    if (!cart.length) {
        desk.innerHTML = `
            <div class="flex flex-col items-center justify-center py-8 text-gray-300">
                <i class="fa-solid fa-cart-shopping text-4xl mb-2"></i>
                <p class="text-sm text-gray-400">Carrito vacío</p>
            </div>`;
        deskSum.innerHTML = '';
        badge.classList.add('hidden');
    } else {
        cart.forEach(item => desk.appendChild(makeCartRow(item)));
        deskSum.innerHTML = `
            <div class="flex justify-between text-xs mb-1 text-gray-500">
                <span>Artículos</span><span>${count}</span>
            </div>
            <div class="flex justify-between text-base font-bold text-zinc-700">
                <span>Total</span><span>$${total.toFixed(2)}</span>
            </div>`;
        badge.textContent = count;
        badge.classList.remove('hidden');
    }

    // ── Mobile modal (si está abierto, re-render) ──
    const mob    = document.getElementById('cartItemsMobile');
    const mobSum = document.getElementById('cartSummaryMobile');
    mob.innerHTML = '';
    cart.forEach(item => mob.appendChild(makeCartRow(item)));
    mobSum.innerHTML = count
        ? `<span class="text-gray-500 font-normal mr-2">${count} art.</span>Total: $${total.toFixed(2)}`
        : 'Carrito vacío';

    // ── Badge mobile ──
    const countEl = document.getElementById('cartCountMobile');
    if (count > 0) {
        countEl.textContent = count;
        countEl.classList.remove('hidden');
    } else {
        countEl.classList.add('hidden');
    }
}

/* ═══════════════════════════════════════════════════════════
   EVENTOS UI
═══════════════════════════════════════════════════════════ */
// Buscador
document.getElementById('searchInput').oninput = e => {
    clearTimeout(window._searchTimer);
    searchTerm = e.target.value.trim();
    window._searchTimer = setTimeout(loadProducts, 350);
};

// Carrito mobile
const cartModalMobile = document.getElementById('cartModalMobile');
document.getElementById('cartBtnMobile').onclick = () => {
    renderCart();
    cartModalMobile.classList.replace('hidden', 'flex');
};
document.getElementById('closeCartMobile').onclick = () => {
    cartModalMobile.classList.replace('flex', 'hidden');
};
cartModalMobile.addEventListener('click', e => {
    if (e.target === cartModalMobile) cartModalMobile.classList.replace('flex', 'hidden');
});

// ══════════════════════════════════════════════════════
// CHECKOUT PANEL
// ══════════════════════════════════════════════════════
let coPayMethod = 'efectivo';

function openCheckout() {
    if (!cart.length) {
        Swal.fire({ icon:'warning', title:'Carrito vacío', timer:1400,
                    showConfirmButton:false, toast:true, position:'top' });
        return;
    }

    const total = cart.reduce((s, i) => s + Number(i.price) * i.quantity, 0);
    const count = cart.reduce((s, i) => s + i.quantity, 0);

    // Encabezado
    document.getElementById('coTotal').textContent    = '$' + total.toFixed(2);
    document.getElementById('coSubtitle').textContent = count + ' artículo' + (count !== 1 ? 's' : '');

    // Items
    const coItems = document.getElementById('coItems');
    coItems.innerHTML = '';
    cart.forEach(item => {
        const d = document.createElement('div');
        d.className = 'flex items-center justify-between py-2 border-b border-gray-50 last:border-0';
        d.innerHTML = `
            <div class="flex-1 min-w-0 mr-3">
                <p class="text-sm font-semibold truncate text-gray-800">${item.name}</p>
                <p class="text-xs text-gray-400">${item.quantity} × $${Number(item.price).toFixed(2)}</p>
            </div>
            <span class="text-sm font-bold shrink-0 text-gray-700">
                $${(Number(item.price) * item.quantity).toFixed(2)}
            </span>`;
        coItems.appendChild(d);
    });

    // Montos rápidos según total
    generateQuickAmounts(total);

    // Reset campos
    document.getElementById('coAmount').value = '';
    document.getElementById('coAmount').classList.remove('border-red-400');
    document.getElementById('coChange').classList.add('hidden');

    // Método efectivo por defecto
    const defaultBtn = document.querySelector('.co-pay-btn[data-method="efectivo"]');
    selectPayMethod(defaultBtn);

    // Abrir
    document.getElementById('checkoutOverlay').classList.remove('hidden');
    document.getElementById('checkoutPanel').classList.add('open');
    cartModalMobile.classList.replace('flex', 'hidden');

    setTimeout(() => {
        if (coPayMethod === 'efectivo')
            document.getElementById('coAmount').focus();
    }, 320);
}

function closeCheckout() {
    document.getElementById('checkoutPanel').classList.remove('open');
    document.getElementById('checkoutOverlay').classList.add('hidden');
}

function selectPayMethod(btn) {
    document.querySelectorAll('.co-pay-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    coPayMethod = btn.dataset.method;
    const cashSection = document.getElementById('coCashSection');
    if (coPayMethod === 'efectivo') {
        cashSection.classList.remove('hidden');
        setTimeout(() => document.getElementById('coAmount').focus(), 50);
    } else {
        cashSection.classList.add('hidden');
    }
}

function generateQuickAmounts(total) {
    const c = document.getElementById('coQuickAmounts');
    c.innerHTML = '';

    // Botón "Exacto"
    const exact = document.createElement('button');
    exact.className = 'co-quick-btn exact';
    exact.innerHTML = '<i class="fa-solid fa-equals mr-1"></i>Exacto';
    exact.onclick = () => { document.getElementById('coAmount').value = total.toFixed(2); updateChange(); };
    c.appendChild(exact);

    // Billetes redondos por encima del total
    [50, 100, 200, 500, 1000, 2000].filter(a => a >= total).slice(0, 5).forEach(a => {
        const btn = document.createElement('button');
        btn.className = 'co-quick-btn';
        btn.textContent = '$' + a;
        btn.onclick = () => { document.getElementById('coAmount').value = a; updateChange(); };
        c.appendChild(btn);
    });

    // Si el total supera $2000, agregar siguiente múltiplo de $500
    if (total > 2000) {
        const next = Math.ceil(total / 500) * 500;
        const btn = document.createElement('button');
        btn.className = 'co-quick-btn';
        btn.textContent = '$' + next;
        btn.onclick = () => { document.getElementById('coAmount').value = next; updateChange(); };
        c.appendChild(btn);
    }
}

function updateChange() {
    const total  = cart.reduce((s, i) => s + Number(i.price) * i.quantity, 0);
    const amount = parseFloat(document.getElementById('coAmount').value) || 0;
    const changeBox = document.getElementById('coChange');
    const changeAmt = document.getElementById('coChangeAmt');

    if (amount <= 0) { changeBox.classList.add('hidden'); return; }

    const change = amount - total;
    changeBox.classList.remove('hidden');

    if (change >= 0) {
        changeAmt.textContent = '$' + change.toFixed(2);
        changeAmt.className = 'text-2xl font-black text-zinc-600';
        document.getElementById('coAmount').classList.remove('border-red-400');
    } else {
        changeAmt.textContent = '−$' + Math.abs(change).toFixed(2);
        changeAmt.className = 'text-2xl font-black text-red-500';
        document.getElementById('coAmount').classList.add('border-red-400');
    }
}

async function confirmSale() {
    const total = cart.reduce((s, i) => s + Number(i.price) * i.quantity, 0);

    // Validar monto si es efectivo
    if (coPayMethod === 'efectivo') {
        const amount = parseFloat(document.getElementById('coAmount').value) || 0;
        if (amount < total) {
            document.getElementById('coAmount').classList.add('border-red-400');
            document.getElementById('coAmount').focus();
            document.getElementById('coAmount').animate(
                [{ transform:'translateX(-6px)' },{ transform:'translateX(6px)' },{ transform:'translateX(0)' }],
                { duration:250, iterations:2 }
            );
            return;
        }
    }

    const btn = document.getElementById('coConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Procesando…';

    try {
        const amountPaid = coPayMethod === 'efectivo'
            ? parseFloat(document.getElementById('coAmount').value)
            : total;

        const res = await fetch(`${API_URL}/sales`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
            body: JSON.stringify({
                customer_name  : 'Cliente',
                customer_email : null,
                payment_method : coPayMethod,
                amount_paid    : amountPaid,
                change_amount  : Math.max(0, amountPaid - total),
                subtotal       : total,
                discount       : 0,
                total,
                branch_id      : localStorage.getItem('selectedBranchId') || null,
                items: cart.map(i => ({
                    product_id : i.id,
                    quantity   : i.quantity,
                    unit_price : i.price,
                    total      : i.price * i.quantity
                }))
            })
        });

        let result;
        try { result = await res.json(); } catch(e) { throw new Error('Respuesta inválida del servidor'); }
        if (!res.ok || !result.success) throw new Error(result?.message || 'Error al procesar venta');

        // ── Éxito ──
        const change = Math.max(0, amountPaid - total);

        // Guardar datos del ticket en localStorage para la ventana de impresión
        localStorage.setItem('lastReceipt', JSON.stringify({
            invoice_number : result.data.invoice_number,
            date           : new Date().toISOString(),
            customer_name  : result.data.customer_name || 'Cliente',
            branch         : localStorage.getItem('selectedBranchName') || 'Sucursal Principal',
            items          : cart.map(i => ({
                name     : i.name,
                code     : i.code || '',
                quantity : i.quantity,
                price    : i.price,
            })),
            subtotal       : total,
            discount       : 0,
            total          : total,
            payment_method : coPayMethod,
            amount_paid    : amountPaid,
            change         : change,
        }));

        // Abrir ventana de impresión (popup pequeño)
        window.open(
            '{{ route("pos.receipt") }}',
            'ticket_print',
            'width=360,height=620,toolbar=0,menubar=0,location=0,status=0,scrollbars=1'
        );

        closeCheckout();
        cart = []; saveCart(); renderCart();

        Swal.fire({
            icon: 'success',
            title: '¡Venta registrada!',
            html: `Total cobrado: <b>$${total.toFixed(2)}</b>` +
                  (coPayMethod === 'efectivo' && change > 0
                      ? `<br>Cambio: <b class="text-zinc-600">$${change.toFixed(2)}</b>` : ''),
            timer: 3000,
            showConfirmButton: false,
        });

    } catch(e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check mr-2"></i> CONFIRMAR VENTA';
    }
}

// Abrir checkout desde los botones
document.getElementById('checkoutBtn').onclick       = openCheckout;
document.getElementById('checkoutMobileBtn').onclick = openCheckout;

// Vaciar carrito
document.getElementById('clearCartBtn').onclick = () => {
    if (!cart.length) return;
    Swal.fire({
        title: '¿Vaciar carrito?', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#dc2626',
        cancelButtonText: 'Cancelar', confirmButtonText: 'Sí, vaciar'
    }).then(r => {
        if (r.isConfirmed) { cart = []; saveCart(); renderCart(); }
    });
};

/* ═══════════════════════════════════════════════════════════
   INIT
═══════════════════════════════════════════════════════════ */
loadBranches();
loadCategories();
loadProducts();
renderCart();

/* ═══════════════════════════════════════════════════════════
   ENTER → confirmar venta cuando el panel está abierto
═══════════════════════════════════════════════════════════ */
document.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    const panel = document.getElementById('checkoutPanel');
    if (!panel.classList.contains('open')) return;

    // Si el foco está en el input de monto, solo confirmar si hay monto suficiente
    if (document.activeElement?.id === 'coAmount') {
        const total  = cart.reduce((s, i) => s + Number(i.price) * i.quantity, 0);
        const amount = parseFloat(document.getElementById('coAmount').value) || 0;
        if (amount < total) return; // dejar que el usuario siga escribiendo
    }
    e.preventDefault();
    confirmSale();
});

/* ═══════════════════════════════════════════════════════════
   LECTOR DE CÓDIGO DE BARRAS
   ─ Scanner USB/BT: teclas < 60ms entre sí + Enter al final
   ─ Tipeo manual: teclas más lentas → buffer se descarta
   ─ Si el buscador tiene foco → scanner escribe en él
     y el debounce dispara la búsqueda normal
═══════════════════════════════════════════════════════════ */
(function initBarcodeScanner() {
    const MIN_LENGTH   = 4;    // caracteres mínimos para considerar código
    const MAX_INTERVAL = 60;   // ms máximos entre teclas del scanner
    const DONE_TIMEOUT = 80;   // ms sin tecla → escaneo completo

    let buffer      = '';
    let lastKeyTime = 0;
    let doneTimer   = null;

    const indicator = document.getElementById('scannerIndicator');
    const scanText  = document.getElementById('scannerText');

    function showIndicator(msg) {
        scanText.textContent = msg;
        indicator.classList.remove('hidden');
        indicator.classList.add('flex');
        clearTimeout(indicator._t);
        indicator._t = setTimeout(() => {
            indicator.classList.add('hidden');
            indicator.classList.remove('flex');
        }, 2500);
    }

    async function processScan(code) {
        code = code.trim();
        if (code.length < MIN_LENGTH) return;

        showIndicator('Buscando: ' + code);

        // 1) Buscar en la lista ya cargada (coincidencia exacta)
        let found = products.find(p => String(p.code) === code);

        // 2) Si no está → consultar API
        if (!found) {
            try {
                const data = await api('/products?search=' + encodeURIComponent(code));
                found = data.find(p => String(p.code) === code);
            } catch (e) { /* silencioso */ }
        }

        if (found) {
            addToCart(found);   // ← agrega automáticamente
            showIndicator('Agregado: ' + found.name.substring(0, 28));

            // Flash visual en la tarjeta (si está en el grid)
            const card = document.querySelector(`[data-pid="${found.id}"]`);
            if (card) {
                card.classList.add('scan-flash', 'border-zinc-600');
                setTimeout(() => card.classList.remove('scan-flash', 'border-zinc-600'), 700);
            }
        } else {
            showIndicator('No encontrado: ' + code);
            Swal.fire({
                icon: 'warning', title: 'Producto no encontrado',
                text: 'Código: ' + code,
                timer: 2000, showConfirmButton: false,
                toast: true, position: 'top-end'
            });
        }
    }

    document.addEventListener('keydown', e => {
        // No interferir cuando el checkout está abierto
        if (document.getElementById('checkoutPanel')?.classList.contains('open')) return;

        const now      = Date.now();
        const interval = now - lastKeyTime;
        lastKeyTime    = now;

        // Resetear buffer si la tecla llegó muy tarde (tipeo manual)
        if (interval > MAX_INTERVAL && buffer.length > 0) buffer = '';

        // Ignorar teclas modificadoras
        if (['Shift','Control','Alt','Meta','CapsLock','Tab','Escape'].includes(e.key)) return;

        if (e.key === 'Enter') {
            clearTimeout(doneTimer);
            const active    = document.activeElement;
            const inField   = active &&
                              ['INPUT','TEXTAREA','SELECT'].includes(active.tagName) &&
                              active.id !== 'searchInput';
            if (!inField && buffer.length >= MIN_LENGTH) {
                e.preventDefault();
                processScan(buffer);
            }
            buffer = '';
            return;
        }

        if (e.key.length !== 1) return;

        // Si el buscador está activo → el scanner escribe ahí (debounce dispara loadProducts)
        if (document.activeElement?.id === 'searchInput') {
            buffer = '';
            return;
        }

        buffer += e.key;
        clearTimeout(doneTimer);
        doneTimer = setTimeout(() => {
            if (buffer.length >= MIN_LENGTH) processScan(buffer);
            buffer = '';
        }, DONE_TIMEOUT);
    });
})();
</script>
</body>
</html>
