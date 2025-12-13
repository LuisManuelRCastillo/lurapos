<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - GranVM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

    <nav class="bg-white-700 text-white p-4 shadow">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
             <img style="max-width: 100px;" src=" {{ asset('/assets/img/granvn-logosf.png') }}" alt="">
            <h1 style="color: #4A5568;" class="text-lg font-semibold">Administración de Inventario</h1>
            <a href="{{ url('/') }}" class="bg-green-600 text-white px-5 py-2.5 rounded-lg rounded hover:bg-green-700">Regresar a ventas</a>
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
                        <li> {{ $error }}</li>
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
                <input name="code" value="{{ old('code') }}" class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Nombre</label>
                <input name="name" value="{{ old('name') }}" class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Categoría</label>
                <select name="category_id" class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
                    <option value="">Seleccione una categoría</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Tamaño</label>
                <input name="size" value="{{ old('size') }}" class="w-full border rounded p-2 focus:ring focus:ring-green-200">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Stock inicial</label>
                <input type="number" name="stock" value="{{ old('stock', 0) }}" min="0" class="w-full border rounded p-2 focus:ring focus:ring-green-200">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Stock mínimo</label>
                <input type="number" name="min_stock" value="{{ old('min_stock', 5) }}" min="0" class="w-full border rounded p-2 focus:ring focus:ring-green-200">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Precio costo</label>
                <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price') }}" min="0" class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Precio venta</label>
                <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price') }}" min="0" class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-semibold mb-1">Descripción</label>
                <textarea name="description" rows="3" class="w-full border rounded p-2 focus:ring focus:ring-green-200">{{ old('description') }}</textarea>
            </div>

            <div class="col-span-2 flex justify-end">
                <button type="submit" class="bg-green-700 text-white px-6 py-2 rounded hover:bg-green-800 transition">
                    Guardar producto
                </button>
            </div>
        </form>

        {{-- Tabla de productos existentes --}}
        <h2 class="text-2xl font-bold mb-4 text-green-700">Lista de Productos</h2>

        {{-- Filtros y búsqueda --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-semibold mb-1">Buscar</label>
                <input type="text" id="searchInput" placeholder="Código, nombre..." 
                       class="w-full border rounded p-2 focus:ring focus:ring-green-200">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Categoría</label>
                <select id="filterCategory" class="w-full border rounded p-2 focus:ring focus:ring-green-200">
                    <option value="">Todas las categorías</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Stock</label>
                <select id="filterStock" class="w-full border rounded p-2 focus:ring focus:ring-green-200">
                    <option value="">Todos</option>
                    <option value="low">Stock bajo (≤5)</option>
                    <option value="available">Disponible (>5)</option>
                    <option value="zero">Sin stock</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Ordenar por</label>
                <select id="sortBy" class="w-full border rounded p-2 focus:ring focus:ring-green-200">
                    <option value="id-asc">ID (menor a mayor)</option>
                    <option value="id-desc">ID (mayor a menor)</option>
                    <option value="name-asc">Nombre (A-Z)</option>
                    <option value="name-desc">Nombre (Z-A)</option>
                    <option value="stock-asc">Stock (menor a mayor)</option>
                    <option value="stock-desc">Stock (mayor a menor)</option>
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
                        <th class="px-3 py-2 border">Categoría</th>
                        <th class="px-3 py-2 border cursor-pointer hover:bg-green-600" onclick="sortByColumn('stock')">
                            Stock <span id="sort-stock" class="text-xs">↕</span>
                        </th>
                        <th class="px-3 py-2 border cursor-pointer hover:bg-green-600" onclick="sortByColumn('price')">
                            Precio Venta <span id="sort-price" class="text-xs">↕</span>
                        </th>
                        <th class="px-3 py-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    @forelse ($products as $p)
                        <tr class="hover:bg-gray-50 product-row" 
                            data-id="{{ $p->id }}"
                            data-code="{{ $p->code }}"
                            data-name="{{ strtolower($p->name) }}"
                            data-category="{{ $p->category->name ?? '' }}"
                            data-stock="{{ $p->stock }}"
                            data-price="{{ $p->sale_price }}">
                            <td class="border px-3 py-2 text-center">{{ $p->id }}</td>
                            <td class="border px-3 py-2">{{ $p->code }}</td>
                            <td class="border px-3 py-2">{{ $p->name }}</td>
                            <td class="border px-3 py-2">{{ $p->category->name ?? '-' }}</td>
                            <td class="border px-3 py-2 text-center">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($p->stock <= 0) bg-red-100 text-red-700
                                    @elseif($p->stock <= 5) bg-yellow-100 text-yellow-700
                                    @else bg-green-100 text-green-700
                                    @endif">
                                    {{ $p->stock }}
                                </span>
                            </td>
                            <td class="border px-3 py-2 text-right">${{ number_format($p->sale_price, 2) }}</td>
                            <td class="border px-3 py-2 text-center">
                                <button type="button"
                                    class="bg-blue-600 px-5 py-2.5 rounded-lg text-white font-semibold hover:bg-blue-700"
                                    onclick='openEditModal(@json($p))'>
                                    Editar
                                </button>
                                <form action="{{ route('inventory.destroy', $p) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar este producto?')">
                                    @csrf @method('DELETE')
                                    <button class="bg-red-600 px-5 py-2.5 rounded-lg text-white font-semibold hover:bg-red-700 ml-2">Borrar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyRow"><td colspan="7" class="text-center py-4 text-gray-500">No hay productos registrados</td></tr>
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
            <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6">
                <h2 class="text-xl font-semibold mb-4 text-green-700">Editar Producto</h2>

                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="id" id="edit_id">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Código</label>
                            <input type="text" name="code" id="edit_code" class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Nombre</label>
                            <input type="text" name="name" id="edit_name" class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Categoría</label>
                            <select name="category_id" id="edit_category_id" class="w-full border rounded p-2 focus:ring focus:ring-green-200" required>
                                <option value="">Seleccione</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Tamaño</label>
                            <input type="text" name="size" id="edit_size" class="w-full border rounded p-2 focus:ring focus:ring-green-200">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Stock</label>
                            <input type="number" name="stock" id="edit_stock" class="w-full border rounded p-2 focus:ring focus:ring-green-200" min="0">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Precio costo</label>
                            <input type="number" step="0.01" name="cost_price" id="edit_cost_price" class="w-full border rounded p-2 focus:ring focus:ring-green-200" min="0">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Precio venta</label>
                            <input type="number" step="0.01" name="sale_price" id="edit_sale_price" class="w-full border rounded p-2 focus:ring focus:ring-green-200" min="0">
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">
                            Cancelar
                        </button>
                        <button type="submit" class="bg-green-700 text-white px-6 py-2 rounded hover:bg-green-800">
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
    const form = document.getElementById('editForm');

    function openEditModal(product) {
        modal.classList.remove('hidden');

        document.getElementById('edit_id').value = product.id;
        document.getElementById('edit_code').value = product.code;
        document.getElementById('edit_name').value = product.name;
        document.getElementById('edit_category_id').value = product.category_id;
        document.getElementById('edit_size').value = product.size ?? '';
        document.getElementById('edit_stock').value = product.stock ?? 0;
        document.getElementById('edit_cost_price').value = product.cost_price ?? 0;
        document.getElementById('edit_sale_price').value = product.sale_price ?? 0;

        form.action = "{{ route('inventory.update', ':id') }}".replace(':id', product.id);
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    // ===== FILTROS Y ORDENAMIENTO =====
    const searchInput = document.getElementById('searchInput');
    const filterCategory = document.getElementById('filterCategory');
    const filterStock = document.getElementById('filterStock');
    const sortBy = document.getElementById('sortBy');
    const tableBody = document.getElementById('productsTableBody');
    const noResults = document.getElementById('noResults');

    let allRows = Array.from(document.querySelectorAll('.product-row'));
    let currentSort = { field: null, order: 'asc' };

    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = filterCategory.value.toLowerCase();
        const selectedStock = filterStock.value;

        let visibleCount = 0;

        allRows.forEach(row => {
            const code = row.dataset.code.toLowerCase();
            const name = row.dataset.name;
            const category = row.dataset.category.toLowerCase();
            const stock = parseInt(row.dataset.stock);

            // Filtro de búsqueda
            const matchesSearch = code.includes(searchTerm) || name.includes(searchTerm);

            // Filtro de categoría
            const matchesCategory = !selectedCategory || category === selectedCategory;

            // Filtro de stock
            let matchesStock = true;
            if (selectedStock === 'low') matchesStock = stock > 0 && stock <= 5;
            else if (selectedStock === 'available') matchesStock = stock > 5;
            else if (selectedStock === 'zero') matchesStock = stock === 0;

            if (matchesSearch && matchesCategory && matchesStock) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Mostrar mensaje si no hay resultados
        if (visibleCount === 0) {
            noResults.classList.remove('hidden');
            tableBody.parentElement.parentElement.classList.add('hidden');
        } else {
            noResults.classList.add('hidden');
            tableBody.parentElement.parentElement.classList.remove('hidden');
        }
    }

    function sortTable() {
        const [field, order] = sortBy.value.split('-');
        currentSort = { field, order };
        
        allRows.sort((a, b) => {
            let valA, valB;

            switch(field) {
                case 'id':
                    valA = parseInt(a.dataset.id);
                    valB = parseInt(b.dataset.id);
                    break;
                case 'name':
                    valA = a.dataset.name;
                    valB = b.dataset.name;
                    break;
                case 'stock':
                    valA = parseInt(a.dataset.stock);
                    valB = parseInt(b.dataset.stock);
                    break;
                case 'price':
                    valA = parseFloat(a.dataset.price);
                    valB = parseFloat(b.dataset.price);
                    break;
            }

            if (order === 'asc') {
                return valA > valB ? 1 : -1;
            } else {
                return valA < valB ? 1 : -1;
            }
        });

        // Reordenar en el DOM
        allRows.forEach(row => tableBody.appendChild(row));
        updateSortIndicators(field, order);
        applyFilters();
    }

    // Ordenar al hacer clic en las columnas
    function sortByColumn(field) {
        // Si es la misma columna, alternar orden
        if (currentSort.field === field) {
            currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.field = field;
            currentSort.order = 'asc';
        }

        allRows.sort((a, b) => {
            let valA, valB;

            switch(field) {
                case 'id':
                    valA = parseInt(a.dataset.id);
                    valB = parseInt(b.dataset.id);
                    break;
                case 'name':
                    valA = a.dataset.name;
                    valB = b.dataset.name;
                    break;
                case 'stock':
                    valA = parseInt(a.dataset.stock);
                    valB = parseInt(b.dataset.stock);
                    break;
                case 'price':
                    valA = parseFloat(a.dataset.price);
                    valB = parseFloat(b.dataset.price);
                    break;
            }

            if (currentSort.order === 'asc') {
                return valA > valB ? 1 : -1;
            } else {
                return valA < valB ? 1 : -1;
            }
        });

        allRows.forEach(row => tableBody.appendChild(row));
        updateSortIndicators(field, currentSort.order);
        
        // Actualizar también el select
        sortBy.value = `${field}-${currentSort.order}`;
        applyFilters();
    }

    // Actualizar indicadores visuales de ordenamiento
    function updateSortIndicators(field, order) {
        // Resetear todos los indicadores
        ['id', 'name', 'stock', 'price'].forEach(col => {
            const indicator = document.getElementById(`sort-${col}`);
            if (indicator) {
                indicator.textContent = '↕';
                indicator.classList.remove('font-bold');
            }
        });

        // Activar el indicador actual
        const activeIndicator = document.getElementById(`sort-${field}`);
        if (activeIndicator) {
            activeIndicator.textContent = order === 'asc' ? '↑' : '↓';
            activeIndicator.classList.add('font-bold');
        }
    }

    // Event listeners
    searchInput.addEventListener('input', applyFilters);
    filterCategory.addEventListener('change', applyFilters);
    filterStock.addEventListener('change', applyFilters);
    sortBy.addEventListener('change', sortTable);
</script>

</body>
</html>