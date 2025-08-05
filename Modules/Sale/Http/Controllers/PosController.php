<?php

namespace Modules\Sale\Http\Controllers;

use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\People\Entities\Customer;
use Modules\Product\Entities\Category;
use Modules\Product\Entities\Product;
use Modules\Sale\Entities\Sale;
use Modules\Sale\Entities\SaleDetails;
use Modules\Sale\Entities\SalePayment;
use Modules\Sale\Http\Requests\StorePosSaleRequest;
use Modules\Sale\Http\Requests\StoreSaleRequest;
use Modules\Sale\Http\Requests\UpdateSaleRequest;
use Modules\Sale\Http\Requests\UpdateSalePaymentRequest;
use Midtrans;

class PosController extends Controller
{

    public function index() {
        Cart::instance('sale')->destroy();

        $customers = Customer::all();
        $product_categories = Category::all();

        return view('sale::pos.index', compact('product_categories', 'customers'));
    }


    public function store(StorePosSaleRequest $request) {
        DB::transaction(function () use ($request) {
            // Langkah 1: Selalu buat data Penjualan (Sale) terlebih dahulu
            $sale = Sale::create([
                'date' => now(), // Simpan tanggal dan waktu lengkap
                'reference' => 'PSL',
                'customer_id' => $request->customer_id,
                'user_id' => auth()->user()->id,
                'customer_name' => Customer::findOrFail($request->customer_id)->customer_name,
                'tax_percentage' => $request->tax_percentage,
                'discount_percentage' => $request->discount_percentage,
                'shipping_amount' => $request->shipping_amount * 100,
                'paid_amount' => 0, // Awalnya tidak ada pembayaran
                'total_amount' => $request->total_amount * 100,
                'due_amount' => $request->total_amount * 100,
                'status' => 'Pending',
                'payment_status' => 'Unpaid',
                'payment_method' => $request->payment_method,
                'note' => $request->note,
                'tax_amount' => Cart::instance('sale')->tax() * 100,
                'discount_amount' => Cart::instance('sale')->discount() * 100,
            ]);

            // Simpan detail produk
            foreach (Cart::instance('sale')->content() as $cart_item) {
                SaleDetails::create([
                    'sale_id' => $sale->id,
                    'product_id' => $cart_item->id,
                    'product_name' => $cart_item->name,
                    'product_code' => $cart_item->options->code,
                    'quantity' => $cart_item->qty,
                    'price' => $cart_item->price * 100,
                    'unit_price' => $cart_item->options->unit_price * 100,
                    'sub_total' => $cart_item->options->sub_total * 100,
                    'product_discount_amount' => $cart_item->options->product_discount * 100,
                    'product_discount_type' => $cart_item->options->product_discount_type,
                    'product_tax_amount' => $cart_item->options->product_tax * 100,
                ]);
                Product::findOrFail($cart_item->id)->decrement('product_quantity', $cart_item->qty);
            }

            // Langkah 2: Proses pembayaran berdasarkan metode yang dipilih
            if ($request->payment_method == 'QRIS' || $request->payment_method == 'Midtrans') {
                // Proses Midtrans
                Midtrans\Config::$serverKey = config('midtrans.server_key');
                Midtrans\Config::$isProduction = config('midtrans.is_production');

                $order_id = 'SALE-' . $sale->id . '-' . time();
                $params = [
                    'transaction_details' => ['order_id' => $order_id, 'gross_amount' => $total],
                    'customer_details' => ['first_name' => $sale->customer_name],
                ];

                $sale->update(['midtrans_order_id' => $order_id]);
                $snapToken = Midtrans\Snap::getSnapToken($params);

                // LANGSUNG RETURN DARI SINI
                $respons = response()->json(['snap_token' => $snapToken]);
            }
            else{
                SalePayment::create([
                    'date' => now(),
                    'reference' => 'PAY/' . $sale->reference,
                    'amount' => $request->paid_amount * 100,
                    'sale_id' => $sale->id,
                    'payment_method' => $request->payment_method
                ]);

                // Update status penjualan untuk pembayaran manual
                $due_amount = $request->total_amount - $request->paid_amount;
                $payment_status = $due_amount <= 0 ? 'Paid' : 'Partial';

                $sale->update([
                    'paid_amount' => $request->paid_amount * 100,
                    'due_amount' => $due_amount * 100,
                    'payment_status' => $payment_status,
                    'status' => 'Completed'
                ]);

                // Proses Pembayaran Manual berhasil
                Cart::instance('sale')->destroy();
                toast('POS Sale Created!', 'success');

                // Kirim URL redirect untuk cetak struk
                $respons = response()->json(['redirect_url' => route('sales.pos.pdf', $sale->id)]);
            }
        });
        return $respons;
    }

