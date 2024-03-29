<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WingsNestEgg extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'nest_id', 'author', 'description', 'docker_image', 'docker_images', 'startup', 'egg_id', 'environment'
    ];

    protected $casts = [
        'docker_images' => 'json',
        'environment' => 'json',
    ];
}
