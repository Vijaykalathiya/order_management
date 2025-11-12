@extends('layouts.app')

@section('title', 'All Orders')

@section('css')
<link rel="stylesheet" href="{{ asset('css/tabulator.min.css') }}">
<style>
    .container {
        margin: 30px auto;
        max-width: 1400px;
    }

    .analytics-section {
        margin-bottom: 30px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .chart-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .chart-container h3 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #333;
    }

    .filter-section {
        margin-bottom: 20px;
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .filter-section label {
        margin-right: 15px;
    }

    .filter-section input {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .btn {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-primary {
        background-color: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background-color: #0056b3;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
        margin-left: 5px;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }

    .export-btn {
        background-color: #28a745;
        color: white;
        margin-left: 10px;
    }

    .export-btn:hover {
        background-color: #218838;
    }

    .print-btn {
        background-color: #17a2b8;
        color: white;
        padding: 5px 10px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
    }

    .print-btn:hover {
        background-color: #138496;
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .stat-card h4 {
        margin: 0 0 10px 0;
        color: #666;
        font-size: 14px;
    }

    .stat-card .value {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }

    #orders-table {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .loading {
        text-align: center;
        padding: 20px;
        color: #666;
    }

    .btn-danger {
        background-color: #dc3545;
        color: white;
        margin-left: 10px;
    }
    .btn-danger:hover {
        background-color: #c82333;
    }


    @media (max-width: 1024px) {
        .analytics-section {
            grid-template-columns: 1fr;
        }
        .stats-cards {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
@endsection

@section('content')

<div class="container">
    <h2>Orders Dashboard</h2>

    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <h4>Total Orders</h4>
            <div class="value" id="totalOrders">-</div>
        </div>
        <div class="stat-card">
            <h4>Total Revenue</h4>
            <div class="value" id="totalRevenue">₹0</div>
        </div>
        <div class="stat-card">
            <h4>Today's Orders</h4>
            <div class="value" id="todayOrders">-</div>
        </div>
        <div class="stat-card">
            <h4>Avg Order Value</h4>
            <div class="value" id="avgOrderValue">₹0</div>
        </div>
    </div>

    <!-- Analytics Charts -->
    <div class="analytics-section">
        <div class="chart-container">
            <h3>Top 10 Selling Items</h3>
            <canvas id="topItemsChart" height="300"></canvas>
        </div>
        <div class="chart-container">
            <h3>Daily Sales Trend (Last 7 Days)</h3>
            <canvas id="salesTrendChart" height="300"></canvas>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <label>From: <input type="date" id="dateFrom"></label>
        <label>To: <input type="date" id="dateTo"></label>
        <button id="applyDateFilter" class="btn btn-primary">Apply</button>
        <button id="clearDateFilter" class="btn btn-secondary">Clear</button>

        <button id="exportPdfBtn" class="btn export-btn">📄 Export PDF</button>
        <button id="exportExcelBtn" class="btn export-btn">📊 Export Excel</button>
        <button id="deleteOrdersBtn" class="btn btn-danger">🗑️ Delete Orders</button>

    </div>

    <!-- Orders Table -->
    <div id="orders-table"></div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
<script src="{{ asset('js/tabulator.min.js') }}"></script>
<script src="{{ asset('js/jspdf-2.5.1.umd.min.js') }}"></script>
<script src="{{ asset('js/jspdf.plugin.autotable-3.5.25.min.js') }}"></script>
<script src="{{ asset('js/xlsx.full.min.js') }}"></script>
<script src="{{ asset('js/chart-4.4.0.umd.js') }}"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    let currentFilters = {};
    let topItemsChart = null;
    let salesTrendChart = null;
    let lastTopItemsData = null;
    let lastSalesTrendData = null;
    let analyticsLoading = false;

    // Initialize Tabulator
    const table = new Tabulator("#orders-table", {
        ajaxURL: "{{ route('orders.data') }}",
        ajaxConfig: "GET",
        ajaxParams: () => currentFilters,
        paginationMode: "remote",
        filterMode: "remote",
        sortMode: "remote",
        pagination: true,
        paginationSize: 50,
        paginationSizeSelector: [25, 50, 100, 200],
        layout: "fitColumns",
        placeholder: "No Orders Found",
        columns: [
            { title: "Order ID", field: "id", width: 100, headerFilter: "input" },
            { title: "Order Token", field: "token_number", width: 120, headerFilter: "input" },
            { title: "Receipt Token", field: "token.number", width: 130, headerFilter: "input" },
            {
                title: "Items",
                field: "items",
                headerSort: false,
                formatter: function (cell) {
                    const items = cell.getValue();
                    if (!items || !items.length) return "-";
                    return items.map(item =>
                        `<div style="margin-bottom:3px;">${item.name} 
                        <span style="color:#666;">(${item.qty} × ₹${item.price})</span></div>`
                    ).join("");
                }
            },
            {
                title: "Total",
                field: "total_amount",
                width: 120,
                formatter: "money",
                formatterParams: { symbol: "₹", precision: 2 },
                headerFilter: "input"
            },
            {
                title: "Date",
                field: "created_at",
                width: 180,
                sorter: "datetime",
                sorterParams: { format: "YYYY-MM-DD HH:mm:ss" },
                formatter: function (cell) {
                    const val = cell.getValue();
                    if (!val) return "";
                    const d = new Date(val);
                    return d.toLocaleString('en-IN', {
                        year: 'numeric', month: 'short', day: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });
                }
            },
            {
                title: "Actions",
                width: 100,
                headerSort: false,
                formatter: () => `<button class="print-btn">🖨️ Print</button>`,
                cellClick: function (e, cell) {
                    const order = cell.getData();
                    printOrder(order);
                }
            }
        ]
    });

    // Print Order
    function printOrder(order) {
        const orderItems = order.items.map(item => ({
            code: item.item_code,
            name: item.name,
            qty: item.qty,
            price: item.price,
            station: item.station ?? null,
        }));

        $.ajax({
            url: "{{ route('print.order') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                items: orderItems,
                printByStation: false,
                existingOrderId: order.id,
            },
            success: () => alert('Receipt sent to printer.'),
            error: (xhr) => alert('Failed to print: ' + (xhr.responseJSON?.error ?? 'Unknown error'))
        });
    }

    // Date filters
    $("#applyDateFilter").on("click", function () {
        currentFilters.date_from = $("#dateFrom").val() || null;
        currentFilters.date_to = $("#dateTo").val() || null;
        table.setData();
        loadAnalytics();
    });

    $("#clearDateFilter").on("click", function () {
        $("#dateFrom").val("");
        $("#dateTo").val("");
        currentFilters = {};
        table.clearFilter();
        table.setData();
        loadAnalytics();
    });

    // Export PDF
    $("#exportPdfBtn").on("click", function () {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        const data = table.getData();

        if (!data.length) return alert("No data to export.");

        const headers = ["Order ID", "Token", "Items", "Total", "Date"];
        const rows = data.map(o => [
            o.id,
            o.token_number,
            o.items.map(i => `${i.name} (${i.qty})`).join(", "),
            `₹${o.total_amount}`,
            new Date(o.created_at).toLocaleDateString('en-IN')
        ]);

        doc.setFontSize(16);
        doc.text("Orders Report", 14, 16);
        if (currentFilters.date_from || currentFilters.date_to) {
            doc.setFontSize(10);
            doc.text(`Period: ${currentFilters.date_from || 'Start'} to ${currentFilters.date_to || 'Now'}`, 14, 24);
        }

        doc.autoTable({
            startY: currentFilters.date_from || currentFilters.date_to ? 28 : 22,
            head: [headers],
            body: rows,
            styles: { fontSize: 9 },
            headStyles: { fillColor: [52, 73, 94] },
        });

        doc.save(`orders-report-${new Date().toISOString().split('T')[0]}.pdf`);
    });

    // Export Excel
    $("#exportExcelBtn").on("click", function () {
        const data = table.getData();
        if (!data.length) return alert("No data to export.");

        const formatted = data.map(o => ({
            "Order ID": o.id,
            "Token Number": o.token_number,
            "Receipt Token": o.token?.number || "-",
            "Items": o.items.map(i => `${i.name} (${i.qty})`).join(", "),
            "Total": o.total_amount,
            "Date": new Date(o.created_at).toLocaleString('en-IN'),
        }));

        const ws = XLSX.utils.json_to_sheet(formatted);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Orders");
        XLSX.writeFile(wb, `orders-report-${new Date().toISOString().split('T')[0]}.xlsx`);
    });

    // Delete Orders by Date Range
    $("#deleteOrdersBtn").on("click", function () {
        const from = $("#dateFrom").val();
        const to = $("#dateTo").val();

        if (!from && !to) {
            return alert("Please select at least one date (From or To) to delete orders.");
        }

        let confirmMsg = "Are you sure you want to delete all orders";
        if (from && to) {
            confirmMsg += ` between ${from} and ${to}?`;
        } else if (from) {
            confirmMsg += ` from ${from} onward?`;
        } else {
            confirmMsg += ` up to ${to}?`;
        }

        if (!confirm(confirmMsg + " This action cannot be undone.")) return;

        $.ajax({
            url: "{{ route('orders.deleteRange') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                date_from: from || null,
                date_to: to || null,
            },
            success: function (res) {
                alert(res.message || "Orders deleted successfully.");
                table.setData(); // refresh table
                loadAnalytics(); // refresh analytics
            },
            error: function (xhr) {
                console.error("Delete error:", xhr.responseText);
                alert(xhr.responseJSON?.error || "Failed to delete orders. Check console for details.");
            }
        });
    });


    // Analytics Loader
    function loadAnalytics() {
        if (analyticsLoading) return;
        analyticsLoading = true;

        $.ajax({
            url: "{{ route('orders.analytics') }}",
            type: "GET",
            data: currentFilters,
            success: function (data) {
                updateStatistics(data.statistics);

                if (JSON.stringify(data.topItems) !== JSON.stringify(lastTopItemsData)) {
                    renderTopItemsChart(data.topItems);
                    lastTopItemsData = data.topItems;
                }

                if (JSON.stringify(data.salesTrend) !== JSON.stringify(lastSalesTrendData)) {
                    renderSalesTrendChart(data.salesTrend);
                    lastSalesTrendData = data.salesTrend;
                }
            },
            error: function (xhr, status, error) {
                console.error("Analytics load failed:", error);
                $(".analytics-section").html(
                    `<div style="grid-column:1/-1;padding:40px;text-align:center;color:#721c24;background:#f8d7da;border-radius:8px;">
                        <h3>⚠️ Failed to Load Analytics</h3>
                        <p>Error: ${xhr.responseJSON?.message || error}</p>
                        <p>Check browser console (F12) for details</p>
                    </div>`
                );
            },
            complete: function () {
                analyticsLoading = false;
            }
        });
    }

    // Update statistics
    function updateStatistics(stats) {
        $("#totalOrders").text(parseInt(stats.totalOrders).toLocaleString());
        $("#totalRevenue").text(`₹${parseFloat(stats.totalRevenue).toLocaleString('en-IN', { minimumFractionDigits: 2 })}`);
        $("#todayOrders").text(parseInt(stats.todayOrders).toLocaleString());
        $("#avgOrderValue").text(`₹${parseFloat(stats.avgOrderValue).toLocaleString('en-IN', { minimumFractionDigits: 2 })}`);
    }

    // Render Top Items Chart
    function renderTopItemsChart(data) {
        const ctx = document.getElementById("topItemsChart").getContext("2d");
        if (topItemsChart && !topItemsChart._destroyed) topItemsChart.destroy();

        if (!data || !data.length) {
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            return;
        }

        const colors = [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(199, 199, 199, 0.8)',
            'rgba(83, 102, 255, 0.8)',
            'rgba(255, 99, 255, 0.8)',
            'rgba(99, 255, 132, 0.8)'
        ];

        topItemsChart = new Chart(ctx, {
            type: "pie",
            data: {
                labels: data.map(i => i.name),
                datasets: [{
                    data: data.map(i => parseInt(i.total_quantity)),
                    backgroundColor: colors.slice(0, data.length),
                    borderColor: "#fff",
                    borderWidth: 2
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "right" },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce((a,b) => a + b, 0);
                                const pct = ((ctx.parsed / total) * 100).toFixed(1);
                                return `${ctx.label}: ${ctx.parsed} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Render Sales Trend Chart
    function renderSalesTrendChart(data) {
        const ctx = document.getElementById("salesTrendChart").getContext("2d");
        if (salesTrendChart && !salesTrendChart._destroyed) salesTrendChart.destroy();

        if (!data || !data.length) {
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            return;
        }

        salesTrendChart = new Chart(ctx, {
            type: "line",
            data: {
                labels: data.map(i => i.date),
                datasets: [{
                    label: "Daily Revenue",
                    data: data.map(i => parseFloat(i.total)),
                    borderColor: "rgba(75,192,192,1)",
                    backgroundColor: "rgba(75,192,192,0.2)",
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: val => '₹' + val.toLocaleString('en-IN') }
                    },
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { position: "top" },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `Revenue: ₹${ctx.parsed.y.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`
                        }
                    }
                }
            }
        });
    }

    // Initial load
    loadAnalytics();
});
</script>
@endsection
