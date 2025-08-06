<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_type')->nullable()->after('payment_method');
            $table->string('payment_details')->nullable();
        });
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->string('payment_type')->nullable()->after('payment_method');
            $table->string('payment_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('payment_type');
            $table->dropColumn('payment_details');
        });
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropColumn('payment_type');
            $table->dropColumn('payment_details');
        });
    }
};
