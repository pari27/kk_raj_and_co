@extends('layouts.customer')

@section('title', 'Profile — ' . config('app.firm_name'))

@section('content')
@php
    $mobile = preg_replace('/\D/', '', (string) $customer->phone);
    $mobile = strlen($mobile) === 10 ? '+91 '.substr($mobile, 0, 5).' '.substr($mobile, 5) : $customer->phone;

    $lastLogin = $customer->last_login_at;
    $lastLoginText = $lastLogin
        ? ($lastLogin->isToday() ? 'Today' : ($lastLogin->isYesterday() ? 'Yesterday' : $lastLogin->format('j M Y'))).', '.$lastLogin->format('g:i A')
        : null;
    $lastLoginText = collect([$lastLoginText, $customer->last_login_device])->filter()->implode(' · ');
@endphp

<x-page-header title="Profile" :subtitle="'Your details with '.config('app.firm_name')" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('customer.dashboard')], ['label' => 'Profile']]" />

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="p-card mb-3">
            <div class="p-prof-hero">
                <span class="av">{{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}</span>
                <div>
                    <div class="nm">{{ $customer->name }}</div>
                    <span class="ppill lightgreen">Active client</span>
                    <span class="since">Client since {{ $customer->created_at->format('M Y') }}</span>
                </div>
            </div>
            <div class="p-prof-item">
                <span class="ic"><x-portal-icon name="user" :size="15" /></span>
                <span><span class="lb">Full name</span><span class="vl">{{ $customer->name }}</span></span>
            </div>
            <div class="p-prof-item">
                <span class="ic"><x-portal-icon name="phone" :size="15" /></span>
                <span><span class="lb">Mobile number</span><span class="vl">{{ $mobile }}</span></span>
            </div>
            <div class="p-prof-item">
                <span class="ic"><x-portal-icon name="mail" :size="15" /></span>
                <span class="flex-grow-1"><span class="lb">Email</span><span class="vl">{{ $customer->email }}</span></span>
                <span class="ppill lightgreen">Used for login</span>
            </div>
            <div class="p-note">
                To change your name, mobile or email, please contact the firm.
                @if (config('app.firm_phone')) <a href="tel:{{ preg_replace('/[^\d+]/', '', config('app.firm_phone')) }}">Call {{ config('app.firm_phone') }}</a> @endif
            </div>
        </div>

        @if ($contact)
            <div class="p-card">
                <div class="p-card-head teal"><span>Your contact at {{ config('app.firm_name') }}</span></div>
                <div class="p-prof-contact">
                    <span class="av">{{ $contact['initials'] }}</span>
                    <div class="flex-grow-1">
                        <div class="nm">{{ $contact['name'] }}</div>
                        <div class="sb">{{ collect([$contact['phone'], $contact['email']])->filter()->implode(' · ') }}</div>
                    </div>
                    @if ($contact['phone'])
                        <a href="tel:{{ preg_replace('/\D/', '', $contact['phone']) }}" class="call" aria-label="Call {{ $contact['name'] }}"><x-portal-icon name="phone" :size="15" /></a>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="col-12 col-lg-6">
        <div class="p-card mb-3">
            <div class="p-card-head navy"><span>Security</span></div>
            <div class="p-prof-item">
                <span class="ic"><x-portal-icon name="lock" :size="15" /></span>
                <span class="flex-grow-1">
                    <span class="vl">Password</span>
                    <span class="lb mb-0">{{ $customer->password_changed_at ? 'Last changed '.$customer->password_changed_at->format('j M Y') : 'Set when you first signed in' }}</span>
                </span>
                <a href="{{ route('customer.password.edit') }}" class="pbtn-outline">Change</a>
            </div>
            @if ($lastLoginText)
                <div class="p-prof-item">
                    <span class="ic"><x-portal-icon name="shield" :size="15" /></span>
                    <span>
                        <span class="vl">Last login</span>
                        <span class="lb mb-0">{{ $lastLoginText }}</span>
                    </span>
                </div>
            @endif
        </div>

        <div class="p-card mb-3">
            <div class="p-card-head green"><span>Notifications</span></div>
            <form method="POST" action="{{ route('customer.profile.email-updates') }}" class="p-prof-item m-0">
                @csrf
                @method('PUT')
                <span class="ic"><x-portal-icon name="mail" :size="15" /></span>
                <span class="flex-grow-1">
                    <span class="vl">Email updates</span>
                    <span class="lb mb-0">Documents needed, work completed and payment updates</span>
                </span>
                <span class="form-check form-switch m-0 p-0">
                    <input type="hidden" name="email_updates" value="0">
                    <input class="form-check-input m-0 u-0b9c7b" type="checkbox" role="switch" name="email_updates" value="1" @checked($customer->email_notifications_enabled) onchange="this.form.submit()" aria-label="Email updates">
                </span>
            </form>
            @unless ($customer->email_notifications_enabled)
                <div class="p-prof-warn">Email updates are off. You will still see every update under the bell.</div>
            @endunless
            <div class="p-prof-item">
                <span class="ic"><x-portal-icon name="bell" :size="15" /></span>
                <span class="flex-grow-1">
                    <span class="vl">In-app updates</span>
                    <span class="lb mb-0">Always on, shown under the bell</span>
                </span>
                <span class="ppill lightgreen">On</span>
            </div>
        </div>

        <form method="POST" action="{{ route('customer.logout') }}">
            @csrf
            <button type="submit" class="btn w-100 py-2 u-24d101"><x-portal-icon name="logout" :size="16" /> Log out</button>
        </form>
    </div>
</div>
@endsection
