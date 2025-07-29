<?php

namespace Modules\Sale\Http\Controllers;

use Modules\Sale\DataTables\SalePaymentsDataTable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Sale\Entities\Sale;
use Modules\Sale\Entities\SalePayment;
use Midtrans;

class SalePaymentsController extends Controller
{

    public function index($sale_id, SalePaymentsDataTable $dataTable) {
        abort_if(Gate::denies('access_sale_payments'), 403);

        $sale = Sale::findOrFail($sale_id);

        return $dataTable->render('sale::payments.index', compact('sale'));
    }


    public function create($sale_id) {
        abort_if(Gate::denies('access_sale_payments'), 403);

        $sale = Sale::findOrFail($sale_id);

        return view('sale::payments.create', compact('sale'));
    }

    protected function retryMidtransPayment(Sale $sale) {
        // Konfigurasi Midtrans
        Midtrans\Config::$serverKey = config('midtrans.server_key');
        Midtrans\Config::$isProduction = config('midtrans.is_production');

        // Buat Order ID baru yang unik untuk percobaan pembayaran ini
        $order_id = 'SALE-' . $sale->id . '-' . time();

        $params = [
            'transaction_details' => [
                'order_id' => $order_id,
                'gross_amount' => $sale->due_amount / 100, // Gunakan sisa tagihan
            ],
            'customer_details' => [
                'first_name' => $sale->customer_name,
            ],
        ];

        // Update order_id di database untuk pelacakan webhook
        $sale->update(['midtrans_order_id' => $order_id]);

        try {
            $snapToken = Midtrans\Snap::getSnapToken($params);

            // Kembalikan view khusus yang akan memicu popup Midtrans
            return view('sale::payments.midtrans-redirect', compact('snapToken'));

        } catch (\Exception $e) {
            toast('Gagal terhubung ke Midtrans: ' . $e->getMessage(), 'error');
            return redirect()->route('sales.show', $sale->id);
        }
    }

    public function store(Request $request) {
        if ($request->payment_method == 'Midtrans') {
            // Ambil data penjualan terkait
            $sale = Sale::findOrFail($request->sale_id);

            // Panggil method untuk memproses Midtrans
            // Ini adalah method yang sama dengan yang kita buat sebelumnya
            return $this->retryMidtransPayment($sale);
        }
        abort_if(Gate::denies('access_sale_payments'), 403);

        $request->validate([
            'date' => 'required|date',
            'reference' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'note' => 'nullable|string|max:1000',
            'sale_id' => 'required',
            'payment_method' => 'required|string|max:255'
        ]);

        DB::transaction(function () use ($request) {
            SalePayment::create([
                'date' => $request->date,
                'reference' => $request->reference,
                'amount' => $request->amount,
                'note' => $request->note,
                'sale_id' => $request->sale_id,
                'payment_method' => $request->payment_method
            ]);

            $sale = Sale::findOrFail($request->sale_id);

            $due_amount = $sale->due_amount - $request->amount;

            if ($due_amount == $sale->total_amount) {
                $payment_status = 'Unpaid';
            } elseif ($due_amount > 0) {
                $payment_status = 'Partial';
            } else {
                $payment_status = 'Paid';
            }

            $sale->update([
                'paid_amount' => ($sale->paid_amount + $request->amount) * 100,
                'due_amount' => $due_amount * 100,
                'payment_status' => $payment_status
            ]);
        });

        toast('Sale Payment Created!', 'success');

        return redirect()->route('sales.index');
    }


    public function edit($sale_id, SalePayment $salePayment) {
        abort_if(Gate::denies('access_sale_payments'), 403);

        $sale = Sale::findOrFail($sale_id);

        return view('sale::payments.edit', compact('salePayment', 'sale'));
    }


    public function update(Request $request, SalePayment $salePayment) {
        abort_if(Gate::denies('access_sale_payments'), 403);

        $request->validate([
            'date' => 'required|date',
            'reference' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'note' => 'nullable|string|max:1000',
            'sale_id' => 'required',
            'payment_method' => 'required|string|max:255'
        ]);

        DB::transaction(function () use ($request, $salePayment) {
            $sale = $salePayment->sale;

            $due_amount = ($sale->due_amount + $salePayment->amount) - $request->amount;

            if ($due_amount == $sale->total_amount) {
                $payment_status = 'Unpaid';
            } elseif ($due_amount > 0) {
                $payment_status = 'Partial';
            } else {
                $payment_status = 'Paid';
            }

            $sale->update([
                'paid_amount' => (($sale->paid_amount - $salePayment->amount) + $request->amount) * 100,
                'due_amount' => $due_amount * 100,
                'payment_status' => $payment_status
            ]);

            $salePayment->update([
                'date' => $request->date,
                'reference' => $request->reference,
                'amount' => $request->amount,
                'note' => $request->note,
                'sale_id' => $request->sale_id,
                'payment_method' => $request->payment_method
            ]);
        });

        toast('Sale Payment Updated!', 'info');

        return redirect()->route('sales.index');
    }


    public function destroy(SalePayment $salePayment) {
        abort_if(Gate::denies('access_sale_payments'), 403);

        $salePayment->delete();

        toast('Sale Payment Deleted!', 'warning');

        return redirect()->route('sales.index');
    }
}
