<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    private const PROVIDERS = ['google', 'discord', 'steam'];

    public function redirect(string $provider)
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        $oauthUser = Socialite::driver($provider)->user();

        $email = $oauthUser->getEmail() ?? $provider.'-'.$oauthUser->getId().'@users.noreply.mpm';
        $name = $oauthUser->getName() ?? $oauthUser->getNickname() ?? 'User '.Str::random(4);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt(Str::random(40)),
                'email_verified_at' => now(),
            ]
        );

        Auth::login($user, remember: true);

        return redirect()->intended('/app');
    }
}
