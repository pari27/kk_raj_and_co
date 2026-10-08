@extends('layouts.customer')

@section('title', 'Ticket — ' . config('app.name', 'Task Management'))

@section('content')
@php
    $history = [
        ['to' => 'Documents Pending', 'comment' => 'Ticket created from your enquiry.', 'when' => '28 Sep 2026, 9:02 AM'],
        ['to' => 'Documents Received', 'comment' => 'Thank you, we received your documents.', 'when' => '28 Sep 2026, 4:40 PM'],
        ['to' => 'Under Verification', 'comment' => 'Reviewing your documents against our checklist.', 'when' => '29 Sep 2026, 9:15 AM'],
    ];

    $documents = [
        ['type' => 'Sales Register', 'status' => 'Verified', 'mandatory' => true],
        ['type' => 'Bank Statement', 'status' => 'Verified', 'mandatory' => true],
        ['type' => 'Purchase Register', 'status' => 'Pending', 'mandatory' => true],
    ];
@endphp

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-gold">
    <div>
        <h1 class="h4 tm-serif fw-bold mb-1">TKT-2026-0001</h1>
        <p class="tm-muted mb-0" style="font-size: .89rem;">GST Return Filing</p>
    </div>
    <x-status-badge status="Work In Progress" />
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="tm-card p-4 mb-3">
            <h2 class="h6 tm-serif fw-bold mb-3">Status timeline</h2>
            <div class="d-flex flex-column gap-3">
                @foreach ($history as $entry)
                    <div class="d-flex gap-3">
                        <div class="pt-1">
                            <div class="rounded-circle" style="width: 8px; height: 8px; background: var(--tm-accent); margin-top: 4px;"></div>
                        </div>
                        <div class="flex-grow-1 pb-3 border-bottom">
                            <div class="fw-semibold small mb-1">{{ $entry['to'] }}</div>
                            <div class="small mb-1">{{ $entry['comment'] }}</div>
                            <div class="tm-muted" style="font-size: .75rem;">{{ $entry['when'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="tm-card p-4">
            <h2 class="h6 tm-serif fw-bold mb-3">Documents</h2>
            <p class="small tm-muted mb-3">UI preview only — uploads are wired up once the Customer Portal backend is approved and built.</p>
            @foreach ($documents as $doc)
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div>
                        <div class="small fw-semibold">{{ $doc['type'] }}</div>
                        <span class="badge text-bg-{{ $doc['status'] === 'Verified' ? 'success' : 'secondary' }} mt-1">{{ $doc['status'] }}</span>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm">Upload</button>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
