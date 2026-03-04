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
            <!-- Acciones secundarias: solo iconos + tooltip arriba -->
            <div class="flex items-center justify-around pt-1 border-t border-gray-100">

                <div class="relative group">
                    <button id="reprintBtn"
                            class="w-9 h-9 flex items-center justify-center rounded-lg
                                   text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <i class="fa-solid fa-print text-sm"></i>
                    </button>
                    <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2
                                 bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap
                                 opacity-0 group-hover:opacity-100 transition-opacity z-50">
                        Reimprimir último ticket
                    </span>
                </div>

                <div class="relative group">
                    <button id="cashMovementBtn"
                            class="w-9 h-9 flex items-center justify-center rounded-lg
                                   text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <i class="fa-solid fa-money-bill-transfer text-sm"></i>
                    </button>
                    <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2
                                 bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap
                                 opacity-0 group-hover:opacity-100 transition-opacity z-50">
                        Movimiento de efectivo
                    </span>
                </div>

                <div class="relative group">
                    <button id="creditsBtn"
                            class="w-9 h-9 flex items-center justify-center rounded-lg
                                   text-gray-400 hover:text-red-500 hover:bg-gray-100 transition-colors relative">
                        <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
                        <span id="creditsBadge"
                              class="hidden absolute -top-0.5 -right-0.5 bg-red-500 text-white
                                     text-xs font-bold rounded-full w-4 h-4
                                     flex items-center justify-center leading-none"></span>
                    </button>
                    <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2
                                 bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap
                                 opacity-0 group-hover:opacity-100 transition-opacity z-50">
                        Cobros pendientes
                    </span>
                </div>

                <div class="relative group">
                    <button id="historyBtn"
                            class="w-9 h-9 flex items-center justify-center rounded-lg
                                   text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                    </button>
                    <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2
                                 bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap
                                 opacity-0 group-hover:opacity-100 transition-opacity z-50">
                        Historial de ventas
                    </span>
                </div>

                <div class="relative group">
                    <a href="/inventario" target="_blank"
                       class="w-9 h-9 flex items-center justify-center rounded-lg
                              text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors">
                        <i class="fa-solid fa-boxes-stacked text-sm"></i>
                    </a>
                    <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2
                                 bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap
                                 opacity-0 group-hover:opacity-100 transition-opacity z-50">
                        Gestión de inventario
                    </span>
                </div>

            </div>
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
            <!-- Acciones secundarias mobile: icono + etiqueta pequeña -->
            <div class="flex items-center justify-around border-t border-gray-100 pt-2">

                <button id="reprintBtnMobile"
                        class="flex flex-col items-center gap-0.5 text-gray-400 hover:text-gray-600
                               w-14 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fa-solid fa-print text-base"></i>
                    <span class="text-xs leading-none" style="font-size:9px">Reimprimir</span>
                </button>

                <button id="cashMovementBtnMobile"
                        class="flex flex-col items-center gap-0.5 text-gray-400 hover:text-gray-600
                               w-14 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fa-solid fa-money-bill-transfer text-base"></i>
                    <span class="text-xs leading-none" style="font-size:9px">Efectivo</span>
                </button>

                <button id="creditsBtnMobile"
                        class="flex flex-col items-center gap-0.5 text-gray-400 hover:text-red-500
                               w-14 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fa-solid fa-file-invoice-dollar text-base"></i>
                    <span class="text-xs leading-none" style="font-size:9px">Cobros</span>
                </button>

                <button id="historyBtnMobile"
                        class="flex flex-col items-center gap-0.5 text-gray-400 hover:text-gray-600
                               w-14 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fa-solid fa-clock-rotate-left text-base"></i>
                    <span class="text-xs leading-none" style="font-size:9px">Historial</span>
                </button>

                <a href="/inventario" target="_blank"
                   class="flex flex-col items-center gap-0.5 text-gray-400 hover:text-emerald-600
                          w-14 py-1.5 rounded-lg hover:bg-emerald-50 transition-colors">
                    <i class="fa-solid fa-boxes-stacked text-base"></i>
                    <span class="text-xs leading-none" style="font-size:9px">Inventario</span>
                </a>

            </div>
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

    <!-- ── Sección cliente ── -->
    <div class="shrink-0 border-b border-gray-100">

        <!-- Barra compacta (siempre visible) -->
        <button id="coCustomerBar"
                class="w-full flex items-center gap-2 px-4 py-2.5 hover:bg-gray-50 transition-colors text-left"
                onclick="toggleCustomerSearch()">
            <i class="fa-solid fa-user text-gray-300 text-xs shrink-0"></i>
            <span id="coCustomerName" class="text-sm text-gray-400 flex-1 truncate">
                Cliente (opcional)
            </span>
            <i id="coCustomerChevron"
               class="fa-solid fa-chevron-down text-gray-300 text-xs transition-transform duration-200 shrink-0"></i>
        </button>

        <!-- Panel expandible -->
        <div id="coCustomerPanel" class="hidden px-4 pb-3 space-y-2">

            <!-- Búsqueda -->
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs pointer-events-none"></i>
                <input id="coCustomerSearch" type="text"
                       placeholder="Nombre o teléfono…"
                       class="w-full pl-8 pr-3 py-2 border border-gray-200 rounded-lg text-sm
                              focus:outline-none focus:border-zinc-400"
                       oninput="debouncedCustomerSearch(this.value)"
                       autocomplete="off">
            </div>

            <!-- Resultados de búsqueda -->
            <div id="coCustomerResults" class="space-y-0.5 max-h-28 overflow-y-auto"></div>

            <!-- Botón nuevo cliente -->
            <button onclick="toggleNewCustomerForm()"
                    class="w-full text-left text-xs text-zinc-500 hover:text-zinc-700
                           flex items-center gap-1.5 py-1 transition-colors">
                <i class="fa-solid fa-user-plus"></i>
                <span id="coNewToggleLabel">Nuevo cliente</span>
            </button>

            <!-- Formulario nuevo cliente (oculto por defecto) -->
            <div id="coNewCustomerForm" class="hidden space-y-2 pt-1 border-t border-gray-100">
                <input id="coNewName" type="text" placeholder="Nombre *"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm
                              focus:outline-none focus:border-zinc-400">
                <input id="coNewPhone" type="tel" placeholder="Teléfono"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm
                              focus:outline-none focus:border-zinc-400">
                <button onclick="createCustomer()"
                        class="w-full bg-zinc-600 hover:bg-zinc-700 text-white text-sm
                               font-semibold py-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar cliente
                </button>
            </div>
        </div>
    </div>

    <!-- Lista de items del carrito -->
    <div id="coItems"
         class="flex-1 overflow-y-auto px-4 py-3 space-y-1 min-h-0"></div>

    <!-- Método de pago -->
    <div class="shrink-0 px-4 py-3 border-t border-gray-100">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">
            Método de pago
        </p>
        <div class="grid grid-cols-4 gap-1.5">
            <button class="co-pay-btn active" data-method="efectivo" onclick="selectPayMethod(this)">
                <i class="fa-solid fa-money-bill-wave text-lg"></i> Efectivo
            </button>
            <button class="co-pay-btn" data-method="tarjeta" onclick="selectPayMethod(this)">
                <i class="fa-solid fa-credit-card text-lg"></i> Tarjeta
            </button>
            <button class="co-pay-btn" data-method="transferencia" onclick="selectPayMethod(this)">
                <i class="fa-solid fa-mobile-screen text-lg"></i> Transfer
            </button>
            <button class="co-pay-btn" data-method="credito" onclick="selectPayMethod(this)">
                <i class="fa-solid fa-handshake text-lg"></i> Crédito
            </button>
        </div>
    </div>

    <!-- Aviso cuando el método es crédito -->
    <div id="coCreditSection" class="hidden shrink-0 px-4 pb-3 border-t border-gray-100">
        <div class="mt-3 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            <p class="font-semibold mb-1">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i> Venta a crédito
            </p>
            <p class="text-xs text-amber-700">
                El cliente es <strong>obligatorio</strong>. La venta se registra y se descuenta
                del inventario, pero el cobro queda pendiente.
            </p>
        </div>
        <p id="coCreditError"
           class="hidden mt-2 text-xs text-red-600 font-semibold text-center">
            <i class="fa-solid fa-circle-exclamation mr-1"></i>
            Selecciona o registra un cliente antes de continuar.
        </p>
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

