<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'context', 'name', 'company', 'phone', 'contact', 'product_type',
        'message', 'locale', 'status', 'delivery_status', 'source_url',
        'ip_address', 'read_at', 'telegram_sent_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'telegram_sent_at' => 'datetime',
    ];
}
