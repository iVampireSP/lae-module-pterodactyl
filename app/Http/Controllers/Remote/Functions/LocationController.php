<?php

namespace App\Http\Controllers\Remote\Functions;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    //

    public function __invoke() {
        return $this->success(Location::all());
    }
}
