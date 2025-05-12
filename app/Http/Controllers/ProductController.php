<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function showForm()
    {
        $products = Product::latest()->get(); // You can paginate or filter as needed
        return view('products.import', compact('products'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
    
        $data = Excel::toArray([], $request->file('file'));
        $header = array_map('strtolower', $data[0][0]); // Normalize headers
    
        $inserted = 0;
    
        foreach (array_slice($data[0], 1) as $row) {
            $row = array_combine($header, $row);
            $itemCode = $row['item code'] ?? null;
    
            if (!$itemCode || Product::where('item_code', $itemCode)->exists()) {
                continue;
            }
    
            Product::create([
                'item_code' => $itemCode,
                'category_name' => $row['category name'] ?? null,
                'product_name' => $row['product name'] ?? null,
                'product_type' => $row['product type'] ?? null,
                'variant_group_name' => $row['variant group name'] ?? null,
                'variation' => $row['variation'] ?? null,
                'selling_price' => safeDecimal($row['selling price'] ?? null),
                'listing_price' => safeDecimal($row['listing price'] ?? null),
                'master_status' => $row['master status'] ?? null,
                'menu_status' => $row['menu status'] ?? null,
                'stock_status' => $row['stock status'] ?? null,
                'description' => $row['description'] ?? null,
                'item_type' => $row['item type'] ?? null,
                'tax_category' => $row['tax category'] ?? null,
                'tax_type' => $row['tax type'] ?? null,
                'tax_value' => safeDecimal($row['tax value'] ?? null),
                'station' => $row['station'] ?? null,
                'preparation_time' => safeInt($row['preparation time'] ?? null),
                'dietary_tag' => $row['dietary tag'] ?? null,
                'is_mrp_item' => strcasecmp($row['is mrp item'] ?? '', 'Yes') === 0,
                'image_url_1' => $row['image url 1'] ?? null,
                'image_url_2' => $row['image url 2'] ?? null,
                'image_url_3' => $row['image url 3'] ?? null,
                'packaging_charge' => safeDecimal($row['packaging charge'] ?? null),
                'map_purchase_item' => $row['map purchase item'] ?? null,
                'direct_sale_item' => strcasecmp($row['direct sale item'] ?? '', 'Yes') === 0,
                'tag' => $row['tag'] ?? null,
                'new' => $row['new'] ?? null,
                'chefs_special' => $row["chef's special"] ?? null,
                'restaurant_recommended' => $row['restaurant recommended'] ?? null,
                'home_style_meal' => $row['home style meal'] ?? null,
                'fodmap_friendly' => $row['fodmap friendly'] ?? null,
                'dairy_free' => $row['dairy free'] ?? null,
                'gluten_free' => $row['gluten free'] ?? null,
                'lactose_free' => $row['lactose free'] ?? null,
                'spicy' => $row['spicy'] ?? null,
                'wheat_free' => $row['wheat free'] ?? null,
                'calorie_count' => safeDecimal($row['calorie count'] ?? null),
                'protein_count' => safeDecimal($row['protein count'] ?? null),
                'carbohydrate_count' => safeDecimal($row['carbohydrate count'] ?? null),
                'fat_count' => safeDecimal($row['fat count'] ?? null),
                'fibre_count' => safeDecimal($row['fibre count'] ?? null),
                'allergen_type' => $row['allergen type'] ?? null,
                'portion_size' => safeDecimal($row['portion size'] ?? null),
                'portion_unit' => $row['portion unit'] ?? null,
                'serving_info' => $row['serving info'] ?? null,
                'master_product_id' => $row['master productid'] ?? null,
                'external_item_id' => $row['external item id'] ?? null,
                'department_name' => $row['department name'] ?? null,
                'item_unit' => $row['item unit'] ?? null,
                'add_on_category_1' => $row['add on category 1'] ?? null,
                'add_on_products_1' => $row['add on products 1'] ?? null,
                'add_on_rule_1' => $row['add on rule 1'] ?? null,
                'max_qty_1' => safeInt($row['max qty 1'] ?? null),
                'add_on_required_1' => strcasecmp($row['add on required 1'] ?? '', 'Yes') === 0,
                'min_qty_1' => safeInt($row['min qty 1'] ?? null),
            ]);
    
            $inserted++;
        }

        return redirect()->route('products.import')->with('success', "{$inserted} new products added.");

        // return response("{$inserted} new products added.", 200);
    
        // return back()->with('status', "$inserted new products added.");
    }

    public function showOrderForm()
    {
        $products = Product::select('item_code', 'product_name', 'selling_price', 'station', 'category_name')->get();

        // Group categories
        $categories = $products->pluck('category_name')->unique()->filter()->values();

        // Get last 25 unique ordered products (Best Seller)
        $recentOrders = Order::with('items')
            ->latest()
            ->take(25)
            ->get()
            ->pluck('items')
            ->flatten()
            ->unique('item_code')
            ->pluck('item_code');

        $bestSellers = Product::whereIn('item_code', $recentOrders)->get();

        return view('products.order-form', compact('products', 'categories', 'bestSellers'));
    }
}
