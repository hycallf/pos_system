<?php

namespace Modules\Reports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Modules\Sale\Entities\Sale;
use App\Models\User;
// GANTI DENGAN BARIS INI
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;


class ReportsController extends Controller
{

    public function salesByUser(Request $request)
    {
        // Ambil input bulan dan tahun dari request, jika tidak ada, gunakan bulan & tahun saat ini
        $selectedMonth = $request->input('month', date('m'));
        $selectedYear = $request->input('year', date('Y'));

        // Query untuk mengambil data penjualan
        $salesSummary = Sale::join('users', 'sales.user_id', '=', 'users.id')
            // Filter berdasarkan tahun dan bulan yang dipilih
            ->whereYear('sales.date', $selectedYear)
            ->whereMonth('sales.date', $selectedMonth)
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                DB::raw('SUM(sales.total_amount / 100) as total_sales')
            )
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_sales', 'desc')
            ->get();

        // Data untuk mengisi dropdown filter
        $years = Sale::select(DB::raw('YEAR(date) as year'))->distinct()->orderBy('year', 'desc')->pluck('year');
        $months = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];

        // Kirim semua data yang dibutuhkan ke view
        return view('reports::sales.summary-by-user', compact('salesSummary', 'years', 'months', 'selectedMonth', 'selectedYear'));
    }

    // Di dalam ReportController.php

    public function downloadSalesSummaryPdf(Request $request)
    {
        // Logika filter sama persis seperti sebelumnya
        $selectedMonth = $request->input('month', date('m'));
        $selectedYear = $request->input('year', date('Y'));

        $salesSummary = Sale::join('users', 'sales.user_id', '=', 'users.id')
            ->whereYear('sales.date', $selectedYear)
            ->whereMonth('sales.date', $selectedMonth)
            ->select(
                'users.name as user_name',
                DB::raw('SUM(sales.total_amount / 100) as total_sales')
            )
            ->groupBy('users.name')
            ->orderBy('total_sales', 'desc')
            ->get();

        // Data bulan untuk judul PDF
        $monthName = date('F', mktime(0, 0, 0, $selectedMonth, 10));

        // Panggil library SnappyPDF, kirim data, dan stream ke browser
        $pdf = PDF::loadView('reports::sales.pdf.summary-by-user-pdf', [
            'salesSummary' => $salesSummary,
            'month' => $monthName,
            'year' => $selectedYear
        ]);

        // Berikan nama file untuk di-download
        return $pdf->download('sales_summary_report_' . $monthName . '_' . $selectedYear . '.pdf');
    }

    public function downloadSalesDetailsPdf(Request $request)
    {
        // Ambil filter dari URL
        $userId = $request->input('user_id');
        $selectedMonth = $request->input('month', date('m'));
        $selectedYear = $request->input('year', date('Y'));

        // Ambil data user untuk judul
        $user = User::findOrFail($userId);

        // Ambil SEMUA transaksi penjualan dari user tersebut pada periode yang dipilih
        $sales = Sale::where('user_id', $userId)
            ->whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth)
            ->orderBy('date', 'desc')
            ->get();

        // Data bulan untuk judul PDF
        $monthName = date('F', mktime(0, 0, 0, $selectedMonth, 10));

        // Panggil library SnappyPDF, kirim data, dan stream ke browser
        $pdf = PDF::loadView('reports::sales.pdf.details-by-user-pdf', [
            'sales' => $sales,
            'user' => $user,
            'month' => $monthName,
            'year' => $selectedYear
        ]);

        return $pdf->download('sales_details_report_' . $user->name . '_' . $monthName . '_' . $selectedYear . '.pdf');
    }

    public function profitLossReport() {
        abort_if(Gate::denies('access_reports'), 403);

        return view('reports::profit-loss.index');
    }

    public function paymentsReport() {
        abort_if(Gate::denies('access_reports'), 403);

        return view('reports::payments.index');
    }

    public function salesReport(Request $request) {
        abort_if(Gate::denies('access_reports'), 403);
        $customers = \Modules\People\Entities\Customer::all();
        // Ambil semua data dari URL
        $userId = $request->get('user_id');
        $month = $request->get('month');
        $year = $request->get('year');

        // Cari nama kasir untuk ditampilkan di header
        $user = User::find($userId);

        return view('reports::sales.index', compact('customers', 'userId', 'month', 'year', 'user'));
    }

    public function purchasesReport() {
        abort_if(Gate::denies('access_reports'), 403);

        return view('reports::purchases.index');
    }

    public function salesReturnReport() {
        abort_if(Gate::denies('access_reports'), 403);

        return view('reports::sales-return.index');
    }

    public function purchasesReturnReport() {
        abort_if(Gate::denies('access_reports'), 403);

        return view('reports::purchases-return.index');
    }
}
