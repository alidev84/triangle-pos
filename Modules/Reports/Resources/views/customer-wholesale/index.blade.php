@extends('layouts.app')

@section('title', 'Customer Report')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item active">Customer Report</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <livewire:reports.customer-wholesale-report :customers="\Modules\People\Entities\Customer::all()"/>
    </div>
@endsection

