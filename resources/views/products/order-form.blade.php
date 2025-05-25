@extends('layouts.app')

@section('title', 'Order Entry')

@section('css')
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ddd;
            box-sizing: border-box;
            border-radius: 4px;
        }

        input:focus {
            background-color: #e0f7fa;
        }

        .order-details {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            margin-top: 20px;
        }

        .product-listing {
            width: 75%;
        }

        .order-grid {
            width: 90%;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            background-color: #fff;
        }

        .order-grid h2 {
            margin-bottom: 20px;
        }

        .summary {
            width: 25%;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 8px;
            background-color: #fff;
        }

        .summary h3 {
            margin-bottom: 15px;
        }

        .summary table {
            width: 100%;
        }

        .order-grid table, .summary table {
            margin-bottom: 20px;
        }

        .order-summary-footer {
            font-weight: bold;
            text-align: right;
            padding-top: 10px;
        }

        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .delete-item {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .delete-item:hover {
            background-color: #c82333;
        }

        .product-id,
        .product-name,
        .product-price,
        .quantity {
            max-width: 200px;
        }

        .product-id,
        .product-name,
        .product-price {
            text-align: center;
        }

        .quantity {
            text-align: center;
        }

        label {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-footer button {
            background-color: #28a745;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            color: white;
        }

        .form-footer button:hover {
            background-color: #218838;
        }

        .order-summary-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 10px
        }

        .category-listing {
            display: flex;
        }

        .favorites {
            flex-basis: 10rem;
            flex-grow: 0;
            flex-shrink: 0;
        }

        .item-display {
            margin-left: 4rem;
        }

        button.category-btn {
            background: #f1f8ff;
            padding: 5px;
            text-align: end;
            font-size: 14px;
            font-weight: 600;
        }

        .qty-control {
            display: inline-flex;
            align-items: center;
            background-color: #1976d2; /* Material blue */
            border-radius: 10px;
            padding: 4px 8px;
            color: white;
            font-weight: bold;
            min-width: 90px;
            justify-content: space-between;
        }

        .qty-btn {
            background: transparent;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            width: 28px;
            height: 28px;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:focus {
            outline: none;
        }

        .qty-display {
            margin: 0 8px;
            min-width: 20px;
            text-align: center;
            font-size: 16px;
        }



        /* Responsiveness */
        @media (max-width: 768px) {
            .order-details {
                flex-direction: column;
                align-items: center;
            }

            .order-grid, .summary {
                width: 100%;
            }

            .form-footer {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
@endsection

@section('content')

    <div class="order-details">
        <!-- MIDDLE PANEL: Order Entry + Product Browser -->
        <div class="product-listing">
            <div class="order-grid">
                <form id="orderForm">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>QTY</th>
                            </tr>
                        </thead>
                        <tbody id="orderGrid">
                            <tr>
                                <td><input type="text" class="product-id" name="product_id[]"></td>
                                <td><input type="text" class="product-name" name="product_name[]" readonly tabindex="-1"></td>
                                <td><input type="text" class="product-price" name="product_price[]" readonly tabindex="-1"></td>
                                <td><input type="number" class="quantity" name="quantity[]" value="1"></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>

            <div class="category-listing">

                <!-- LEFT SIDEBAR (Categories & Best Seller) -->
                <div class="favorites">
                    <ul id="categoryList" style="list-style: none; padding: 0;">
                        <li><button class="category-btn" data-type="best-seller" style="width: 100%; margin-bottom: 10px;">Best Seller</button></li>
                        @foreach($categories as $category)
                            <li>
                                <button class="category-btn" data-type="category" data-category="{{ $category }}" style="width: 100%; margin-bottom: 5px;">
                                    {{ ucfirst($category) }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Search + Items Display -->
                <div class="item-display">
                    <div id="categoryItemsDisplay"
                        style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 15px;">
                        <!-- Populated via JS -->
                    </div>
                </div>

            </div>
        </div>

        <!-- RIGHT: Order Summary -->
        <div class="summary">
            <h3>Order Summary</h3>
            <div class="order-summary-header"> 
                <label>
                    <input type="checkbox" id="printByStation">
                    Print by Station
                </label>
                <div class="form-footer">
                    <button type="button" id="printOrder">Create Order & Print</button>
                </div>
            </div>
            <table id="orderSummary">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>QTY</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Populated dynamically -->
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>

    <script>
        const products = @json($products);
        const bestSellers = @json($bestSellers);
    
        let currentItems = [];
    
        function renderItems(items) {
            currentItems = items;
            displayFilteredItems($('#itemSearchInput').val());
        }
    
        function displayFilteredItems(searchTerm = '') {
            $('#categoryItemsDisplay').empty();
    
            const filtered = currentItems.filter(item =>
                item.item_code.toLowerCase().includes(searchTerm.toLowerCase())
            );
    
            if (filtered.length === 0) {
                $('#categoryItemsDisplay').html('<p>No matching items found.</p>');
                return;
            }
    
            filtered.forEach(item => {
                const card = `
                    <div class="fav-card"
                        data-code="${item.item_code}"
                        data-name="${item.product_name}"
                        data-station="${item.station}"
                        data-price="${parseFloat(item.selling_price)}"
                        style="background: #f1f8ff; padding: 10px; border: 1px solid #ccc;
                            border-radius: 8px; width: 140px; text-align: center; cursor: pointer;">
                        <small>${item.item_code} - </small>
                        <strong>${item.product_name}</strong>
                        <div>₹${parseFloat(item.selling_price).toFixed(2)}</div>
                    </div>
                `;
                $('#categoryItemsDisplay').append(card);
            });
        }
    
        $(document).ready(function () {
            $('.product-id:first').focus();
            let orderList = [];

            // Load best sellers on page load
            renderItems(bestSellers);

            // Live match for product name and price
            $(document).on('input', '.product-id', function () {
                const $row = $(this).closest('tr');
                const inputCode = $(this).val().trim();
                const product = products.find(p => p.item_code === inputCode);
    
                if (product) {
                    $row.find('.product-name').val(product.product_name);
                    $row.find('.product-price').val(product.selling_price);
                } else {
                    $row.find('.product-name').val('');
                    $row.find('.product-price').val('');
                }
            });
    
            // Tab directly from ID to QTY
            $(document).on('keydown', '.product-id', function (e) {
                if (e.key === 'Tab' || e.key === 'Enter') {
                    e.preventDefault();
                    const $row = $(this).closest('tr');
                    const $qty = $row.find('.quantity');
                    $qty.focus().select(); // Focus and select text
                }
            });

            function normalizeCodeMatch(list, code) {
                return list.find(item => String(item.code) === String(code));
            }

            // Enter on QTY adds to orderList
            $(document).on('keydown', '.quantity', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
    
                    const $row = $(this).closest('tr');
                    const code = $row.find('.product-id').val().trim();
                    const product = products.find(p => p.item_code === code);
    
                    if (!product) {
                        alert("Invalid product code");
                        return;
                    }
    
                    const name = product.product_name;
                    const price = parseFloat(product.selling_price);
                    const station = product.station || 'Default';
                    const qty = parseInt($row.find('.quantity').val().trim());
    
                    if (code && name && !isNaN(price)) {
                        const existing = normalizeCodeMatch(orderList, code);
                        if (existing) {
                            existing.qty += qty;
                        } else {
                            orderList.push({ code: String(code), name, price, qty: Math.abs(qty), station });
                        }
    
                        updateSummaryTable();
    
                        // Clear for next entry
                        $row.find('.product-id').val('').focus();
                        $row.find('.product-name').val('');
                        $row.find('.product-price').val('');
                        $row.find('.quantity').val('1');
                    } else {
                        alert('Complete all fields with valid data.');
                    }
                }
            });
    
            // Category buttons
            $('.category-btn').click(function () {
                const type = $(this).data('type');
                if (type === 'best-seller') {
                    renderItems(bestSellers);
                } else {
                    const category = $(this).data('category');
                    const filtered = products.filter(p => p.category_name === category);
                    renderItems(filtered);
                }
            });
    
            // Search input
            $('#itemSearchInput').on('input', function () {
                displayFilteredItems($(this).val());
            });
    
            // Click to add to order
            $(document).on('click', '.fav-card', function () {
                const code = $(this).data('code');
                const name = $(this).data('name');
                const price = parseFloat($(this).data('price'));
                const station = $(this).data('station') || 'Default';
    
                const existing = normalizeCodeMatch(orderList, code);
                if (existing) {
                    existing.qty += 1;
                } else {
                    orderList.push({ code: String(code), name, price, qty: 1, station });
                }
    
                updateSummaryTable();
            });

            $(document).on('click', '.delete-item', function () {
                const index = $(this).closest('tr').data('index');
                orderList.splice(index, 1); // Remove from list
                updateSummaryTable();       // Refresh the UI
            });

                        // Handle Print Logic
            function handlePrint() {
                const printByStation = $('#printByStation').is(':checked');
    
                if (!orderList.length) {
                    alert('No items in order!');
                    return;
                }
    
                $.ajax({
                    url: "{{ route('print.order') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        items: orderList,
                        printByStation: printByStation
                    },
                    success: function () {
                        alert('Order sent to printer');
                    },
                    error: function (xhr) {
                        alert('Failed to print: ' + xhr.responseJSON?.error);
                    }
                });
            }
    
            // Manual Print Button
            $('#printOrder').click(function () {
                handlePrint();
            });

            $(document).on('click', '.increase-qty', function () {
                const code = $(this).data('code');
                const existing = normalizeCodeMatch(orderList, code);
                if (existing) {
                    existing.qty++;
                    updateSummaryTable();
                }
            });

            $(document).on('click', '.decrease-qty', function () {
                const code = $(this).data('code');
                const existingIndex = orderList.findIndex(item => String(item.code) === String(code));
                if (existingIndex > -1) {
                    orderList[existingIndex].qty--;
                    if (orderList[existingIndex].qty <= 0) {
                        orderList.splice(existingIndex, 1); // Remove item
                    }
                    updateSummaryTable();
                }
            });

            function updateSummaryTable() {
                const $tbody = $('#orderSummary tbody');
                $tbody.empty();

                let total_amount = 0;

                orderList.forEach((item, index) => {
                    const total = (item.qty * item.price).toFixed(2);
                    total_amount += parseFloat(total);

                    $tbody.append(`
                        <tr data-index="${index}">
                            <td>${item.name}</td>
                            <td>
                                <div class="qty-control">
                                    <button class="qty-btn decrease-qty" data-code="${item.code}">−</button>
                                    <span class="qty-display">${item.qty}</span>
                                    <button class="qty-btn increase-qty" data-code="${item.code}">+</button>
                                </div>
                            </td>
                            <td>${item.price}</td>
                            <td>${total}</td>
                        </tr>
                    `);
                });

                $tbody.append(`
                    <tr>
                        <td colspan="3" class="order-summary-footer">Total Amount</td>
                        <td class="order-summary-footer">${total_amount.toFixed(2)}</td>
                    </tr>
                `);
            }

        });
    </script>
@endsection
