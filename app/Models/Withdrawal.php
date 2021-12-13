<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
      'user_id', 'transaction_id', 'destination', 'amount'
    ];
}
