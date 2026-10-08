@extends('layouts.app')

@section('title', 'Email Templates — ' . config('app.name', 'Task Management'))

@section('content')
@php
    $templates = [
        ['key' => 'employee_welcome', 'subject' => 'Your account has been created'],
        ['key' => 'customer_otp', 'subject' => 'Your one-time login code'],
        ['key' => 'ticket_reassigned', 'subject' => 'A ticket has been reassigned to you'],
        ['key' => 'task_completed', 'subject' => 'Your ticket has been marked completed'],
        ['key' => 'payment_received', 'subject' => 'Payment received — thank you'],
    ];
@endphp

<x-page-header title="Settings" subtitle="Firm profile, branding and system preferences" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Settings'], ['label' => 'Email Templates']]" />

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.firm-profile') }}">Firm Profile</a></li>
    <li class="nav-item"><span class="nav-link active">Email Templates</span></li>
</ul>

<div class="tm-card p-0">
    <div class="table-responsive">
        <table class="table tm-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Template key</th>
                    <th>Subject</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($templates as $i => $template)
                    <tr>
                        <td class="fw-semibold"><code>{{ $template['key'] }}</code></td>
                        <td>{{ $template['subject'] }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.settings.email-templates.edit', $i + 1) }}" class="small text-decoration-underline">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
