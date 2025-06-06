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
                        $includeTotal = ($index === $lastGroupIndex);
                        $this->printToPrinter($items, $nextTokenNumber, $items[0]['station'], $index+1, $lastGroupIndex+1, $includeTotal, $grandTotal);
                    }
                } else {
                    $this->printToPrinter($orderItems, $nextTokenNumber, null, null, null, true, $grandTotal);
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


    private function printToPrinter($items, $tokenNumber, $station = null, $subStation= null, $totalStation= null,  $includeTotal = false, $grandTotal = 0)
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
                $printer->setTextSize(1, 2);
                $printer->setEmphasis(true);
            
                // Station (if provided)
                if ($station) {
                    $printer->setJustification(Printer::JUSTIFY_CENTER);
                    $printer->text("$station\n");
                }

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
                $printer->feed();
            
                // === ITEM LIST ===
                $printer->setJustification(Printer::JUSTIFY_CENTER);

                // Switch to smaller font to fit more text per line
                $printer->setFont(Printer::FONT_A);

                foreach ($items as $item) {
                    $line = sprintf("%-16s %-2d Rs.%d\n", strtoupper($item['name']), $item['qty'], $item['price']);
                    $printer->text($line);
                }

                $nameParts = $this->splitItemName(strtoupper($item['name']), 15);

                $line = sprintf("%-16s %-2d Rs.%d\n", $nameParts[0], $item['qty'], $item['price']);

                for ($i = 1; $i < count($nameParts); $i++) {
                    // Print remaining parts on new lines, qty and price empty
                    $line .= sprintf("%-16s\n", $nameParts[$i]);
                }

                // Reset to default font after item list
                $printer->setFont(Printer::FONT_A);

            
                // === TOTAL ===
                if ($includeTotal) {
                    $printer->feed();
                    $printer->setJustification(Printer::JUSTIFY_CENTER);
                    $printer->setEmphasis(true);
                    $printer->text("TOTAL: Rs. " . number_format($grandTotal, 2) . "\n");
                    $printer->setEmphasis(false);
                }
            
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


}
