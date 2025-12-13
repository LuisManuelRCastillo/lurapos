<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Compra</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: Arial, Helvetica, sans-serif;
            color: #1a1a1a;
            background: #ffffff;
            padding: 30px;
            font-size: 12pt;
        }
        
        .container {
            max-width: 100%;
            margin: 0 auto;
            border: 3px solid #1B5E20;
            padding: 0;
        }
        
        /* Header */
        .header {
            padding: 35px 30px;
            margin-bottom: 0;
            text-align: center;
            border-bottom: 3px solid #1B5E20;
        }
        
        .logo {
            max-width: 180px;
            height: auto;
            display: block;
            margin: 0 auto 15px auto;
        }
        
        .company-name {
            color: #1a1a1a;
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .header-subtitle {
            color: #64748b;
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }
        
        .invoice-badge {
            display: inline-block;
            border: 2px solid #1B5E20;
            color: #1B5E20;
            padding: 10px 25px;
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        /* Información de fecha */
        .info-section {
            padding: 25px 30px;
            border-bottom: 2px solid #1B5E20;
        }
        
        .info-row {
            text-align: right;
        }
        
        .info-label {
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 12pt;
            color: #1a1a1a;
            font-weight: bold;
        }
        
        /* Productos */
        .products-section {
            margin-bottom: 0;
            padding: 25px 30px;
        }
        
        .section-title {
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #64748b;
            margin: 0 0 15px 0;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        
        thead tr {
            border-top: 2px solid #1B5E20;
            border-bottom: 2px solid #1B5E20;
        }
        
        th {
            padding: 12px 15px;
            text-align: left;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
            color: #1B5E20;
            border: none;
        }
        
        th:last-child {
            text-align: right;
        }
        
        th:nth-child(2),
        th:nth-child(3) {
            text-align: center;
        }
        
        tbody tr {
            background-color: #ffffff;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11pt;
            color: #334155;
        }
        
        td:last-child {
            text-align: right;
            font-weight: bold;
            color: #1a1a1a;
        }
        
        td:nth-child(2),
        td:nth-child(3) {
            text-align: center;
        }
        
        tbody tr:last-child td {
            border-bottom: 2px solid #1B5E20;
        }
        
        /* Total */
        .total-section {
            padding: 20px 30px;
            margin-top: 0;
            border-top: 3px solid #1B5E20;
        }
        
        .total-container {
            text-align: right;
        }
        
        .total-label {
            font-size: 11pt;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 5px;
        }


        .total-discount{
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #ff0000;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .total-amount {
            font-size: 24pt;
            color: #1B5E20;
            font-weight: bold;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 25px 30px;
            border-top: 3px solid #1B5E20;
        }
        
        .snowflakes {
            font-size: 18pt;
            color: #1B5E20;
            margin-bottom: 15px;
        }
        
        .thank-you {
            font-size: 14pt;
            color: #1a1a1a;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .footer-text {
            font-size: 10pt;
            color: #64748b;
            line-height: 1.6;
        }
        
        .highlight {
            color: #1B5E20;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
          
            <div class="company-name">Gran Villa Navideña Metepec</div>
            <div class="header-subtitle">Comprobante de Compra</div>
            <div class="invoice-badge">{{ $sale->invoice_number }}</div>
        </div>
        
        <!-- Información de fecha -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Fecha de Emisión</div>
                <div class="info-value">{{ $sale->created_at->format('d/m/Y') }}</div>
            </div>
        </div>
        
        <!-- Productos -->
        <div class="products-section">
            <div class="section-title">Detalle de Productos</div>
            <table>
                <thead>
                    <tr>
                        <th>PRODUCTO</th>
                        <th>CANT.</th>
                        <th>PRECIO UNIT.</th>
                        <th>SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->details as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format($item->unit_price, 2) }}</td>
                        <td>${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="total-section">
            <div class="total-container">
                <div class="total-discount">Descuento: ${{ number_format($sale->discount, 2) }}</div>
            </div>
        </div>
        <!-- Total -->
        <div class="total-section">
            <div class="total-container">
                <div class="total-label">Total a Pagar</div>
                <div class="total-amount">${{ number_format($sale->total, 2) }}</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">

            <div class="thank-you">¡Gracias por su preferencia!</div>
            <div class="footer-text">
                Les deseamos una <span class="highlight">Feliz Navidad</span><br>
                y un próspero Año Nuevo
            </div>
        </div>
    </div>
</body>
</html>