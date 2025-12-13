<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reporte de ventas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">

                <h2 class="text-2xl font-bold mb-4">Dashboard de Ventas</h2>

                <!-- 🔹 Filtros -->
                <form id="filtroForm" class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

                    <div>
                        <label class="text-sm">Sucursal:</label>
                        <select id="branchSelect" name="branch_id" class="border rounded p-2 w-full">
                            <option value="">Todas</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm">Desde:</label>
                        <input type="date" id="dateFrom" name="date_from" class="border rounded p-2 w-full">
                    </div>

                    <div>
                        <label class="text-sm">Hasta:</label>
                        <input type="date" id="dateTo" name="date_to" class="border rounded p-2 w-full">
                    </div>

                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Aplicar filtros
                    </button>

                </form>

                {{-- KPIs --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="p-4 bg-gray-100 rounded-lg shadow text-center">
                        <h3 class="text-gray-500">Ventas del período</h3>
                        <p id="ventasDia" class="text-3xl font-bold">$0.00</p>
                    </div>

                    <div class="p-4 bg-gray-100 rounded-lg shadow text-center">
                        <h3 class="text-gray-500">Transacciones</h3>
                        <p id="transacciones" class="text-3xl font-bold">0</p>
                    </div>

                    <div class="p-4 bg-gray-100 rounded-lg shadow text-center">
                        <h3 class="text-gray-500">Ticket Promedio</h3>
                        <p id="ticketPromedio" class="text-3xl font-bold">$0.00</p>
                    </div>

                    <div class="p-4 bg-gray-100 rounded-lg shadow text-center">
                        <h3 class="text-gray-500">Stock Bajo</h3>
                        <p id="stockBajo" class="text-3xl font-bold">0</p>
                    </div>
                </div>

                {{-- Gráficas --}}
                <div class="bg-white p-4 rounded-lg shadow mb-6">
                    <h3 class="text-lg font-semibold mb-2">Ventas por día</h3>
                    <canvas id="chartVentas"></canvas>
                </div>

                <div class="bg-white p-4 rounded-lg shadow mb-6">
                    <h3 class="text-lg font-semibold mb-2">Top 5 productos más vendidos</h3>
                    <canvas id="chartTop"></canvas>
                </div>

                <div class="bg-white p-4 rounded-lg shadow mb-6">
                    <h3 class="text-lg font-semibold mb-2">Métodos de pago</h3>
                    <canvas id="chartMetodoPago"></canvas>
                </div>

                {{-- 🔹 Tabla detallada --}}
                <div class="bg-white p-4 rounded-lg shadow mt-6">
                    <h3 class="text-lg font-semibold mb-2">Detalle de ventas por ticket</h3>

                    <table class="w-full text-sm text-left border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 border">Fecha</th>
                                <th class="p-2 border">Productos</th>
                                <th class="p-2 border">Cant.</th>
                                <th class="p-2 border">Total Venta</th>
                                <th class="p-2 border">Método Pago</th>
                            </tr>
                        </thead>
                        <tbody id="tablaVentasDetalle">
                            <tr><td colspan="5" class="text-center p-4">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    {{-- JS --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let chartVentas, chartTop, chartMetodoPago;

        function cargarSucursales() {
            fetch("/gvnmetepec/api/pos/branches", { credentials: "include" })
                .then(res => res.json())
                .then(branches => {
                    const select = document.getElementById("branchSelect");
                    select.innerHTML = '<option value="">Todas</option>';
                    branches.forEach(branch => {
                        const option = document.createElement("option");
                        option.value = branch.id;
                        option.textContent = branch.name ?? branch.branch_name ?? "Sucursal";
                        select.appendChild(option);
                    });
                });
        }

        function cargarDashboard(dateFrom = null, dateTo = null) {
            const branchId = document.getElementById("branchSelect").value;
            let url = "/gvnmetepec/dashboard/data?";
            if (branchId) url += `branch_id=${branchId}&`;
            if (dateFrom && dateTo) url += `date_from=${dateFrom}&date_to=${dateTo}`;

            fetch(url, { credentials: "include" })
                .then(res => res.json())
                .then(data => {
                    document.getElementById("ventasDia").innerText = `$${parseFloat(data.total_sales ?? 0).toFixed(2)}`;
                    document.getElementById("transacciones").innerText = data.transactions_count;
                    document.getElementById("ticketPromedio").innerText =
                        data.average_ticket ? `$${parseFloat(data.average_ticket).toFixed(2)}` : "$0.00";
                    document.getElementById("stockBajo").innerText = data.low_stock_products;

                    if (chartVentas) chartVentas.destroy();
                    if (chartTop) chartTop.destroy();
                    if (chartMetodoPago) chartMetodoPago.destroy();

                    chartVentas = new Chart(document.getElementById("chartVentas"), {
                        type: 'line',
                        data: {
                            labels: data.daily_sales.map(s => s.date),
                            datasets: [{ label: 'Ventas ($)', data: data.daily_sales.map(s => s.total), borderWidth: 2 }]
                        }
                    });

                    chartMetodoPago = new Chart(document.getElementById("chartMetodoPago"), {
                        type: 'pie',
                        data: {
                            labels: data.sales_by_method.map(m => m.payment_method),
                            datasets: [{ data: data.sales_by_method.map(m => m.total) }]
                        }
                    });

                    chartTop = new Chart(document.getElementById("chartTop"), {
                        type: 'bar',
                        data: {
                            labels: data.top_products.map(p => p.name),
                            datasets: [{
                                label: 'Unidades vendidas',
                                data: data.top_products.map(p => p.total_sold)
                            }]
                        }
                    });

                    const tbody = document.getElementById("tablaVentasDetalle");
                    tbody.innerHTML = "";

                    if (!data.sales_detail || data.sales_detail.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="5" class="text-center p-4">No hay ventas en el período seleccionado</td></tr>`;
                        return;
                    }

                    data.sales_detail.forEach(sale => {
                        tbody.innerHTML += `
                        <tr>
                            <td class="p-2 border">${sale.sale_date}</td>
                            <td class="p-2 border">${sale.products}</td>
                            <td class="p-2 border">${sale.total_quantity}</td>
                            <td class="p-2 border">$${parseFloat(sale.total_amount).toFixed(2)}</td>
                            <td class="p-2 border">${sale.payment_method}</td>
                        </tr>
                        `;
                    });
                });
        }

        document.addEventListener("DOMContentLoaded", () => {
            cargarSucursales();

            const today = new Date().toISOString().slice(0, 10);
            document.getElementById("dateFrom").value = today;
            document.getElementById("dateTo").value = today;
            cargarDashboard(today, today);
        });

        document.getElementById("branchSelect").addEventListener("change", () => {
            cargarDashboard(
                document.getElementById("dateFrom").value,
                document.getElementById("dateTo").value
            );
        });

        document.getElementById("filtroForm").addEventListener("submit", (e) => {
            e.preventDefault();
            cargarDashboard(
                document.getElementById("dateFrom").value,
                document.getElementById("dateTo").value
            );
        });
    </script>

</x-app-layout>
