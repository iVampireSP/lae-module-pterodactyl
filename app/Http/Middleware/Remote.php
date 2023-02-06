<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class Remote
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        // add json header
        $request->headers->set('Accept', 'application/json');
        if (!$request->hasHeader('X-Module-Api-Token')) {
            return $this->unauthorized();
        }

        $token = $request->header('X-Module-Api-Token');
        if ($token !== config('remote.api_token')) {
            return $this->unauthorized();
        }

        $user_id = $request->header('X-User-Id');
        $user = null;

        if ($user_id) {
            $user = User::where('id', $user_id)->first();
            // if user null
            if (!$user) {
                $http = Http::remote('remote')->asForm();
                $user = $http->get('/users/' . $user_id)->json();

                $user = User::create([
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                ]);
            }
            
            Auth::login($user);
        }
        
        // created_at and updated_at 序列化
        $data = [
            'created_at' => Carbon::parse($request->created_at ?? now())->toDateTimeString(),
            'updated_at' => Carbon::parse($request->updated_at ?? now())->toDateTimeString(),
        ];
        
        if ($user_id) {
            $data['user_id'] = $user_id;
        }
        
        if ($user) {
            $data['user'] = $user;
        }
        
        
        $request->merge($data);

        return $next($request);
    }

    public function unauthorized()
    {
        return response()->json([
            'message' => 'Unauthorized.'
        ], 401);
    }
}
