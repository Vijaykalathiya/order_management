<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Http\Request;

class ProductsExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Product::query();

        if ($search = $this->request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('product_name', 'like', "%$search%")
                  ->orWhere('category_name', 'like', "%$search%")
                  ->orWhere('item_code', 'like', "%$search%");
            });
        }

        // Add any extra filters you want here (category/date range etc.)

        return $query->select(
            'item_code',
            'product_name',
            'category_name',
            'selling_price',
            'created_at',
            'updated_at'
        )->orderBy('item_code')->get();
    }

    public function headings(): array
    {
        return [
            'Item Code',
            'Product Name',
            'Category',
            'Selling Price',
            'Created At',
            'Updated At',
        ];
    }
}
