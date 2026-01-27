<?php

namespace Modules\AccountingCore\App\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use App\Services\AddonService\AddonService;
use App\Services\Settings\ModuleSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\AccountingCore\App\Models\BasicTransaction;
use Modules\AccountingCore\App\Models\BasicTransactionCategory;
use Yajra\DataTables\Facades\DataTables;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:accountingcore.transactions.index')->only(['index', 'indexAjax']);
        $this->middleware('permission:accountingcore.transactions.create')->only(['create']);
        $this->middleware('permission:accountingcore.transactions.store')->only(['store']);
        $this->middleware('permission:accountingcore.transactions.show')->only(['show']);
        $this->middleware('permission:accountingcore.transactions.edit')->only(['edit']);
        $this->middleware('permission:accountingcore.transactions.update')->only(['update']);
        $this->middleware('permission:accountingcore.transactions.destroy')->only(['destroy']);
        $this->middleware('permission:accountingcore.transactions.delete-attachment')->only(['deleteAttachment']);
    }

    /**
     * Display a listing of transactions.
     */
    public function index(Request $request)
    {
        // Check if AccountingPro is enabled
        $addonService = app(AddonService::class);
        if ($addonService->isAddonEnabled('AccountingPro')) {
            return redirect()->route('accountingpro.transactions.journal-entries.index');
        }

        // Get module settings
        $settingsService = app(ModuleSettingsService::class);
        $allowFutureDates = $settingsService->get('AccountingCore', 'allow_future_dates', false);
        $requireAttachments = $settingsService->get('AccountingCore', 'require_attachments', false);

        // Get filter data
        $categories = BasicTransactionCategory::active()->orderBy('name')->get();

        // Breadcrumb data
        $breadcrumbs = [
            ['name' => __('Accounting'), 'url' => route('accountingcore.dashboard')],
            ['name' => __('Transactions'), 'url' => ''],
        ];

        return view('accountingcore::transactions.index', compact('categories', 'breadcrumbs', 'allowFutureDates', 'requireAttachments'));
    }

    /**
     * Get transactions data for DataTables.
     */
    public function indexAjax(Request $request)
    {
        $query = BasicTransaction::with(['category', 'createdBy', 'sourceable']);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->end_date);
        }

        // DataTables search
        if ($request->has('search') && ! empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('type_badge', function ($model) {
                return $model->type_badge;
            })
            ->addColumn('category_name', function ($model) {
                return $model->category ? $model->category->name : '-';
            })
            ->addColumn('formatted_amount', function ($model) {
                return $model->formatted_amount;
            })
            ->addColumn('formatted_date', function ($model) {
                return $model->formatted_date;
            })
            ->addColumn('payment_method_badge', function ($model) {
                return $model->payment_method_badge;
            })
            ->addColumn('attachment_icon', function ($model) {
                if ($model->hasAttachment()) {
                    return '<a href="'.$model->attachment_url.'" target="_blank" class="text-primary"><i class="bx bx-paperclip"></i></a>';
                }

                return '';
            })
            ->addColumn('user', function ($model) {
                return view('components.datatable-user', ['user' => $model->createdBy])->render();
            })
            ->addColumn('source_document', function ($model) {
                if (! $model->sourceable) {
                    return '<span class="badge bg-label-secondary">'.__('Manual').'</span>';
                }

                $sourceType = class_basename($model->sourceable_type);
                $route = null;
                $label = '';
                $icon = 'bx-file';

                if ($sourceType === 'CoreSalesOrder') {
                    $route = route('systemcore.sales-orders.show', $model->sourceable_id);
                    $label = __('SO: ').$model->sourceable->order_number;
                    $icon = 'bx-shopping-bag';
                } elseif ($sourceType === 'CorePurchaseOrder') {
                    $route = route('systemcore.purchase-orders.show', $model->sourceable_id);
                    $label = __('PO: ').$model->sourceable->order_number;
                    $icon = 'bx-cart';
                }

                if ($route) {
                    return '<a href="'.$route.'" class="text-primary" title="'.__('View Source Document').'">
                                <i class="bx '.$icon.' me-1"></i>'.$label.'
                            </a>';
                }

                return '<span class="badge bg-label-secondary">'.$sourceType.'</span>';
            })
            ->addColumn('actions', function ($model) {
                $actions = [];

                if (auth()->user()->can('accountingcore.transactions.show')) {
                    $actions[] = [
                        'label' => __('View'),
                        'icon' => 'bx bx-show',
                        'onclick' => "viewTransaction({$model->id})",
                    ];
                }

                if (auth()->user()->can('accountingcore.transactions.edit')) {
                    $actions[] = [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'onclick' => "editTransaction({$model->id})",
                    ];
                }

                if (auth()->user()->can('accountingcore.transactions.destroy')) {
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => "deleteTransaction({$model->id})",
                        'class' => 'text-danger',
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $model->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['type_badge', 'category_name', 'formatted_amount', 'formatted_date', 'payment_method_badge', 'attachment_icon', 'user', 'source_document', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create()
    {
        $categories = BasicTransactionCategory::active()->orderBy('name')->get();
        $paymentMethods = [
            'cash' => __('Cash'),
            'bank_transfer' => __('Bank Transfer'),
            'credit_card' => __('Credit Card'),
            'check' => __('Check'),
            'other' => __('Other'),
        ];

        // Breadcrumb data
        $breadcrumbs = [
            ['name' => __('Accounting'), 'url' => route('accountingcore.dashboard')],
            ['name' => __('Transactions'), 'url' => route('accountingcore.transactions.index')],
            ['name' => __('Create'), 'url' => ''],
        ];

        return view('accountingcore::transactions.create', compact('categories', 'paymentMethods', 'breadcrumbs'));
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request)
    {
        // Get module settings
        $settingsService = app(ModuleSettingsService::class);
        $allowFutureDates = $settingsService->get('AccountingCore', 'allow_future_dates', false);
        $requireAttachments = $settingsService->get('AccountingCore', 'require_attachments', false);

        // Build validation rules
        $rules = [
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'required|exists:basic_transaction_categories,id',
            'transaction_date' => 'required|date'.(! $allowFutureDates ? '|before_or_equal:today' : ''),
            'description' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|in:cash,bank_transfer,credit_card,check,other',
            'tags' => 'nullable|array',
            'attachment' => ($requireAttachments ? 'required' : 'nullable').'|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ];

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $data = $request->only([
                'type', 'amount', 'category_id', 'transaction_date',
                'description', 'reference_number', 'payment_method', 'tags',
            ]);

            $data['created_by_id'] = auth()->id();
            $transaction = BasicTransaction::create($data);

            // Handle file upload using Laravel Storage
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = 'transaction_'.$transaction->id.'_'.time().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('transactions/'.date('Y/m'), $fileName, 'public');

                $transaction->update([
                    'attachment_path' => $path,
                    'attachment_original_name' => $file->getClientOriginalName(),
                    'attachment_size' => $file->getSize(),
                    'attachment_mime_type' => $file->getMimeType(),
                ]);
            }

            DB::commit();

            return Success::response([
                'message' => __('Transaction created successfully'),
                'id' => $transaction->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return Error::response(__('Failed to create transaction: ').$e->getMessage());
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show($id)
    {
        $transaction = BasicTransaction::with(['category', 'createdBy', 'updatedBy', 'sourceable'])->findOrFail($id);

        // Check if request is AJAX
        if (request()->ajax()) {
            // Add formatted fields for display
            $transactionData = $transaction->toArray();

            // Fix timezone issue: override transaction_date with proper format for frontend
            $transactionData['transaction_date'] = $transaction->transaction_date->format('Y-m-d');

            $transactionData['formatted_date'] = $transaction->formatted_date;
            $transactionData['formatted_amount'] = $transaction->formatted_amount;
            $transactionData['type_badge'] = $transaction->type_badge;
            $transactionData['payment_method_badge'] = $transaction->payment_method_badge;
            $transactionData['attachment_url'] = $transaction->attachment_url;

            // Add source document information
            if ($transaction->sourceable) {
                $sourceType = class_basename($transaction->sourceable_type);
                $sourceUrl = null;
                $sourceLabel = '';

                if ($sourceType === 'CoreSalesOrder') {
                    $sourceUrl = route('systemcore.sales-orders.show', $transaction->sourceable_id);
                    $sourceLabel = __('Sales Order: ').$transaction->sourceable->order_number;
                } elseif ($sourceType === 'CorePurchaseOrder') {
                    $sourceUrl = route('systemcore.purchase-orders.show', $transaction->sourceable_id);
                    $sourceLabel = __('Purchase Order: ').$transaction->sourceable->order_number;
                }

                $transactionData['source_document'] = [
                    'type' => $sourceType,
                    'url' => $sourceUrl,
                    'label' => $sourceLabel,
                    'order_number' => $transaction->sourceable->order_number ?? null,
                ];
            }

            // Get attachment info if any
            if ($transaction->hasAttachment()) {
                $transactionData['files'] = [[
                    'id' => null,
                    'name' => $transaction->attachment_original_name ?? basename($transaction->attachment_path),
                    'size' => $transaction->attachment_size ?? 0,
                    'url' => asset('storage/'.$transaction->attachment_path),
                    'download_url' => route('accountingcore.transactions.download-attachment', [
                        'id' => $transaction->id,
                    ]),
                ]];
            }

            return Success::response($transactionData);
        }

        // Breadcrumb data
        $breadcrumbs = [
            ['name' => __('Accounting'), 'url' => route('accountingcore.dashboard')],
            ['name' => __('Transactions'), 'url' => route('accountingcore.transactions.index')],
            ['name' => $transaction->transaction_number, 'url' => ''],
        ];

        return view('accountingcore::transactions.show', compact('transaction', 'breadcrumbs'));
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit($id)
    {
        $transaction = BasicTransaction::findOrFail($id);
        $categories = BasicTransactionCategory::active()->orderBy('name')->get();
        $paymentMethods = [
            'cash' => __('Cash'),
            'bank_transfer' => __('Bank Transfer'),
            'credit_card' => __('Credit Card'),
            'check' => __('Check'),
            'other' => __('Other'),
        ];

        // Breadcrumb data
        $breadcrumbs = [
            ['name' => __('Accounting'), 'url' => route('accountingcore.dashboard')],
            ['name' => __('Transactions'), 'url' => route('accountingcore.transactions.index')],
            ['name' => __('Edit').' '.$transaction->transaction_number, 'url' => ''],
        ];

        return view('accountingcore::transactions.edit', compact('transaction', 'categories', 'paymentMethods', 'breadcrumbs'));
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'required|exists:basic_transaction_categories,id',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|in:cash,bank_transfer,credit_card,check,other',
            'tags' => 'nullable|array',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            DB::beginTransaction();

            $transaction = BasicTransaction::findOrFail($id);

            $data = $request->only([
                'type', 'amount', 'category_id', 'transaction_date',
                'description', 'reference_number', 'payment_method', 'tags',
            ]);

            // Handle file upload using Laravel Storage
            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($transaction->attachment_path && Storage::disk('public')->exists($transaction->attachment_path)) {
                    Storage::disk('public')->delete($transaction->attachment_path);
                }

                // Upload new file
                $file = $request->file('attachment');
                $fileName = 'transaction_'.$transaction->id.'_'.time().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('transactions/'.date('Y/m'), $fileName, 'public');

                $data['attachment_path'] = $path;
                $data['attachment_original_name'] = $file->getClientOriginalName();
                $data['attachment_size'] = $file->getSize();
                $data['attachment_mime_type'] = $file->getMimeType();
            }

            $data['updated_by_id'] = auth()->id();
            $transaction->update($data);

            DB::commit();

            return Success::response([
                'message' => __('Transaction updated successfully'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return Error::response(__('Failed to update transaction: ').$e->getMessage());
        }
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy($id)
    {
        try {
            $transaction = BasicTransaction::findOrFail($id);

            // Delete attachment if exists
            if ($transaction->attachment_path && Storage::disk('public')->exists($transaction->attachment_path)) {
                Storage::disk('public')->delete($transaction->attachment_path);
            }

            $transaction->delete();

            return Success::response([
                'message' => __('Transaction deleted successfully'),
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to delete transaction: ').$e->getMessage());
        }
    }

    /**
     * Delete attachment from transaction.
     */
    public function deleteAttachment($id)
    {
        try {
            $transaction = BasicTransaction::findOrFail($id);

            // Delete attachment if exists
            if ($transaction->attachment_path && Storage::disk('public')->exists($transaction->attachment_path)) {
                Storage::disk('public')->delete($transaction->attachment_path);
            }

            $transaction->update([
                'attachment_path' => null,
                'attachment_original_name' => null,
                'attachment_size' => null,
                'attachment_mime_type' => null,
            ]);

            return Success::response([
                'message' => __('Attachment deleted successfully'),
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to delete attachment: ').$e->getMessage());
        }
    }

    /**
     * Download attachment from transaction.
     */
    public function downloadAttachment($id)
    {
        try {
            $transaction = BasicTransaction::findOrFail($id);

            // Download attachment
            if ($transaction->attachment_path && Storage::disk('public')->exists($transaction->attachment_path)) {
                $fileName = $transaction->attachment_original_name ?? basename($transaction->attachment_path);

                return response()->download(storage_path('app/public/'.$transaction->attachment_path), $fileName);
            }

            return Error::response(__('Attachment not found'));

        } catch (\Exception $e) {
            return Error::response(__('Failed to download attachment: ').$e->getMessage());
        }
    }
}
