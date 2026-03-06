<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LuraPos - Gestión de Fotos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

    <nav class="bg-white text-white p-4 shadow">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <img style="max-width: 100px;" src="{{ asset('/assets/img/logoSF.png') }}" alt="">
            <h1 style="color: #4A5568;" class="text-lg font-semibold">Gestión de Fotos de Productos</h1>
            <a href="{{ url('/inventario') }}" class="bg-green-600 text-white px-5 py-2.5 rounded-lg hover:bg-green-700">
                Regresar a Inventario
            </a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto mt-6 px-4 pb-12">

        {{-- ESTADÍSTICAS RÁPIDAS --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-3xl font-bold text-green-700" id="statTotal">--</p>
                <p class="text-sm text-gray-500 mt-1">Total productos</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-3xl font-bold text-blue-600" id="statWithPhoto">--</p>
                <p class="text-sm text-gray-500 mt-1">Con foto verificada</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-3xl font-bold text-orange-500" id="statWithout">--</p>
                <p class="text-sm text-gray-500 mt-1">Sin foto / pendientes</p>
            </div>
        </div>

        <div class="flex gap-6">

            {{-- PANEL IZQUIERDO: lista de productos --}}
            <div class="w-1/3 bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h2 class="font-bold text-green-700 text-lg mb-3">Productos</h2>

                    {{-- Filtros --}}
                    <input type="text" id="searchProducts" placeholder="Buscar por código o nombre..."
                           class="w-full border rounded p-2 text-sm focus:ring focus:ring-green-200 mb-2">

                    <select id="filterStatus" class="w-full border rounded p-2 text-sm focus:ring focus:ring-green-200">
                        <option value="all">Todos los productos</option>
                        <option value="pending" selected>Sin foto (pendientes)</option>
                        <option value="done">Con foto verificada</option>
                    </select>
                </div>

                {{-- Lista scrolleable --}}
                <div id="productList" class="overflow-y-auto" style="max-height: 65vh;">
                    <div class="flex items-center justify-center p-8 text-gray-400">
                        <div class="text-center">
                            <div class="text-4xl mb-2">⏳</div>
                            <p class="text-sm">Cargando productos...</p>
                        </div>
                    </div>
                </div>

                {{-- Paginación simple --}}
                <div class="p-3 border-t flex items-center justify-between text-sm text-gray-500">
                    <span id="paginationInfo">--</span>
                    <div class="flex gap-2">
                        <button id="btnPrev" onclick="changePage(-1)"
                            class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300 disabled:opacity-40"
                            disabled>←</button>
                        <button id="btnNext" onclick="changePage(1)"
                            class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300 disabled:opacity-40"
                            disabled>→</button>
                    </div>
                </div>
            </div>

            {{-- PANEL DERECHO: editor de foto --}}
            <div class="flex-1 bg-white rounded-lg shadow p-6">

                {{-- Estado vacío --}}
                <div id="emptyState" class="flex flex-col items-center justify-center h-full text-gray-400 min-h-96">
                    <div class="text-6xl mb-4">📷</div>
                    <p class="text-lg font-semibold">Selecciona un producto</p>
                    <p class="text-sm mt-1">Haz clic en un producto de la lista para asignarle una foto</p>
                </div>

                {{-- Panel del producto seleccionado --}}
                <div id="productPanel" class="hidden">

                    {{-- Info del producto --}}
                    <div class="flex items-start justify-between mb-5">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <span id="prodSku" class="bg-gray-100 text-gray-600 text-xs font-mono px-2 py-1 rounded"></span>
                                <span id="prodBrand" class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded hidden"></span>
                                <span id="prodVerifiedBadge" class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded hidden">✓ Foto verificada</span>
                            </div>
                            <h2 id="prodName" class="text-xl font-bold text-gray-800"></h2>
                            <p id="prodCategory" class="text-sm text-gray-500 mt-1"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400">Precio venta</p>
                            <p id="prodPrice" class="text-lg font-bold text-green-700"></p>
                        </div>
                    </div>

                    {{-- Foto actual --}}
                    <div class="grid grid-cols-2 gap-6 mb-6">

                        <div>
                            <p class="text-sm font-semibold text-gray-600 mb-2">Foto actual</p>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-gray-50" style="height: 220px;">
                                <img id="currentPhoto"
                                     class="hidden max-h-full max-w-full object-contain rounded"
                                     style="max-height: 210px;"
                                     alt="Foto actual">
                                <div id="noPhotoMsg" class="text-center text-gray-400">
                                    <div class="text-4xl mb-1">🖼️</div>
                                    <p class="text-xs">Sin foto</p>
                                </div>
                            </div>
                            {{-- Botón eliminar foto actual --}}
                            <button id="btnDeletePhoto" onclick="deleteCurrentPhoto()"
                                class="hidden mt-2 w-full text-xs text-red-600 border border-red-300 rounded px-3 py-1.5 hover:bg-red-50">
                                Eliminar foto actual
                            </button>
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-gray-600 mb-2">Vista previa</p>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-gray-50" style="height: 220px;">
                                <img id="previewImg"
                                     class="hidden max-h-full max-w-full object-contain rounded"
                                     style="max-height: 210px;"
                                     alt="Vista previa">
                                <div id="previewPlaceholder" class="text-center text-gray-400">
                                    <div class="text-4xl mb-1">👁️</div>
                                    <p class="text-xs">Aquí verás la foto antes de guardar</p>
                                </div>
                            </div>
                            <div id="previewStatus" class="hidden mt-2 text-center text-xs"></div>
                        </div>
                    </div>

                    {{-- TABS de fuente de imagen --}}
                    <div class="mb-4 border-b">
                        <div class="flex gap-0">
                            <button onclick="setTab('url')"
                                class="tab-btn px-4 py-2 text-sm font-medium border-b-2 border-green-600 text-green-700"
                                data-tab="url">
                                Por URL
                            </button>
                            <button onclick="setTab('upload')"
                                class="tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                                data-tab="upload">
                                Subir archivo
                            </button>
                        </div>
                    </div>

                    {{-- TAB: URL --}}
                    <div id="tab-url">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            URL de la imagen
                            <span class="text-xs font-normal text-gray-400 ml-1">(Truper, proveedor o cualquier URL directa a imagen)</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="url" id="imageUrl"
                                placeholder="https://..."
                                class="flex-1 border rounded p-2 text-sm focus:ring focus:ring-green-200">
                            <button onclick="previewFromUrl()"
                                class="bg-gray-700 text-white px-4 py-2 rounded text-sm hover:bg-gray-800">
                                Previsualizar
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            Pega la URL de la foto del banco de Truper u otro proveedor y haz clic en "Previsualizar" antes de guardar.
                        </p>
                    </div>

                    {{-- TAB: Subir archivo --}}
                    <div id="tab-upload" class="hidden">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Subir foto desde tu equipo</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-green-400 transition"
                             id="dropZone"
                             ondragover="event.preventDefault(); this.classList.add('border-green-500')"
                             ondragleave="this.classList.remove('border-green-500')"
                             ondrop="handleDrop(event)">
                            <div class="text-3xl mb-2">📁</div>
                            <p class="text-sm text-gray-500">Arrastra una imagen aquí o</p>
                            <label class="mt-2 inline-block cursor-pointer">
                                <span class="bg-green-600 text-white text-xs px-3 py-1.5 rounded hover:bg-green-700">
                                    Seleccionar archivo
                                </span>
                                <input type="file" id="fileInput" accept="image/*" class="hidden" onchange="previewFromFile(event)">
                            </label>
                            <p class="text-xs text-gray-400 mt-2">JPG, PNG, WEBP — máx. 5MB</p>
                        </div>
                    </div>

                    {{-- Botones de acción --}}
                    <div class="mt-5 flex gap-3">
                        <button id="btnSave" onclick="savePhoto()"
                            class="flex-1 bg-green-700 text-white py-2.5 rounded-lg font-semibold hover:bg-green-800 transition disabled:opacity-50"
                            disabled>
                            Guardar foto
                        </button>
                        <button onclick="skipProduct()"
                            class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                            Saltar →
                        </button>
                    </div>

                    {{-- Progreso batch --}}
                    <div class="mt-4 bg-gray-50 rounded-lg p-3">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Progreso de asignación</span>
                            <span id="progressText">0 / 0</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="progressBar" class="bg-green-600 h-2 rounded-full transition-all" style="width: 0%"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </main>

    {{-- Toast de notificaciones --}}
    <div id="toast" class="hidden fixed bottom-6 right-6 z-50 px-5 py-3 rounded-lg shadow-lg text-white text-sm font-medium max-w-xs">
    </div>

<script>
// ─────────────────────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Normaliza cualquier valor de imagen guardado en la BD:
 *  - URL completa (http/https)  → se usa tal cual
 *  - Ruta relativa              → se le antepone /storage/
 */
function imgUrl(value) {
    if (!value) return null;
    return value.startsWith('http') ? value : `/storage/${value}`;
}

// ─────────────────────────────────────────────────────────────────────────────
// ESTADO GLOBAL
// ─────────────────────────────────────────────────────────────────────────────
let products      = [];
let currentPage   = 1;
let totalPages    = 1;
let perPage       = 20;
let selectedId    = null;
let previewSource = null; // 'url' | 'file'
let previewBlob   = null; // para archivos locales
let activeTab     = 'url';

// ─────────────────────────────────────────────────────────────────────────────
// INICIALIZACIÓN
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    document.getElementById('searchProducts').addEventListener('input', debounce(() => {
        currentPage = 1;
        loadProducts();
    }, 350));
    document.getElementById('filterStatus').addEventListener('change', () => {
        currentPage = 1;
        loadProducts();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// CARGAR LISTA DE PRODUCTOS
// ─────────────────────────────────────────────────────────────────────────────
async function loadProducts() {
    const search   = document.getElementById('searchProducts').value;
    const status   = document.getElementById('filterStatus').value;

    const params = new URLSearchParams({ page: currentPage, per_page: perPage });
    if (search)           params.set('search', search);
    if (status !== 'all') params.set('photo_status', status);

    const list = document.getElementById('productList');
    list.innerHTML = `<div class="flex items-center justify-center p-8 text-gray-400">
        <div class="text-center"><div class="text-4xl mb-2">⏳</div><p class="text-sm">Cargando...</p></div>
    </div>`;

    try {
        const res  = await fetch(`/api/fotos/products?${params}`);
        const data = await res.json();

        products   = data.data;
        totalPages = data.last_page;

        updateStats(data.stats);
        renderList();
        updatePagination(data.current_page, data.last_page, data.total);
    } catch (e) {
        list.innerHTML = `<div class="p-6 text-center text-red-500 text-sm">Error cargando productos</div>`;
    }
}

function updateStats(stats) {
    if (!stats) return;
    document.getElementById('statTotal').textContent     = stats.total     ?? '--';
    document.getElementById('statWithPhoto').textContent = stats.verified  ?? '--';
    document.getElementById('statWithout').textContent   = stats.pending   ?? '--';
}

function renderList() {
    const list = document.getElementById('productList');

    if (products.length === 0) {
        list.innerHTML = `<div class="flex flex-col items-center justify-center p-8 text-gray-400">
            <div class="text-4xl mb-2">✅</div>
            <p class="text-sm font-medium">Sin productos pendientes</p>
        </div>`;
        return;
    }

    list.innerHTML = products.map(p => {
        const hasPhoto   = !!p.image;
        const isVerified = !!p.photo_verified;
        const isSelected = p.id === selectedId;

        const badge = isVerified
            ? `<span class="text-xs text-green-600">✓ verificada</span>`
            : hasPhoto
                ? `<span class="text-xs text-yellow-600">foto sin verificar</span>`
                : `<span class="text-xs text-gray-400">sin foto</span>`;

        const thumbnail = hasPhoto
            ? `<img src="${imgUrl(p.image)}" class="w-10 h-10 object-cover rounded border" onerror="this.style.display='none'">`
            : `<div class="w-10 h-10 bg-gray-100 rounded border flex items-center justify-center text-gray-300 text-lg">📷</div>`;

        return `<div onclick="selectProduct(${p.id})"
            class="product-item flex items-center gap-3 p-3 cursor-pointer border-b hover:bg-green-50 transition
                   ${isSelected ? 'bg-green-50 border-l-4 border-l-green-600' : ''}">
            ${thumbnail}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold truncate">${p.name}</p>
                <p class="text-xs text-gray-400">${p.sku ?? p.code ?? ''} ${p.brand ? '· ' + p.brand : ''}</p>
                ${badge}
            </div>
        </div>`;
    }).join('');
}

// ─────────────────────────────────────────────────────────────────────────────
// SELECCIONAR PRODUCTO
// ─────────────────────────────────────────────────────────────────────────────
function selectProduct(id) {
    selectedId = id;
    const p = products.find(x => x.id === id);
    if (!p) return;

    // Re-render lista para marcar seleccionado
    renderList();

    // Llenar panel derecho
    document.getElementById('emptyState').classList.add('hidden');
    document.getElementById('productPanel').classList.remove('hidden');

    document.getElementById('prodSku').textContent      = p.sku ?? p.code ?? '';
    document.getElementById('prodName').textContent     = p.name;
    document.getElementById('prodCategory').textContent = p.category ?? '';
    document.getElementById('prodPrice').textContent    = p.sale_price ? `$${parseFloat(p.sale_price).toFixed(2)}` : '';

    const brandBadge = document.getElementById('prodBrand');
    if (p.brand) {
        brandBadge.textContent = p.brand;
        brandBadge.classList.remove('hidden');
    } else {
        brandBadge.classList.add('hidden');
    }

    const verifiedBadge = document.getElementById('prodVerifiedBadge');
    if (p.photo_verified) {
        verifiedBadge.classList.remove('hidden');
    } else {
        verifiedBadge.classList.add('hidden');
    }

    // Foto actual
    const currentPhoto = document.getElementById('currentPhoto');
    const noPhotoMsg   = document.getElementById('noPhotoMsg');
    const btnDelete    = document.getElementById('btnDeletePhoto');

    if (p.image) {
        currentPhoto.src = imgUrl(p.image);
        currentPhoto.classList.remove('hidden');
        noPhotoMsg.classList.add('hidden');
        btnDelete.classList.remove('hidden');
    } else {
        currentPhoto.classList.add('hidden');
        noPhotoMsg.classList.remove('hidden');
        btnDelete.classList.add('hidden');
    }

    // Limpiar preview
    resetPreview();

    // Actualizar barra de progreso
    updateProgress();
}

// ─────────────────────────────────────────────────────────────────────────────
// TABS
// ─────────────────────────────────────────────────────────────────────────────
function setTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.tab-btn').forEach(btn => {
        if (btn.dataset.tab === tab) {
            btn.classList.add('border-green-600', 'text-green-700');
            btn.classList.remove('border-transparent', 'text-gray-500');
        } else {
            btn.classList.remove('border-green-600', 'text-green-700');
            btn.classList.add('border-transparent', 'text-gray-500');
        }
    });
    document.getElementById('tab-url').classList.toggle('hidden', tab !== 'url');
    document.getElementById('tab-upload').classList.toggle('hidden', tab !== 'upload');
    resetPreview();
}

// ─────────────────────────────────────────────────────────────────────────────
// PREVIEW DESDE URL
// ─────────────────────────────────────────────────────────────────────────────
function previewFromUrl() {
    const url = document.getElementById('imageUrl').value.trim();
    if (!url) { showToast('Pega una URL válida', 'error'); return; }

    const preview     = document.getElementById('previewImg');
    const placeholder = document.getElementById('previewPlaceholder');
    const status      = document.getElementById('previewStatus');

    status.classList.remove('hidden');
    status.className = 'mt-2 text-center text-xs text-gray-500';
    status.textContent = 'Cargando imagen...';

    preview.onload = () => {
        placeholder.classList.add('hidden');
        preview.classList.remove('hidden');
        status.className = 'mt-2 text-center text-xs text-green-600';
        status.textContent = '✓ Imagen cargada correctamente';
        previewSource = 'url';
        document.getElementById('btnSave').disabled = false;
    };

    preview.onerror = () => {
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        status.className = 'mt-2 text-center text-xs text-red-500';
        status.textContent = '✗ No se pudo cargar la imagen. Verifica la URL.';
        previewSource = null;
        document.getElementById('btnSave').disabled = true;
    };

    preview.src = url;
}

// ─────────────────────────────────────────────────────────────────────────────
// PREVIEW DESDE ARCHIVO
// ─────────────────────────────────────────────────────────────────────────────
function previewFromFile(event) {
    const file = event.target.files[0];
    if (!file) return;
    handleFile(file);
}

function handleDrop(event) {
    event.preventDefault();
    document.getElementById('dropZone').classList.remove('border-green-500');
    const file = event.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        handleFile(file);
    } else {
        showToast('Solo se admiten archivos de imagen', 'error');
    }
}

