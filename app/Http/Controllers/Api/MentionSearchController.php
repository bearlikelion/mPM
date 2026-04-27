<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Support\SiteTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MentionSearchController extends Controller
{
    public function __invoke(Request $request, SiteTenant $tenant): JsonResponse
    {
        $user = Auth::user();
        $orgId = (int) $request->query('org');
        $query = trim((string) $request->query('q', ''));

        $organization = $orgId
            ? $user->organizations()->whereKey($orgId)->first()
            : $tenant->currentOrganization($user);

        if (! $organization instanceof Organization) {
            return response()->json([]);
        }

        $results = User::query()
            ->whereHas('organizations', fn ($builder) => $builder->whereKey($organization->id))
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($inner) use ($query) {
                    $inner->where('name', 'like', '%'.$query.'%')
                        ->orWhere('email', 'like', '%'.$query.'%');
                });
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'email'])
            ->map(fn (User $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'email' => $candidate->email,
                'avatar' => $candidate->avatarUrl(),
            ])
            ->values();

        return response()->json($results);
    }
}
