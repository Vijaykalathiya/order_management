<!DOCTYPE html>
<html>
<head>
    <title>Order Entry</title>
    <style>
        body {
            display: flex;
            gap: 40px;
            font-family: sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 2px solid black;
            padding: 10px;
        }

        input[type="text"], input[type="number"] {
            width: 100%;
            border: none;
            padding: 8px;
            box-sizing: border-box;
        }

        input:focus {
            background-color: #e0f7fa;
        }

        .order-grid {
            width: 60%;
        }

        .summary {
            width: 35%;
        }

        .summary h3 {
            margin-bottom: 10px;
        }

        .summary table {
            width: 100%;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="order-grid">
    <label>
        <input type="checkbox" id="printByStation">
        Print by Station
    </label>
    
    <h2>Order Entry</h2>
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
                    <td><input type="text" class="product-name" name="product_name[]" tabindex="-1"></td>
                    <td><input type="text" class="product-price" name="product_price[]" readonly tabindex="-1"></td>
                    <td><input type="number" class="quantity" name="quantity[]" value="1"></td>
                </tr>
            </tbody>
        </table>
        <br>
        <button type="button" id="printOrder">Print Order</button>
    </form>
</div>

<div class="summary">
    <h3>Order Summary</h3>
    <table id="orderSummary">
        <thead>
            <tr>
                <th>Item</th>
                <th>QTY</th>
                <th>Price</th>
                <th>Total</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <!-- Populated dynamically -->
        </tbody>
    </table>
</div>

<script>
    const products = @json($products);

    $(document).ready(function () {
        $('.product-id:first').focus();

        let orderList = [];

        function updateSummaryTable() {
            const $tbody = $('#orderSummary tbody');
            $tbody.empty();

            let total_amount = parseFloat(0);
            orderList.forEach((item, index) => {
                const total = (item.qty * item.price).toFixed(2);
                total_amount = (parseFloat(total_amount) + parseFloat(total)).toFixed(2);
                $tbody.append(`
                    <tr data-index="${index}">
                        <td>${item.name}</td>
                        <td>${item.qty}</td>
                        <td>${item.price}</td>
                        <td>${total}</td>
                        <td><button class="btn btn-sm btn-danger delete-item">Delete</button></td>
                    </tr>
                `);
            });
            $tbody.append(`
                <tr>
                    <td colspan=3>Total Amount</td>
                    <td>${total_amount}</td>
                </tr>
            `);
        }

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

        // Live match by product name or code from product-name input
        // $(document).on('input', '.product-name', function () {
        //     const $row = $(this).closest('tr');
        //     const input = $(this).val().trim().toLowerCase();

        //     // Match full string in either product name or item code
        //     const product = products.find(p =>
        //         p.product_name.toLowerCase() === input ||
        //         p.item_code.toLowerCase() === input
        //     );

        //     if (product) {
        //         $row.find('.product-name').val(product.product_name);
        //         $row.find('.product-id').val(product.item_code);
        //         $row.find('.product-price').val(product.selling_price);
        //     } else {
        //         // Optionally clear fields if no match
        //         $row.find('.product-id').val('');
        //         $row.find('.product-price').val('');
        //     }
        // });

        // Tab directly from ID to QTY
        $(document).on('keydown', '.product-id', function (e) {
            if (e.key === 'Tab' || e.key === 'Enter') {
                e.preventDefault();
                const $row = $(this).closest('tr');
                const $qty = $row.find('.quantity');
                $qty.focus().select(); // Focus and select text
            }
        });

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

                if (code && name && !isNaN(price) && qty > 0) {
                    const existing = orderList.find(item => item.code === code);
                    if (existing) {
                        existing.qty += qty;
                    } else {
                        orderList.push({ code, name, price, qty, station });
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

        // F7 shortcut for printing
        $(document).on('keydown', function (e) {
            if (e.key === 'F7') {
                e.preventDefault();
                handlePrint();
            }
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

            // if (printByStation) {
            //     const grouped = {};

            //     orderList.forEach(item => {
            //         if (!grouped[item.station]) grouped[item.station] = [];
            //         grouped[item.station].push(item);
            //     });

            //     console.log("Printing by station...");
            //     for (const [station, items] of Object.entries(grouped)) {
            //         console.log(`\n--- Station: ${station} ---`);
            //         items.forEach(i => {
            //             console.log(`${i.name} x${i.qty} @ ${i.price} = ${i.qty * i.price}`);
            //         });
            //     }

            // } else {
            //     console.log("\n--- Unified Order ---");
            //     orderList.forEach(i => {
            //         console.log(`${i.name} x${i.qty} @ ${i.price} = ${i.qty * i.price}`);
            //     });
            //     $.ajax({
            //         url: "{{ route('print.order') }}",
            //         type: "POST",
            //         data: {
            //             _token: "{{ csrf_token() }}",
            //             items: orderList,
            //             by_station: printByStation
            //         },
            //         success: function () {
            //             alert('Order sent to printer');
            //         },
            //         error: function (xhr) {
            //             alert('Failed to print: ' + xhr.responseJSON?.error);
            //         }
            //     });
            // }

            alert("Order printed (check console for output)");
        }

        // Manual Print Button
        $('#printOrder').click(function () {
            handlePrint();
        });

        $(document).on('click', '.delete-item', function () {
            const index = $(this).closest('tr').data('index');
            orderList.splice(index, 1); // Remove from list
            updateSummaryTable();       // Refresh the UI
        });
    });
</script>


</body>
</html>
