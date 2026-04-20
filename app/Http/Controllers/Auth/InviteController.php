<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OrganizationInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class InviteController extends Controller
{
    public function show(string $token)
    {
        $invite = OrganizationInvite::where('token', $token)->firstOrFail();
        abort_if($invite->isExpired() || $invite->accepted_at, 410, 'Invite expired or already used');

        return view('auth.invite', ['invite' => $invite]);
    }

    public function accept(Request $request, string $token)
    {
        $invite = OrganizationInvite::where('token', $token)->firstOrFail();
        abort_if($invite->isExpired() || $invite->accepted_at, 410);

        $data = $request->validate([
            'name' => ['required_without:_existing', 'string', 'max:255'],
            'password' => ['required_without:_existing', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($invite, $data) {
            $user = Auth::user() ?? User::firstOrCreate(
                ['email' => $invite->email],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => now(),
                ]
            );

            $invite->organization->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => $invite->role,
                    'joined_at' => now(),
                ],
            ]);

            if (! $user->default_organization_id) {
                $user->update(['default_organization_id' => $invite->organization_id]);
            }

            $invite->update(['accepted_at' => now()]);

            if (! Auth::check()) {
                Auth::login($user, remember: true);
            }
        });

        return redirect('/app');
    }
}