<!-- ══════════ MODAL MOVIMIENTO DE EFECTIVO ══════════ -->
<div id="cashMovementOverlay"
     class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm flex flex-col"
         style="max-height:90dvh">

        <!-- Header -->
        <div class="shrink-0 flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800">
                <i class="fa-solid fa-money-bill-transfer mr-2 text-zinc-600"></i>
                Movimiento de efectivo
            </h2>
            <button onclick="closeCashModal()"
                    class="w-8 h-8 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-600
                           flex items-center justify-center text-xl leading-none transition-colors">
                &times;
            </button>
        </div>

        <!-- Formulario (fijo, no hace scroll) -->
        <div class="shrink-0 px-5 pt-4 pb-3 space-y-3">

            <!-- Tipo: Entrada / Salida -->
            <div class="grid grid-cols-2 gap-2">
                <button id="cashTypeEntrada" data-type="entrada"
                        onclick="setCashType('entrada')"
                        class="cash-type-btn py-2.5 rounded-xl border-2 border-gray-200
                               text-sm font-bold text-gray-500
                               flex items-center justify-center gap-2 transition-all">
                    <i class="fa-solid fa-arrow-down text-green-500"></i> Entrada
                </button>
                <button id="cashTypeSalida" data-type="salida"
                        onclick="setCashType('salida')"
                        class="cash-type-btn py-2.5 rounded-xl border-2 border-zinc-600 bg-zinc-50
                               text-sm font-bold text-zinc-700
                               flex items-center justify-center gap-2 transition-all">
                    <i class="fa-solid fa-arrow-up text-red-500"></i> Salida
                </button>
            </div>

            <!-- Concepto + Monto en fila -->
            <input id="cashConcept" type="text"
                   placeholder="Concepto *  (pago proveedor, fondo…)"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm
                          focus:outline-none focus:border-zinc-400 transition-colors">

            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-lg">$</span>
                <input id="cashAmount" type="number" min="0.01" step="0.01" placeholder="0.00"
                       class="w-full pl-9 pr-4 py-3 border-2 border-gray-200 rounded-xl
                              text-2xl font-black text-center
                              focus:outline-none focus:border-zinc-400 transition-colors">
            </div>

            <button id="cashSubmitBtn" onclick="submitCashMovement()"
                    class="w-full bg-zinc-600 hover:bg-zinc-700 active:bg-zinc-800
                           text-white font-bold py-3 rounded-xl transition-colors">
                <i class="fa-solid fa-check mr-1"></i> Registrar
            </button>
        </div>

        <!-- Divisor + Totales del día -->
        <div class="shrink-0 border-t border-gray-100 px-5 pt-3 pb-1 flex items-center justify-between">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">
                <i class="fa-solid fa-calendar-day mr-1"></i> Hoy
            </p>
            <div id="cashTotalsBar" class="flex gap-3 text-xs font-bold hidden">
                <span id="cashTotalEntradas" class="text-green-600"></span>
                <span id="cashTotalSalidas"  class="text-red-500"></span>
            </div>
        </div>

        <!-- Lista scrollable de movimientos de hoy -->
        <div id="cashMovementList"
             class="flex-1 overflow-y-auto px-5 pb-4 min-h-0 space-y-0.5">
            <p class="text-xs text-gray-400 text-center py-4">Cargando…</p>
        </div>
    </div>
