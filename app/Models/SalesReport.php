<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReport extends Model
{
    protected $fillable = [
        'client_id',
        'label',
        'report_date',
        'source_file',
        'content',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
