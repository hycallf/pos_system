<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Belanja - {{ $sale->reference }}</title>
    <style>
        /* CSS ini dirancang khusus untuk printer struk thermal */
        @page {
            /* Mengatur margin cetak menjadi sangat kecil */
            margin: 3mm 5mm;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10pt;
            /* Ukuran font standar untuk struk */
            color: #000;
            line-height: 1.4;
        }

        .receipt-container {
            width: 100%;
        }

        .header,
        .footer {
            text-align: center;
        }

        .header .company-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .header .address {
            font-size: 9pt;
            margin-bottom: 5px;
        }

        .separator {
            width: 100%;
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .info-table,
        .summary-table {
            width: 100%;
        }

        .info-table td,
        .summary-table td {
            padding: 1px 0;
        }

        .items-table {
            width: 100%;
            margin-top: 5px;
        }

        /* Style untuk setiap item produk */
        .item .name {
            display: block;
            /* Nama produk di baris pertama */
        }

        .item .details {
            display: flex;
            justify-content: space-between;
            /* Membuat harga rata kanan */
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            /* Menebalkan baris total */
        }

        .footer .thank-you {
            margin-top: 5px;
            font-size: 9pt;
        }

        .barcode {
            margin-top: 10px;
        }
    </style>
</head>

<body onload="window.print()">

    <div class="receipt-container">

        <div class="header">
            <div class="company-name">{{ settings()->company_name }}</div>
            <div class="address">
                {{ settings()->company_address }}<br>
                Telp: {{ settings()->company_phone }}
            </div>
            <div class="separator"></div>
            <table class="info-table">
                <tr>
                    <td>Tanggal</td>
                    <td>: {{ $sale->date }}</td>
                </tr>
                <tr>
                    <td>No. Struk</td>
                    <td>: {{ $sale->reference }}</td>
                </tr>
                <tr>
                    <td>Kasir</td>
                    <td>: {{ $sale->user->name }}</td>
                </tr>
            </table>
            <div class="separator"></div>
        </div>

        <div class="items">
            @foreach ($sale->saleDetails as $detail)
                <div class="item">
                    <span class="name">{{ $detail->product_name }}</span>
                    <div class="details">
                        <span>{{ $detail->quantity }}x {{ number_format($detail->price) }}</span>
                        <span>{{ number_format($detail->sub_total) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="separator"></div>

        <div class="summary">
            <div class="summary-row">
                <span>Total Belanja</span>
                <span>{{ format_currency($sale->total_amount + $sale->discount_amount - $sale->tax_amount - $sale->shipping_amount) }}</span>
            </div>
            @if ($sale->discount_amount > 0)
                <div class="summary-row">
                    <span>Diskon</span>
                    <span>-{{ format_currency($sale->discount_amount) }}</span>
                </div>
            @endif
            @if ($sale->tax_amount > 0)
                <div class="summary-row">
                    <span>Pajak</span>
                    <span>{{ format_currency($sale->tax_amount) }}</span>
                </div>
            @endif
            @if ($sale->shipping_amount > 0)
                <div class="summary-row">
                    <span>Ongkir</span>
                    <span>{{ format_currency($sale->shipping_amount) }}</span>
                </div>
            @endif
            <div class="separator"></div>
            <div class="summary-row">
                <span>GRAND TOTAL</span>
                <span>{{ format_currency($sale->total_amount) }}</span>
            </div>
            <div class="summary-row">
                <span>TUNAI ({{ $sale->payment_type ?? $sale->payment_method }})</span>
                <span>{{ format_currency($sale->paid_amount) }}</span>
            </div>
            <div class="summary-row">
                <span>KEMBALI</span>
                <span>{{ format_currency($sale->paid_amount - $sale->total_amount) }}</span>
            </div>
        </div>

        <div class="footer">
            <div class="separator"></div>
            <div class="thank-you">
                TERIMA KASIH TELAH BERBELANJA
            </div>
            <div class="barcode">
                {!! \Milon\Barcode\Facades\DNS1DFacade::getBarcodeSVG($sale->reference, 'C128', 1.5, 40, 'black', false) !!}
            </div>
        </div>

    </div>

</body>

</html>
