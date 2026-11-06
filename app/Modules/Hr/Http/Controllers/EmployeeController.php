<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Money;
use App\Modules\Hr\Http\Requests\StoreEmployeeRequest;
use App\Modules\Hr\Http\Requests\UpdateEmployeeRequest;
use App\Modules\Hr\Models\Department;
use App\Modules\Hr\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $search = trim((string) $request->string('q'));

        $employees = Employee::query()
            ->with('department')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%");
            }))
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Employee $employee) => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'name' => $employee->fullName(),
                'email' => $employee->email,
                'job_title' => $employee->job_title,
                'department' => $employee->department?->name,
                'contract_type' => Employee::CONTRACT_TYPES[$employee->contract_type] ?? $employee->contract_type,
                'started_on' => $employee->started_on?->format('j M Y'),
                'salary' => $employee->displaySalary(),
                'is_active' => $employee->is_active,
                'can' => ['update' => $request->user()->can('update', $employee)],
            ]);

        return Inertia::render('People/Employees/Index', [
            'employees' => $employees,
            'filters' => ['q' => $search],
            'stats' => [
                [
                    'key' => 'headcount',
                    'label' => 'Headcount',
                    'value' => Employee::query()->where('is_active', true)->count(),
                    'meta' => Employee::query()->count().' on file',
                    'icon' => 'users',
                ],
                [
                    'key' => 'payroll',
                    'label' => 'Monthly payroll',
                    'value' => Money::format((int) Employee::query()->where('is_active', true)->get()->sum(fn (Employee $employee) => $employee->monthlyGrossPence())),
                    'meta' => 'Gross, before deductions',
                    'icon' => 'banknotes',
                ],
                [
                    'key' => 'departments',
                    'label' => 'Departments',
                    'value' => Department::query()->count(),
                    'meta' => 'Across the business',
                    'icon' => 'building',
                ],
                [
                    'key' => 'salaries',
                    'label' => 'Annual salary bill',
                    'value' => Money::format((int) Employee::query()->where('is_active', true)->sum('annual_salary_pence')),
                    'meta' => 'Active employees',
                    'icon' => 'chart',
                ],
            ],
            'can' => ['create' => $request->user()->can('create', Employee::class)],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Employee::class);

        return Inertia::render('People/Employees/Create', [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'contractTypes' => Employee::CONTRACT_TYPES,
            'today' => now()->toDateString(),
            'suggestedNumber' => $this->nextNumber(),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $this->prepare($request->validated());
        $data['employee_number'] = $data['employee_number'] ?? $this->nextNumber();
        $data['is_active'] = $data['is_active'] ?? true;

        $employee = Employee::create($data);

        return redirect()
            ->route('people.employees.index')
            ->with('success', "{$employee->fullName()} was added to the team.");
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('update', $employee);

        return Inertia::render('People/Employees/Edit', [
            'employee' => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'department_id' => $employee->department_id,
                'job_title' => $employee->job_title,
                'contract_type' => $employee->contract_type,
                'started_on' => $employee->started_on?->toDateString(),
                'ended_on' => $employee->ended_on?->toDateString(),
                'annual_salary' => Money::toDecimal($employee->annual_salary_pence),
                'payroll_reference' => $employee->payroll_reference,
                'is_active' => $employee->is_active,
            ],
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'contractTypes' => Employee::CONTRACT_TYPES,
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->prepare($request->validated()));

        return redirect()
            ->route('people.employees.index')
            ->with('success', "{$employee->fullName()} was updated.");
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepare(array $data): array
    {
        $data['annual_salary_pence'] = Money::parse($data['annual_salary'] ?? 0);
        unset($data['annual_salary']);

        return $data;
    }

    private function nextNumber(): string
    {
        $latest = Employee::withTrashed()->orderByDesc('id')->value('employee_number');
        $next = $latest && preg_match('/(\d+)$/', $latest, $matches) ? ((int) $matches[1]) + 1 : 1;

        return 'EMP-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
