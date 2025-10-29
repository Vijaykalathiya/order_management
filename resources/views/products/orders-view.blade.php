@extends('layouts.app')

@section('title', 'All Orders')

@section('css')
<link rel="stylesheet" href="{{ asset('css/tabulator.min.css') }}">
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

    #totalAmountDisplay {
        margin-top: 20px;
        font-weight: bold;
        font-size: 18px;
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
        <button id="exportExcelBtn" class="export-btn" style="margin-left: 10px;">Export as Excel</button>
    </div>

    <div id="orders-table"></div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
<script src="{{ asset('js/tabulator.min.js') }}"></script>
<script src="{{ asset('js/jspdf-2.5.1.umd.min.js') }}"></script>
<script src="{{ asset('js/jspdf.plugin.autotable-3.5.25.min.js') }}"></script>
<script src="{{asset('js/xlsx.full.min.js')}}"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const table = new Tabulator("#orders-table", {
            data: @json($orders),
            layout: "fitColumns",
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
                {
                    title: "Total",
                    field: "total_amount",
                    headerFilter: "input",
                    topCalc: "sum", // ✅ This shows the sum under the column
                    topCalcFormatter: "money",
                    topCalcFormatterParams: {
                        symbol: "₹",
                        precision: 2
                    }
                },
                {
                    title: "Date",
                    field: "created_at",
                    headerFilter: "input",
                    mutator: value => {
                        if (!value) return "";
                        const dateObj = new Date(value);
                        return dateObj.toLocaleString();
                    }
                },
                {
                    title: "Print",
                    headerSort: false,
                    formatter: () => `<button class="print-btn">🖨️ Print</button>`,
                    cellClick: function (e, cell) {
                        const order = cell.getData();

                        console.log(order);

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
                            success: function () {
                                alert('Receipt sent to printer.');
                            },
                            error: function (xhr) {
                                alert('Failed to print: ' + (xhr.responseJSON?.error ?? 'Unknown error'));
                            }
                        });
                    }
                }
            ],
            downloadConfig: {
                columnGroups: false,
                rowGroups: false,
                columnCalcs: false,
            }
        });

        
document.getElementById("applyDateFilter").addEventListener("click", () => {
    const fromVal = document.getElementById("dateFrom").value; // "YYYY-MM-DD"
    const toVal = document.getElementById("dateTo").value;     // "YYYY-MM-DD"

    // Convert input values into Date objects safely (ignore timezones)
    const fromDate = fromVal ? new Date(fromVal + "T00:00:00") : null;
    const toDate = toVal ? new Date(toVal + "T23:59:59") : null; // include whole day

    table.setFilter((rowData) => {
        const rawDate = rowData.created_at; // e.g. "10/29/2025, 3:17:51 PM"
        if (!rawDate) return false;

        // Extract date part & parse MM/DD/YYYY correctly
        const [datePart] = rawDate.split(",");
        const [month, day, year] = datePart.trim().split("/").map(Number);

        // Construct row date with no time bias
        const rowDate = new Date(year, month - 1, day, 12, 0, 0); // midday = avoids timezone drift

        // Apply inclusive filter
        const include =
            (!fromDate || rowDate >= fromDate) &&
            (!toDate || rowDate <= toDate);

        return include;
    });
});







        document.getElementById("clearDateFilter").addEventListener("click", () => {
            document.getElementById("dateFrom").value = "";
            document.getElementById("dateTo").value = "";
            table.clearFilter(true);
        });

        // document.getElementById("exportPdfBtn").addEventListener("click", () => {
        //     const { jsPDF } = window.jspdf;
        //     const doc = new jsPDF();

        //     const data = table.getData();

        //     if (!data.length) {
        //         alert("No data to export.");
        //         return;
        //     }

        //     const headers = ["Order ID", "Token Number", "Items", "Total", "Date"];
        //     const rows = data.map(order => [
        //         order.id,
        //         order.token_number,
        //         order.items.map(i => `${i.name} (${i.qty})`).join(", "),
        //         order.total_amount,
        //         new Date(order.created_at).toLocaleString(),
        //     ]);

        //     doc.setFontSize(14);
        //     doc.text("Orders Report", 14, 16);

        //     doc.autoTable({
        //         startY: 20,
        //         head: [headers],
        //         body: rows,
        //         styles: { fontSize: 10 },
        //         headStyles: { fillColor: [52, 73, 94] },
        //     });

        //     doc.save("orders-report.pdf");
        // });

        // document.getElementById("exportExcelBtn").addEventListener("click", () => {
        //     const fullData = table.getData();

        //     if (!fullData.length) {
        //         alert("No data to export.");
        //         return;
        //     }

        //     const formattedData = fullData.map(order => ({
        //         "Order ID": order.id,
        //         "Token Number": order.token_number,
        //         "Items": order.items.map(i => `${i.name} (${i.qty})`).join(", "),
        //         "Total": order.total_amount,
        //         "Date": new Date(order.created_at).toLocaleString(),
        //     }));

        //     const ws = XLSX.utils.json_to_sheet(formattedData);
        //     const wb = XLSX.utils.book_new();
        //     XLSX.utils.book_append_sheet(wb, ws, "Orders");

        //     XLSX.writeFile(wb, "orders-report.xlsx");
        // });

        document.getElementById("exportPdfBtn").addEventListener("click", () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Get only visible (filtered and sorted) data
    const data = table.getData("active");

    if (!data.length) {
        alert("No data to export.");
        return;
    }

    const headers = ["Order ID", "Token Number", "Items", "Total", "Date"];
    const rows = data.map(order => [
        order.id,
        order.token_number,
        order.items.map(i => `${i.name} (${i.qty})`).join(", "),
        order.total_amount,
        new Date(order.created_at).toLocaleString(),
    ]);

    doc.setFontSize(14);
    doc.text("Orders Report", 14, 16);

    doc.autoTable({
        startY: 20,
        head: [headers],
        body: rows,
        styles: { fontSize: 10 },
        headStyles: { fillColor: [52, 73, 94] },
    });

    doc.save("orders-report.pdf");
});

document.getElementById("exportExcelBtn").addEventListener("click", () => {
    // Get only visible (filtered and sorted) data
    const fullData = table.getData("active");

    if (!fullData.length) {
        alert("No data to export.");
        return;
    }

    const formattedData = fullData.map(order => ({
        "Order ID": order.id,
        "Token Number": order.token_number,
        "Items": order.items.map(i => `${i.name} (${i.qty})`).join(", "),
        "Total": order.total_amount,
        "Date": new Date(order.created_at).toLocaleString(),
    }));

    const ws = XLSX.utils.json_to_sheet(formattedData);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Orders");

    XLSX.writeFile(wb, "orders-report.xlsx");
});

    });
</script>
@endsection
