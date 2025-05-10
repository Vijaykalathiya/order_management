@extends('layouts.app')

@section('title', 'All Orders')

@section('css')
<link href="https://unpkg.com/tabulator-tables@5.4.4/dist/css/tabulator.min.css" rel="stylesheet">
<style>
    .container {
        margin: 30px auto;
        max-width: 1200px;
    }

    .filter-section {
        margin-bottom: 20px;
    }

    .filter-section input {
        padding: 5px;
        margin-right: 10px;
    }

    .export-btn {
        background-color: #007bff;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 10px;
    }

    .export-btn:hover {
        background-color: #0056b3;
    }

    .tabulator-header-filter input {
        width: 100%;
        padding: 5px;
        box-sizing: border-box;
    }
</style>
@endsection

@section('content')

<div class="container">
    <h2>All Orders</h2>

    <div style="margin-bottom: 15px;">
        <label>From: <input type="date" id="dateFrom"></label>
        <label style="margin-left: 15px;">To: <input type="date" id="dateTo"></label>
        <button id="applyDateFilter" style="margin-left: 15px;">Apply</button>
        <button id="clearDateFilter" style="margin-left: 5px;">Clear</button>

        <button id="exportPdfBtn" class="export-btn" style="margin-left: 10px;">Export as PDF</button>


    </div>

    <div id="orders-table"></div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/tabulator.min.js') }}"></script>
<script src="{{ asset('js/jspdf-2.5.1.umd.min') }}"></script>
<script src="{{ asset('js/jspdf.plugin.autotable-3.5.25.min.js') }}"></script>


<script>
    document.addEventListener("DOMContentLoaded", function () {
        const table = new Tabulator("#orders-table", {
            data: @json($orders->items()),
            layout: "fitColumns",
            pagination: "local",
            paginationSize: 10,
            columns: [
                { title: "Order ID", field: "id", headerFilter: "input" },
                { title: "Order Token", field: "token_number", headerFilter: "input" },
                { title: "Receipt Token", field: "token.number", headerFilter: "input" },
                {
                    title: "Items",
                    field: "items",
                    headerSort: false,
                    formatter: function (cell) {
                        const items = cell.getValue();
                        return items.map(item => `${item.name} (${item.qty} * ${item.price})`).join("<br>");
                    }
                },
                { title: "Total", field: "total_amount", headerFilter: "input" },
                {
                    title: "Date",
                    field: "created_at",
                    headerFilter: "input",
                    mutator: value => {
                        if (!value) return "";
                        const dateObj = new Date(value);
                        return dateObj.toLocaleString(); // includes both date & time in local format
                    }
                }

            ]
        });

        // Custom date range filter
        function dateRangeFilter(data) {
            const from = document.getElementById("dateFrom").value;
            const to = document.getElementById("dateTo").value;

            const date = data.created_at.split("T")[0];

            return (!from || date >= from) && (!to || date <= to);
        }

        document.getElementById("applyDateFilter").addEventListener("click", () => {
            const from = document.getElementById("dateFrom").value;
            const to = document.getElementById("dateTo").value;

            table.setFilter((data) => {
                const rawDate = data.created_at;

                if (!rawDate) return false;

                const dateObj = new Date(rawDate);
                if (isNaN(dateObj.getTime())) return false;

                const dateOnly = dateObj.toISOString().split("T")[0];

                return (!from || dateOnly >= from) && (!to || dateOnly <= to);
            });
        });


        document.getElementById("clearDateFilter").addEventListener("click", () => {
            document.getElementById("dateFrom").value = "";
            document.getElementById("dateTo").value = "";
            table.clearFilter(true);
        });


        document.getElementById("exportPdfBtn").addEventListener("click", () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Fetch current filtered data
            const data = table.getData();

            if (!data.length) {
                alert("No data to export.");
                return;
            }

            // Prepare table headers and rows
            const headers = ["Order ID", "Token Number", "Items", "Total", "Date"];
            const rows = data.map(order => [
                order.id,
                order.token_number,
                order.items.map(i => `${i.name} (${i.qty})`).join(", "),
                order.total_amount,
                order.created_at.split("T")[0],
            ]);

            // Add title
            doc.setFontSize(14);
            doc.text("Orders Report", 14, 16);

            // Draw table
            doc.autoTable({
                startY: 20,
                head: [headers],
                body: rows,
                styles: { fontSize: 10 },
                headStyles: { fillColor: [52, 73, 94] },
            });

            // Save the PDF
            doc.save("orders-report.pdf");
        });



    });
</script>
@endsection
