@extends('layouts.app')

@section('title', 'POS')

@section('third_party_stylesheets')

@endsection

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item active">POS</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                @include('utils.alerts')
            </div>
            <div class="col-lg-7">
                <livewire:search-product />
                <livewire:pos.product-list :categories="$product_categories" />
            </div>
            <div class="col-lg-5">
                <livewire:pos.checkout :cart-instance="'sale'" :customers="$customers" />
            </div>
        </div>
    </div>
@endsection

@push('page_scripts')
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}">
    </script>
    <script>
        $(document).ready(function() {
            window.addEventListener('showCheckoutModal', event => {
                $('#checkoutModal').modal('show');
            });

            $('#checkout-form').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                var submitButton = $(this).find('button[type="submit"]');
                submitButton.prop('disabled', true).text('Processing...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.snap_token) {
                            $('#checkoutModal').modal('hide');
                            snap.pay(response.snap_token, {
                                onSuccess: function(result) {
                                    alert("Pembayaran berhasil!");
                                    // 1. Ambil sale_id dari order_id yang dikembalikan Midtrans
                                    let sale_id = result.order_id.split('-')[1];

                                    // 2. Buat URL untuk invoice dan halaman utama
                                    let printUrl = `/sales/pos/receipt/${sale_id}`;
                                    let redirectUrl = "{{ route('sales.index') }}";

                                    // 3. Buka invoice di tab baru dan redirect halaman utama
                                    window.open(printUrl, '_blank');
                                    window.location.href = redirectUrl;
                                },
                                onPending: function(result) {
                                    alert("Menunggu pembayaran Anda!");
                                    location.reload();
                                },
                                onError: function(result) {
                                    alert("Pembayaran Gagal!");
                                    submitButton.prop('disabled', false).text(
                                        'Submit');
                                },
                                onClose: function() {
                                    alert(
                                        'Anda menutup popup tanpa menyelesaikan pembayaran.'
                                    );
                                    submitButton.prop('disabled', false).text(
                                        'Submit');
                                }
                            });
                        } else if (response.print_url && response.redirect_url) {
                            window.open(response.print_url, '_blank');
                            window.location.href = response.redirect_url;
                        }
                    },
                    error: function(xhr) {
                        alert('Terjadi kesalahan. Silakan cek console.');
                        console.log(xhr.responseText);
                        submitButton.prop('disabled', false).text('Submit');
                    }
                });
            });
        });
    </script>
@endpush
