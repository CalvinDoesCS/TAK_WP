@extends('layouts.layoutMaster')

@section('title', __('Payment Instructions'))

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h2 class="mb-2">{{ __('Complete Your Payment') }}</h2>
                    <p class="text-muted">{{ __('Follow the instructions below to complete your payment') }}</p>
                </div>

                {{-- Payment Summary --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Payment Summary') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <strong>{{ __('Payment ID') }}:</strong>
                            </div>
                            <div class="col-sm-6">
                                #{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <strong>{{ __('Amount Due') }}:</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="h5 mb-0 text-primary">{{ $payment->formatted_amount }}</span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <strong>{{ __('Description') }}:</strong>
                            </div>
                            <div class="col-sm-6">
                                {{ $payment->description }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>{{ __('Due Date') }}:</strong>
                            </div>
                            <div class="col-sm-6">
                                {{ now()->addDays(3)->format('M d, Y') }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bank Transfer Instructions --}}
                @php
                    $offlineSettings = \Modules\MultiTenancyCore\App\Models\SaasSetting::getOfflinePaymentSettings();
                @endphp
                
                @if($offlineSettings['bank_name'])
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Bank Transfer Instructions') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-4" role="alert">
                                <h6 class="alert-heading mb-1">{{ __('Important') }}</h6>
                                <p class="mb-0">{{ __('Please include your Payment ID (#:id) in the transfer reference', ['id' => str_pad($payment->id, 6, '0', STR_PAD_LEFT)]) }}</p>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-sm-4">
                                    <strong>{{ __('Bank Name') }}:</strong>
                                </div>
                                <div class="col-sm-8">
                                    {{ $offlineSettings['bank_name'] }}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4">
                                    <strong>{{ __('Account Name') }}:</strong>
                                </div>
                                <div class="col-sm-8">
                                    {{ $offlineSettings['account_name'] }}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4">
                                    <strong>{{ __('Account Number') }}:</strong>
                                </div>
                                <div class="col-sm-8">
                                    <code>{{ $offlineSettings['account_number'] }}</code>
                                </div>
                            </div>
                            @if($offlineSettings['routing_number'])
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>{{ __('Routing Number') }}:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <code>{{ $offlineSettings['routing_number'] }}</code>
                                    </div>
                                </div>
                            @endif
                            @if($offlineSettings['swift_code'])
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>{{ __('SWIFT Code') }}:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <code>{{ $offlineSettings['swift_code'] }}</code>
                                    </div>
                                </div>
                            @endif
                            @if($offlineSettings['bank_address'])
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>{{ __('Bank Address') }}:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        {{ $offlineSettings['bank_address'] }}
                                    </div>
                                </div>
                            @endif
                            
                            @if($offlineSettings['payment_instructions'])
                                <hr class="my-4">
                                <h6 class="mb-3">{{ __('Additional Instructions') }}</h6>
                                <p>{!! nl2br(e($offlineSettings['payment_instructions'])) !!}</p>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Upload Proof --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Upload Payment Proof') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($payment->proof_document_path)
                            <div class="alert alert-success mb-4" role="alert">
                                <h6 class="alert-heading mb-1">{{ __('Proof Uploaded') }}</h6>
                                <p class="mb-0">{{ __('Your payment proof was uploaded. We will verify your payment shortly.') }}</p>
                            </div>
                        @else
                            <p class="mb-4">{{ __('After making the bank transfer, please upload a screenshot or PDF of the payment receipt.') }}</p>
                            
                            <form action="{{ route('multitenancycore.tenant.payment.upload-proof', $payment->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label" for="proof">{{ __('Payment Proof') }}</label>
                                    <input type="file" class="form-control" id="proof" name="proof" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="form-text text-muted">{{ __('Accepted formats: PDF, JPG, JPEG, PNG (Max: 5MB)') }}</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-upload me-2"></i>{{ __('Upload Proof') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="mt-4 text-center">
                    <a href="{{ route('multitenancycore.tenant.subscription') }}" class="btn btn-label-secondary">
                        <i class="bx bx-arrow-back me-2"></i>{{ __('Back to Subscription') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection