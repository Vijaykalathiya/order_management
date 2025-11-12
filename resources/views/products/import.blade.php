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
    .btn-success {
        background-color: #28a745;
        color: white;
        margin-left: 10px;
    }
    .btn-success:hover {
        background-color: #218838;
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
        <button id="exportExcelBtn" class="export-btn">
            Export to Excel
        </button>
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
        const filtered = table.getDataCount();
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
            { title: "Product Code", field: "item_code", headerFilter: "input", editor: false },
            { title: "Product Name", field: "product_name", headerFilter: "input", editor: "input" },
            { title: "Category", field: "category_name", headerFilter: "input", editor: "input" },
            { title: "Price", field: "selling_price", headerFilter: "input", editor: "input" },
            {
                title: "Actions",
                formatter: function (cell) {
                    return `
                        <button class="btn-edit" style="color:#007bff;border:none;background:none;">✏️</button>
                        <button class="btn-delete" style="color:#dc3545;border:none;background:none;">🗑️</button>
                    `;
                },
                width: 100,
                hozAlign: "center",
                cellClick: function (e, cell) {
                    const row = cell.getRow();
                    const product = row.getData();
                    const target = e.target;

                    if (target.classList.contains("btn-edit")) {
                        toggleEditRow(row, target);
                    }
                    if (target.classList.contains("btn-delete")) {
                        deleteProduct(product);
                    }
                },
            },
        ],
    });

    // 🔄 Toggle edit/save mode for row
    function toggleEditRow(row, button) {
        const product = row.getData();

        if (button.dataset.mode === "edit") {
            // ✅ Save mode
            saveProduct(row);
            button.textContent = "✏️";
            button.dataset.mode = "view";
            row.getCells().forEach(cell => cell._cell.element.classList.remove("editing"));
            return;
        }

        // ✏️ Switch to edit mode
        button.textContent = "💾";
        button.dataset.mode = "edit";
        row.getCells().forEach(cell => {
            const col = cell.getColumn().getField();
            if (["product_name", "category_name", "selling_price"].includes(col)) {
                cell._cell.element.classList.add("editing");
            }
        });
        row.getElement().style.backgroundColor = "#fff8dc";
    }

    // 💾 Save Product (AJAX)
    async function saveProduct(row) {
        const product = row.getData();

        try {
            const response = await fetch("{{ route('products.update') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(product),
            });

            const data = await response.json();
            if (response.ok && data.success) {
                alert("✅ Product updated successfully!");
                row.getElement().style.backgroundColor = "#eaffea";
            } else {
                alert("⚠️ " + data.message);
            }
        } catch (err) {
            console.error(err);
            alert("Error updating product: " + err.message);
        }
    }

    // 🗑️ Delete Product (AJAX)
    async function deleteProduct(product) {
        if (!confirm(`Delete product "${product.product_name}" permanently?`)) return;

        try {
            const response = await fetch("{{ route('products.delete') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ id: product.id }),
            });

            const data = await response.json();
            if (response.ok && data.success) {
                table.deleteRow(product.id);
                alert("🗑️ Product deleted successfully!");
            } else {
                alert("⚠️ " + data.message);
            }
        } catch (err) {
            console.error(err);
            alert("Error deleting product: " + err.message);
        }
    }

    // 📤 Upload Excel
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
        table.on("dataLoaded", updateProductCount);
        table.on("dataProcessed", updateProductCount);
        updateProductCount();

        // 📄 Export to PDF
        document.getElementById("exportPdfBtn").addEventListener("click", () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const data = table.getData();

            if (!data.length) {
                alert("No data to export.");
                return;
            }

            const headers = ["Product Code", "Product Name", "Category", "Price"];
            const rows = data.map(p => [
                p.item_code,
                p.product_name,
                p.category_name,
                p.selling_price,
            ]);

            doc.setFontSize(14);
            doc.text("Product List", 14, 16);

            doc.autoTable({
                startY: 20,
                head: [headers],
                body: rows,
                styles: { fontSize: 10 },
                headStyles: { fillColor: [40, 167, 69] },
            });

            doc.save("products.pdf");
        });

        // 📊 Export to Excel
        document.getElementById("exportExcelBtn").addEventListener("click", () => {
            const query = new URLSearchParams().toString();
            window.location.href = "{{ route('products.export') }}?" + query;
        });
    });
</script>
@endsection
