<?php

namespace Modules\Sale\Http\Controllers;

use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        return DB::transaction(function () use ($request) {
            // -- AKHIR PERBAIKAN --
            // Langkah 1: Buat SATU data Penjualan (Sale) dengan status awal Unpaid
            $sale = Sale::create([
                'date'              => now(), // Menyimpan tanggal dan waktu
                'reference'         => 'PSL', // Sebaiknya digenerate unik
                'customer_id'       => $request->customer_id,
                'customer_name'     => Customer::find($request->customer_id)->customer_name,
                'total_amount'      => $request->total_amount * 100,
                'paid_amount'       => 0, // Set paid_amount ke 0 dulu
                'due_amount'        => $request->total_amount * 100,
                'status'            => 'Pending', // Status ordernya
                'payment_status'    => 'Unpaid', // Status pembayarannya
                'payment_method'    => $request->payment_method,
                'user_id'           => auth()->user()->id,
                'tax_percentage'      => $request->tax_percentage,
                'tax_amount'          => Cart::instance('sale')->tax(2,'.','') * 100,
                'discount_percentage' => $request->discount_percentage,
                'discount_amount'     => Cart::instance('sale')->discount(2,'.','') * 100,
                'shipping_amount'     => $request->shipping_amount * 100,
                'note'                => $request->note
            ]);

            // Simpan semua detail produk dari keranjang
            foreach (Cart::instance('sale')->content() as $cart_item) {
                SaleDetails::create([
                    'sale_id'                 => $sale->id,
                    'product_id'              => $cart_item->id,
                    'product_name'            => $cart_item->name,
                    'product_code'            => $cart_item->options->code,
                    'quantity'                => $cart_item->qty,
                    'price'                   => $cart_item->price * 100,
                    'unit_price'              => $cart_item->options->unit_price * 100,
                    'sub_total'               => $cart_item->options->sub_total * 100,
                    'product_discount_amount' => $cart_item->options->product_discount * 100,
                    'product_discount_type'   => $cart_item->options->product_discount_type,
                    'product_tax_amount'      => $cart_item->options->product_tax * 100,
                ]);
                // Kurangi stok produk
                Product::findOrFail($cart_item->id)->decrement('product_quantity', $cart_item->qty);
            }

            // Hancurkan keranjang belanja SEGERA setelah detail disimpan
            // Cart::instance('sale')->destroy();

            // Langkah 2: Proses pembayaran berdasarkan metode yang dipilih
            if ($request->payment_method == 'QRIS' || $request->payment_method == 'Midtrans') {
                // Proses Midtrans
                Midtrans\Config::$serverKey = config('midtrans.server_key');
                Midtrans\Config::$isProduction = config('midtrans.is_production');

                 $item_details = [];

                // 1. Tambahkan setiap produk dari keranjang
                foreach (Cart::instance('sale')->content() as $cart_item) {
                    $item_details[] = [
                        'id'       => $cart_item->id,
                        'price'    => round($cart_item->price), // Harga satuan harus bulat
                        'quantity' => $cart_item->qty,
                        'name'     => substr($cart_item->name, 0, 50) // Nama item maks 50 karakter
                    ];
                }

                // 2. Tambahkan Pajak (jika ada)
                $tax = Cart::instance('sale')->tax(2, '.', ''); // Konversi ke sen
                if ($tax > 0) {
                    $item_details[] = [
                        'id'       => 'TAX',
                        'price'    => round($tax),
                        'quantity' => 1,
                        'name'     => 'Pajak'
                    ];
                }
                $shipping_amount = $request->shipping_amount;
                // 3. Tambahkan Ongkos Kirim (jika ada)
                if ($shipping_amount > 0) {
                    $item_details[] = [
                        'id'       => 'SHIPPING',
                        'price'    => round($shipping_amount),
                        'quantity' => 1,
                        'name'     => 'Ongkos Kirim'
                    ];
                }

                // 4. Tambahkan Diskon (sebagai nilai negatif)
                $discount_amount = Cart::instance('sale')->discount(2, '.', ''); // Konversi ke sen
                if ($discount_amount > 0) {
                    $item_details[] = [
                        'id'       => 'DISCOUNT',
                        'price'    => -round($discount_amount), // Diskon harus bernilai negatif
                        'quantity' => 1,
                        'name'     => 'Diskon'
                    ];
                }

                $order_id = 'SALE-' . $sale->id . '-' . time();
                $params = [
                    'transaction_details' => [
                        'order_id' => $order_id,
                        'gross_amount' => round($request->total_amount), // Total harus dalam sen
                    ],
                    'item_details' => $item_details, // <-- Sertakan rincian item di sini
                    'customer_details' => ['first_name' => $sale->customer_name],
                ];

                $sale->update(['midtrans_order_id' => $order_id]);
                $snapToken = Midtrans\Snap::getSnapToken($params);

                // Langsung kembalikan respons JSON dengan Snap Token
                return response()->json(['snap_token' => $snapToken]);
            } else {
                // Proses Pembayaran Manual (Cash, dll)
                // Buat record pembayaran baru
                SalePayment::create([
                    'date'           => now(),
                    'reference'      => 'PAY/' . $sale->reference,
                    'amount'         => $request->paid_amount * 100,
                    'sale_id'        => $sale->id,
                    'payment_method' => $request->payment_method
                ]);

                // Update status di record penjualan utama
                $due_amount = $request->total_amount - $request->paid_amount;
                $payment_status = $due_amount <= 0 ? 'Paid' : 'Partial';

                $sale->update([
                    'paid_amount'    => $request->paid_amount * 100,
                    'due_amount'     => $due_amount * 100,
                    'payment_status' => $payment_status,
                    'status'         => 'Completed'
                ]);

                toast('POS Sale Created!', 'success');

                // Langsung kembalikan respons JSON untuk redirect cetak struk
                return response()->json([
                    'print_url' => route('sales.pos.receipt', $sale->id),
                    'redirect_url' => route('sales.index')
                ]);
            }
        });
    }

    // return response()->json([
    //                 'print_url' => route('sales.pos.pdf', $sale->id),
    //                 'redirect_url' => route('sales.index')
    //             ]);
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

    public function printReceipt($id) {
        $sale = Sale::findOrFail($id);

        // Panggil view baru yang akan kita buat
        return view('sale::receipt-pos', compact('sale'));
    }

}
