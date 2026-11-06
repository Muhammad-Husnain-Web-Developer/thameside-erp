<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StockMovement::class);
    }

    protected function prepareForValidation(): void
    {
        $serials = $this->input('serials');

        $this->merge([
            'unit_cost_pence' => $this->filled('unit_cost') ? Money::parse($this->input('unit_cost')) : null,
            'serials' => is_string($serials)
                ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $serials) ?: [])))
                : $serials,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'type' => ['required', 'string', Rule::in(array_keys(StockMovement::TYPES))],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:500'],
            'serials' => ['nullable', 'array'],
            'serials.*' => ['string', 'max:80'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
