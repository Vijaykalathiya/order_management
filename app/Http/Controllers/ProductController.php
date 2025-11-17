<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_code' => 'required|string|max:50',
            'product_name' => 'required|string|max:255',
            'category_name' => 'required|string|max:255',
            'selling_price' => 'required|numeric|min:0',
            'station' => 'required',
        ]);

        // Check if item_code already exists
        $exists = \App\Models\Product::where('item_code', $validated['item_code'])->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Item code already exists. Please use a different one.',
            ]);
        }

        $product = \App\Models\Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product added successfully!',
            'product' => $product,
        ]);
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

        try {

            $data = Excel::toArray([], $request->file('file'));
            $header = array_map('strtolower', $data[0][0]); // Normalize headers
        
            $inserted = 0;
        
            foreach (array_slice($data[0], 1) as $row) {
                $row = array_combine($header, $row);
                $itemCode = $row['item code'] ?? null;
        	$checkExist = Product::where('item_code', $itemCode)->first();
                if (!$itemCode || $checkExist) {
                    $checkExist->category_name = $row['category name'] ?? null;
                    $checkExist->product_name = $row['product name'] ?? null;
                    $checkExist->product_type = $row['product type'] ?? null;
                    $checkExist->variant_group_name = $row['variant group name'] ?? null;
                    $checkExist->variation = $row['variation'] ?? null;
                    $checkExist->selling_price = safeDecimal($row['selling price'] ?? null);
                    $checkExist->listing_price = safeDecimal($row['listing price'] ?? null);
                    $checkExist->master_status = $row['master status'] ?? null;
                    $checkExist->menu_status = $row['menu status'] ?? null;
                    $checkExist->stock_status = $row['stock status'] ?? null;
                    $checkExist->description = $row['description'] ?? null;
                    $checkExist->item_type = $row['item type'] ?? null;
                    $checkExist->tax_category = $row['tax category'] ?? null;
                    $checkExist->tax_type = $row['tax type'] ?? null;
                    $checkExist->tax_value = safeDecimal($row['tax value'] ?? null);
                    $checkExist->station = $row['station'] ?? null;
                    $checkExist->preparation_time = safeInt($row['preparation time'] ?? null);
                    $checkExist->dietary_tag = $row['dietary tag'] ?? null;
                    $checkExist->is_mrp_item = strcasecmp($row['is mrp item'] ?? '', 'Yes') === 0;
                    $checkExist->image_url_1 = $row['image url 1'] ?? null;
                    $checkExist->image_url_2 = $row['image url 2'] ?? null;
                    $checkExist->image_url_3 = $row['image url 3'] ?? null;
                    $checkExist->packaging_charge = safeDecimal($row['packaging charge'] ?? null);
                    $checkExist->map_purchase_item = $row['map purchase item'] ?? null;
                    $checkExist->direct_sale_item = strcasecmp($row['direct sale item'] ?? '', 'Yes') === 0;
                    $checkExist->tag = $row['tag'] ?? null;
                    $checkExist->new = $row['new'] ?? null;
                    $checkExist->chefs_special = $row["chef's special"] ?? null;
                    $checkExist->restaurant_recommended = $row['restaurant recommended'] ?? null;
                    $checkExist->home_style_meal = $row['home style meal'] ?? null;
                    $checkExist->fodmap_friendly = $row['fodmap friendly'] ?? null;
                    $checkExist->dairy_free = $row['dairy free'] ?? null;
                    $checkExist->gluten_free = $row['gluten free'] ?? null;
                    $checkExist->lactose_free = $row['lactose free'] ?? null;
                    $checkExist->spicy = $row['spicy'] ?? null;
                    $checkExist->wheat_free = $row['wheat free'] ?? null;
                    $checkExist->calorie_count = safeDecimal($row['calorie count'] ?? null);
                    $checkExist->protein_count = safeDecimal($row['protein count'] ?? null);
                    $checkExist->carbohydrate_count = safeDecimal($row['carbohydrate count'] ?? null);
                    $checkExist->fat_count = safeDecimal($row['fat count'] ?? null);
                    $checkExist->fibre_count = safeDecimal($row['fibre count'] ?? null);
                    $checkExist->allergen_type = $row['allergen type'] ?? null;
                    $checkExist->portion_size = safeDecimal($row['portion size'] ?? null);
                    $checkExist->portion_unit = $row['portion unit'] ?? null;
                    $checkExist->serving_info = $row['serving info'] ?? null;
                    $checkExist->master_product_id = $row['master productid'] ?? null;
                    $checkExist->external_item_id = $row['external item id'] ?? null;
                    $checkExist->department_name = $row['department name'] ?? null;
                    $checkExist->item_unit = $row['item unit'] ?? null;
                    $checkExist->add_on_category_1 = $row['add on category 1'] ?? null;
                    $checkExist->add_on_products_1 = $row['add on products 1'] ?? null;
                    $checkExist->add_on_rule_1 = $row['add on rule 1'] ?? null;
                    $checkExist->max_qty_1 = safeInt($row['max qty 1'] ?? null);
                    $checkExist->add_on_required_1 = strcasecmp($row['add on required 1'] ?? '', 'Yes') === 0;
                    $checkExist->min_qty_1 = safeInt($row['min qty 1'] ?? null);
                    $checkExist->save();
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
    
            $products = Product::latest()->get();
    
            return response()->json([
                'success' => true,
                'message' => "$inserted new products added.",
                'products' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Import failed: " . $e->getMessage()
            ], 500);
        }
    }

    public function import1(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {

            $data = Excel::toArray([], $request->file('file'));

            if (empty($data) || empty($data[0])) {
                throw new \Exception('Excel sheet is empty or unreadable.');
            }

            $header = array_map(fn($h) => strtolower(trim($h)), $data[0][0]); // normalize headers
            $inserted = 0;
        
            foreach (array_slice($data[0], 1) as $rowIndex => $row) {
                if (count(array_filter($row, fn($v) => $v !== null && $v !== '')) === 0) continue;

                if (count($row) < count($header)) {
                    $row = array_pad($row, count($header), null);
                } elseif (count($row) > count($header)) {
                    $row = array_slice($row, 0, count($header));
                }

                $row = @array_combine($header, $row);
                if (!$row) {
                    throw new \Exception("Invalid row format at line " . ($rowIndex + 2));
                }

                $itemCode = $row['item code'] ?? null;
                if (!$itemCode) continue;


        	    $checkExist = Product::where('item_code', $itemCode)->first();
                if (!$itemCode || $checkExist) {
                    $checkExist->fill([
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
                    $checkExist->save();
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
    
            $products = Product::latest()->get();
    
            return response()->json([
                'success' => true,
                'message' => "$inserted new products added.",
                'products' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Import failed: " . $e->getMessage()
            ], 500);
        }
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

    public function updateAjax(Request $request)
    {
        $product = Product::find($request->id);
        if (!$product) return response()->json(['success' => false, 'message' => 'Product not found']);

        $product->fill($request->only([
            'product_name', 'category_name', 'selling_price',
        ]))->save();

        return response()->json(['success' => true]);
    }

    public function deleteAjax(Request $request)
    {
        $product = Product::find($request->id);
        if (!$product) return response()->json(['success' => false, 'message' => 'Product not found']);

        $product->delete();
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        return Excel::download(new ProductsExport($request), 'Products_' . now()->format('Y_m_d_His') . '.xlsx');
    }


}
