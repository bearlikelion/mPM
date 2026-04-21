<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Support\SiteTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchOrganizationController extends Controller
{
    public function __invoke(Request $request, Organization $organization, SiteTenant $siteTenant): RedirectResponse
    {
        $this->authorize('view', $organization);

        $siteTenant->switchOrganization($request->user(), $organization);

        return redirect()->to($this->previousUrlWithoutFilters());
    }

    protected function previousUrlWithoutFilters(): string
    {
        $previousUrl = url()->previous();
        $parsedUrl = parse_url($previousUrl);
        $query = [];

        parse_str($parsedUrl['query'] ?? '', $query);

        unset(
            $query['project'],
            $query['sprint'],
            $query['assignee'],
            $query['epic'],
            $query['tag'],
            $query['highlight'],
            $query['task'],
            $query['status']
        );

        $path = $parsedUrl['path'] ?? route('dashboard', absolute: false);
        $queryString = http_build_query($query);

        return $queryString === '' ? $path : "{$path}?{$queryString}";
    }
}
