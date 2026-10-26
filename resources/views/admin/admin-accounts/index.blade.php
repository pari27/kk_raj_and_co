@extends('layouts.app')

@section('title', 'Admin Accounts — ' . config('app.name', 'Task Management'))

@section('content')
<x-page-header title="Admin Accounts" subtitle="Users with Admin access to this system" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Admin Accounts']]">
    <x-slot:actions>
        <a href="{{ route('admin.admin-accounts.create') }}" class="btn btn-tm-primary">+ Add Admin</a>
    </x-slot:actions>
</x-page-header>

<div class="tm-card p-0">
    <div class="table-responsive">
        <table class="table tm-table align-middle mb-0 admin-accounts-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($accounts as $account)
                    <tr>
                        <td class="fw-semibold">
                            {{ $account->name }}
                        </td>
                        <td class="tm-muted">{{ $account->email }}</td>
                        <td><span class="badge text-bg-{{ $account->is_active ? 'success' : 'secondary' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-3">
                                @unless ($account->hasSetPassword())
                                    <form method="POST" action="{{ route('admin.admin-accounts.resend-invite', $account) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0 small">Resend invitation</button>
                                    </form>
                                @endunless
                                <a href="{{ route('admin.admin-accounts.edit', $account) }}" class="tm-icon-btn-sm" title="Edit" aria-label="Edit {{ $account->name }}"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#4a9b3e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5Z"></path></svg></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center tm-muted py-5">No Admin accounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
