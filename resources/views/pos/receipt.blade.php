<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket</title>
@php
    /* Logo embebido como base64 */
    $logoPath = public_path('assets/img/logoSF.png');
    $logoB64  = file_exists($logoPath)
                    ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                    : null;

    /* QR del sitio web para solicitar facturas – generado en PHP, sin depender de internet */
    try {
        $qrWriter  = new \BaconQrCode\Writer(
            new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(120),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            )
        );
        $qrSvg  = $qrWriter->writeString('https://rodcas.luradev.com');
        $qrB64  = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
    } catch (\Throwable $e) {
        $qrB64 = null;
    }
@endphp
<style>
    /* ── Página 58 mm ─────────────────────────────────── */
    @page {
        size: 58mm auto;
        margin: 0;          /* márgenes a 0 – los controla el padding del body */
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Arial, 'Helvetica Neue', sans-serif;
        font-size: 8.5px;
        font-weight: 700;
        width: 58mm;
        /* padding: top left bottom right
           izq 2mm / der 3mm → área útil 53mm, sobra espacio para bold */
        padding: 2mm 3mm 2mm 2mm;
        color: #000;
        background: #fff;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* ── Tamaños ──────────────────────────────────────── */
    .xs  { font-size: 7px;   }
    .sm  { font-size: 8px;   }
    .md  { font-size: 10px;  }
    .lg  { font-size: 13px;  }
    .xl  { font-size: 16px;  }
    .blk { font-weight: 900; }
    .reg { font-weight: 500; }

    /* ── Alineación ───────────────────────────────────── */
    .center { text-align: center; }
    .right  { text-align: right;  }

    /* ── Separadores ──────────────────────────────────── */
    .sep  { border: none; border-top: 1px dashed #000; margin: 3px 0; }
    .sep2 { border: none; border-top: 2px solid  #000; margin: 3px 0; }

    /* ── Logo ─────────────────────────────────────────── */
    .logo-wrap {
        text-align: center;
        padding: 3px 0 1px;
    }
    .logo-wrap img {
        max-width: 28mm;
        max-height: 14mm;
        object-fit: contain;
        filter: grayscale(100%) contrast(1.2);
    }

    /* ── Encabezado ───────────────────────────────────── */
    .store-name {
        font-size: 14px;
        font-weight: 900;
        text-align: center;
        letter-spacing: 1.5px;
        line-height: 1.1;
    }
    .store-sub {
        font-size: 7.5px;
        font-weight: 700;
        text-align: center;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-top: 1px;
    }

    /* ── Meta (folio, fecha, cliente) ─────────────────── */
    .meta-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        font-size: 8px;
        font-weight: 700;
        margin: 1.5px 0;
    }
    .meta-row .label { font-weight: 900; }
    .meta-row .val   { font-weight: 700; }
    .folio {
        font-size: 9px;
        font-weight: 900;
        letter-spacing: 0.3px;
    }
    .cliente-row {
        font-size: 7.5px;
        font-weight: 700;
        margin: 1.5px 0;
    }

    /* ── Artículos ────────────────────────────────────── */
    .col-hdr {
        display: flex;
        justify-content: space-between;
        font-size: 7px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin: 1px 0;
    }
    .item { margin: 2.5px 0; }
    .item-name {
        font-size: 8.5px;
        font-weight: 900;
        line-height: 1.2;
        word-break: break-word;
    }
    .item-detail {
        display: flex;
        justify-content: space-between;
        font-size: 7.5px;
        font-weight: 700;
        padding-left: 4px;
        margin-top: 0.5px;
    }
    .item-detail .qty { font-weight: 700; }
    .item-detail .imp { font-weight: 900; }

    /* ── Totales ──────────────────────────────────────── */
    .row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        font-size: 8px;
        font-weight: 700;
        margin: 1.5px 0;
    }
    .row.total {
        font-size: 15px;
        font-weight: 900;
        letter-spacing: 0.5px;
        margin: 3px 0;
    }

    /* ── Pago ─────────────────────────────────────────── */
    .pago-row {
        display: flex;
        justify-content: space-between;
        font-size: 8.5px;
        font-weight: 700;
        margin: 1.5px 0;
    }
    .credito-alert {
        text-align: center;
        font-size: 8px;
        font-weight: 900;
        letter-spacing: 0.3px;
        padding: 2px 0;
    }

    /* ── Pie ──────────────────────────────────────────── */
    .footer-msg {
        text-align: center;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: 0.5px;
        margin: 3px 0 1px;
    }
    .footer-sub {
        text-align: center;
        font-size: 7px;
        font-weight: 700;
        margin-bottom: 2px;
    }
    /* ── QR + contacto ────────────────────────────────── */
    .qr-wrap {
        text-align: center;
        padding: 3px 0 1px;
    }
    .qr-wrap img {
        width: 22mm;
        height: 22mm;
    }
    .qr-label {
        text-align: center;
        font-size: 6.5px;
        font-weight: 900;
        letter-spacing: 0.3px;
        margin: 1px 0 0;
    }
    .qr-sub {
        text-align: center;
        font-size: 6px;
        font-weight: 700;
        margin: 0.5px 0 2px;
    }

    /* ── Loading ──────────────────────────────────────── */
    #loading {
        padding: 16px;
        text-align: center;
        font-family: sans-serif;
        color: #555;
    }
    #receipt { display: none; }

    @media print {
        #loading  { display: none !important; }
        #receipt  { display: block !important; }
    }
