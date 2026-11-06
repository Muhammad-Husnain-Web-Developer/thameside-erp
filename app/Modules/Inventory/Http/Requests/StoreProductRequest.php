<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    protected function prepareForValidation(): void
    {
        $costPence = Money::parse($this->input('cost_price'));
        $sellPence = Money::parse($this->input('sell_price'));

        $this->merge([
            'cost_price' => Money::toDecimal($costPence),
            'sell_price' => Money::toDecimal($sellPence),
            'cost_price_pence' => $costPence,
            'sell_price_pence' => $sellPence,
            'tracks_serial' => $this->boolean('tracks_serial'),
            'is_active' => $this->boolean('is_active'),
            'opening_serials' => $this->serialList($this->input('opening_serials')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:40', Rule::unique('products', 'sku')],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'product_category_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')],
            'brand' => ['nullable', 'string', 'max:80'],
            'unit' => ['required', 'string', 'max:20'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0'],
            'cost_price_pence' => ['required', 'integer', 'min:0'],
            'sell_price_pence' => ['required', 'integer', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:100000'],
            'tracks_serial' => ['boolean'],
            'is_active' => ['boolean'],
            'opening_stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'opening_serials' => ['array'],
            'opening_serials.*' => ['string', 'max:80'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function serialList(mixed $value): array
    {
        if (! is_string($value)) {
            return is_array($value) ? array_values($value) : [];
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[\r\n,]+/', $value) ?: [],
        )));
    }
}
