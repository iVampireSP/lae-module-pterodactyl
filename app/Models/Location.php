<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'location_id', 'price',
        'cpu_price', 'memory_price', 'disk_price',
        'database_price', 'backup_price', 'allocation_price',
    ];

    public function hosts() {
        return $this->hasMany(Host::class);
    }
}
