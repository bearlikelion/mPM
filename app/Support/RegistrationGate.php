<?php

namespace App\Support;

use App\Models\OrganizationInvite;
use App\Models\SiteInvite;
use App\Models\SiteSetting;

class RegistrationGate
{
    public static function allowsRegistration(?string $inviteToken = null): bool
    {
        $settings = SiteSetting::current();

        if ($settings->registration_enabled) {
            return true;
        }

        if (SiteInvite::findValidByToken($inviteToken)) {
            return true;
        }

        if ($settings->org_invites_bypass_registration && static::validOrgInvite($inviteToken)) {
            return true;
        }

        return false;
    }

    public static function allowsOrgCreation(): bool
    {
        return SiteSetting::current()->org_creation_enabled;
    }

    protected static function validOrgInvite(?string $token): bool
    {
        if (! $token) {
            return false;
        }

        $invite = OrganizationInvite::where('token', $token)->first();

        return $invite && ! $invite->isExpired() && ! $invite->accepted_at;
    }
}
