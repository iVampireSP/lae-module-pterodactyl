<?php

namespace App\Models;

use App\Models\Client;
use App\Models\WingsNestEgg;
use App\Models\WorkOrder\WorkOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Host extends Model
{
    use HasFactory;

    protected $table = 'hosts';

    protected $fillable = [
        'id',
        'identifier',
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

    public function getRouteKeyName()
    {
        return 'host_id';
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

            $model->price = (new \App\Http\Controllers\Remote\HostController)->calcPrice($model->toArray());

            $http->patch('/hosts/' . $model->host_id, [
                'price' => $model->price
            ]);
        });

        // created
        static::created(function ($model) {
            $model->load('location');
            $model->location->increment('servers');
        });

        // update
        static::updating(function ($model) {
            $http = Http::remote('remote')->asForm();


            if ($model->status == 'suspended') {
                $model->suspended_at = now();
            } else if ($model->status == 'running') {
                $model->suspended_at = null;
            }

            $model->price = (new \App\Http\Controllers\Remote\HostController)->calcPrice($model->toArray());

            $http->patch('/hosts/' . $model->host_id, [
                'price' => $model->price,
                'suspended_at' => $model->suspended_at,
                'status' => $model->status,
            ]);
        });


        // when deleted
        static::deleting(function ($model) {
            Location::find($model->location_id)->decrement('servers');
        });
    }
}
