<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // <-- Tambahkan ini untuk logging
use Midtrans;
use Modules\Sale\Entities\Sale;
use Modules\Sale\Entities\SalePayment;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Tambahkan logging untuk melihat setiap notifikasi yang masuk
        Log::info('Midtrans Webhook received:', $request->all());

        // Konfigurasi Midtrans
        Midtrans\Config::$serverKey = config('midtrans.server_key');
        Midtrans\Config::$isProduction = config('midtrans.is_production');

        try {
            $notification = new Midtrans\Notification();
        } catch (\Exception $e) {
            Log::error('Midtrans Webhook - Invalid Notification: ' . $e->getMessage());
            return response()->json(['message' => 'Invalid notification.'], 400);
        }

        $orderId = $notification->order_id;
        $transactionStatus = $notification->transaction_status;
        $rawPaymentType = $notification->payment_type;

        $sale = Sale::where('midtrans_order_id', $orderId)->first();

        if (!$sale) {
            Log::warning('Midtrans Webhook - Sale not found for Order ID: ' . $orderId);
            return response()->json(['message' => 'Sale not found.'], 404);
        }

        // Hanya proses jika statusnya Paid atau Partial (untuk jaga-jaga)
        if ($sale->payment_status == 'Paid' || $sale->payment_status == 'Partial') {
            Log::info('Midtrans Webhook - Sale already paid for Order ID: ' . $orderId);
            return response()->json(['message' => 'Sale already processed.']);
        }

        // Update status pembayaran berdasarkan notifikasi
        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {

            $generalMethod = 'Other'; // Kategori umum
            $specificType = ucwords(str_replace('_', ' ', $rawPaymentType)); // Metode spesifik (misal: BCA VA)
            $paymentDetails = null; // Detail unik (misal: no VA)

            $payload = $request->all();

            // Logika untuk mengelompokkan dan mengisi detail
            // 1. Cek apakah ini Bank Transfer / Virtual Account
            if (str_contains($rawPaymentType, 'bank_transfer') || str_contains($rawPaymentType, 'va')) {
                $generalMethod = 'Bank Transfer';
                if (isset($payload['va_numbers'][0]['va_number'])) {
                    $paymentDetails = $payload['va_numbers'][0]['va_number'];
                    // Ambil nama bank dari va_numbers jika ada
                    $specificType = strtoupper($payload['va_numbers'][0]['bank']) . ' VA';
                }
            }
            // 2. Cek apakah ini E-Wallet
            elseif (in_array($rawPaymentType, ['gopay', 'qris', 'shopeepay'])) {
                $generalMethod = 'E-Wallet';
                // Detail spesifik sudah benar dari $specificType (Gopay, Qris, dll)
            }
            // 3. Cek apakah ini Kartu Kredit
            elseif ($rawPaymentType == 'credit_card') {
                $generalMethod = 'Kartu Kredit';
                if (isset($payload['masked_card']) && isset($payload['bank'])) {
                    $paymentDetails = strtoupper($payload['bank']) . ' - ' . $payload['masked_card'];
                }
            }

            // Update tabel 'sales'
            $sale->update([
                'status'          => 'Completed',
                'payment_status'  => 'Paid',
                'paid_amount'     => $sale->total_amount* 100, // Konversi ke sen
                'due_amount'      => 0,
                'payment_method'  => $generalMethod,
                'payment_type'    => $specificType,
                'payment_details' => $paymentDetails
            ]);

            // Buat record 'sale_payments'
            SalePayment::create([
                'date'            => now(),
                'reference'       => 'PAY/' . $sale->reference,
                'amount'          => $sale->total_amount,
                'sale_id'         => $sale->id,
                'payment_method'  => $generalMethod,
                'payment_type'    => $specificType,
                'payment_details' => $paymentDetails
            ]);

            Log::info('Midtrans Webhook - SUCCESS: Sale ' . $sale->id . ' status updated to Paid with method ' . $formattedPaymentMethod);

        } elseif ($transactionStatus == 'cancel' || $transactionStatus == 'expire' || $transactionStatus == 'deny') {
            $sale->update(['status' => 'Cancelled']);
            Log::warning('Midtrans Webhook - Sale ' . $sale->id . ' status updated to Cancelled.');
        }

        return response()->json(['message' => 'Notification successfully processed.']);
    }
}
