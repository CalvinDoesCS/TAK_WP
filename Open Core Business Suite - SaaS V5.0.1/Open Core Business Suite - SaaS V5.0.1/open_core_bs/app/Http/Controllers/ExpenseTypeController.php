<?php

namespace App\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Requests\StoreExpenseTypeRequest;
use App\Http\Requests\UpdateExpenseTypeRequest;
use App\Models\ExpenseType;
use Illuminate\Http\Request;

class ExpenseTypeController extends Controller
{
    public function index()
    {
        return view('expense-types.index');
    }

    public function datatable(Request $request)
    {
        try {
            $columns = [
                1 => 'name',
                2 => 'notes',
                3 => 'status',
            ];

            $query = ExpenseType::query();

            $totalData = $query->count();

            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')] ?? 'name';
            $dir = $request->input('order.0.dir') ?? 'asc';

            if (empty($request->input('search.value'))) {
                $expenseTypes = $query->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();
                $totalFiltered = $totalData;
            } else {
                $search = $request->input('search.value');
                $expenseTypes = $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                })
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();

                $totalFiltered = ExpenseType::where('name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->count();
            }

            $data = [];
            foreach ($expenseTypes as $expenseType) {
                $actions = [
                    [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'class' => 'edit-expense-type',
                        'data-id' => $expenseType->id,
                    ],
                    [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'class' => 'delete-expense-type text-danger',
                        'data-id' => $expenseType->id,
                    ],
                ];

                $data[] = [
                    'id' => $expenseType->id,
                    'name' => $expenseType->name,
                    'description' => $expenseType->notes,
                    'status' => $expenseType->status->value,
                    'actions' => view('components.datatable-actions', [
                        'id' => $expenseType->id,
                        'actions' => $actions,
                    ])->render(),
                ];
            }

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    public function store(StoreExpenseTypeRequest $request)
    {
        try {
            $expenseType = new ExpenseType;
            $expenseType->name = $request->name;
            $expenseType->notes = $request->description;
            $expenseType->status = $request->status;
            $expenseType->code = strtoupper(str_replace(' ', '_', $request->name));
            $expenseType->save();

            return Success::response(__('Expense type created successfully'));
        } catch (\Exception $e) {
            return Error::response(__('Failed to create expense type'));
        }
    }

    public function update(UpdateExpenseTypeRequest $request, $id)
    {
        try {
            $expenseType = ExpenseType::findOrFail($id);
            $expenseType->name = $request->name;
            $expenseType->notes = $request->description;
            $expenseType->status = $request->status;
            $expenseType->save();

            return Success::response(__('Expense type updated successfully'));
        } catch (\Exception $e) {
            return Error::response(__('Failed to update expense type'));
        }
    }

    public function destroy($id)
    {
        try {
            $expenseType = ExpenseType::findOrFail($id);
            $expenseType->delete();

            return Success::response(__('Expense type deleted successfully'));
        } catch (\Exception $e) {
            return Error::response(__('Failed to delete expense type'));
        }
    }
}