</div>


<!-- ══════════ MODAL COBROS PENDIENTES ══════════ -->
<div id="creditsOverlay"
     class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col"
         style="max-height:90dvh">

        <!-- Header -->
        <div class="shrink-0 flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div>
                <h2 class="font-bold text-gray-800">
                    <i class="fa-solid fa-file-invoice-dollar mr-2 text-zinc-600"></i>
                    Cobros pendientes
                </h2>
                <p id="creditsTotalPending" class="text-xs text-gray-400 mt-0.5"></p>
            </div>
            <button onclick="closeCreditsModal()"
                    class="w-8 h-8 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-600
                           flex items-center justify-center text-xl leading-none transition-colors">
                &times;
            </button>
        </div>

        <!-- Buscador -->
        <div class="shrink-0 px-5 pt-3 pb-2">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2
                           text-gray-300 text-xs pointer-events-none"></i>
                <input id="creditsSearch" type="text"
                       placeholder="Buscar por cliente o folio…"
                       class="w-full pl-8 pr-3 py-2 border border-gray-200 rounded-lg text-sm
                              focus:outline-none focus:border-zinc-400"
                       oninput="filterCredits(this.value)">
            </div>
        </div>

        <!-- Lista scrollable -->
        <div id="creditsList"
             class="flex-1 overflow-y-auto px-5 pb-5 min-h-0 space-y-3">
            <p class="text-xs text-gray-400 text-center py-6">Cargando…</p>
        </div>
    </div>
</div>


<!-- ══════════ MODAL HISTORIAL DE VENTAS ══════════ -->
<div id="historyOverlay"
     class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col"
         style="max-height:90dvh">

        <!-- Header -->
        <div class="shrink-0 flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800">
                <i class="fa-solid fa-clock-rotate-left mr-2 text-zinc-600"></i>
                Historial de ventas
            </h2>
            <button onclick="closeHistoryModal()"
                    class="w-8 h-8 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-600
                           flex items-center justify-center text-xl leading-none transition-colors">
                &times;
            </button>
        </div>

        <!-- Filtro de período -->
        <div class="shrink-0 px-5 py-3 border-b border-gray-100 flex gap-2">
            <button class="hist-filter-btn flex-1 py-1.5 rounded-lg text-xs font-bold transition-colors
                           bg-zinc-600 text-white"
                    data-filter="today" onclick="setHistoryFilter('today')">
                Hoy
            </button>
            <button class="hist-filter-btn flex-1 py-1.5 rounded-lg text-xs font-bold transition-colors
                           bg-gray-100 text-gray-500"
                    data-filter="yesterday" onclick="setHistoryFilter('yesterday')">
                Ayer
            </button>
            <button class="hist-filter-btn flex-1 py-1.5 rounded-lg text-xs font-bold transition-colors
                           bg-gray-100 text-gray-500"
                    data-filter="week" onclick="setHistoryFilter('week')">
                7 días
            </button>
        </div>

        <!-- Resumen del período -->
        <div id="historySummary"
             class="shrink-0 hidden px-5 py-2 bg-gray-50 border-b border-gray-100
                    flex gap-4 text-xs text-gray-600">
        </div>

        <!-- Lista scrollable -->
        <div id="historyList"
             class="flex-1 overflow-y-auto px-5 pb-4 min-h-0">
            <p class="text-xs text-gray-400 text-center py-6">Cargando…</p>
        </div>
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
let coPayMethod  = 'efectivo';
let coCustomer   = null;       // { id, name, phone } del cliente seleccionado

/* ═══════════════════════════════════════════════════════════
   CLIENTE EN CHECKOUT
═══════════════════════════════════════════════════════════ */
function toggleCustomerSearch() {
    const panel   = document.getElementById('coCustomerPanel');
    const chevron = document.getElementById('coCustomerChevron');
    const isOpen  = !panel.classList.contains('hidden');

    if (isOpen) {
        panel.classList.add('hidden');
        chevron.style.transform = '';
    } else {
        panel.classList.remove('hidden');
        chevron.style.transform = 'rotate(180deg)';
        document.getElementById('coCustomerSearch').focus();
        renderCustomerResults([]); // limpiar resultados previos
    }
}

function toggleNewCustomerForm() {
    const form  = document.getElementById('coNewCustomerForm');
    const label = document.getElementById('coNewToggleLabel');
    const open  = form.classList.toggle('hidden');
    label.textContent = open ? 'Nuevo cliente' : 'Cancelar';
    if (!open) document.getElementById('coNewName').focus();
}

let _customerTimer = null;
function debouncedCustomerSearch(term) {
    clearTimeout(_customerTimer);
    if (term.length < 2) { renderCustomerResults([]); return; }
    _customerTimer = setTimeout(() => fetchCustomers(term), 300);
}

async function fetchCustomers(term) {
    try {
        const data = await api(`/customers?search=${encodeURIComponent(term)}`);
        renderCustomerResults(data);
    } catch (e) { /* silencioso */ }
}

function renderCustomerResults(list) {
    const c = document.getElementById('coCustomerResults');
    c.innerHTML = '';
    if (!list.length) return;
    list.forEach(cu => {
        const btn = document.createElement('button');
        btn.className = 'w-full text-left px-3 py-1.5 rounded-lg hover:bg-zinc-50 transition-colors';
        btn.innerHTML = `
            <span class="text-sm font-semibold text-gray-800">${cu.name}</span>
            ${cu.phone ? `<span class="text-xs text-gray-400 ml-2">${cu.phone}</span>` : ''}`;
        btn.onclick = () => selectCustomer(cu);
        c.appendChild(btn);
    });
}

