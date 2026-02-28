<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket</title>
<style>
    /* ── Página: papel 58mm ──────────────────────────────── */
    @page {
        size: 58mm auto;
        margin: 2mm 1mm;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 9px;
        width: 56mm;
        color: #000;
        background: #fff;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* ── Utilidades ─────────────────────────────────────── */
    .center  { text-align: center; }
    .right   { text-align: right; }
    .bold    { font-weight: bold; }
    .sm      { font-size: 8px; }
    .md      { font-size: 10px; }
    .lg      { font-size: 13px; }

    /* ── Separadores ────────────────────────────────────── */
    .sep  { border: none; border-top: 1px dashed #000; margin: 3px 0; }
    .sep2 { border: none; border-top: 1px solid  #000; margin: 3px 0; }

    /* ── Filas de artículos ─────────────────────────────── */
    .items-table { width: 100%; border-collapse: collapse; }
    .items-table th { text-align: left; font-size: 8px; padding: 1px 0; }
    .items-table td { vertical-align: top; padding: 1px 0; font-size: 8.5px; }
    .items-table .td-name { width: 55%; word-break: break-word; }
    .items-table .td-qty  { width: 10%; text-align: center; }
    .items-table .td-pu   { width: 17%; text-align: right; }
    .items-table .td-tot  { width: 18%; text-align: right; }

    /* ── Fila de totales ────────────────────────────────── */
    .tot-row { display: flex; justify-content: space-between; margin: 1px 0; }

    /* ── Ocultar en pantalla (solo imprimir) ────────────── */
    #loading { padding: 20px; text-align: center; font-family: sans-serif; color: #555; }
    #receipt { display: none; }

    @media print {
        #loading { display: none !important; }
        #receipt { display: block !important; }
    }
</style>
</head>
<body>

<div id="loading">
    <p>⏳ Preparando ticket…</p>
</div>

<div id="receipt"></div>

<script>
window.onload = function () {
    const raw = localStorage.getItem('lastReceipt');
    const loading = document.getElementById('loading');
    const receipt = document.getElementById('receipt');

    if (!raw) {
        loading.innerHTML = '<p style="color:red">Sin datos de ticket.<br>Realiza una venta primero.</p>';
        return;
    }

    const d = JSON.parse(raw);

    /* ── Fecha ───────────────────────────────── */
    const dt   = new Date(d.date);
    const fecha = dt.toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit', year:'numeric' });
    const hora  = dt.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });

    /* ── Artículos ───────────────────────────── */
    let itemsRows = '';
    d.items.forEach(item => {
        const importe = (Number(item.price) * item.quantity).toFixed(2);
        const nombre  = item.name.length > 22 ? item.name.substring(0, 21) + '…' : item.name;
        itemsRows += `
            <tr>
                <td class="td-name">${nombre}</td>
                <td class="td-qty">${item.quantity}</td>
                <td class="td-pu">$${Number(item.price).toFixed(2)}</td>
                <td class="td-tot">$${importe}</td>
            </tr>`;
    });

    /* ── Descuento ───────────────────────────── */
    const descHtml = Number(d.discount) > 0
        ? `<div class="tot-row"><span>DESCUENTO</span><span>-$${Number(d.discount).toFixed(2)}</span></div>`
        : '';

    /* ── Cambio ──────────────────────────────── */
    const cambioHtml = Number(d.change) > 0
        ? `<div class="tot-row"><span>CAMBIO</span><span>$${Number(d.change).toFixed(2)}</span></div>`
        : '';

    /* ── Método pago ─────────────────────────── */
    const metodos = { efectivo: 'EFECTIVO', tarjeta: 'TARJETA', transferencia: 'TRANSFER.' };
    const metodo  = metodos[d.payment_method] || d.payment_method.toUpperCase();

    /* ── Render ──────────────────────────────── */
    receipt.innerHTML = `
        <!-- ENCABEZADO -->
        <div class="center bold lg">RODCAS</div>
        <div class="center sm">Ferretería</div>
        <div class="center sm">${d.branch || 'Sucursal Principal'}</div>
        <hr class="sep2">

        <!-- FOLIO Y FECHA -->
        <div><span class="bold">TICKET:</span> ${d.invoice_number}</div>
        <div>${fecha} &nbsp; ${hora}</div>
        <div>CLIENTE: ${d.customer_name || 'Cliente'}</div>
        <hr class="sep">

        <!-- ARTÍCULOS -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="td-name">ARTÍCULO</th>
                    <th class="td-qty">QTY</th>
                    <th class="td-pu">P.U.</th>
                    <th class="td-tot">TOTAL</th>
                </tr>
            </thead>
            <tbody>${itemsRows}</tbody>
        </table>
        <hr class="sep">

        <!-- TOTALES -->
        <div class="tot-row"><span>SUBTOTAL</span><span>$${Number(d.subtotal).toFixed(2)}</span></div>
        ${descHtml}
        <hr class="sep2">
        <div class="tot-row bold md"><span>TOTAL</span><span>$${Number(d.total).toFixed(2)}</span></div>
        <hr class="sep2">

        <!-- PAGO -->
        <div class="tot-row"><span>${metodo}</span><span>$${Number(d.amount_paid).toFixed(2)}</span></div>
        ${cambioHtml}

        <!-- PIE -->
        <hr class="sep2" style="margin-top:5px">
        <div class="center bold" style="margin:3px 0">¡Gracias por su compra!</div>
        <div class="center sm" style="margin-bottom:2px">Conserve su ticket</div>
        <hr class="sep2">
    `;

    /* ── Auto-imprimir ───────────────────────── */
    setTimeout(() => {
        window.print();
        // Cierra la ventana popup después de imprimir
        window.addEventListener('afterprint', () => window.close());
        // Fallback por si afterprint no dispara (ej. diálogo cancelado)
        setTimeout(() => window.close(), 4000);
    }, 350);
};
</script>

</body>
</html>
