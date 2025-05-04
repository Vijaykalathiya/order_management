<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'item_code', 'category_name', 'product_name', 'product_type',
        'variant_group_name', 'variation', 'selling_price', 'listing_price',
        'master_status', 'menu_status', 'stock_status', 'description',
        'item_type', 'tax_category', 'tax_type', 'tax_value', 'station',
        'preparation_time', 'dietary_tag', 'is_mrp_item', 'image_url_1',
        'image_url_2', 'image_url_3', 'packaging_charge', 'map_purchase_item',
        'direct_sale_item', 'tag', 'new', 'chefs_special',
        'restaurant_recommended', 'home_style_meal', 'fodmap_friendly',
        'dairy_free', 'gluten_free', 'lactose_free', 'spicy', 'wheat_free',
        'calorie_count', 'protein_count', 'carbohydrate_count', 'fat_count',
        'fibre_count', 'allergen_type', 'portion_size', 'portion_unit',
        'serving_info', 'master_product_id', 'external_item_id',
        'department_name', 'item_unit', 'add_on_category_1',
        'add_on_products_1', 'add_on_rule_1', 'max_qty_1', 'add_on_required_1',
        'min_qty_1'
    ];
}
