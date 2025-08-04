<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Details Report</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 6px;
        }

        th {
            background-color: #f2f2f2;
        }

        h1 {
            text-align: center;
            margin-bottom: 0;
        }

        p {
            text-align: center;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <h1>Sales Details Report</h1>
    <p>Kasir: <strong>{{ $user->name }}</strong> | Periode: {{ $month }} {{ $year }}</p>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Reference</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Dibayar</th>
                <th>Sisa</th>
                <th>Status Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td>{{ $sale->date }}</td>
                    <td>{{ $sale->reference }}</td>
                    <td>{{ $sale->customer_name }}</td>
                    <td>{{ $sale->status }}</td>
                    <td>{{ format_currency($sale->total_amount) }}</td>
                    <td>{{ format_currency($sale->paid_amount) }}</td>
                    <td>{{ format_currency($sale->due_amount) }}</td>
                    <td>{{ $sale->payment_status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">Tidak ada data penjualan untuk user ini pada periode
                        yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
