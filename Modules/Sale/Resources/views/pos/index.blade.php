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
