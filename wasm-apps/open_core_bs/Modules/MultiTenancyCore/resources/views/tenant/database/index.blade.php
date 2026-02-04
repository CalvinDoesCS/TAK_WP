@extends('layouts.layoutMaster')

@section('title', __('Database Information'))

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="text-center mb-5">
                    <h2 class="mb-2">{{ __('Database Information') }}</h2>
                    <p class="text-muted">{{ __('Access your database credentials and connection details') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                @if($isProvisioned)
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Database Credentials') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning mb-4" role="alert">
                                <h6 class="alert-heading mb-1">
                                    <i class="bx bx-shield-quarter me-2"></i>{{ __('Security Notice') }}
                                </h6>
                                <p class="mb-0">{{ __('Keep these credentials secure. Do not share them publicly or commit them to version control.') }}</p>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <td class="fw-medium" style="width: 30%;">{{ __('Database Host') }}</td>
                                            <td>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <code class="me-2">{{ $database->db_host }}</code>
                                                    <button class="btn btn-sm btn-icon btn-label-secondary" onclick="copyToClipboard('{{ $database->db_host }}', this)">
                                                        <i class="bx bx-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">{{ __('Database Port') }}</td>
                                            <td>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <code class="me-2">{{ $database->db_port }}</code>
                                                    <button class="btn btn-sm btn-icon btn-label-secondary" onclick="copyToClipboard('{{ $database->db_port }}', this)">
                                                        <i class="bx bx-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">{{ __('Database Name') }}</td>
                                            <td>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <code class="me-2">{{ $database->db_name }}</code>
                                                    <button class="btn btn-sm btn-icon btn-label-secondary" onclick="copyToClipboard('{{ $database->db_name }}', this)">
                                                        <i class="bx bx-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">{{ __('Database Username') }}</td>
                                            <td>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <code class="me-2">{{ $database->db_username }}</code>
                                                    <button class="btn btn-sm btn-icon btn-label-secondary" onclick="copyToClipboard('{{ $database->db_username }}', this)">
                                                        <i class="bx bx-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">{{ __('Database Password') }}</td>
                                            <td>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <code class="me-2" id="dbPassword">••••••••</code>
                                                    <div>
                                                        <button class="btn btn-sm btn-icon btn-label-secondary me-1" onclick="togglePassword()">
                                                            <i class="bx bx-show" id="toggleIcon"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-icon btn-label-secondary" onclick="copyToClipboard('{{ $database->db_password }}', this)">
                                                            <i class="bx bx-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                <h6 class="mb-3">{{ __('Connection Examples') }}</h6>
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#phpTab" role="tab">
                                            PHP
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#nodeTab" role="tab">
                                            Node.js
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pythonTab" role="tab">
                                            Python
                                        </button>
                                    </li>
                                </ul>
                                <div class="tab-content pt-3">
                                    <div class="tab-pane fade show active" id="phpTab" role="tabpanel">
                                        <pre class="bg-light p-3 rounded"><code>$connection = new PDO(
    'mysql:host={{ $database->db_host }};port={{ $database->db_port }};dbname={{ $database->db_name }}',
    '{{ $database->db_username }}',
    'your_password'
);</code></pre>
                                    </div>
                                    <div class="tab-pane fade" id="nodeTab" role="tabpanel">
                                        <pre class="bg-light p-3 rounded"><code>const mysql = require('mysql2');
const connection = mysql.createConnection({
    host: '{{ $database->db_host }}',
    port: {{ $database->db_port }},
    user: '{{ $database->db_username }}',
    password: 'your_password',
    database: '{{ $database->db_name }}'
});</code></pre>
                                    </div>
                                    <div class="tab-pane fade" id="pythonTab" role="tabpanel">
                                        <pre class="bg-light p-3 rounded"><code>import mysql.connector

connection = mysql.connector.connect(
    host="{{ $database->db_host }}",
    port={{ $database->db_port }},
    user="{{ $database->db_username }}",
    password="your_password",
    database="{{ $database->db_name }}"
)</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Additional Information --}}
                    <div class="card mt-4">
                        <div class="card-body">
                            <h6 class="mb-3">{{ __('Database Information') }}</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="bx bx-check-circle text-success me-2"></i>
                                    {{ __('Database provisioned on :date', ['date' => $database->created_at->format('M d, Y')]) }}
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-check-circle text-success me-2"></i>
                                    {{ __('All tables and data have been migrated') }}
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-info-circle text-info me-2"></i>
                                    {{ __('Regular backups are performed automatically') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                @else
                    {{-- Not Provisioned Yet --}}
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <i class="bx bx-data" style="font-size: 4rem; color: #e0e0e0;"></i>
                            </div>
                            <h5 class="mb-3">{{ __('Database Not Yet Provisioned') }}</h5>
                            <p class="text-muted mb-4">
                                {{ __('Your database is being set up. This usually takes a few minutes after your subscription is activated.') }}
                            </p>
                            
                            @if($tenant->status === 'approved')
                                <div class="alert alert-info" role="alert">
                                    <i class="bx bx-info-circle me-2"></i>
                                    {{ __('Your account is approved. Database provisioning will begin shortly.') }}
                                </div>
                            @elseif($tenant->status === 'pending')
                                <div class="alert alert-warning" role="alert">
                                    <i class="bx bx-time me-2"></i>
                                    {{ __('Your account is pending approval. Database will be provisioned after approval.') }}
                                </div>
                            @endif
                            
                            <a href="{{ route('multitenancycore.tenant.dashboard') }}" class="btn btn-primary">
                                <i class="bx bx-arrow-back me-2"></i>{{ __('Back to Dashboard') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script>
    // Password visibility toggle
    let passwordVisible = false;
    const actualPassword = @json($database->db_password ?? '');
    
    function togglePassword() {
        const passwordEl = document.getElementById('dbPassword');
        const toggleIcon = document.getElementById('toggleIcon');
        
        passwordVisible = !passwordVisible;
        
        if (passwordVisible) {
            passwordEl.textContent = actualPassword;
            toggleIcon.classList.remove('bx-show');
            toggleIcon.classList.add('bx-hide');
        } else {
            passwordEl.textContent = '••••••••';
            toggleIcon.classList.remove('bx-hide');
            toggleIcon.classList.add('bx-show');
        }
    }
    
    // Copy to clipboard
    function copyToClipboard(text, button) {
        navigator.clipboard.writeText(text).then(function() {
            // Change icon temporarily
            const icon = button.querySelector('i');
            icon.classList.remove('bx-copy');
            icon.classList.add('bx-check');
            
            // Show toast or change back after delay
            setTimeout(() => {
                icon.classList.remove('bx-check');
                icon.classList.add('bx-copy');
            }, 2000);
        });
    }
</script>
@endsection