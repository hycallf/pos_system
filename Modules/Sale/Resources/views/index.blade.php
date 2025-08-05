@extends('layouts.app')

@section('title', 'Sales')

@section('third_party_stylesheets')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item active">Sales</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <a href="{{ route('sales.create') }}" class="btn btn-primary">
                            Add Sale <i class="bi bi-plus"></i>
                        </a>

                        <hr>

                        <div class="table-responsive">
                            {!! $dataTable->table() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page_scripts')
    {!! $dataTable->scripts() !!}

    {{-- Tambahkan script Midtrans dan listener di sini --}}
    {{-- <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}">
    </script>
    <script>
        // Pastikan event listener ditambahkan setelah datatable selesai digambar
        $(document).on('click', '.retry-midtrans-payment', function() {
            let snapToken = $(this).data('token');

            if (snapToken) {
                snap.pay(snapToken, {
                    onSuccess: function(result) {
                        alert("Pembayaran berhasil!");
                        // Muat ulang datatable untuk melihat status terbaru
                        window.LaravelDataTables["sales-table"].ajax.reload();
                    },
                    onPending: function(result) {
                        alert("Menunggu pembayaran Anda!");
                        window.LaravelDataTables["sales-table"].ajax.reload();
                    },
                    onError: function(result) {
                        alert("Pembayaran Gagal!");
                    },
                    onClose: function() {
                        console.log('Anda menutup popup tanpa menyelesaikan pembayaran.');
                    }
                });
            } else {
                alert('Snap token tidak ditemukan!');
            }
        });
    </script> --}}
@endpush
