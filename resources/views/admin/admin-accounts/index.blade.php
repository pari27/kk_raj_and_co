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
                    <th>Role</th>
                    <th>Status</th>
                    <th>Password</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($accounts as $account)
                    <tr>
                        <td class="fw-semibold">
                            {{ $account->name }}
                            @if ($account->id === auth()->id())<span class="badge rounded-pill text-bg-light border fw-normal ms-1">You</span>@endif
                        </td>
                        <td class="tm-muted">{{ $account->email }}</td>
                        <td><span class="badge text-bg-dark">Admin</span></td>
                        <td><span class="badge text-bg-{{ $account->is_active ? 'success' : 'secondary' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="small {{ $account->hasSetPassword() ? 'tm-muted' : 'text-warning-emphasis' }}">{{ $account->hasSetPassword() ? 'Set' : 'Invitation pending' }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-3">
                                @unless ($account->hasSetPassword())
                                    <form method="POST" action="{{ route('admin.admin-accounts.resend-invite', $account) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0 small">Resend invitation</button>
                                    </form>
                                @endunless
                                <a href="{{ route('admin.admin-accounts.edit', $account) }}" class="small text-decoration-underline">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center tm-muted py-5">No Admin accounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
