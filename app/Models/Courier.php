<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'email', 'address', 'level', 'registered_at'];

    protected function casts(): array
    {
        return ['level' => 'integer', 'registered_at' => 'datetime'];
    }
}
