<?php

namespace App\Modules\Hr\Http\Requests;

use App\Modules\Hr\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Employee::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_number' => ['nullable', 'string', 'max:20', Rule::unique('employees', 'employee_number')->whereNull('deleted_at')],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'job_title' => ['required', 'string', 'max:255'],
            'contract_type' => ['required', Rule::in(array_keys(Employee::CONTRACT_TYPES))],
            'started_on' => ['required', 'date'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'annual_salary' => ['required', 'numeric', 'min:0'],
            'payroll_reference' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ];
    }
}
