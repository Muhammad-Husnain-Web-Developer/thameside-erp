<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Models\Product;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', Product::class);
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['sku'] = [
            'required', 'string', 'max:40',
            Rule::unique('products', 'sku')->ignore($this->route('product')),
        ];

        unset($rules['opening_stock'], $rules['opening_serials'], $rules['opening_serials.*']);

        return $rules;
    }
}
