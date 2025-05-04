<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('category_name')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_type')->nullable();
            $table->string('variant_group_name')->nullable();
            $table->string('variation')->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->decimal('listing_price', 10, 2)->nullable();
            $table->string('master_status')->nullable();
            $table->string('menu_status')->nullable();
            $table->string('stock_status')->nullable();
            $table->text('description')->nullable();
            $table->string('item_type')->nullable();
            $table->string('tax_category')->nullable();
            $table->string('tax_type')->nullable();
            $table->decimal('tax_value', 5, 2)->nullable();
            $table->string('station')->nullable();
            $table->integer('preparation_time')->nullable();
            $table->string('dietary_tag')->nullable();
            $table->boolean('is_mrp_item')->default(false);
            $table->string('image_url_1')->nullable();
            $table->string('image_url_2')->nullable();
            $table->string('image_url_3')->nullable();
            $table->decimal('packaging_charge', 10, 2)->nullable();
            $table->string('map_purchase_item')->nullable();
            $table->boolean('direct_sale_item')->default(false);
            $table->string('tag')->nullable();
            $table->string('new')->nullable();
            $table->string('chefs_special')->nullable();
            $table->string('restaurant_recommended')->nullable();
            $table->string('home_style_meal')->nullable();
            $table->string('fodmap_friendly')->nullable();
            $table->string('dairy_free')->nullable();
            $table->string('gluten_free')->nullable();
            $table->string('lactose_free')->nullable();
            $table->string('spicy')->nullable();
            $table->string('wheat_free')->nullable();
            $table->decimal('calorie_count', 8, 2)->nullable();
            $table->decimal('protein_count', 8, 2)->nullable();
            $table->decimal('carbohydrate_count', 8, 2)->nullable();
            $table->decimal('fat_count', 8, 2)->nullable();
            $table->decimal('fibre_count', 8, 2)->nullable();
            $table->string('allergen_type')->nullable();
            $table->decimal('portion_size', 8, 2)->nullable();
            $table->string('portion_unit')->nullable();
            $table->text('serving_info')->nullable();
            $table->string('master_product_id')->nullable();
            $table->string('external_item_id')->nullable();
            $table->string('department_name')->nullable();
            $table->string('item_unit')->nullable();
            $table->string('add_on_category_1')->nullable();
            $table->text('add_on_products_1')->nullable();
            $table->string('add_on_rule_1')->nullable();
            $table->integer('max_qty_1')->nullable();
            $table->boolean('add_on_required_1')->default(false);
            $table->integer('min_qty_1')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
