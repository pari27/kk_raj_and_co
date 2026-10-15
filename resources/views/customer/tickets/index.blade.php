@extends('layouts.customer')

@section('title', 'My services — ' . config('app.firm_name'))

@section('content')
<x-page-header title="My services" subtitle="Everything we are doing, or have done, for you" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('customer.dashboard')], ['label' => 'My services']]" />

@include('customer._services-card')
@endsection
