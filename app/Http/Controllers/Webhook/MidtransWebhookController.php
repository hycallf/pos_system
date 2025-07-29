<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Midtrans;
use Modules\Sale\Entities\Sale;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Konfigurasi Midtrans
        Midtrans\Config::$serverKey = config('midtrans.server_key');
        Midtrans\Config::$isProduction = config('midtrans.is_production');

        // Buat instance notifikasi
        try {
            $notification = new Midtrans\Notification();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid notification.'], 400);
        }

        // Ambil data penting dari notifikasi
        $orderId = $notification->order_id;
        $statusCode = $notification->status_code;
        $grossAmount = $notification->gross_amount;
        $transactionStatus = $notification->transaction_status;
        $paymentType = $notification->payment_type;

        // Cari transaksi di database Anda berdasarkan `midtrans_order_id`
        $sale = Sale::where('midtrans_order_id', $orderId)->first();

        if (!$sale) {
            return response()->json(['message' => 'Sale not found.'], 404);
        }

        // Verifikasi signature key untuk keamanan
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . config('midtrans.server_key'));
        if ($signature !== $notification->signature_key) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        // Update status pembayaran berdasarkan notifikasi
        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            // Pembayaran berhasil
            $formattedPaymentMethod = ucwords(str_replace('_', ' ', $paymentType));
            $sale->update([
                'payment_status' => 'Paid',
                'paid_amount' => $sale->total_amount, // Anggap lunas
                'due_amount' => 0,
                'payment_method' => $formattedPaymentMethod,
            ]);
        } elseif ($transactionStatus == 'pending') {
            // Pembayaran tertunda
            // Anda bisa biarkan statusnya 'Unpaid' atau buat status baru 'Pending Payment'
        } elseif ($transactionStatus == 'deny' || $transactionStatus == 'expire' || $transactionStatus == 'cancel') {
            // Pembayaran gagal atau dibatalkan
            $sale->update(['status' => 'Cancelled']);
        }

        return response()->json(['message' => 'Notification successfully processed.']);
    }
}
