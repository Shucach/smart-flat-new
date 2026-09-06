<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class ApiLoginController extends Controller
{
    /**
     * @throws AuthenticationException
     */
    public function __invoke(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (auth()->attempt($request->only('email', 'password'), true)) {
            $user = User::query()->where('id', auth()->user()->id)->first();

            return [
                'token' => $user->createToken('API Token')->plainTextToken,
            ];
        }
        throw new AuthenticationException;
    }
}
