@extends('layouts.app')

@section('title', 'Import Products')

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

    .export-btn {
        background-color: #28a745;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 10px;
    }

    .export-btn:hover {
        background-color: #1e7e34;
    }

    .tabulator-header-filter input {
        width: 100%;
        padding: 5px;
        box-sizing: border-box;
    }

    .upload-form {
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .product-information {
        display: flex;
        gap: 2rem;
    }

    .product-upload, .product-listing {
        padding: 10px;
    }

    .product-listing {
        border-left: 1px solid;
        border-left-style: dotted;
    }
</style>
@endsection

@section('content')

<div class="product-information">

    <div class="product-upload">  
        <h1 class="page-title">Upload Product Excel File</h1>

        <form id="uploadForm" enctype="multipart/form-data" class="upload-form">
            <input type="file" name="file" id="fileInput" required accept=".xlsx,.xls" class="input-file">
            <span id="fileNameDisplay" class="file-name-display"></span>

            <button type="submit" class="btn-primary" id="uploadBtn">Upload</button>

            <div id="loader" class="loader" style="display: none;"></div>
        </form>

        <div id="result" class="upload-result"></div>
        @if(session('success'))
            <div style="color: green; font-weight: bold; margin-bottom: 10px;">
                {{ session('success') }}
            </div>
        @endif

    </div>

    <div class="product-listing"> 
        <button id="exportPdfBtn" class="export-btn">Export as PDF</button>
        <div id="product-count" class="mb-2 font-bold text-right"></div>
        <div id="products-table" style="margin-top: 20px;"></div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/tabulator.min.js') }}"></script>
    <script src="{{ asset('js/jspdf-2.5.1.umd.min.js') }}"></script>
    <script src="{{ asset('js/jspdf.plugin.autotable-3.5.25.min.js') }}"></script>

    <script>
        const fileInput = document.getElementById('fileInput');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        const loader = document.getElementById('loader');
        const resultDiv = document.getElementById('result');
        const uploadBtn = document.getElementById('uploadBtn');

        fileInput.addEventListener('change', function () {
            const fileName = this.files[0]?.name || '';
            fileNameDisplay.textContent = fileName ? `Selected: ${fileName}` : '';
        });

        function updateProductCount() {
            const total = table.getData().length;
            const filtered = table.getDataCount(); // counts filtered rows
            const display = filtered === total 
                ? `Total Products: ${total}` 
                : `Showing ${filtered} of ${total} products`;
            document.getElementById('product-count').textContent = display;
        }

        let table = new Tabulator("#products-table", {
            data: @json($products ?? []),
            layout: "fitColumns",
            pagination: "local",
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100],
            columns: [
                { title: "Product Code", field: "item_code", headerFilter: "input" },
                { title: "Product Name", field: "product_name", headerFilter: "input" },
                { title: "Category", field: "category_name", headerFilter: "input" },
                { title: "Price", field: "selling_price", headerFilter: "input" }
            ]
        });

        document.getElementById('uploadForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            loader.style.display = 'inline-block';
            uploadBtn.disabled = true;
            resultDiv.textContent = '';

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const response = await fetch("{{ route('products.import') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    resultDiv.innerHTML = `<p class="success-message">${data.message}</p>`;

                    // ✅ Update Tabulator with new data
                    table.setData(data.products);
                    table.clearFilter();
                    table.clearSort();

                } else {
                    resultDiv.innerHTML = `<p class="error-message">${data.message}</p>`;
                }
            } catch (err) {
                resultDiv.innerHTML = `<p class="error-message">An error occurred: ${err.message}</p>`;
            } finally {
                loader.style.display = 'none';
                uploadBtn.disabled = false;
            }
        });

        document.addEventListener("DOMContentLoaded", function () {

            // Hook into changes
            table.on("dataLoaded", updateProductCount);
            table.on("dataProcessed", updateProductCount);

            // Optional: Initial update
            updateProductCount();

            document.getElementById("exportPdfBtn").addEventListener("click", () => {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                const data = table.getData();

                if (!data.length) {
                    alert("No data to export.");
                    return;
                }

                const headers = ["Product Name", "Category", "Price", "Quantity", "Created At"];
                const rows = data.map(p => [
                    p.name,
                    p.category,
                    p.price,
                    p.quantity,
                    p.created_at.split("T")[0]
                ]);

                doc.setFontSize(14);
                doc.text("Uploaded Products", 14, 16);

                doc.autoTable({
                    startY: 20,
                    head: [headers],
                    body: rows,
                    styles: { fontSize: 10 },
                    headStyles: { fillColor: [40, 167, 69] },
                });

                doc.save("products.pdf");
            });
        });
    </script>
@endsection
