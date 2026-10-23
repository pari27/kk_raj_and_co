@extends('layouts.app')

@section('title', 'Payment Details — ' . config('app.name', 'Task Management'))

@section('content')
<x-page-header title="Settings" subtitle="Firm profile, branding and system preferences" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Settings'], ['label' => 'Payment Details']]" />

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.firm-profile') }}">Firm Profile</a></li>
    <li class="nav-item"><span class="nav-link active">Payment Details</span></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.email-templates.index') }}">Email Templates</a></li>
</ul>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="tm-card p-4">

            <form method="POST" action="{{ route('admin.settings.payment-details.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="tm-field-label d-block" for="paymentUpiInput">UPI ID</label>
                    <input type="text" id="paymentUpiInput" name="payment_upi_id" maxlength="100" placeholder="name@bank" class="form-control tm-field @error('payment_upi_id') is-invalid @enderror" value="{{ old('payment_upi_id', $setting->payment_upi_id) }}">
                    @error('payment_upi_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <h2 class="h6 fw-bold mb-3">Bank transfer</h2>
                <div class="mb-3">
                    <label class="tm-field-label d-block" for="paymentAccountNameInput">Account name</label>
                    <input type="text" id="paymentAccountNameInput" name="payment_bank_account_name" maxlength="120" class="form-control tm-field @error('payment_bank_account_name') is-invalid @enderror" value="{{ old('payment_bank_account_name', $setting->payment_bank_account_name) }}">
                    @error('payment_bank_account_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-7">
                        <label class="tm-field-label d-block" for="paymentAccountNumberInput">Account number</label>
                        <input type="text" id="paymentAccountNumberInput" name="payment_bank_account_number" maxlength="40" inputmode="numeric" class="form-control tm-field @error('payment_bank_account_number') is-invalid @enderror" value="{{ old('payment_bank_account_number', $setting->payment_bank_account_number) }}">
                        @error('payment_bank_account_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="tm-field-label d-block" for="paymentIfscInput">IFSC code</label>
                        <input type="text" id="paymentIfscInput" name="payment_bank_ifsc" maxlength="11" placeholder="HDFC0001234" class="form-control tm-field text-uppercase @error('payment_bank_ifsc') is-invalid @enderror" value="{{ old('payment_bank_ifsc', $setting->payment_bank_ifsc) }}">
                        @error('payment_bank_ifsc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mb-4">
                    <label class="tm-field-label d-block" for="paymentBranchInput">Bank and branch</label>
                    <input type="text" id="paymentBranchInput" name="payment_bank_branch" maxlength="160" placeholder="HDFC Bank, Park Street, Kolkata" class="form-control tm-field @error('payment_bank_branch') is-invalid @enderror" value="{{ old('payment_bank_branch', $setting->payment_bank_branch) }}">
                    @error('payment_bank_branch')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <h2 class="h6 fw-bold mb-3">Anything else clients should know</h2>
                <div class="mb-4">
                    <label class="tm-field-label d-block" for="paymentInstructionsInput">Extra instructions</label>
                    <textarea id="paymentInstructionsInput" name="payment_instructions" rows="3" maxlength="1000" placeholder="For example: cheques should be drawn in favour of the firm. Payment by cash is accepted at the office." class="form-control tm-field @error('payment_instructions') is-invalid @enderror">{{ old('payment_instructions', $setting->payment_instructions) }}</textarea>
                    @error('payment_instructions')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-tm-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="tm-card p-0 overflow-hidden">
            <div class="tm-section-title">Where clients see this</div>
            <div class="p-4 payment-help">
                <p class="mb-2">On the client portal, the <strong>Payments</strong> page opens a "How to pay" window with three tabs:</p>
                <ul class="mb-0 ps-3">
                    <li><strong>UPI</strong>: your UPI ID, with a Copy button.</li>
                    <li><strong>Bank transfer</strong>: account name, number, IFSC and branch, each with a Copy button.</li>
                    <li><strong>Pay at office</strong>: your firm address from <a href="{{ route('admin.settings.firm-profile') }}">Firm Profile</a>.</li>
                </ul>
                <p class="mt-3 mb-0 tm-muted">Your extra instructions appear at the bottom of the window. A tab is hidden when its details are empty (Pay at office always shows).</p>
            </div>
        </div>
    </div>
</div>
@endsection