function selectCustomer(cu) {
    coCustomer = cu;
    document.getElementById('coCustomerName').textContent = cu.name + (cu.phone ? ' · ' + cu.phone : '');
    document.getElementById('coCustomerName').classList.replace('text-gray-400', 'text-gray-700');
    document.getElementById('coCustomerPanel').classList.add('hidden');
    document.getElementById('coCustomerChevron').style.transform = '';
}

function clearCustomer() {
    coCustomer = null;
    document.getElementById('coCustomerName').textContent = 'Cliente (opcional)';
    document.getElementById('coCustomerName').classList.replace('text-gray-700', 'text-gray-400');
    document.getElementById('coCustomerSearch').value = '';
    renderCustomerResults([]);
}

async function createCustomer() {
    const name  = document.getElementById('coNewName').value.trim();
    const phone = document.getElementById('coNewPhone').value.trim();
    if (!name) {
        document.getElementById('coNewName').classList.add('border-red-400');
        document.getElementById('coNewName').focus();
        return;
    }
    document.getElementById('coNewName').classList.remove('border-red-400');

    try {
        const res = await api('/customers', {
            method: 'POST',
            body: JSON.stringify({ name, phone: phone || null }),
        });
        selectCustomer(res.data);
        // Reset form
        document.getElementById('coNewName').value  = '';
        document.getElementById('coNewPhone').value = '';
        document.getElementById('coNewCustomerForm').classList.add('hidden');
        document.getElementById('coNewToggleLabel').textContent = 'Nuevo cliente';
        Swal.fire({ icon:'success', title:'Cliente guardado', timer:1200,
                    showConfirmButton:false, toast:true, position:'top' });
    } catch (e) {
        Swal.fire('Error', 'No se pudo guardar el cliente', 'error');
    }
}

