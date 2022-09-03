<?php

namespace App\Models;

use App\Models\Client;
use App\Models\WingsNestEgg;
use App\Models\WorkOrder\WorkOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Host extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hosts';

    protected $fillable = [
        'id',
        'name',
        'user_id',
        'host_id',
        'price',
        'status',
        'cpu_limit',
        'memory',
        'disk',
        'databases',
        'backups',
        'allocations',
        'node_id',
        'server_id',
        'egg_id',
        'location_id',
        'ip',
        'port',
    ];

    protected $casts = [
        'configuration' => 'array',
        'suspended_at' => 'datetime',
    ];

    // scope thisUser
    public function scopeThisUser($query)
    {
        $user_id = request('user_id');
        return $query->where('user_id', $user_id);
    }


    // user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function egg()
    {
        return $this->belongsTo(WingsNestEgg::class, 'egg_id', 'egg_id');
    }

    // workOrders
    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    // scope
    public function scopeRunning($query)
    {
        return $query->where('status', 'running')->where('price', '!=', 0);
    }

    public function calcPrice()
    {
        $this->load('location');
        $price = 0;
        $price += $this->location->price;
        $price += ($this->cpu_limit / 100) * 0.25;
        $price += ($this->memory) * 0.3;
        $price += ($this->disk / 100) * 0.25;
        $price += $this->backups * 0.5;
        $price += $this->allocations * 1;
        $price += $this->databases * 1;

        return $price;
    }

    // on createing
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $http = Http::remote('remote')->asForm();
            // if id exists
            if ($model->where('id', $model->id)->exists()) {
                return false;
            }

            $model->price = $model->calcPrice();

            $http->patch('/hosts/' . $model->host_id, [
                'price' => $model->price
            ]);
        });

        // update
        static::updating(function ($model) {
            $http = Http::remote('remote')->asForm();


            if ($model->status == 'suspended') {
                $model->suspended_at = now();
            } else if ($model->status == 'running') {
                $model->suspended_at = null;
            }

            $model->price = $model->calcPrice();

            $http->patch('/hosts/' . $model->host_id, [
                'price' => $model->price
            ]);
        });
    }
}
