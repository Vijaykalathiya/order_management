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
        $orders = Order::with(['items', 'token'])->paginate(10);
        return view('products.orders-view', compact('orders'));
    }

    public function printOrder(Request $request)
    {
        $orderItems = $request->input('items', []);
        $printByStation = filter_var($request->input('printByStation', false), FILTER_VALIDATE_BOOLEAN);

        if (empty($orderItems)) {
            return response()->json(['error' => 'No items to print.'], 400);
        }

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
                    $this->printToPrinter($items, $nextTokenNumber, $items[0]['station'], $includeTotal, $grandTotal);
                }
            } else {
                $this->printToPrinter($orderItems, $nextTokenNumber, null, true, $grandTotal);
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

    // private function printToPrinter($items, $tokenNumber, $station = null, $includeTotal = false, $grandTotal = 0)
    // {
    //     // Logging as plain text
    //     $output = "Token: $tokenNumber\n";
    //     if ($station) {
    //         $output .= "Station: $station\n";
    //     }
    //     $output .= str_repeat('-', 32) . "\n";
    //     foreach ($items as $item) {
    //         $output .= "{$item['name']} x{$item['qty']} @ ₹{$item['price']}\n";
    //     }
    //     if ($includeTotal) {
    //         $output .= str_repeat('-', 32) . "\n";
    //         $output .= "TOTAL: ₹" . number_format($grandTotal, 2) . "\n";
    //     }
    //     $output .= "\n\n";
    //     \Log::info($output);

    //     // ESC/POS printer output
    //     try {
            
    //         $connector = new WindowsPrintConnector("smb://localhost/TVS3230");
    //         $printer = new Printer($connector);

    //         // Header - Centered Token
    //         $printer->setJustification(Printer::JUSTIFY_CENTER);
    //         $printer->setTextSize(2, 2);
    //         $printer->setEmphasis(true);
    //         $printer->text("TOKEN: $tokenNumber\n");
    //         $printer->setTextSize(1, 1);
    //         $printer->setEmphasis(false);

    //         // Add Date and Time in small font
    //         $currentTime = date('Y-m-d H:i:s'); // Current Date and Time
    //         $printer->setJustification(Printer::JUSTIFY_CENTER);
    //         $printer->setTextSize(1, 1); // Small font size for date and time
    //         $printer->text("Date & Time: $currentTime\n");

    //         // Optional Station
    //         if ($station) {
    //             $printer->setJustification(Printer::JUSTIFY_LEFT);
    //             $printer->text("Station: $station\n");
    //         }

    //         $printer->text(str_repeat('-', 32) . "\n");

    //         // Order Items
    //         foreach ($items as $item) {
    //             $line = sprintf("%-20s x%-2d @ ₹%d\n", $item['name'], $item['qty'], $item['price']);
    //             $printer->text($line);
    //         }

    //         // Total (only in last print if required)
    //         if ($includeTotal) {
    //             $printer->text(str_repeat('-', 32) . "\n");
    //             $printer->setJustification(Printer::JUSTIFY_RIGHT);
    //             $printer->setEmphasis(true);
    //             $printer->text("TOTAL: ₹" . number_format($grandTotal, 2) . "\n");
    //             $printer->setEmphasis(false);
    //         }

    //         $printer->feed(2);
    //         $printer->cut();
    //         $printer->close();

    //     } catch (\Exception $e) {
    //         \Log::error("Print failed: " . $e->getMessage());
    //     }
        
    // }

    private function printToPrinter($items, $tokenNumber, $station = null, $includeTotal = false, $grandTotal = 0)
    {
        // Logging as plain text
        // $output = "Token: $tokenNumber\n";
        // if ($station) {
        //     $output .= "Station: $station\n";
        // }
        // $currentTime = date('d-m-Y h:i A');
        // $output .= "Date: $currentTime\n";

        // $output .= str_repeat('-', 32) . "\n";
        // $line = sprintf("%-16s %s %s %s\n", 'Item Name', 'Qty', 'Rate', 'Amt');
        // $output .= "{$line}";
        // $output .= str_repeat('-', 32) . "\n";
        // foreach ($items as $item) {
        //     $line = sprintf("%-16s %d %3.0f %3.0f\n", strtoupper($item['name']), $item['qty'], $item['price'], ($item['qty'] * $item['price']));
        //     $output .= "{$line}";
        // }
        // if ($includeTotal) {
        //     $output .= str_repeat('-', 32) . "\n";
        //     $output .= "TOTAL: Rs. " . number_format($grandTotal, 2) . "\n";
        // }
        // $output .= "\n\n";
        // \Log::info($output);

        try {
            // Connect to the printer
            $connector = new WindowsPrintConnector("smb://localhost/TVS3230");
            $printer = new Printer($connector);
        
            // === HEADER ===
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2);
            $printer->setEmphasis(true);
            $printer->text("TOKEN: $tokenNumber\n");
            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false);
        
            // Station (if provided)
            if ($station) {
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->text("Station: $station\n");
            }
        
            // Date and Time
            $currentTime = date('d-m-Y h:i A');
            $printer->text("Date: $currentTime\n");
            $printer->feed();
        
            // === ITEM LIST ===
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            // Switch to smaller font to fit more text per line
            $printer->setFont(Printer::FONT_B);

            foreach ($items as $item) {
                $line = sprintf("%-16s Rs. %6.2f x%-2d\n", strtoupper($item['name']), $item['price'], $item['qty']);
                $printer->text($line);
            }

            // Reset to default font after item list
            $printer->setFont(Printer::FONT_A);

        
            // === TOTAL ===
            if ($includeTotal) {
                $printer->feed();
                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                $printer->setEmphasis(true);
                $printer->text("TOTAL: Rs. " . number_format($grandTotal, 2) . "\n");
                $printer->setEmphasis(false);
            }
        
            // === FINALIZE ===
            $printer->feed(2); // Feed 2 lines for space
            $printer->cut(); // Cut the paper
            $printer->close(); // Close the printer connection
        } catch (\Exception $e) {
            // Error handling: Log the error if print fails
            \Log::error("Print failed: " . $e->getMessage());
        }        
    }


}
