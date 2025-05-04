<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function printOrder(Request $request)
    {
        $orderItems = $request->input('items', []);
        $printByStation = filter_var($request->input('printByStation', false), FILTER_VALIDATE_BOOLEAN);

        if (empty($orderItems)) {
            return response()->json(['error' => 'No items to print.'], 400);
        }

        DB::beginTransaction();

        try {
            $latestOrder = Order::latest()->first();
            $tokenNumber = 'T' . str_pad(optional($latestOrder)->id + 1, 5, '0', STR_PAD_LEFT);

            $grandTotal = collect($orderItems)->reduce(function ($carry, $item) {
                return $carry + ($item['qty'] * $item['price']);
            }, 0);

            $order = Order::create([
                'token_number' => $tokenNumber,
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

            // ✅ Correctly handle based on flag
            if ($printByStation) {
                $grouped = collect($orderItems)->groupBy('station')->values();
                $lastGroupIndex = $grouped->count() - 1;

                foreach ($grouped as $index => $items) {
                    $includeTotal = ($index === $lastGroupIndex);
                    $this->printToPrinter($items, $tokenNumber, $items[0]['station'], $includeTotal, $grandTotal);
                }
            } else {
                // Unified single print, station doesn't matter
                $this->printToPrinter($orderItems, $tokenNumber, null, true, $grandTotal);
            }

            DB::commit();

            return response()->json(['message' => 'Order saved and printed.', 'token' => $tokenNumber]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Order failed: ' . $e->getMessage()], 500);
        }
    }

    private function printToPrinter($items, $tokenNumber, $station = null, $includeTotal = false, $grandTotal = 0)
    {
        $output = "Token: $tokenNumber\n";
        if ($station) {
            $output .= "Station: $station\n";
        }

        $output .= str_repeat('-', 32) . "\n";

        foreach ($items as $item) {
            $output .= "{$item['name']} x{$item['qty']} @ ₹{$item['price']}\n";
        }

        if ($includeTotal) {
            $output .= str_repeat('-', 32) . "\n";
            $output .= "TOTAL: ₹" . number_format($grandTotal, 2) . "\n";
        }

        $output .= "\n\n";

        \Log::info($output);

        // Use escpos-php for real printing if needed:
        /*
        $connector = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector("RP82");
        $printer = new \Mike42\Escpos\Printer($connector);
        $printer->text($output);
        $printer->cut();
        $printer->close();
        */
    }
}
