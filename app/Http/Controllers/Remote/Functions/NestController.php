<?php

namespace App\Http\Controllers\Remote\Functions;

use App\Models\WingsNest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NestController extends Controller
{
    public function nests() {
        $nest = new WingsNest();
        $nests = $nest->where('display', 1)->where('found', 1)->with('eggs')->get();

        return $this->success($nests);
    }
}
