@extends('layouts.app')

@section('title', 'Sales Report by Cashier')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item active">Sales Report by Cashier</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">

        <div class="card">
            <div class="card-body">

                <div class="d-flex justify-content-end mb-3">
                    <button onclick="window.print()" class="btn btn-primary mr-2">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="{{ route('reports.sales.summary.pdf', ['month' => $selectedMonth, 'year' => $selectedYear]) }}"
                        class="btn btn-danger">
                        <i class="bi bi-file-earmark-pdf"></i> Download PDF
                    </a>
                </div>

                <form action="{{ route('reports.sales.summary') }}" method="GET">
                    <div class="form-row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="month">Bulan</label>
                                <select name="month" id="month" class="form-control">
                                    @foreach ($months as $num => $name)
                                        <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="year">Tahun</label>
                                <select name="year" id="year" class="form-control">
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" style="margin-top: 32px;">
                                    Filter <i class="bi bi-filter"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nama Kasir</th>
                                        <th>Total Penjualan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($salesSummary as $summary)
                                        <tr>
                                            <td>{{ $summary->user_name }}</td>
                                            <td>{{ format_currency($summary->total_sales) }}</td>
                                            <td>
                                                <a href="{{ route('reports.sales.details', [
                                                    'user_id' => $summary->user_id,
                                                    'month' => $selectedMonth,
                                                    'year' => $selectedYear,
                                                ]) }}"
                                                    class="btn btn-primary btn-sm">
                                                    Lihat Detail <i class="bi bi-arrow-right"></i>
                                                </a>
                                                <a href="{{ route('reports.sales.details.pdf', [
                                                    'user_id' => $summary->user_id,
                                                    'month' => $selectedMonth,
                                                    'year' => $selectedYear,
                                                ]) }}"
                                                    class="btn btn-danger btn-sm">
                                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center">Tidak ada data penjualan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page_scripts')
    {{-- CSS KHUSUS UNTUK PRINT --}}
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .printable-area,
            .printable-area * {
                visibility: visible;
            }

            .printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .d-print-none {
                display: none !important;
            }
        }
    </style>
@endpush