</style>
</head>
<body>

<div id="loading">
    <p>⏳ Preparando ticket…</p>
</div>

<div id="receipt"></div>

<script>
const LOGO_SRC = @json($logoB64);
const QR_SRC   = @json($qrB64);

window.onload = function () {
    const raw     = localStorage.getItem('lastReceipt');
    const loading = document.getElementById('loading');
    const receipt = document.getElementById('receipt');

    if (!raw) {
        loading.innerHTML = '<p style="color:red">Sin datos de ticket.<br>Realiza una venta primero.</p>';
        return;
    }

    const d = JSON.parse(raw);

    /* ── Fecha ──────────────────────────────── */
    const dt    = new Date(d.date);
    const fecha = dt.toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit', year:'2-digit' });
    const hora  = dt.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });

    /* ── Logo ───────────────────────────────── */
    const logoHtml = LOGO_SRC
        ? `<div class="logo-wrap"><img src="${LOGO_SRC}" alt="Logo"></div>`
        : '';

    /* ── Artículos ──────────────────────────── */
    let itemsHtml = '';
    d.items.forEach(item => {
        const nombre  = item.name.length > 28 ? item.name.substring(0, 27) + '…' : item.name;
        const importe = (Number(item.price) * item.quantity).toFixed(2);
        itemsHtml += `
            <div class="item">
                <div class="item-name">${nombre}</div>
                <div class="item-detail">
                    <span class="qty">${item.quantity} × $${Number(item.price).toFixed(2)}</span>
                    <span class="imp">$${importe}</span>
                </div>
            </div>`;
    });

    /* ── Descuento ──────────────────────────── */
    const descHtml = Number(d.discount) > 0
        ? `<div class="row"><span>Descuento</span><span>-$${Number(d.discount).toFixed(2)}</span></div>`
        : '';

    /* ── Método de pago ─────────────────────── */
    const esCredito = d.payment_method === 'credito';
    const metodos   = {
        efectivo      : 'Efectivo',
        tarjeta       : 'Tarjeta',
        transferencia : 'Transferencia',
        mixto         : 'Mixto',
        credito       : 'Crédito',
    };
    const metodo = metodos[d.payment_method] || d.payment_method;

    const cambioHtml = !esCredito && Number(d.change) > 0
        ? `<div class="pago-row"><span>Cambio</span><span>$${Number(d.change).toFixed(2)}</span></div>`
        : '';

    const pagoHtml = esCredito
        ? `<div class="credito-alert">— CRÉDITO PENDIENTE —</div>
           <div class="pago-row"><span>Debe:</span><span>$${Number(d.total).toFixed(2)}</span></div>`
        : `<div class="pago-row">
               <span>${metodo}</span>
               <span>$${Number(d.amount_paid).toFixed(2)}</span>
           </div>
           ${cambioHtml}`;

    /* ── Render ─────────────────────────────── */
    receipt.innerHTML = `
        ${logoHtml}

        <div class="store-name">RODCAS</div>
        <div class="store-sub">Ferretería</div>
     

        <hr class="sep2" style="margin-top:4px">

        <div class="meta-row">
            <span class="folio">${d.invoice_number}</span>
            <span class="sm">${fecha} ${hora}</span>
        </div>
        <div class="cliente-row">
            <span style="font-weight:900">Cliente: </span>${d.customer_name || 'Público general'}
        </div>

        <hr class="sep">

        <div class="col-hdr">
            <span>Artículo</span><span>Importe</span>
        </div>
        <hr class="sep">

        ${itemsHtml}

        <hr class="sep">

        <div class="row"><span>Subtotal</span><span>$${Number(d.subtotal).toFixed(2)}</span></div>
        ${descHtml}

        <hr class="sep2">
        <div class="row total"><span>TOTAL</span><span>$${Number(d.total).toFixed(2)}</span></div>
        <hr class="sep2">

        ${pagoHtml}

        <hr class="sep2" style="margin-top:5px">
        <div class="footer-msg">¡GRACIAS POR SU COMPRA!</div>
        <div class="footer-sub">Conserve su ticket · Tel. 720 829 3653</div>
        ${QR_SRC ? `
        <hr class="sep">
        <div class="qr-wrap"><img src="${QR_SRC}" alt="QR Factura"></div>
        <div class="qr-label">¿Necesitas factura?</div>
        <div class="qr-sub">Escanea el QR o visita rodcas.luradev.com</div>
        ` : `
        <hr class="sep">
        <div class="qr-sub" style="margin-top:2px">Facturación: rodcas.luradev.com</div>
        <div class="qr-sub">ferreteriarodcasfacturas@gmail.com</div>
        `}
        <hr class="sep2">
    `;

    /* ── Auto-imprimir ──────────────────────── */
    setTimeout(() => {
        window.print();
        window.addEventListener('afterprint', () => window.close());
        setTimeout(() => window.close(), 4000);
    }, 400);
};
</script>
</body>
</html>