    /**
     * Method untuk menangani pembayaran manual (logika asli).
     */
    // protected function processManualPayment(StorePosSaleRequest $request) {
    //     DB::transaction(function () use ($request) {
    //         $due_amount = $request->total_amount - $request->paid_amount;

    //         if ($due_amount == $request->total_amount) {
    //             $payment_status = 'Unpaid';
    //         } elseif ($due_amount > 0) {
    //             $payment_status = 'Partial';
    //         } else {
    //             $payment_status = 'Paid';
    //         }

    //         $sale = Sale::create([
    //             'date' => now()->format('Y-m-d'),
    //             'reference' => 'PSL',
    //             'customer_id' => $request->customer_id,
    //             'user_id' => auth()->user()->id,
    //             'customer_name' => Customer::findOrFail($request->customer_id)->customer_name,
    //             'tax_percentage' => $request->tax_percentage,
    //             'discount_percentage' => $request->discount_percentage,
    //             'shipping_amount' => $request->shipping_amount * 100,
    //             'paid_amount' => $request->paid_amount * 100,
    //             'total_amount' => $request->total_amount * 100,
    //             'due_amount' => $due_amount * 100,
    //             'status' => 'Completed',
    //             'payment_status' => $payment_status,
    //             'payment_method' => $request->payment_method,
    //             'note' => $request->note,
    //             'tax_amount' => Cart::instance('sale')->tax() * 100,
    //             'discount_amount' => Cart::instance('sale')->discount() * 100,
    //         ]);

    //         foreach (Cart::instance('sale')->content() as $cart_item) {
    //             SaleDetails::create([
    //                 'sale_id' => $sale->id,
    //                 'product_id' => $cart_item->id,
    //                 'product_name' => $cart_item->name,
    //                 'product_code' => $cart_item->options->code,
    //                 'quantity' => $cart_item->qty,
    //                 'price' => $cart_item->price * 100,
    //                 'unit_price' => $cart_item->options->unit_price * 100,
    //                 'sub_total' => $cart_item->options->sub_total * 100,
    //                 'product_discount_amount' => $cart_item->options->product_discount * 100,
    //                 'product_discount_type' => $cart_item->options->product_discount_type,
    //                 'product_tax_amount' => $cart_item->options->product_tax * 100,
    //             ]);

    //             $product = Product::findOrFail($cart_item->id);
    //             $product->update([
    //                 'product_quantity' => $product->product_quantity - $cart_item->qty
    //             ]);
    //         }

    //         Cart::instance('sale')->destroy();

    //         if ($sale->paid_amount > 0) {
    //             SalePayment::create([
    //                 'date' => now()->format('Y-m-d'),
    //                 'reference' => 'INV/'.$sale->reference,
    //                 'amount' => $sale->paid_amount,
    //                 'sale_id' => $sale->id,
    //                 'payment_method' => $request->payment_method
    //             ]);
    //         }
    //     });

    //     toast('POS Sale Created!', 'success');

    //     return redirect()->route('sales.index');
    // }

    public function print($id) {
        $sale = Sale::findOrFail($id);

        return view('sale::print-pos', compact('sale'));
    }

}
