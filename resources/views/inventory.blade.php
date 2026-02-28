<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LuraPos - Inventario</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

    <nav class="bg-white text-white p-4 shadow">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <img style="max-width: 100px;" src="{{ asset('/assets/img/logoSF.png') }}" alt="">
            <h1 style="color: #4A5568;" class="text-lg font-semibold">Administración de Inventario</h1>
            <div class="flex gap-3">
                <a href="{{ route('fotos.view') }}" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700">
                    📷 Gestión de Fotos
                </a>
                <a href="{{ url('/') }}" class="bg-green-600 text-white px-5 py-2.5 rounded-lg hover:bg-green-700">Regresar a ventas</a>
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto mt-8 p-6 bg-white shadow-lg rounded">

        {{-- Mensajes --}}
        @if (session('success'))
            <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-2 rounded mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Formulario de nuevo producto --}}
        <h2 class="text-2xl font-bold mb-6 text-green-700">Nuevo Producto</h2>

        <form action="{{ route('inventory.store') }}" method="POST" class="grid grid-cols-2 gap-4 mb-10">
            @csrf

            <div>
                <label class="block text-sm font-semibold mb-1">Código</label>
                <input name="codigo" value="{{ old('codigo') }}"
                       class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Nombre / Descripción</label>
                <input name="producto" value="{{ old('producto') }}"
                       class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Departamento</label>
                <input name="dpto" value="{{ old('dpto') }}" list="dpto-list"
                       class="w-full border rounded p-2 focus:ring focus:ring-green-200"
                       placeholder="Ej: FERRETERIA, PLOMERIA...">
                <datalist id="dpto-list">
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}">
                    @endforeach
                </datalist>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Existencia inicial</label>
                <input type="number" name="existencia" value="{{ old('existencia', 0) }}" min="0"
                       class="w-full border rounded p-2 focus:ring focus:ring-green-200">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Inv. mínimo</label>
                <input type="number" name="inv_min" value="{{ old('inv_min', 1) }}" min="0"
                       class="w-full border rounded p-2 focus:ring focus:ring-green-200">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Inv. máximo</label>
                <input type="number" name="inv_max" value="{{ old('inv_max', 10) }}" min="0"
                       class="w-full border rounded p-2 focus:ring focus:ring-green-200">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Precio costo</label>
                <input type="number" step="0.01" name="p_costo" value="{{ old('p_costo') }}" min="0"
                       class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Precio venta (público)</label>
                <input type="number" step="0.01" name="p_venta" value="{{ old('p_venta') }}" min="0"
                       class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Precio mayoreo</label>
                <input type="number" step="0.01" name="p_mayoreo" value="{{ old('p_mayoreo', 0) }}" min="0"
                       class="w-full border rounded p-2 focus:ring focus:ring-green-200">
            </div>

            <div class="col-span-2 flex justify-end">
                <button type="submit" class="bg-green-700 text-white px-6 py-2 rounded hover:bg-green-800 transition">
                    Guardar producto
                </button>
            </div>
        </form>

        {{-- Tabla de productos existentes --}}
        <h2 class="text-2xl font-bold mb-4 text-green-700">
            Lista de Productos
            <span class="text-base font-normal text-gray-500">({{ $products->count() }} registros)</span>
        </h2>

        {{-- Filtros --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-semibold mb-1">Buscar</label>
                <input type="text" id="searchInput" placeholder="Código, nombre..."
                       class="w-full border rounded p-2 focus:ring focus:ring-green-200">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Departamento</label>
                <select id="filterCategory" class="w-full border rounded p-2 focus:ring focus:ring-green-200">
                    <option value="">Todos</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Stock</label>
                <select id="filterStock" class="w-full border rounded p-2 focus:ring focus:ring-green-200">
                    <option value="">Todos</option>
                    <option value="low">Stock bajo (≤ mínimo)</option>
                    <option value="available">Con existencia</option>
                    <option value="zero">Sin existencia</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Ordenar por</label>
                <select id="sortBy" class="w-full border rounded p-2 focus:ring focus:ring-green-200">
                    <option value="id-asc">ID (menor a mayor)</option>
                    <option value="id-desc">ID (mayor a menor)</option>
                    <option value="name-asc">Nombre (A-Z)</option>
                    <option value="name-desc">Nombre (Z-A)</option>
                    <option value="stock-asc">Existencia (menor a mayor)</option>
                    <option value="stock-desc">Existencia (mayor a menor)</option>
                    <option value="price-asc">Precio (menor a mayor)</option>
                    <option value="price-desc">Precio (mayor a menor)</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300 text-sm">
                <thead class="bg-green-700 text-white">
                    <tr>
                        <th class="px-3 py-2 border cursor-pointer hover:bg-green-600" onclick="sortByColumn('id')">
                            ID <span id="sort-id" class="text-xs">↕</span>
                        </th>
                        <th class="px-3 py-2 border">Código</th>
                        <th class="px-3 py-2 border cursor-pointer hover:bg-green-600" onclick="sortByColumn('name')">
                            Nombre <span id="sort-name" class="text-xs">↕</span>
                        </th>
                        <th class="px-3 py-2 border">Depto.</th>
                        <th class="px-3 py-2 border cursor-pointer hover:bg-green-600" onclick="sortByColumn('stock')">
                            Existencia <span id="sort-stock" class="text-xs">↕</span>
                        </th>
                        <th class="px-3 py-2 border cursor-pointer hover:bg-green-600" onclick="sortByColumn('price')">
                            P. Venta <span id="sort-price" class="text-xs">↕</span>
                        </th>
                        <th class="px-3 py-2 border">P. Mayoreo</th>
                        <th class="px-3 py-2 border">Foto</th>
                        <th class="px-3 py-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    @forelse ($products as $p)
                        <tr class="hover:bg-gray-50 product-row"
                            data-id="{{ $p->id }}"
                            data-code="{{ $p->codigo }}"
                            data-name="{{ strtolower(trim($p->producto)) }}"
                            data-category="{{ strtolower($p->dpto ?? '') }}"
                            data-stock="{{ $p->existencia }}"
                            data-minstock="{{ $p->inv_min }}"
                            data-price="{{ $p->p_venta }}">

                            <td class="border px-3 py-2 text-center text-gray-400 text-xs">{{ $p->id }}</td>
                            <td class="border px-3 py-2 font-mono text-xs">{{ $p->codigo }}</td>
                            <td class="border px-3 py-2">{{ trim($p->producto) }}</td>
                            <td class="border px-3 py-2 text-xs">{{ $p->dpto ?? '-' }}</td>
                            <td class="border px-3 py-2 text-center">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($p->existencia <= 0) bg-red-100 text-red-700
                                    @elseif($p->existencia <= $p->inv_min) bg-yellow-100 text-yellow-700
                                    @else bg-green-100 text-green-700
                                    @endif">
                                    {{ $p->existencia }}
                                </span>
                            </td>
                            <td class="border px-3 py-2 text-right">${{ number_format($p->p_venta, 2) }}</td>
                            <td class="border px-3 py-2 text-right">
                                @if($p->p_mayoreo > 0)
                                    ${{ number_format($p->p_mayoreo, 2) }}
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                            <td class="border px-3 py-2 text-center">
                                @if($p->image)
                                    <img src="{{ $p->image }}" class="h-8 w-8 object-cover rounded mx-auto"
                                         title="{{ $p->photo_verified ? 'Verificada' : 'Sin verificar' }}">
                                @else
                                    <span class="text-gray-300 text-lg">📷</span>
                                @endif
                            </td>
                            <td class="border px-3 py-2 text-center whitespace-nowrap">
                                <button type="button"
                                    class="bg-blue-600 px-3 py-1.5 rounded text-white text-xs font-semibold hover:bg-blue-700"
                                    onclick='openEditModal(@json($p))'>
                                    Editar
                                </button>
                                <form action="{{ route('inventory.destroy', $p) }}" method="POST"
                                      class="inline-block"
                                      onsubmit="return confirm('¿Eliminar este producto?')">
                                    @csrf @method('DELETE')
                                    <button class="bg-red-600 px-3 py-1.5 rounded text-white text-xs font-semibold hover:bg-red-700 ml-1">
                                        Borrar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyRow">
                            <td colspan="9" class="text-center py-4 text-gray-500">No hay productos registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="noResults" class="hidden text-center py-8 text-gray-500">
            <div class="text-6xl mb-2">🔍</div>
            <p class="text-lg font-semibold">No se encontraron productos</p>
        </div>

        <!-- Modal para editar producto -->
        <div id="editModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-xl p-6 max-h-screen overflow-y-auto">
                <h2 class="text-xl font-semibold mb-4 text-green-700">Editar Producto</h2>

                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Código</label>
                            <input type="text" name="codigo" id="edit_codigo"
                                   class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Nombre / Descripción</label>
                            <input type="text" name="producto" id="edit_producto"
                                   class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Departamento</label>
                            <input type="text" name="dpto" id="edit_dpto" list="dpto-list"
                                   class="w-full border rounded p-2 focus:ring focus:ring-green-200">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Existencia</label>
                            <input type="number" name="existencia" id="edit_existencia"
                                   class="w-full border rounded p-2 focus:ring focus:ring-green-200" min="0">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Inv. mínimo</label>
                            <input type="number" name="inv_min" id="edit_inv_min"
                                   class="w-full border rounded p-2 focus:ring focus:ring-green-200" min="0">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Inv. máximo</label>
                            <input type="number" name="inv_max" id="edit_inv_max"
                                   class="w-full border rounded p-2 focus:ring focus:ring-green-200" min="0">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Precio costo</label>
                            <input type="number" step="0.01" name="p_costo" id="edit_p_costo"
                                   class="w-full border rounded p-2 focus:ring focus:ring-green-200" min="0">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Precio venta</label>
                            <input type="number" step="0.01" name="p_venta" id="edit_p_venta"
                                   class="w-full border rounded p-2 focus:ring focus:ring-green-200" min="0">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-semibold mb-1">Precio mayoreo</label>
                            <input type="number" step="0.01" name="p_mayoreo" id="edit_p_mayoreo"
                                   class="w-full border rounded p-2 focus:ring focus:ring-green-200" min="0">
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()"
                                class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="bg-green-700 text-white px-6 py-2 rounded hover:bg-green-800">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>

    <footer class="text-center text-sm text-gray-500 py-6">
        © {{ date('Y') }} LuraDev. Todos los derechos reservados.
    </footer>

<script>
    const modal = document.getElementById('editModal');
    const form  = document.getElementById('editForm');

    function openEditModal(product) {
        modal.classList.remove('hidden');

        document.getElementById('edit_codigo').value    = product.codigo    ?? '';
        document.getElementById('edit_producto').value  = product.producto  ?? '';
        document.getElementById('edit_dpto').value      = product.dpto      ?? '';
        document.getElementById('edit_existencia').value= product.existencia ?? 0;
        document.getElementById('edit_inv_min').value   = product.inv_min   ?? 0;
        document.getElementById('edit_inv_max').value   = product.inv_max   ?? 0;
        document.getElementById('edit_p_costo').value   = product.p_costo   ?? 0;
        document.getElementById('edit_p_venta').value   = product.p_venta   ?? 0;
        document.getElementById('edit_p_mayoreo').value = product.p_mayoreo ?? 0;

        form.action = "{{ route('inventory.update', ':id') }}".replace(':id', product.id);
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    // ===== FILTROS Y ORDENAMIENTO =====
    const searchInput    = document.getElementById('searchInput');
    const filterCategory = document.getElementById('filterCategory');
    const filterStock    = document.getElementById('filterStock');
    const sortBy         = document.getElementById('sortBy');
    const tableBody      = document.getElementById('productsTableBody');
    const noResults      = document.getElementById('noResults');

    let allRows      = Array.from(document.querySelectorAll('.product-row'));
    let currentSort  = { field: null, order: 'asc' };

    function applyFilters() {
        const searchTerm       = searchInput.value.toLowerCase();
        const selectedCategory = filterCategory.value.toLowerCase();
        const selectedStock    = filterStock.value;

        let visibleCount = 0;

        allRows.forEach(row => {
            const code     = row.dataset.code.toLowerCase();
            const name     = row.dataset.name;
            const category = row.dataset.category;
            const stock    = parseInt(row.dataset.stock);
            const minStock = parseInt(row.dataset.minstock);

            const matchesSearch   = code.includes(searchTerm) || name.includes(searchTerm);
            const matchesCategory = !selectedCategory || category === selectedCategory;

            let matchesStock = true;
            if (selectedStock === 'low')       matchesStock = stock > 0 && stock <= minStock;
            else if (selectedStock === 'available') matchesStock = stock > 0;
            else if (selectedStock === 'zero') matchesStock = stock === 0;

            if (matchesSearch && matchesCategory && matchesStock) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        noResults.classList.toggle('hidden', visibleCount > 0);
        tableBody.parentElement.parentElement.classList.toggle('hidden', visibleCount === 0);
    }

    function sortByColumn(field) {
        currentSort.order = (currentSort.field === field && currentSort.order === 'asc') ? 'desc' : 'asc';
        currentSort.field = field;

        allRows.sort((a, b) => {
            let valA, valB;
            switch (field) {
                case 'id':    valA = parseInt(a.dataset.id);    valB = parseInt(b.dataset.id);    break;
                case 'name':  valA = a.dataset.name;            valB = b.dataset.name;            break;
                case 'stock': valA = parseInt(a.dataset.stock); valB = parseInt(b.dataset.stock); break;
                case 'price': valA = parseFloat(a.dataset.price); valB = parseFloat(b.dataset.price); break;
            }
            return currentSort.order === 'asc' ? (valA > valB ? 1 : -1) : (valA < valB ? 1 : -1);
        });

        allRows.forEach(row => tableBody.appendChild(row));
        updateSortIndicators(field, currentSort.order);
        sortBy.value = `${field}-${currentSort.order}`;
        applyFilters();
    }

    function sortTable() {
        const [field, order] = sortBy.value.split('-');
        currentSort = { field, order };
        allRows.sort((a, b) => {
            let valA, valB;
            switch (field) {
                case 'id':    valA = parseInt(a.dataset.id);    valB = parseInt(b.dataset.id);    break;
                case 'name':  valA = a.dataset.name;            valB = b.dataset.name;            break;
                case 'stock': valA = parseInt(a.dataset.stock); valB = parseInt(b.dataset.stock); break;
                case 'price': valA = parseFloat(a.dataset.price); valB = parseFloat(b.dataset.price); break;
            }
            return order === 'asc' ? (valA > valB ? 1 : -1) : (valA < valB ? 1 : -1);
        });
        allRows.forEach(row => tableBody.appendChild(row));
        updateSortIndicators(field, order);
        applyFilters();
    }

    function updateSortIndicators(field, order) {
        ['id', 'name', 'stock', 'price'].forEach(col => {
            const el = document.getElementById(`sort-${col}`);
            if (el) { el.textContent = '↕'; el.classList.remove('font-bold'); }
        });
        const active = document.getElementById(`sort-${field}`);
        if (active) { active.textContent = order === 'asc' ? '↑' : '↓'; active.classList.add('font-bold'); }
    }

    searchInput.addEventListener('input', applyFilters);
    filterCategory.addEventListener('change', applyFilters);
    filterStock.addEventListener('change', applyFilters);
    sortBy.addEventListener('change', sortTable);

    // ─────────────────────────────────────────────────────────────────────
    // LECTOR DE CÓDIGO DE BARRAS
    // ─────────────────────────────────────────────────────────────────────
    (function initBarcodeScanner() {
        const MIN_LENGTH   = 4;
        const MAX_INTERVAL = 60;   // ms máximos entre teclas para considerar scanner
        const DONE_TIMEOUT = 80;   // ms sin tecla → procesar

        let buffer      = '';
        let lastKeyTime = 0;
        let doneTimer   = null;

        function toast(msg, ok = true) {
            let t = document.getElementById('scanToast');
            if (!t) {
                t = document.createElement('div');
                t.id = 'scanToast';
                t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);' +
                    'z-index:9999;padding:10px 22px;border-radius:999px;font-size:14px;' +
                    'font-weight:600;color:#fff;box-shadow:0 4px 16px rgba(0,0,0,.25);transition:opacity .3s';
                document.body.appendChild(t);
            }
            t.style.background = ok ? '#16a34a' : '#dc2626';
            t.textContent      = msg;
            t.style.opacity    = '1';
            clearTimeout(t._timer);
            t._timer = setTimeout(() => { t.style.opacity = '0'; }, 2500);
        }

        function processScan(code) {
            code = code.trim();
            if (code.length < MIN_LENGTH) return;

            // Llenar el buscador y filtrar la tabla
            searchInput.value = code;
            applyFilters();

            const visible = document.querySelectorAll('.product-row:not([style*="display: none"])');
            if (visible.length === 1) {
                visible[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                visible[0].classList.add('bg-yellow-50', 'outline', 'outline-2', 'outline-yellow-400');
                setTimeout(() => {
                    visible[0].classList.remove('bg-yellow-50', 'outline', 'outline-2', 'outline-yellow-400');
                }, 2500);
                toast('✓ Producto encontrado');
            } else if (visible.length === 0) {
                toast('⚠ Código no encontrado: ' + code, false);
            } else {
                toast('🔍 ' + visible.length + ' coincidencias');
            }
        }

        document.addEventListener('keydown', (e) => {
            const now      = Date.now();
            const interval = now - lastKeyTime;
            lastKeyTime    = now;

            // Resetear buffer si el intervalo es demasiado largo (tipeo manual)
            if (interval > MAX_INTERVAL && buffer.length > 0) buffer = '';

            if (['Shift','Control','Alt','Meta','CapsLock','Tab','Escape'].includes(e.key)) return;

            const active     = document.activeElement;
            const isAnyInput = active && ['INPUT','TEXTAREA','SELECT'].includes(active.tagName);

            if (e.key === 'Enter') {
                clearTimeout(doneTimer);
                // Si está en el campo código o producto del form → no interceptar
                // (el scanner llena el campo y Enter mueve al siguiente o envía)
                if (isAnyInput && active !== searchInput) {
                    // Mover foco al siguiente campo si está en código
                    if (active.name === 'codigo' && buffer.length >= MIN_LENGTH) {
                        toast('📷 Código escaneado: ' + (active.value || buffer));
                    }
                    buffer = '';
                    return;
                }
                if (buffer.length >= MIN_LENGTH) {
                    e.preventDefault();
                    processScan(buffer);
                }
                buffer = '';
                return;
            }

            if (e.key.length !== 1) return;

            // Si el foco está en cualquier input → el scanner escribe ahí directamente
            // No capturamos globalmente para no duplicar
            if (isAnyInput) {
                // Pero sí mostramos el toast si está en el campo código del formulario
                if (active.name === 'codigo') {
                    clearTimeout(doneTimer);
                    doneTimer = setTimeout(() => {
                        if (active.value.length >= MIN_LENGTH) {
                            toast('📷 Código listo: ' + active.value);
                            // Mover foco al nombre del producto
                            const next = document.querySelector('input[name="producto"]');
                            if (next) next.focus();
                        }
                    }, DONE_TIMEOUT + 20);
                }
                buffer = '';
                return;
            }

            // Sin foco en ningún input → captura global (scanner "en el aire")
            buffer += e.key;
            clearTimeout(doneTimer);
            doneTimer = setTimeout(() => {
                if (buffer.length >= MIN_LENGTH) processScan(buffer);
                buffer = '';
            }, DONE_TIMEOUT);
        });

        // Al hacer clic en el buscador manualmente → resetear buffer
        searchInput.addEventListener('focus', () => { buffer = ''; });
    })();
</script>

</body>
</html>
