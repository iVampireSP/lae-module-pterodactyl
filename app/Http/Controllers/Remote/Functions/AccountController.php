<?php

namespace App\Http\Controllers\Remote\Functions;

use App\Models\User;
use Illuminate\Http\Request;
use App\Exceptions\PanelException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PanelController;

class AccountController extends Controller
{
    //
    // public function index(Request $request) {
    //     return $this->success([
    //         'email' => User::find($request->user_id)->email,
    //     ]);
    // }

    public function update(Request $request) {
        $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $panel = new PanelController();

        try {
            $user = User::find($request->user_id);

            if (is_null($user)) {
                return $this->error('您还没有创建过 游戏容器，请先创建一个。');
            }

            $user = $panel->getUserByEmail($user->email);
            if (count($user['data']) == 0) {
                return $this->error('找不到用户');
            }
        } catch (PanelException) {
            return $this->error('找不到用户');
        }

        // $user_id = $user['data'][0]['attributes']['id'];

        $panel->updateUser($user['data'][0]['attributes']['id'], [
            'password' => $request->password,
            'email' => $user['data'][0]['attributes']['email'],
            'username' => $user['data'][0]['attributes']['username'],
            'first_name' => $user['data'][0]['attributes']['first_name'],
            'last_name' => $user['data'][0]['attributes']['last_name']
        ]);

        return $this->success();
    }
}
