<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Token;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        return view('products.orders-view');
    }

    /**
     * Get paginated orders data for Tabulator
     */
    public function getData(Request $request)
    {
        $query = Order::with(['items', 'token']);

        // Apply date filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply column filters
        if ($request->filled('filter')) {
            foreach ($request->filter as $filter) {
                $field = $filter['field'];
                $value = $filter['value'];

                if ($field === 'id') {
                    $query->where('id', $value);
                } elseif ($field === 'token_number') {
                    $query->where('token_number', 'like', "%{$value}%");
                } elseif ($field === 'token.number') {
                    $query->whereHas('token', function($q) use ($value) {
                        $q->where('number', 'like', "%{$value}%");
                    });
                } elseif ($field === 'total_amount') {
                    $query->where('total_amount', '>=', $value);
                }
            }
        }

        // Apply sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        
        if ($sortField === 'created_at') {
            $query->orderBy('created_at', $sortDir);
        } elseif ($sortField === 'total_amount') {
            $query->orderBy('total_amount', $sortDir);
        }

        // Get total count before pagination
        $totalCount = $query->count();

        // Apply pagination
        $page = $request->input('page', 1);
        $size = $request->input('size', 50);
        
        $orders = $query->skip(($page - 1) * $size)
                       ->take($size)
                       ->get();

        // Format the response for Tabulator
        return response()->json([
            'last_page' => ceil($totalCount / $size),
            'data' => $orders->map(function($order) {
                return [
                    'id' => $order->id,
                    'token_number' => $order->token_number,
                    'token' => $order->token ? ['number' => $order->token->number] : null,
                    'items' => $order->items->map(function($item) {
                        return [
                            'item_code' => $item->item_code,
                            'name' => $item->name,
                            'qty' => $item->qty,
                            'price' => $item->price,
                            'station' => $item->station,
                        ];
                    }),
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at->toDateTimeString(),
                ];
            })
        ]);
    }

    /**
     * Get analytics data
     */
    public function getAnalytics(Request $request)
    {
        try {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            // Build base query
            $ordersQuery = Order::query();
            
            if ($dateFrom) {
                $ordersQuery->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $ordersQuery->whereDate('created_at', '<=', $dateTo);
            }

            // Statistics
            $totalOrders = (clone $ordersQuery)->count();
            $totalRevenue = (clone $ordersQuery)->sum('total_amount') ?? 0;
            $avgOrderValue = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;

            $statistics = [
                'totalOrders' => $totalOrders,
                'totalRevenue' => round($totalRevenue, 2),
                'todayOrders' => Order::whereDate('created_at', today())->count(),
                'avgOrderValue' => round($avgOrderValue, 2),
            ];

            // Top 10 selling items - Check if OrderItem model exists
            $topItems = [];
            if (class_exists('App\Models\OrderItem')) {
                $topItemsQuery = OrderItem::select('name', DB::raw('SUM(qty) as total_quantity'))
                    ->groupBy('name')
                    ->orderBy('total_quantity', 'desc')
                    ->limit(10);

                if ($dateFrom || $dateTo) {
                    $topItemsQuery->whereHas('order', function($q) use ($dateFrom, $dateTo) {
                        if ($dateFrom) $q->whereDate('created_at', '>=', $dateFrom);
                        if ($dateTo) $q->whereDate('created_at', '<=', $dateTo);
                    });
                }

                $topItems = $topItemsQuery->get()->toArray();
            } else {
                // Fallback: Extract from order items JSON/relationship
                $orders = (clone $ordersQuery)->with('items')->get();
                $itemCounts = [];
                
                foreach ($orders as $order) {
                    foreach ($order->items as $item) {
                        $name = $item->name ?? $item['name'] ?? 'Unknown';
                        $qty = $item->qty ?? $item['qty'] ?? 1;
                        
                        if (!isset($itemCounts[$name])) {
                            $itemCounts[$name] = 0;
                        }
                        $itemCounts[$name] += $qty;
                    }
                }
                
                arsort($itemCounts);
                $topItems = array_map(function($name, $count) {
                    return ['name' => $name, 'total_quantity' => $count];
                }, array_keys(array_slice($itemCounts, 0, 10)), array_slice($itemCounts, 0, 10));
            }

            // Sales trend (last 7 days or filtered period)
            $salesTrend = [];
            
            if ($dateFrom && $dateTo) {
                $salesTrendQuery = Order::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(total_amount) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->groupBy('date')
                ->orderBy('date', 'asc');
                
                $salesTrend = $salesTrendQuery->get()->map(function($item) {
                    return [
                        'date' => date('M d', strtotime($item->date)),
                        'total' => (float) $item->total,
                        'count' => $item->count,
                    ];
                })->toArray();
            } else {
                // Get last 30 days to ensure we have some data
                $salesTrendQuery = Order::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(total_amount) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date', 'asc');
                
                $results = $salesTrendQuery->get();
                
                // If no data in last 30 days, get the most recent data
                if ($results->isEmpty()) {
                    $salesTrendQuery = Order::select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('SUM(total_amount) as total'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->limit(7);
                    
                    $results = $salesTrendQuery->get()->reverse()->values();
                }
                
                $salesTrend = $results->map(function($item) {
                    return [
                        'date' => date('M d', strtotime($item->date)),
                        'total' => (float) $item->total,
                        'count' => $item->count,
                    ];
                })->toArray();
            }

            return response()->json([
                'statistics' => $statistics,
                'topItems' => $topItems,
                'salesTrend' => $salesTrend,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Analytics error: ' . $e->getMessage());
            
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'statistics' => [
                    'totalOrders' => 0,
                    'totalRevenue' => 0,
                    'todayOrders' => 0,
                    'avgOrderValue' => 0,
                ],
                'topItems' => [],
                'salesTrend' => [],
            ], 500);
        }
    }

    public function showOrderDetails(Request $request)
    {
        $orders = Order::with(['items', 'token'])
               ->orderBy('created_at', 'desc')
               ->get();

        return view('products.orders-view', compact('orders'));

    }

    public function printOrder(Request $request)
    {
        $orderItems = $request->input('items', []);
        $printByStation = filter_var($request->input('printByStation', false), FILTER_VALIDATE_BOOLEAN);
        $existingOrderId = $request->input('existingOrderId', null);

        if (empty($orderItems)) {
            return response()->json(['error' => 'No items to print.'], 400);
        }

        if($existingOrderId) {
            \Log::info("order found: " . $existingOrderId);
            $tokenNumer = Token::where('order_id', $existingOrderId)->first();;
            // Calculate grand total
            $grandTotal = collect($orderItems)->reduce(function ($carry, $item) {
                return $carry + ($item['qty'] * $item['price']);
            }, 0);

            $this->printToPrinter($orderItems, $tokenNumer->number, null, null, null, true, $grandTotal);

        } else {

            DB::beginTransaction();
    
            try {
                // Get latest token number and wrap at 99
                $latestToken = Token::latest()->first();
                $nextTokenNumber = ($latestToken && $latestToken->number < 99) ? $latestToken->number + 1 : 1;
    
                // Calculate grand total
                $grandTotal = collect($orderItems)->reduce(function ($carry, $item) {
                    return $carry + ($item['qty'] * $item['price']);
                }, 0);
    
                // Create Order
                $order = Order::create([
                    'token_number' => 'T' . str_pad($nextTokenNumber, 5, '0', STR_PAD_LEFT), // Optional: keep padded
                    'total_amount' => $grandTotal,
                ]);
    
                foreach ($orderItems as $item) {
                    $order->items()->create([
                        'item_code' => $item['code'],
                        'name' => $item['name'],
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'station' => $item['station'] ?? null,
                    ]);
                }
    
                // Save token tracking
                Token::create([
                    'number' => $nextTokenNumber,
                    'order_id' => $order->id,
                ]);
    
                // Use actual token number in print
                if ($printByStation) {
                    $grouped = collect($orderItems)->groupBy('station')->values();
                    $lastGroupIndex = $grouped->count() - 1;
    
                    foreach ($grouped as $index => $items) {
                        $subTotal = collect($items)->sum(function ($item) {
                            return $item['qty'] * $item['price'];
                        });

                        $includeTotal = ($index === $lastGroupIndex);
                        $this->printToPrinter($items, $nextTokenNumber, $items[0]['station'], $index+1, $lastGroupIndex+1, $includeTotal, $grandTotal, $subTotal);
                    }
                } else {
                    $this->printToPrinter($orderItems, $nextTokenNumber, null, null, null, true, $grandTotal, 0);
                }
    
                DB::commit();
    
                return response()->json([
                    'message' => 'Order saved and printed.',
                    'token' => $nextTokenNumber
                ]);
    
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Order failed: ' . $e->getMessage()], 500);
            }
        }



    }

    // Function to split string into chunks of 15 characters
    function splitItemName($name, $length = 15) {
        return str_split($name, $length);
    }


    private function printToPrinter($items, $tokenNumber, $station = null, $subStation= null, $totalStation= null,  $includeTotal = false, $grandTotal = 0, $subTotal = 0)
    {

        // "smb://localhost/TVS3230",
        // "smb://localhost/RugtekPrinter"

        $printers = [
            "smb://localhost/RugtekPrinter"
        ];
    
        $printed = false;

        foreach ($printers as $printerPath) {
            try {
                // Connect to the printer
                $connector = new WindowsPrintConnector($printerPath);
                $printer = new Printer($connector);
            
                // === HEADER ===

                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 1);
                $printer->setEmphasis(false);
                $printer->setUnderline(Printer::UNDERLINE_SINGLE);
                $appName = config('app.PRINT_TEXT');
                $printer->text("$appName\n");
                $printer->setUnderline(Printer::UNDERLINE_NONE);

                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 2);
                $printer->setEmphasis(true);
            
                // Station (if provided)
                if ($station) {
                    $printer->setJustification(Printer::JUSTIFY_CENTER);
                    $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
                    $printer->text("$station\n");
                }


                $printer->selectPrintMode(Printer::MODE_FONT_A);
                $printer->setTextSize(1, 1);
                $printer->setEmphasis(true);
                if ($station) {
                    $printer->text("TOKEN: $tokenNumber ($totalStation - $subStation)\n");
                } else {
                    $printer->text("TOKEN: $tokenNumber\n");
                }
                
                $printer->setTextSize(1, 1);
                $printer->setEmphasis(false);
            
                // Date and Time
                $currentTime = date('d-m-Y h:i A');
                $printer->text("Date: $currentTime\n");
                $printer->text("-------------------------------\n");
                // $printer->feed();
            
                // === ITEM LIST ===
                $printer->setJustification(Printer::JUSTIFY_CENTER);

                // Switch to smaller font to fit more text per line
                $printer->setFont(Printer::FONT_A);

                $printer->setEmphasis(true);
                foreach ($items as $item) {
                    $line = sprintf("%-16s %-2d Rs.%d\n", strtoupper($item['name']), $item['qty'], $item['price']);
                    $printer->text($line);
                }
                $printer->setEmphasis(false);

                $nameParts = $this->splitItemName(strtoupper($item['name']), 15);

                $line = sprintf("%-16s %-2d Rs.%d\n", $nameParts[0], $item['qty'], $item['price']);

                for ($i = 1; $i < count($nameParts); $i++) {
                    // Print remaining parts on new lines, qty and price empty
                    $line .= sprintf("%-16s\n", $nameParts[$i]);
                }
                $printer->text("-------------------------------\n");

                // Reset to default font after item list
                $printer->setFont(Printer::FONT_A);

                // === TOTAL ===
                if ($includeTotal) {
                    // $printer->feed();
                    $printer->setEmphasis(true);
                    $printer->setTextSize(1,1);
                    $printer->setJustification(Printer::JUSTIFY_CENTER);
                    if ($station) {
                        $printer->setPrintLeftMargin(125);
                        $printer->text("SUB TOTAL:" . number_format($subTotal, 2) . "\n");
                    }
                    $printer->setPrintLeftMargin(0);
                    $printer->text("GRAND TOTAL:" . number_format($grandTotal, 2) . "\n");
                    $printer->setEmphasis(false);
                } else {
                    // $printer->feed();
                    $printer->setJustification(Printer::JUSTIFY_CENTER);
                    $printer->setPrintLeftMargin(125);
                    $printer->setEmphasis(true);
                    $printer->setTextSize(1,1);
                    $printer->text("SUB TOTAL:" . number_format($subTotal, 2) . "\n");
                    $printer->setEmphasis(false);
                }
            
                $printer->selectPrintMode(Printer::MODE_FONT_A);
                // === FINALIZE ===
                $printer->feed(2); // Feed 2 lines for space
                $printer->cut(); // Cut the paper
                $printer->close(); // Close the printer connection

                $printed = true;
                break;

            } catch (\Exception $e) {
                // Error handling: Log the error if print fails
                // \Log::error("Print failed: " . $e->getMessage());
                \Log::error("Printer [$printerPath] failed: " . $e->getMessage());
                continue;
            }
        }
        if (!$printed) {
            return response()->json(['error' => 'All printers failed'], 500);
        }
    
        return response()->json(['success' => true]);
    }

    private function printToPrinterPrathna($items, $tokenNumber, $station = null, $subStation= null, $totalStation= null,  $includeTotal = false, $grandTotal = 0, $subTotal = 0)
    {

        // "smb://localhost/TVS3230",
        // "smb://localhost/RugtekPrinter"

        $printers = [
            "XP80C"
        ];
    
        $printed = false;

        foreach ($printers as $printerPath) {
            try {
                // Connect to the printer
                $connector = new WindowsPrintConnector($printerPath);
                $printer = new Printer($connector);
            
                // === HEADER ===

                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->setTextSize(1, 1);
                $printer->setEmphasis(false);
                $printer->setPrintLeftMargin(110);
                $printer->setUnderline(Printer::UNDERLINE_SINGLE);
                $appName = config('app.PRINT_TEXT');
                $printer->text("$appName\n");
                $printer->setUnderline(Printer::UNDERLINE_NONE);
                $printer->setPrintLeftMargin(25);

                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->setTextSize(1, 2);
                $printer->setEmphasis(true);
            
                // Station (if provided)
                if ($station) {
                    $printer->setJustification(Printer::JUSTIFY_LEFT);
                    $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
                    $printer->text("$station\n");
                }


                $printer->selectPrintMode(Printer::MODE_FONT_A);
                $printer->setTextSize(1, 1);
                $printer->setEmphasis(true);
                if ($station) {
                    $printer->text("TOKEN: $tokenNumber ($totalStation - $subStation)\n");
                } else {
                    $printer->text("TOKEN: $tokenNumber\n");
                }
                
                $printer->setTextSize(1, 1);
                $printer->setEmphasis(false);
            
                // Date and Time
                $currentTime = date('d-m-Y h:i A');
                $printer->text("Date: $currentTime\n");
                $printer->text("-------------------------------\n");
                // $printer->feed();
            
                // === ITEM LIST ===
                $printer->setJustification(Printer::JUSTIFY_LEFT);

                // Switch to smaller font to fit more text per line
                $printer->setFont(Printer::FONT_A);

                $printer->setEmphasis(true);
                $printer->setPrintLeftMargin(0);
                foreach ($items as $item) {
                    $line = sprintf("%-16s %-2d Rs.%d\n", strtoupper($item['name']), $item['qty'], $item['price']);
                    $printer->text($line);
                }
                $printer->setEmphasis(false);
                $printer->setPrintLeftMargin(25);

                $nameParts = $this->splitItemName(strtoupper($item['name']), 15);

                $line = sprintf("%-16s %-2d Rs.%d\n", $nameParts[0], $item['qty'], $item['price']);

                for ($i = 1; $i < count($nameParts); $i++) {
                    // Print remaining parts on new lines, qty and price empty
                    $line .= sprintf("%-16s\n", $nameParts[$i]);
                }
                $printer->text("-------------------------------\n");

                // Reset to default font after item list
                $printer->setFont(Printer::FONT_A);

                // === TOTAL ===
                if ($includeTotal) {
                    // $printer->feed();
                    $printer->setEmphasis(true);
                    $printer->setTextSize(1,1);
                    $printer->setJustification(Printer::JUSTIFY_LEFT);
                    if ($station) {
                        $printer->setPrintLeftMargin(140);
                        $printer->text("SUB TOTAL:" . number_format($subTotal, 2) . "\n");
                    }
                    $printer->setPrintLeftMargin(50);
                    $printer->text("GRAND TOTAL:" . number_format($grandTotal, 2) . "\n");
                    $printer->setEmphasis(false);
                } else {
                    // $printer->feed();
                    $printer->setJustification(Printer::JUSTIFY_LEFT);
                    $printer->setPrintLeftMargin(140);
                    $printer->setEmphasis(true);
                    $printer->setTextSize(1,1);
                    $printer->text("SUB TOTAL:" . number_format($subTotal, 2) . "\n");
                    $printer->setEmphasis(false);
                }
            
                $printer->selectPrintMode(Printer::MODE_FONT_A);
                // === FINALIZE ===
                $printer->feed(2); // Feed 2 lines for space
                $printer->cut(); // Cut the paper
                $printer->close(); // Close the printer connection

                $printed = true;
                break;

            } catch (\Exception $e) {
                // Error handling: Log the error if print fails
                // \Log::error("Print failed: " . $e->getMessage());
                \Log::error("Printer [$printerPath] failed: " . $e->getMessage());
                continue;
            }
        }
        if (!$printed) {
            return response()->json(['error' => 'All printers failed'], 500);
        }
    
        return response()->json(['success' => true]);
    }

    public function deleteRange(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Order::query();

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        } elseif ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        } elseif ($dateTo) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $deletedCount = $query->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} orders deleted successfully.",
        ]);
    }

    public function filter(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        $orders = Order::whereBetween('order_date', [$start, $end])
            ->with('items') // if you have relation
            ->get();

        // For Chart
        $chartData = $orders
            ->groupBy(fn($o) => \Carbon\Carbon::parse($o->order_date)->format('Y-m-d'))
            ->map(fn($group, $date) => [
                'label' => $date,
                'total' => $group->sum('total'),
            ])->values();

        // For Item Summary Grid
        $itemSummary = $orders->flatMap->items
            ->groupBy('item_code')
            ->map(fn($items, $code) => [
                'item_code' => $code,
                'item_name' => $items->first()->item_name,
                'total_qty' => $items->sum('quantity'),
                'total_amount' => $items->sum(fn($i) => $i->quantity * $i->price),
            ])->values();

        return response()->json([
            'orders' => $orders,
            'chart' => $chartData,
            'item_summary' => $itemSummary,
        ]);
    }

    public function exportAll(Request $request)
    {
        $query = Order::with(['items', 'token']);

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return response()->json(
            $query->orderBy('id', 'desc')->get()
        );
    }

    public function exportAnalysis(Request $request)
    {
        $query = OrderItem::query()
            ->selectRaw('name as product, SUM(qty) as total_qty');

        // Date filters
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $query->groupBy('name')
            ->orderBy('name');

        return response()->json($query->get());
    }

}
