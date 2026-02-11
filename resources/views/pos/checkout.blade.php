<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>LuraPos - Checkout</title>

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif; background: #f9fafb; }
</style>
</head>
<body>
<div class="max-w-md mx-auto p-6 bg-white shadow-lg mt-10 rounded-lg">
    <h2 class="text-2xl font-bold mb-4">Checkout</h2><label> <p id="selectedBranch"></p></label>


    <!-- Resumen carrito -->
    <div id="cartSummary" class="mb-4 space-y-2"></div>


    <!-- Cliente -->
    <input id="customerName" type="text" placeholder="Nombre cliente (opcional)" class="w-full p-3 mb-3 border rounded-lg">
    <input id="customerEmail" type="email" placeholder="Email cliente (opcional)" class="w-full p-3 mb-3 border rounded-lg">

    <!-- Método de pago -->
    <select id="paymentMethod" class="w-full p-3 mb-3 border rounded-lg">
        <option value="efectivo">Efectivo</option>
        <option value="tarjeta">Tarjeta</option>
        <option value="transferencia">Transferencia</option>
    </select>

    <!-- Monto recibido -->
    <input id="amountPaid" type="number" placeholder="Monto recibido" class="w-full p-3 mb-3 border rounded-lg">

    <!-- Descuentos -->
    <div class="mb-4 flex gap-2">
        <button class="discount-btn px-4 py-2 bg-gray-100 rounded-lg font-bold hover:bg-gray-200" data-discount="0.05">5% </button>
        <button class="discount-btn px-4 py-2 bg-gray-100 rounded-lg font-bold hover:bg-gray-200" data-discount="0.10">10% </button>
        <!-- <button class="discount-btn px-4 py-2 bg-gray-100 rounded-lg font-bold hover:bg-gray-200" data-discount="0.15">15% </button> -->
    </div>

    <!-- Totales -->
    <div id="summaryContainer" class="mb-4 space-y-1 font-bold"></div>

    <button id="processSaleBtn" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700">
        Procesar Venta
    </button>
</div>

<script>
const API_URL = "/api/pos";
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

// Recuperar carrito del localStorage
let cart = JSON.parse(localStorage.getItem('cart') || '[]');
let discount = 0;
const selectedBranchId = localStorage.getItem('selectedBranchId') || null;
const selectedBranchName = localStorage.getItem('selectedBranchName') || 'No seleccionada';

document.getElementById('selectedBranch').textContent = `Sucursal: ${selectedBranchName}`;

const cartSummary = document.getElementById('cartSummary');
const summaryContainer = document.getElementById('summaryContainer');

// Renderizar carrito
function renderCart() {
    cartSummary.innerHTML = '';
    if (!cart.length) return cartSummary.innerHTML = '<p class="text-gray-400">Carrito vacío</p>';
    cart.forEach(item => {
        cartSummary.innerHTML += `<div class="flex justify-between">${item.name} x${item.quantity}<span>$${(item.price*item.quantity).toFixed(2)}</span></div>`;
    });
    updateSummary();
}

// Totales
function updateSummary() {
    const subtotal = cart.reduce((s,i)=>s+i.price*i.quantity,0);
    const total = subtotal*(1-discount);
    const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
    const change = Math.max(0, amountPaid-total);

    summaryContainer.innerHTML = `
        <div>Subtotal: $${subtotal.toFixed(2)}</div>
        ${discount>0?`<div>Descuento ${discount*100}%: -$${(subtotal*discount).toFixed(2)}</div>`:''}
        <div class="text-lg text-green-600 font-bold">TOTAL: $${total.toFixed(2)}</div>
        ${amountPaid>=total?`<div class="text-green-600">Cambio: $${change.toFixed(2)}</div>`:''}
    `;
}

document.getElementById('amountPaid').oninput = updateSummary;

// Descuentos
document.querySelectorAll('.discount-btn').forEach(btn=>{
    btn.onclick = ()=>{
        discount = parseFloat(btn.dataset.discount);
        updateSummary();
    };
});

// Procesar venta
document.getElementById('processSaleBtn').onclick = async () => {
    if (!cart.length) return Swal.fire('Carrito vacío','','error');

    const customerName = document.getElementById('customerName').value || 'Cliente';
    const customerEmail = document.getElementById('customerEmail').value || null;
    const paymentMethod = document.getElementById('paymentMethod').value;
    const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;

    const subtotal = cart.reduce((s,i)=>s+i.price*i.quantity,0);
    const total = subtotal*(1-discount);
    const change = Math.max(0, amountPaid-total);

    if (!amountPaid || amountPaid<total) return Swal.fire('Monto insuficiente','','error');

    const confirm = await Swal.fire({
        title: `Confirmar venta por $${total.toFixed(2)}?`,
        icon: 'question', showCancelButton:true,
        confirmButtonText:'Confirmar', cancelButtonText:'Cancelar'
    });

    if (!confirm.isConfirmed) return;

    try {
        const res = await fetch(`${API_URL}/sales`, {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body:JSON.stringify({
                customer_name: customerName,
                customer_email: customerEmail,
                payment_method: paymentMethod,
                amount_paid: amountPaid,
                change_amount: change,
                subtotal,
                discount: subtotal*discount,
                total,
                branch_id: selectedBranchId,
                items: cart.map(i=>({product_id:i.id, quantity:i.quantity, unit_price:i.price, total:i.price*i.quantity}))
            })
        });

        let result;
        try {
            result = await res.json(); // intentamos parsear JSON
        } catch(parseError) {
            throw new Error("Respuesta inválida del servidor.");
        }

        if (!res.ok || !result.success) {
            return Swal.fire('Error', result?.message || 'Error al procesar venta', 'error');
        }

        Swal.fire(
            'Venta procesada', 
            `Total: $${total.toFixed(2)}\nCambio: $${change.toFixed(2)}\nCorreo enviado: ${result.data.email_sent ? 'Sí' : 'No'}`, 
            'success'
        );

        localStorage.removeItem('cart');

        window.location.href = "{{ url('/pos') }}";


    } catch(e) {
        console.error(e);
        Swal.fire('Error','No se pudo procesar la venta. Revisa la consola.','error');
    }
};

renderCart();
</script>


</body>
</html>
