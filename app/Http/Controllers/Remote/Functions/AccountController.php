<?php

namespace App\Http\Controllers\Remote\Functions;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    //
    public function index(Request $request) {
        return $this->success([
            'email' => User::find($request->user_id)->email,
        ]);
    }

    public function update(Request $request) {

    }
}
