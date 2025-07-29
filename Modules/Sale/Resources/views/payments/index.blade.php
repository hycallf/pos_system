@extends('layouts.app')

@section('title', 'Sale Payments')

@section('third_party_stylesheets')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
        <li class="breadcrumb-item"><a href="{{ route('sales.show', $sale) }}">{{ $sale->reference }}</a></li>
        <li class="breadcrumb-item active">Payments</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                @include('utils.alerts')
                <div class="card">
                    <div class="card-body">
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

    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}">
    </script>
    <script>
        $(document).ready(function() {
            window.addEventListener('showCheckoutModal', event => {
                $('#checkoutModal').modal('show');

                $('#paid_amount').maskMoney({
                    prefix: '{{ settings()->currency->symbol }}',
                    thousands: '{{ settings()->currency->thousand_separator }}',
                    decimal: '{{ settings()->currency->decimal_separator }}',
                    allowZero: false,
                });

                $('#total_amount').maskMoney({
                    prefix: '{{ settings()->currency->symbol }}',
                    thousands: '{{ settings()->currency->thousand_separator }}',
                    decimal: '{{ settings()->currency->decimal_separator }}',
                    allowZero: true,
                });

                $('#paid_amount').maskMoney('mask');
                $('#total_amount').maskMoney('mask');

                $('#checkout-form').on('submit', function(e) {

                    // Unmask input total_amount
                    var total_amount = $('#total_amount').maskMoney('unmasked')[0];
                    $('#total_amount').val(total_amount);

                    // HANYA unmask paid_amount jika elemennya ada
                    if ($('#paid_amount').length) {
                        var paid_amount = $('#paid_amount').maskMoney('unmasked')[0];
                        $('#paid_amount').val(paid_amount);
                    }

                    // Kirim data ke controller via AJAX
                    var formData = $(this).serialize();
                    var submitButton = $(this).find('button[type="submit"]');
                    submitButton.prop('disabled', true).text('Processing...');

                    $.ajax({
                        url: $(this).attr('action'),
                        method: 'POST',
                        data: formData,
                        success: function(response) {
                            if (response.snap_token) {
                                // Jika dapat snap_token, buka popup Midtrans
                                $('#checkoutModal').modal('hide');
                                snap.pay(response.snap_token, {
                                    onSuccess: function(result) {
                                        alert("Pembayaran berhasil!");
                                        let sale_id = result.order_id.split(
                                            '-')[1];
                                        window.location.href =
                                            "/sales/pos/pdf/" + sale_id;
                                    },
                                    onPending: function(result) {
                                        alert("Menunggu pembayaran Anda!");
                                        location.reload();
                                    },
                                    onError: function(result) {
                                        alert("Pembayaran Gagal!");
                                        submitButton.prop('disabled', false)
                                            .text('Submit');
                                    },
                                    onClose: function() {
                                        alert(
                                            'Anda menutup popup tanpa menyelesaikan pembayaran.'
                                        );
                                        submitButton.prop('disabled', false)
                                            .text('Submit');
                                    }
                                });
                            } else if (response.redirect_url) {
                                // Jika pembayaran cash, redirect ke halaman struk
                                window.location.href = response.redirect_url;
                            }
                        },
                        error: function(xhr) {
                            alert('Terjadi kesalahan. Silakan cek console.');
                            console.log(xhr.responseText);
                            submitButton.prop('disabled', false).text('Submit');
                        }
                    });
                }); // <-- Penutup untuk $('#checkout-form').on('submit', ...)
            });
        });
    </script>


    <script>
        // Listener untuk event dari Livewire
        window.addEventListener('open-midtrans-snap', event => {
            snap.pay(event.detail.token, {
                onSuccess: function(result) {
                    alert("Pembayaran berhasil!");
                    // Beritahu Livewire bahwa pembayaran sukses untuk proses selanjutnya
                    Livewire.emit('paymentSuccess', result);
                },
                onPending: function(result) {
                    alert("Menunggu pembayaran Anda!");
                    // Anda bisa emit event 'paymentPending' jika perlu
                },
                onError: function(result) {
                    alert("Pembayaran Gagal!");
                },
                onClose: function() {
                    console.log('Anda menutup popup tanpa menyelesaikan pembayaran');
                }
            });
        });

        window.addEventListener('error', event => {
            alert(event.detail.message);
        });
    </script>
@endpush