function resetCustomerSection() {
    coCustomer = null;
    document.getElementById('coCustomerName').textContent = 'Cliente (opcional)';
    document.getElementById('coCustomerName').classList.remove('text-gray-700');
    document.getElementById('coCustomerName').classList.add('text-gray-400');
    document.getElementById('coCustomerSearch').value = '';
    document.getElementById('coCustomerPanel').classList.add('hidden');
    document.getElementById('coCustomerChevron').style.transform = '';
    document.getElementById('coNewCustomerForm').classList.add('hidden');
    document.getElementById('coNewToggleLabel').textContent = 'Nuevo cliente';
    renderCustomerResults([]);
}

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

    // Reset cliente
    resetCustomerSection();

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

    const cashSection   = document.getElementById('coCashSection');
    const creditSection = document.getElementById('coCreditSection');
    const creditError   = document.getElementById('coCreditError');

    cashSection.classList.add('hidden');
    creditSection.classList.add('hidden');
    creditError.classList.add('hidden');

    if (coPayMethod === 'efectivo') {
        cashSection.classList.remove('hidden');
        setTimeout(() => document.getElementById('coAmount').focus(), 50);
    } else if (coPayMethod === 'credito') {
        creditSection.classList.remove('hidden');
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

    // Validar cliente si es crédito
    if (coPayMethod === 'credito' && !coCustomer) {
        document.getElementById('coCreditError').classList.remove('hidden');
        // Abrir sección de cliente si está cerrada
        const panel = document.getElementById('coCustomerPanel');
        if (panel.classList.contains('hidden')) toggleCustomerSearch();
        return;
    }

    const btn = document.getElementById('coConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Procesando…';

    try {
        const amountPaid = coPayMethod === 'efectivo'
            ? parseFloat(document.getElementById('coAmount').value)
            : coPayMethod === 'credito'
                ? 0
                : total;

        const res = await fetch(`${API_URL}/sales`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
            body: JSON.stringify({
                customer_id    : coCustomer?.id   ?? null,
                customer_name  : coCustomer?.name ?? 'Cliente',
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

        // Actualizar badge de cobros si fue crédito
        if (coPayMethod === 'credito') {
            loadCredits(); // refresca el conteo del badge
        }

        Swal.fire({
            icon: 'success',
            title: coPayMethod === 'credito' ? '¡Crédito registrado!' : '¡Venta registrada!',
            html: coPayMethod === 'credito'
                ? `A nombre de: <b>${coCustomer?.name ?? 'Cliente'}</b><br>Pendiente: <b class="text-red-500">$${total.toFixed(2)}</b>`
                : `Total cobrado: <b>$${total.toFixed(2)}</b>` +
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
   REIMPRIMIR ÚLTIMO TICKET
═══════════════════════════════════════════════════════════ */
function reprintLastTicket() {
    if (!localStorage.getItem('lastReceipt')) {
        Swal.fire({
            icon: 'info', title: 'Sin ticket reciente',
            text: 'Realiza una venta primero.',
            timer: 2000, showConfirmButton: false,
            toast: true, position: 'top'
        });
        return;
    }
    window.open(
        '{{ route("pos.receipt") }}',
        'ticket_print',
        'width=360,height=620,toolbar=0,menubar=0,location=0,status=0,scrollbars=1'
    );
}

document.getElementById('reprintBtn').onclick       = reprintLastTicket;
document.getElementById('reprintBtnMobile').onclick = reprintLastTicket;

/* ═══════════════════════════════════════════════════════════
   MOVIMIENTOS DE EFECTIVO
═══════════════════════════════════════════════════════════ */
let cashMovementType = 'salida';

function openCashModal() {
    cashMovementType = 'salida';
    setCashType('salida');
    document.getElementById('cashConcept').value = '';
    document.getElementById('cashAmount').value  = '';
    document.getElementById('cashConcept').classList.remove('border-red-400');
    document.getElementById('cashAmount').classList.remove('border-red-400');

    const overlay = document.getElementById('cashMovementOverlay');
    overlay.classList.replace('hidden', 'flex');

    // Cerrar modal de carrito mobile si está abierto
    cartModalMobile.classList.replace('flex', 'hidden');

    loadCashMovements();
    setTimeout(() => document.getElementById('cashConcept').focus(), 80);
}

async function loadCashMovements() {
    const list = document.getElementById('cashMovementList');
    list.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Cargando…</p>';
    try {
        const today = new Date().toLocaleDateString('sv-SE'); // YYYY-MM-DD local
        const data  = await api(`/cash-movements?date=${today}`);
        renderCashMovements(data);
    } catch(e) {
        list.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">No se pudieron cargar</p>';
    }
}

function renderCashMovements(list) {
    const c              = document.getElementById('cashMovementList');
    const totalsBar      = document.getElementById('cashTotalsBar');
    const totalEntradasEl = document.getElementById('cashTotalEntradas');
    const totalSalidasEl  = document.getElementById('cashTotalSalidas');

    if (!list.length) {
        c.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Sin movimientos hoy</p>';
        totalsBar.classList.add('hidden');
        return;
    }

    let sumEntradas = 0, sumSalidas = 0;
    list.forEach(m => {
        if (m.type === 'entrada') sumEntradas += parseFloat(m.amount);
        else                      sumSalidas  += parseFloat(m.amount);
    });

    totalEntradasEl.textContent = `+$${sumEntradas.toFixed(2)}`;
    totalSalidasEl.textContent  = `-$${sumSalidas.toFixed(2)}`;
    totalsBar.classList.remove('hidden');

    c.innerHTML = '';
    list.forEach(m => {
        const entrada = m.type === 'entrada';
        const time    = new Date(m.created_at).toLocaleTimeString('es-MX',
                            { hour: '2-digit', minute: '2-digit' });

        const row = document.createElement('div');
        row.className = 'flex items-center gap-2.5 py-2 border-b border-gray-50 last:border-0';
        row.innerHTML = `
            <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0
                        ${entrada ? 'bg-green-100' : 'bg-red-100'}">
                <i class="fa-solid fa-arrow-${entrada ? 'down text-green-600' : 'up text-red-500'} text-xs"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-800 truncate">${m.concept}</p>
                <p class="text-xs text-gray-400">${time}</p>
            </div>
            <span class="text-sm font-bold shrink-0 ${entrada ? 'text-green-600' : 'text-red-500'}">
                ${entrada ? '+' : '-'}$${parseFloat(m.amount).toFixed(2)}
            </span>`;
        c.appendChild(row);
    });
}

function closeCashModal() {
    document.getElementById('cashMovementOverlay').classList.replace('flex', 'hidden');
}

function setCashType(type) {
    cashMovementType = type;
    document.querySelectorAll('.cash-type-btn').forEach(btn => {
        const active = btn.dataset.type === type;
        btn.classList.toggle('border-zinc-600', active);
        btn.classList.toggle('bg-zinc-50',      active);
        btn.classList.toggle('text-zinc-700',   active);
        btn.classList.toggle('border-gray-200', !active);
        btn.classList.toggle('text-gray-500',   !active);
    });
}

async function submitCashMovement() {
    const concept = document.getElementById('cashConcept').value.trim();
    const amount  = parseFloat(document.getElementById('cashAmount').value) || 0;

    let valid = true;
    if (!concept) {
        document.getElementById('cashConcept').classList.add('border-red-400');
        document.getElementById('cashConcept').focus();
        valid = false;
    } else {
        document.getElementById('cashConcept').classList.remove('border-red-400');
    }
    if (amount <= 0) {
        document.getElementById('cashAmount').classList.add('border-red-400');
        if (valid) document.getElementById('cashAmount').focus();
        valid = false;
    } else {
        document.getElementById('cashAmount').classList.remove('border-red-400');
    }
    if (!valid) return;

    const btn = document.getElementById('cashSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Guardando…';

    try {
        await api('/cash-movements', {
            method : 'POST',
            body   : JSON.stringify({
                type      : cashMovementType,
                concept   : concept,
                amount    : amount,
                branch_id : localStorage.getItem('selectedBranchId') || null,
            }),
        });

        // Limpiar form y refrescar lista (sin cerrar el modal)
        document.getElementById('cashConcept').value = '';
        document.getElementById('cashAmount').value  = '';
        setCashType('salida');
        loadCashMovements();

        const label = cashMovementType === 'entrada' ? 'Entrada' : 'Salida';
        Swal.fire({
            icon  : cashMovementType === 'entrada' ? 'success' : 'info',
            title : `${label} registrada`,
            html  : `<b>${concept}</b>: $${amount.toFixed(2)}`,
            timer : 2000,
            showConfirmButton : false,
            toast    : true,
            position : 'top',
        });

    } catch (e) {
        Swal.fire('Error', 'No se pudo registrar el movimiento', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check mr-1"></i> Registrar';
    }
}

document.getElementById('cashMovementBtn').onclick       = openCashModal;
document.getElementById('cashMovementBtnMobile').onclick = openCashModal;

// Cerrar al hacer click en el overlay
document.getElementById('cashMovementOverlay').addEventListener('click', e => {
    if (e.target === document.getElementById('cashMovementOverlay')) closeCashModal();
});

/* ═══════════════════════════════════════════════════════════
   COBROS PENDIENTES (Créditos)
═══════════════════════════════════════════════════════════ */
let _allCredits = [];

function openCreditsModal() {
    document.getElementById('creditsSearch').value = '';
    const overlay = document.getElementById('creditsOverlay');
    overlay.classList.replace('hidden', 'flex');
    cartModalMobile.classList.replace('flex', 'hidden');
    loadCredits();
}

function closeCreditsModal() {
    document.getElementById('creditsOverlay').classList.replace('flex', 'hidden');
}

async function loadCredits() {
    document.getElementById('creditsList').innerHTML =
        '<p class="text-xs text-gray-400 text-center py-6">Cargando…</p>';
    try {
        _allCredits = await api('/credits?status=pendiente');
        renderCredits(_allCredits);
        updateCreditsBadge(_allCredits.length);
    } catch(e) {
        document.getElementById('creditsList').innerHTML =
            '<p class="text-xs text-red-400 text-center py-6">Error al cargar</p>';
    }
}

function filterCredits(term) {
    if (!term) { renderCredits(_allCredits); return; }
    const t = term.toLowerCase();
    renderCredits(_allCredits.filter(c =>
        c.customer_name.toLowerCase().includes(t) ||
        c.invoice_number.toLowerCase().includes(t)
    ));
}

function updateCreditsBadge(count) {
    const badge = document.getElementById('creditsBadge');
    if (count > 0) {
        badge.textContent = count;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

function renderCredits(list) {
    const c = document.getElementById('creditsList');
    const totalEl = document.getElementById('creditsTotalPending');

    if (!list.length) {
        c.innerHTML = `
            <div class="flex flex-col items-center justify-center py-10 text-gray-300">
                <i class="fa-solid fa-circle-check text-4xl mb-2 text-green-400"></i>
                <p class="text-sm text-gray-500 font-semibold">Sin cobros pendientes</p>
            </div>`;
        totalEl.textContent = '';
        return;
    }

    const totalPending = list.reduce((s, c) => s + c.remaining, 0);
    totalEl.textContent = `${list.length} crédito${list.length > 1 ? 's' : ''} · $${totalPending.toFixed(2)} por cobrar`;

    c.innerHTML = '';
    list.forEach(credit => {
        const date  = new Date(credit.created_at).toLocaleDateString('es-MX',
                          { day: '2-digit', month: 'short', year: '2-digit' });
        const pct   = Math.min(100, (credit.paid_amount / credit.original_amount) * 100).toFixed(0);
        const card  = document.createElement('div');
        card.className = 'bg-gray-50 rounded-xl p-4 border border-gray-100';
        card.dataset.creditId = credit.id;
        card.innerHTML = `
            <div class="flex items-start justify-between gap-2 mb-2">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-800 text-sm truncate">${credit.customer_name}</p>
                    <p class="text-xs text-gray-400">Folio ${credit.invoice_number} · ${date}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-base font-black text-red-500">-$${credit.remaining.toFixed(2)}</p>
                    <p class="text-xs text-gray-400">de $${credit.original_amount.toFixed(2)}</p>
                </div>
            </div>
            <!-- Barra de progreso -->
            <div class="w-full bg-gray-200 rounded-full h-1.5 mb-3">
                <div class="bg-zinc-500 h-1.5 rounded-full transition-all" style="width:${pct}%"></div>
            </div>
            ${credit.paid_amount > 0
                ? `<p class="text-xs text-gray-500 mb-2">Abonado: $${credit.paid_amount.toFixed(2)}</p>`
                : ''}
            <!-- Formulario de abono (oculto) -->
            <div class="abono-form hidden flex gap-2 items-center mt-1">
                <div class="relative flex-1">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">$</span>
                    <input type="number" min="0.01" step="0.01"
                           placeholder="${credit.remaining.toFixed(2)}"
                           max="${credit.remaining.toFixed(2)}"
                           class="abono-input w-full pl-7 pr-2 py-2 border-2 border-gray-200 rounded-lg
                                  text-sm font-bold focus:outline-none focus:border-zinc-400 transition-colors">
                </div>
                <button class="btn-abono-confirm bg-zinc-600 hover:bg-zinc-700 text-white
                               text-xs font-bold px-3 py-2 rounded-lg transition-colors whitespace-nowrap">
                    Confirmar
                </button>
                <button class="btn-abono-cancel text-gray-400 hover:text-gray-600 text-xs px-2 py-2">
                    Cancelar
                </button>
            </div>
            <button class="btn-abono-open w-full text-center text-xs text-zinc-600 hover:text-zinc-800
                           font-semibold py-1.5 rounded-lg hover:bg-zinc-50 transition-colors border
                           border-zinc-200 mt-1">
                <i class="fa-solid fa-plus mr-1"></i> Registrar abono
            </button>`;

        // Eventos del card
        const abonoForm    = card.querySelector('.abono-form');
        const abonoOpenBtn = card.querySelector('.btn-abono-open');
        const abonoConfirm = card.querySelector('.btn-abono-confirm');
        const abonoCancel  = card.querySelector('.btn-abono-cancel');
        const abonoInput   = card.querySelector('.abono-input');

        abonoOpenBtn.onclick = () => {
            abonoForm.classList.remove('hidden');
            abonoOpenBtn.classList.add('hidden');
            abonoInput.value = credit.remaining.toFixed(2);
            abonoInput.focus();
            abonoInput.select();
        };

        abonoCancel.onclick = () => {
            abonoForm.classList.add('hidden');
            abonoOpenBtn.classList.remove('hidden');
        };

        abonoConfirm.onclick = () => submitAbono(credit.id, parseFloat(abonoInput.value), abonoConfirm);

        abonoInput.onkeydown = e => {
            if (e.key === 'Enter') abonoConfirm.click();
            if (e.key === 'Escape') abonoCancel.click();
        };

        c.appendChild(card);
    });
}

async function submitAbono(creditId, amount, btn) {
    if (!amount || amount <= 0) return;

    btn.disabled = true;
    btn.textContent = '…';

    try {
        const res = await api(`/credits/${creditId}/pay`, {
            method : 'POST',
            body   : JSON.stringify({ amount }),
        });

        Swal.fire({
            icon  : res.status === 'pagado' ? 'success' : 'info',
            title : res.message,
            html  : res.status === 'pagado'
                ? '¡Crédito liquidado!'
                : `Restante: <b>$${res.remaining.toFixed(2)}</b>`,
            timer : 2500,
            showConfirmButton : false,
            toast    : true,
            position : 'top',
        });

        // Refrescar lista
        loadCredits();
        document.getElementById('creditsSearch').value = '';

    } catch(e) {
        let msg = 'No se pudo registrar';
        try { msg = JSON.parse(e.message)?.message || msg; } catch(_) {}
        Swal.fire('Error', msg, 'error');
        btn.disabled = false;
        btn.textContent = 'Confirmar';
    }
}

document.getElementById('creditsBtn').onclick       = openCreditsModal;
document.getElementById('creditsBtnMobile').onclick = openCreditsModal;
document.getElementById('creditsOverlay').addEventListener('click', e => {
    if (e.target === document.getElementById('creditsOverlay')) closeCreditsModal();
});

/* ═══════════════════════════════════════════════════════════
   HISTORIAL DE VENTAS
═══════════════════════════════════════════════════════════ */
let _historyFilter = 'today';

const METHOD_LABELS = {
    efectivo      : { label: 'Efectivo',   cls: 'bg-green-100 text-green-700'   },
    tarjeta       : { label: 'Tarjeta',    cls: 'bg-blue-100 text-blue-700'     },
    transferencia : { label: 'Transfer.',  cls: 'bg-indigo-100 text-indigo-700' },
    mixto         : { label: 'Mixto',      cls: 'bg-purple-100 text-purple-700' },
    credito       : { label: 'Crédito',    cls: 'bg-amber-100 text-amber-700'   },
};

function openHistoryModal() {
    document.getElementById('historyOverlay').classList.replace('hidden', 'flex');
    cartModalMobile.classList.replace('flex', 'hidden');
    setHistoryFilter('today');
}

function closeHistoryModal() {
    document.getElementById('historyOverlay').classList.replace('flex', 'hidden');
}

function setHistoryFilter(filter) {
    _historyFilter = filter;
    document.querySelectorAll('.hist-filter-btn').forEach(btn => {
        const active = btn.dataset.filter === filter;
        btn.classList.toggle('bg-zinc-600', active);
        btn.classList.toggle('text-white',  active);
        btn.classList.toggle('bg-gray-100', !active);
        btn.classList.toggle('text-gray-500', !active);
    });
    loadHistory();
}

async function loadHistory() {
    const list = document.getElementById('historyList');
    list.innerHTML = '<p class="text-xs text-gray-400 text-center py-6">Cargando…</p>';
    document.getElementById('historySummary').classList.add('hidden');

    const today = new Date().toLocaleDateString('sv-SE');
    let dateFrom, dateTo;

    if (_historyFilter === 'today') {
        dateFrom = dateTo = today;
    } else if (_historyFilter === 'yesterday') {
        const y = new Date(); y.setDate(y.getDate() - 1);
        dateFrom = dateTo = y.toLocaleDateString('sv-SE');
    } else {
        const w = new Date(); w.setDate(w.getDate() - 6);
        dateFrom = w.toLocaleDateString('sv-SE');
        dateTo   = today;
    }

    try {
        const result = await api(`/sales?date_from=${dateFrom}&date_to=${dateTo}&per_page=100`);
        renderHistory(result.data || []);
    } catch(e) {
        list.innerHTML = '<p class="text-xs text-red-400 text-center py-6">Error al cargar</p>';
    }
}

function renderHistory(sales) {
    const list    = document.getElementById('historyList');
    const summary = document.getElementById('historySummary');

    if (!sales.length) {
        list.innerHTML = `
            <div class="flex flex-col items-center justify-center py-10 text-gray-300">
                <i class="fa-solid fa-receipt text-4xl mb-2"></i>
                <p class="text-sm text-gray-400">Sin ventas en este período</p>
            </div>`;
        summary.classList.add('hidden');
        return;
    }

    // Resumen del período
    const completadas = sales.filter(s => s.status !== 'cancelada');
    const totalVentas = completadas.reduce((sum, s) => sum + Number(s.total), 0);
    const numCreditos = completadas.filter(s => s.payment_method === 'credito').length;
    summary.innerHTML = `
        <span><b>${completadas.length}</b> venta${completadas.length !== 1 ? 's' : ''}</span>
        <span class="text-gray-300">|</span>
        <span>Total: <b>$${totalVentas.toFixed(2)}</b></span>
        ${numCreditos ? `<span class="text-gray-300">|</span><span class="text-amber-600"><b>${numCreditos}</b> crédito${numCreditos > 1 ? 's' : ''}</span>` : ''}`;
    summary.classList.remove('hidden');

    list.innerHTML = '';
    sales.forEach(sale => {
        const m          = METHOD_LABELS[sale.payment_method] || { label: sale.payment_method, cls: 'bg-gray-100 text-gray-600' };
        const time       = new Date(sale.sale_date).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
        const clientName = sale.customer?.name ?? 'Cliente general';
        const cancelled  = sale.status === 'cancelada';

        const row = document.createElement('div');
        row.className = `py-3 border-b border-gray-100 last:border-0 flex items-center gap-3${cancelled ? ' opacity-40' : ''}`;
        row.innerHTML = `
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                    <span class="text-xs font-bold text-gray-800">${sale.invoice_number}</span>
                    <span class="text-xs text-gray-400">${time}</span>
                    <span class="text-xs px-1.5 py-0.5 rounded-full font-semibold ${m.cls}">${m.label}</span>
                    ${cancelled ? '<span class="text-xs px-1.5 py-0.5 rounded-full bg-red-100 text-red-600 font-semibold">Cancelada</span>' : ''}
                </div>
                <p class="text-xs text-gray-400 truncate">${clientName}</p>
            </div>
            <div class="text-right shrink-0 flex flex-col items-end gap-1">
                <p class="text-sm font-black text-gray-800${cancelled ? ' line-through text-gray-400' : ''}">
                    $${Number(sale.total).toFixed(2)}
                </p>
                <button class="btn-reprint-hist text-xs text-zinc-500 hover:text-zinc-700 font-semibold
                               flex items-center gap-1 transition-colors"
                        data-id="${sale.id}">
                    <i class="fa-solid fa-print text-xs"></i> Reimprimir
                </button>
                ${!cancelled ? `
                <button class="btn-cancel-hist text-xs text-red-400 hover:text-red-600 font-semibold
                               flex items-center gap-1 transition-colors"
                        data-id="${sale.id}" data-invoice="${sale.invoice_number}">
                    <i class="fa-solid fa-ban text-xs"></i> Cancelar
                </button>` : ''}
            </div>`;

        row.querySelector('.btn-reprint-hist').onclick = function() {
            reprintFromHistory(sale.id, this);
        };

        if (!cancelled) {
            row.querySelector('.btn-cancel-hist').onclick = function() {
                cancelSaleFromHistory(sale.id, sale.invoice_number, row);
            };
        }

        list.appendChild(row);
    });
}

async function reprintFromHistory(saleId, btn) {
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i>';

    try {
        const sale = await api(`/sales/${saleId}`);

        localStorage.setItem('lastReceipt', JSON.stringify({
            invoice_number : sale.invoice_number,
            date           : sale.sale_date,
            customer_name  : sale.customer?.name ?? 'Cliente',
            branch         : localStorage.getItem('selectedBranchName') || 'Sucursal Principal',
            items          : (sale.details || []).map(d => ({
                name     : d.product_name,
                code     : d.product_code || '',
                quantity : d.quantity,
                price    : d.unit_price,
            })),
            subtotal       : sale.subtotal,
            discount       : sale.discount   || 0,
            total          : sale.total,
            payment_method : sale.payment_method,
            amount_paid    : sale.amount_paid,
            change         : sale.change_amount || 0,
        }));

        window.open(
            '{{ route("pos.receipt") }}',
            'ticket_print',
            'width=360,height=620,toolbar=0,menubar=0,location=0,status=0,scrollbars=1'
        );

    } catch(e) {
        Swal.fire({ icon:'error', title:'Error', text:'No se pudo cargar el ticket',
                    timer:2000, showConfirmButton:false });
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

async function cancelSaleFromHistory(saleId, invoiceNumber, rowEl) {
    const result = await Swal.fire({
        title            : `¿Cancelar venta ${invoiceNumber}?`,
        html             : `<span class="text-sm text-gray-500">Se restaurará el stock de todos los artículos.<br>Esta acción <b>no se puede deshacer</b>.</span>`,
        icon             : 'warning',
        showCancelButton : true,
        confirmButtonText: 'Sí, cancelar venta',
        cancelButtonText : 'No, mantener',
        confirmButtonColor: '#dc2626',
        reverseButtons   : true,
    });

    if (!result.isConfirmed) return;

    try {
        await api(`/sales/${saleId}/cancel`, { method: 'POST' });

        /* Marcar fila visualmente como cancelada */
        rowEl.classList.add('opacity-40');
        const cancelBtn = rowEl.querySelector('.btn-cancel-hist');
        if (cancelBtn) cancelBtn.remove();

        /* Añadir badge cancelada */
        const badges = rowEl.querySelector('.flex.items-center.gap-1\\.5');
        if (badges) {
            const badge = document.createElement('span');
            badge.className = 'text-xs px-1.5 py-0.5 rounded-full bg-red-100 text-red-600 font-semibold';
            badge.textContent = 'Cancelada';
            badges.appendChild(badge);
        }

        /* Tachado del total */
        const totalEl = rowEl.querySelector('.text-sm.font-black');
        if (totalEl) totalEl.classList.add('line-through', 'text-gray-400');

        /* Recargar productos en el POS (stock actualizado) */
        loadProducts();

        Swal.fire({
            icon             : 'success',
            title            : 'Venta cancelada',
            text             : `${invoiceNumber} fue cancelada y el inventario fue restaurado.`,
            timer            : 2500,
            showConfirmButton: false,
        });

    } catch(e) {
        let msg = e.message;
        try { msg = JSON.parse(e.message).message || msg; } catch {}
        Swal.fire({ icon: 'error', title: 'Error', text: msg });
    }
}

document.getElementById('historyBtn').onclick       = openHistoryModal;
document.getElementById('historyBtnMobile').onclick = openHistoryModal;
document.getElementById('historyOverlay').addEventListener('click', e => {
    if (e.target === document.getElementById('historyOverlay')) closeHistoryModal();
});

/* ═══════════════════════════════════════════════════════════
   INIT
═══════════════════════════════════════════════════════════ */
loadBranches();
loadCategories();
loadProducts();
renderCart();

// Cargar badge de cobros al inicio
api('/credits?status=pendiente').then(data => updateCreditsBadge(data.length)).catch(() => {});

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

        // 1) Buscar en la lista ya cargada (trim en ambos lados por espacios en BD)
        let found = products.find(p => String(p.code).trim() === code);

        // 2) Si no está en memoria → consultar API
        if (!found) {
            try {
                const data = await api('/products?search=' + encodeURIComponent(code));
                found = data.find(p => String(p.code).trim() === code);
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