function handleFile(file) {
    if (file.size > 5 * 1024 * 1024) {
        showToast('El archivo supera los 5MB', 'error');
        return;
    }

    previewBlob = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        const preview     = document.getElementById('previewImg');
        const placeholder = document.getElementById('previewPlaceholder');
        const status      = document.getElementById('previewStatus');

        preview.src = e.target.result;
        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
        status.classList.remove('hidden');
        status.className = 'mt-2 text-center text-xs text-green-600';
        status.textContent = `✓ ${file.name} (${(file.size / 1024).toFixed(0)} KB)`;

        previewSource = 'file';
        document.getElementById('btnSave').disabled = false;
    };
    reader.readAsDataURL(file);
}

// ─────────────────────────────────────────────────────────────────────────────
// GUARDAR FOTO
// ─────────────────────────────────────────────────────────────────────────────
async function savePhoto() {
    if (!selectedId || !previewSource) return;

    const btn = document.getElementById('btnSave');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
        let res;

        if (previewSource === 'url') {
            const url = document.getElementById('imageUrl').value.trim();
            res = await fetch(`/api/fotos/products/${selectedId}/photo`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ source: 'url', url }),
            });
        } else {
            const formData = new FormData();
            formData.append('photo', previewBlob);
            formData.append('source', 'upload');
            res = await fetch(`/api/fotos/products/${selectedId}/photo`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: formData,
            });
        }

        const data = await res.json();

        if (data.success) {
            showToast('Foto guardada y verificada', 'success');

            // Actualizar foto actual en panel
            const currentPhoto = document.getElementById('currentPhoto');
            const noPhotoMsg   = document.getElementById('noPhotoMsg');
            const btnDelete    = document.getElementById('btnDeletePhoto');
            const badge        = document.getElementById('prodVerifiedBadge');

            currentPhoto.src = imgUrl(data.image_url);
            currentPhoto.classList.remove('hidden');
            noPhotoMsg.classList.add('hidden');
            btnDelete.classList.remove('hidden');
            badge.classList.remove('hidden');

            // Actualizar en array local
            const prod = products.find(x => x.id === selectedId);
            if (prod) {
                prod.image          = data.image_url;
                prod.photo_verified = true;
            }

            renderList();
            resetPreview();

            // Auto-avanzar al siguiente pendiente después de 1.2s
            setTimeout(() => autoAdvance(), 1200);
        } else {
            showToast(data.message ?? 'Error al guardar', 'error');
        }
    } catch (e) {
        showToast('Error de conexión', 'error');
    } finally {
        btn.textContent = 'Guardar foto';
        btn.disabled = !previewSource;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// ELIMINAR FOTO ACTUAL
// ─────────────────────────────────────────────────────────────────────────────
async function deleteCurrentPhoto() {
    if (!selectedId) return;
    if (!confirm('¿Eliminar la foto de este producto?')) return;

    try {
        const res  = await fetch(`/api/fotos/products/${selectedId}/photo`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        });
        const data = await res.json();

        if (data.success) {
            showToast('Foto eliminada', 'success');

            document.getElementById('currentPhoto').classList.add('hidden');
            document.getElementById('noPhotoMsg').classList.remove('hidden');
            document.getElementById('btnDeletePhoto').classList.add('hidden');
            document.getElementById('prodVerifiedBadge').classList.add('hidden');

            const prod = products.find(x => x.id === selectedId);
            if (prod) { prod.image = null; prod.photo_verified = false; }
            renderList();
        } else {
            showToast('Error al eliminar', 'error');
        }
    } catch (e) {
        showToast('Error de conexión', 'error');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// SALTAR AL SIGUIENTE PRODUCTO
// ─────────────────────────────────────────────────────────────────────────────
function skipProduct() {
    autoAdvance();
}

function autoAdvance() {
    const currentIndex = products.findIndex(x => x.id === selectedId);
    const next = products[currentIndex + 1];
    if (next) {
        selectProduct(next.id);
    } else if (currentPage < totalPages) {
        currentPage++;
        loadProducts().then(() => {
            if (products.length > 0) selectProduct(products[0].id);
        });
    } else {
        showToast('Has revisado todos los productos de esta página', 'success');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// UTILIDADES
// ─────────────────────────────────────────────────────────────────────────────
function resetPreview() {
    previewSource = null;
    previewBlob   = null;
    document.getElementById('previewImg').classList.add('hidden');
    document.getElementById('previewImg').src = '';
    document.getElementById('previewPlaceholder').classList.remove('hidden');
    document.getElementById('previewStatus').classList.add('hidden');
    document.getElementById('imageUrl').value = '';
    document.getElementById('btnSave').disabled = true;
    if (document.getElementById('fileInput')) {
        document.getElementById('fileInput').value = '';
    }
}

function updateProgress() {
    const total   = parseInt(document.getElementById('statTotal').textContent)    || 0;
    const verified = parseInt(document.getElementById('statWithPhoto').textContent) || 0;
    const pct     = total > 0 ? Math.round((verified / total) * 100) : 0;
    document.getElementById('progressBar').style.width = pct + '%';
    document.getElementById('progressText').textContent = `${verified} / ${total}`;
}

function updatePagination(current, last, total) {
    document.getElementById('paginationInfo').textContent = `Página ${current} de ${last} (${total} productos)`;
    document.getElementById('btnPrev').disabled = current <= 1;
    document.getElementById('btnNext').disabled = current >= last;
}

function changePage(delta) {
    const newPage = currentPage + delta;
    if (newPage < 1 || newPage > totalPages) return;
    currentPage = newPage;
    loadProducts();
}

function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className   = `fixed bottom-6 right-6 z-50 px-5 py-3 rounded-lg shadow-lg text-white text-sm font-medium max-w-xs
        ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

function debounce(fn, delay) {
    let timer;
    return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
}
</script>

</body>
</html>
