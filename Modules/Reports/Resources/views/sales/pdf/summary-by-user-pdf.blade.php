<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Summary Report</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dddddd; text-align: left; padding: 8px; }
        th { background-color: #f2f2f2; }
        h1 { text-align: center; }
    </style>
</head>
<body>
    <h1>Sales Summary Report</h1>
    <p>Period: {{ $month }} {{ $year }}</p>

    <table>
        <thead>
            <tr>
                <th>Nama Kasir</th>
                <th>Total Penjualan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesSummary as $summary)
                <tr>
                    <td>{{ $summary->user_name }}</td>
                    <td>{{ format_currency($summary->total_sales) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" style="text-align: center;">Tidak ada data penjualan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
