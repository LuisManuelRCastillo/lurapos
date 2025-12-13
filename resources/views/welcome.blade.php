<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>POS - Selección de Productos</title>

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body { 
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif; 
        background: #f9fafb;
        /* Importante: permitir scroll en body para móviles */
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    .scrollbar-hidden::-webkit-scrollbar { display: none; }
    
    /* Asegurar que el contenedor principal permita scroll en móvil */
    @media (max-width: 768px) {
        .main-container {
            height: auto !important;
            min-height: 100vh;
            overflow-y: visible !important;
        }
        
        .products-grid {
            max-height: none !important;
            overflow-y: visible !important;
        }
    }
</style>
</head>
<body>
<div class="main-container flex flex-col md:flex-row md:h-screen md:overflow-hidden">

    <!-- Panel Productos -->
    <div class="flex-1 flex flex-col p-4">
        <div class="relative flex items-center mb-4">
            <img style="max-width: 50px;" src="{{ asset('/assets/img/granvn-logosf.png') }}" alt="">
            <div id="branchesContainer" class="absolute left-1/2 transform -translate-x-1/2"></div>
        </div>

        <!-- Buscador -->
        <div class="relative mb-4">
            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xl">🔍</span>
            <input id="searchInput" type="text" placeholder="Buscar producto..."
                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500">
        </div>

        <!-- Categorías -->
        <div id="categoriesContainer" class="flex gap-2 overflow-x-auto mb-4 scrollbar-hidden"></div>

        <!-- Productos -->
        <div id="productsContainer" class="products-grid flex-1 md:overflow-y-auto grid grid-cols-2 md:grid-cols-4 gap-4 pb-20 md:pb-4"></div>
    </div>

</div>

<!-- Botón flotante del carrito -->
<button id="cartBtn" class="fixed top-4 right-4 bg-green-600 text-white p-3 rounded-full shadow-lg hover:bg-green-700 z-50">
    🛒 <span id="cartCount">0</span>
</button>

<!-- Modal del carrito -->
<div id="cartModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-40">
    <div class="bg-white w-96 max-w-[90vw] p-6 rounded-lg relative max-h-[90vh] overflow-y-auto">
        <button id="closeCart" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800 font-bold">&times;</button>
        <h2 class="text-xl font-bold mb-4">Carrito</h2>
        <div id="cartItems" class="space-y-2 max-h-64 overflow-y-auto scrollbar-hidden"></div>
        <div id="cartSummaryModal" class="mt-4 font-bold"></div>
        
        <button id="checkoutModalBtn" class="mt-4 w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
            Ir a Checkout
        </button>
    </div>
</div>

<script>
const API_URL = "{{ url('https://luradev.com/gvnmetepec/api/pos') }}";
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

let products = [];
let categories = ['Todas'];
let cart = JSON.parse(localStorage.getItem('cart') || '[]');
let selectedCategory = 'Todas';
let searchTerm = '';

const productsContainer = document.getElementById('productsContainer');
const categoriesContainer = document.getElementById('categoriesContainer');
const branchesContainer = document.getElementById('branchesContainer');
const cartBtn = document.getElementById('cartBtn');
const cartModal = document.getElementById('cartModal');
const closeCart = document.getElementById('closeCart');
const cartItemsModal = document.getElementById('cartItems');
const cartSummaryModal = document.getElementById('cartSummaryModal');
const cartCount = document.getElementById('cartCount');
const checkoutModalBtn = document.getElementById('checkoutModalBtn');

// ======== API ========
async function fetchAPI(endpoint, options = {}) {
    const res = await fetch(`${API_URL}${endpoint}`, {
        ...options,
        headers: {
            'Content-Type':'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept':'application/json',
            ...options.headers
        }
    });
    if(!res.ok) throw new Error(await res.text());
    return res.json();
}
async function loadBranches() {
    try {
        const data = await fetchAPI('/branches');
        branches = [ ...data];  
        renderBranches();
      
    } catch(e) { console.error(e); }
}
// Cargar categorías
async function loadCategories() {
    try {
        const data = await fetchAPI('/categories');
        categories = [ ...data];
        renderCategories();
    } catch(e) { console.error(e); }
}

// Cargar productos
async function loadProducts() {
    try {
        const params = new URLSearchParams();
        if(searchTerm) params.append('search', searchTerm);
        if(selectedCategory !== 'Todas') params.append('category', selectedCategory);
        const data = await fetchAPI(`/products?${params}`);
        products = data;
        renderProducts();
    } catch(e) { console.error(e); }
}
let branches = []; // Aquí van las sucursales
let selectedBranch = null;
function renderBranches() {
    const branchesContainer = document.getElementById('branchesContainer');
    branchesContainer.innerHTML = '';

    branches.forEach(branch => {
        const btn = document.createElement('button');
        btn.textContent = branch.name;
        btn.className = `px-4 py-2 rounded-lg font-semibold transition-all ${
            selectedBranch === branch.id ? 'bg-green-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        }`;
        btn.onclick = () => {
            selectedBranch = branch.id;
          localStorage.setItem('selectedBranchId', branch.id);     
    localStorage.setItem('selectedBranchName', branch.name); 
            renderBranches();
        };
        branchesContainer.appendChild(btn);
    });
}



function renderCategories() {
    categoriesContainer.innerHTML = '';
    categories.forEach(cat => {
        const btn = document.createElement('button');
        btn.textContent = cat;
        btn.className = `px-4 py-2 rounded-lg font-semibold transition-all ${
            selectedCategory===cat ? 'bg-green-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        }`;
        btn.onclick = () => { selectedCategory=cat; loadProducts(); renderCategories(); };
        categoriesContainer.appendChild(btn);
    });
}


function renderProducts() {
    productsContainer.innerHTML = '';
    if(!products.length){
        productsContainer.innerHTML = `<div class="col-span-full text-center py-20 text-gray-400">
            <div class="text-8xl mb-4">🔍</div>
            <p class="text-xl font-semibold">No se encontraron productos</p>
        </div>`;
        return;
    }
    products.forEach(p => {
        const div = document.createElement('div');
        div.className = "bg-white rounded-xl shadow-md p-4 cursor-pointer hover:shadow-2xl transition-all hover:scale-105 border-2 border-transparent hover:border-green-400";
        div.innerHTML = `
            <p class="text-xs text-gray-500 mb-1">${p.code}</p>
            <h3 class="font-bold text-sm mb-2">${p.name}</h3>
            <div class="flex justify-between items-center pt-2">
                <span class="text-lg font-bold text-green-600">${p.price.toFixed(2)}</span>
                <span class="text-xs px-2 py-1 rounded-full ${p.stock<=5?'bg-red-100 text-red-700':'bg-green-100 text-green-700'}">${p.stock} 📦</span>
            </div>`;
        div.onclick = () => addToCart(p);
        productsContainer.appendChild(div);
    });
}


function addToCart(product) {
    const existing = cart.find(i => i.id===product.id);
    if(existing) existing.quantity++;
    else cart.push({...product, quantity:1});
    localStorage.setItem('cart', JSON.stringify(cart));
    renderCartModal();
}

function renderCartModal() {
    cartItemsModal.innerHTML = '';
    let subtotal = 0;

    if(cart.length === 0){
        cartItemsModal.innerHTML = '<p class="text-gray-400 text-center py-4">Carrito vacío</p>';
        cartSummaryModal.innerHTML = '';
        cartCount.innerText = '0';
        return;
    }

    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;

        const div = document.createElement('div');
        div.className = "flex justify-between items-center bg-gray-100 p-2 rounded-lg";
        div.innerHTML = `
            <div class="flex items-center gap-2">
                <button class="bg-gray-200 w-8 h-8 rounded-lg font-bold text-lg">−</button>
                <span class="font-bold text-center w-6">${item.quantity}</span>
                <button class="bg-green-600 text-white w-8 h-8 rounded-lg font-bold text-lg">+</button>
                <span class="ml-2">${item.name}</span>
            </div>
            <span>$${itemTotal.toFixed(2)}</span>
        `;

        div.children[0].children[0].onclick = () => {
            if(item.quantity > 1) item.quantity--;
            else cart = cart.filter(i => i.id !== item.id);
            localStorage.setItem('cart', JSON.stringify(cart));
            renderCartModal();
        };


        div.children[0].children[2].onclick = () => {
            if(item.quantity < item.stock) item.quantity++;
            else Swal.fire('⚠️ Stock máximo alcanzado','','warning');
            localStorage.setItem('cart', JSON.stringify(cart));
            renderCartModal();
        };

        cartItemsModal.appendChild(div);
    });

    cartSummaryModal.innerHTML = `Subtotal: $${subtotal.toFixed(2)}`;
    cartCount.innerText = cart.length;
}


cartBtn.onclick = () => {
    renderCartModal();
    cartModal.classList.remove('hidden');
    cartModal.classList.add('flex');
};

closeCart.onclick = () => {
    cartModal.classList.remove('flex');
    cartModal.classList.add('hidden');
};

checkoutModalBtn.onclick = () => {
    localStorage.setItem('cart', JSON.stringify(cart));
    window.location.href = "{{ url('/checkout') }}";
};


document.getElementById('searchInput').oninput = e => {
    clearTimeout(window.searchTimer);
    searchTerm = e.target.value;
    window.searchTimer = setTimeout(loadProducts, 400);
};

loadBranches(); 
loadCategories();
loadProducts();
renderCartModal();
</script>
</body>
</html>
