@extends('layouts.app')

@section('title', 'Admin Accounts — ' . config('app.name', 'Task Management'))

@section('content')
@php
    $accounts = [
        ['name' => 'Shashi Kumar', 'email' => 'shashi@firm.com', 'role' => 'Super Admin', 'active' => true, 'locked' => true],
        ['name' => 'Neha Verma', 'email' => 'neha.verma@firm.com', 'role' => 'Admin', 'active' => true, 'locked' => false],
    ];
@endphp

<x-page-header title="Admin Accounts" subtitle="Users with Super Admin or Admin access to this system" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Admin Accounts']]">
    <x-slot:actions>
        <a href="{{ route('admin.admin-accounts.create') }}" class="btn btn-tm-primary">+ Add Admin</a>
    </x-slot:actions>
</x-page-header>

<div class="tm-card p-0">
    <div class="table-responsive">
        <table class="table tm-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($accounts as $i => $account)
                    <tr>
                        <td class="fw-semibold">{{ $account['name'] }}</td>
                        <td class="tm-muted">{{ $account['email'] }}</td>
                        <td><span class="badge text-bg-dark">{{ $account['role'] }}</span></td>
                        <td><span class="badge text-bg-{{ $account['active'] ? 'success' : 'secondary' }}">{{ $account['active'] ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            @if ($account['locked'])
                                <span class="small tm-muted" title="Super Admin cannot be edited or deactivated">Protected</span>
                            @else
                                <a href="{{ route('admin.admin-accounts.edit', $i + 1) }}" class="small text-decoration-underline">Edit</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
