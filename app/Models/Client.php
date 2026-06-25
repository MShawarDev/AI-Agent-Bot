<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'bot_name',
        'system_prompt',
        'currency',
        'brand_color',
        'logo_path',
        'starter_prompts',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'starter_prompts' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function salesReports(): HasMany
    {
        return $this->hasMany(SalesReport::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
