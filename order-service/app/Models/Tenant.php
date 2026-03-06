<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = ['name', 'slug', 'domain', 'is_active', 'settings', 'webhook_url', 'plan'];
    protected $casts = ['is_active' => 'boolean', 'settings' => 'array'];
}
