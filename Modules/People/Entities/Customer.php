<?php

namespace Modules\People\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Sale\Entities\Sale;

class Customer extends Model
{

    use HasFactory;

    protected $guarded = [];

    public function sales() {
        // Satu customer bisa memiliki banyak sales
        return $this->hasMany(Sale::class, 'customer_id', 'id');
    }

    protected static function newFactory() {
        return \Modules\People\Database\factories\CustomerFactory::new();
    }

}
