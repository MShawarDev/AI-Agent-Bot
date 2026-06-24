<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReport extends Model
{
    protected $fillable = [
        'label',
        'report_date',
        'source_file',
        'content',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];
}
