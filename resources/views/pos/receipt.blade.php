<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket</title>
<style>
    /* ── Página: papel 48mm ──────────────────────────────── */
    @page {
        size: 48mm auto;
        margin: 2mm 1mm;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 8px;
        width: 46mm;
        color: #000;
        background: #fff;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* ── Utilidades ─────────────────────────────────────── */
    .center { text-align: center; }
    .right  { text-align: right; }
    .bold   { font-weight: bold; }
    .sm     { font-size: 7px; }
    .md     { font-size: 9px; }
    .lg     { font-size: 12px; }

    /* ── Separadores ────────────────────────────────────── */
    .sep  { border: none; border-top: 1px dashed #000; margin: 2px 0; }
    .sep2 { border: none; border-top: 1px solid  #000; margin: 2px 0; }

    /* ── Artículos: 2 líneas por producto ───────────────── */
    .item { margin: 2px 0; }
    .item-name {
        font-size: 8px;
        line-height: 1.2;
        word-break: break-word;
    }
    .item-detail {
        display: flex;
        justify-content: space-between;
        font-size: 7.5px;
        padding-left: 5px;
    }
    .item-detail .qty  { color: #333; }
    .item-detail .imp  { font-weight: bold; }

    /* ── Filas de totales / pago ─────────────────────────── */
    .row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        font-size: 8px;
        margin: 1px 0;
    }
    .row.total {
        font-size: 10px;
        font-weight: bold;
    }

    /* ── Loading / print toggle ──────────────────────────── */
    #loading { padding: 16px; text-align: center; font-family: sans-serif; color: #555; }
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
    const raw     = localStorage.getItem('lastReceipt');
    const loading = document.getElementById('loading');
    const receipt = document.getElementById('receipt');

    if (!raw) {
        loading.innerHTML = '<p style="color:red">Sin datos de ticket.<br>Realiza una venta primero.</p>';
        return;
    }

    const d = JSON.parse(raw);

    /* ── Fecha ───────────────────────────────── */
    const dt    = new Date(d.date);
    const fecha = dt.toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit', year:'numeric' });
    const hora  = dt.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });

    /* ── Artículos (2 líneas) ────────────────── */
    let itemsHtml = '';
    d.items.forEach(item => {
        const nombre  = item.name.length > 26 ? item.name.substring(0, 25) + '…' : item.name;
        const importe = (Number(item.price) * item.quantity).toFixed(2);
        itemsHtml += `
            <div class="item">
                <div class="item-name">${nombre}</div>
                <div class="item-detail">
                    <span class="qty">${item.quantity} &times; $${Number(item.price).toFixed(2)}</span>
                    <span class="imp">$${importe}</span>
                </div>
            </div>`;
    });

    /* ── Descuento ───────────────────────────── */
    const descHtml = Number(d.discount) > 0
        ? `<div class="row"><span>Descuento</span><span>-$${Number(d.discount).toFixed(2)}</span></div>`
        : '';

    /* ── Cambio ──────────────────────────────── */
    const cambioHtml = Number(d.change) > 0
        ? `<div class="row"><span>Cambio</span><span>$${Number(d.change).toFixed(2)}</span></div>`
        : '';

    /* ── Método de pago ──────────────────────── */
    const esCredito = d.payment_method === 'credito';
    const metodos   = {
        efectivo      : 'Efectivo',
        tarjeta       : 'Tarjeta',
        transferencia : 'Transferencia',
        mixto         : 'Mixto',
        credito       : 'Crédito',
    };
    const metodo = metodos[d.payment_method] || d.payment_method;

    const pagoHtml = esCredito
        ? `<div class="row bold" style="font-size:9px">
               <span>** CRÉDITO PENDIENTE **</span>
           </div>
           <div class="row">
               <span>Debe:</span>
               <span>$${Number(d.total).toFixed(2)}</span>
           </div>
           <div class="center sm" style="margin-top:2px">* Pendiente de pago *</div>`
        : `<div class="row">
               <span>${metodo}</span>
               <span>$${Number(d.amount_paid).toFixed(2)}</span>
           </div>
           ${cambioHtml}`;

    /* ── Render ──────────────────────────────── */
    receipt.innerHTML = `
        <!-- ENCABEZADO -->
        <div class="center bold lg">RODCAS</div>
        <div class="center sm">Ferretería</div>
        <div class="center sm">${d.branch || 'Sucursal Principal'}</div>
        <hr class="sep2">

        <!-- FOLIO Y FECHA -->
        <div class="row">
            <span class="bold">${d.invoice_number}</span>
            <span>${fecha} ${hora}</span>
        </div>
        <div class="sm" style="margin:1px 0">
            Cliente: ${d.customer_name || 'Público general'}
        </div>
        <hr class="sep">

        <!-- ARTÍCULOS -->
        <div class="row sm bold" style="margin-bottom:1px">
            <span>ARTÍCULO</span><span>IMPORTE</span>
        </div>
        <hr class="sep">
        ${itemsHtml}
        <hr class="sep">

        <!-- TOTALES -->
        <div class="row"><span>Subtotal</span><span>$${Number(d.subtotal).toFixed(2)}</span></div>
        ${descHtml}
        <hr class="sep2">
        <div class="row total"><span>TOTAL</span><span>$${Number(d.total).toFixed(2)}</span></div>
        <hr class="sep2">

        <!-- PAGO -->
        ${pagoHtml}

        <!-- PIE -->
        <hr class="sep2" style="margin-top:4px">
        <div class="center bold" style="margin:3px 0; font-size:8.5px">¡Gracias por su compra!</div>
        <div class="center sm" style="margin-bottom:2px">Conserve su ticket</div>
        <hr class="sep2">
    `;

    /* ── Auto-imprimir ───────────────────────── */
    setTimeout(() => {
        window.print();
        window.addEventListener('afterprint', () => window.close());
        setTimeout(() => window.close(), 4000);
    }, 350);
};
</script>

</body>
</html>
