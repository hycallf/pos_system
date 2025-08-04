@extends('layouts.app')

@section('title', 'Sales Report')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.sales.summary') }}">Sales Report</a></li>
        <li class="breadcrumb-item active">Details</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                @if ($user)
                    <h3>Detail Transaksi: <strong>{{ $user->name }}</strong></h3>
                @endif
            </div>
            <a href="{{ route('reports.sales.summary') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Summary
            </a>
        </div>

        <livewire:reports.sales-report :customers="$customers" :user-id="$userId" :month="$month" :year="$year" />
    </div>
@endsection
