<?php

use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\SiteInvite;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\RegistrationGate;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $timezone = 'UTC';
    public string $organization_name = '';
    public string $organization_timezone = 'UTC';
    public string $password = '';
    public string $password_confirmation = '';

    #[Url(as: 'invite')]
    public ?string $inviteToken = null;

    public function mount(): void
    {
        if (! RegistrationGate::allowsRegistration($this->inviteToken)) {
            abort(403, 'Registration is currently disabled.');
        }

        $orgInvite = $this->matchingOrgInvite();

        if ($orgInvite) {
            $this->redirectRoute('invite.show', ['token' => $this->inviteToken], navigate: true);
        }
    }

    public function register(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'timezone' => ['required', 'timezone:all'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ];

        $settings = SiteSetting::current();

        if ($settings->org_creation_enabled) {
            $rules['organization_name'] = ['required', 'string', 'max:255'];
            $rules['organization_timezone'] = ['required', 'timezone:all'];
        }

        $validated = $this->validate($rules);
        $validated['password'] = Hash::make($validated['password']);

        $siteInvite = SiteInvite::findValidByToken($this->inviteToken);

        $user = DB::transaction(function () use ($validated, $settings, $siteInvite): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'timezone' => $validated['timezone'],
                'password' => $validated['password'],
            ]);

            if ($settings->org_creation_enabled) {
                $organization = Organization::create([
                    'name' => $validated['organization_name'],
                    'slug' => $this->makeUniqueOrganizationSlug($validated['organization_name']),
                    'timezone' => $validated['organization_timezone'],
                ]);

                $organization->users()->attach($user, [
                    'role' => 'org_admin',
                    'joined_at' => now(),
                ]);

                $user->update(['default_organization_id' => $organization->id]);
            }

            $siteInvite?->consume();

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    protected function matchingOrgInvite(): ?OrganizationInvite
    {
        if (! $this->inviteToken) {
            return null;
        }

        $invite = OrganizationInvite::where('token', $this->inviteToken)->first();

        return $invite && ! $invite->isExpired() && ! $invite->accepted_at ? $invite : null;
    }

    protected function makeUniqueOrganizationSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Create your workspace" description="Set up your account, pick your timezone, and create the organization you will work from" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        <x-mary-input wire:model="name" id="name" label="{{ __('Name') }}" type="text" name="name" required autofocus autocomplete="name" placeholder="Full name" />
        <x-mary-input wire:model="email" id="email" label="{{ __('Email address') }}" type="email" name="email" required autocomplete="email" placeholder="email@example.com" />

        <x-mary-select
            wire:model="timezone"
            id="timezone"
            name="timezone"
            label="{{ __('Your timezone') }}"
            :options="collect(\App\Support\Timezones::options())->map(fn($l, $v) => ['id' => $v, 'name' => $l])->values()->all()"
            required
        />

        @if(\App\Models\SiteSetting::current()->org_creation_enabled)
            <x-mary-input wire:model="organization_name" id="organization_name" label="{{ __('Organization name') }}" type="text" name="organization_name" required autocomplete="organization" placeholder="Nerdibear" />

            <x-mary-select
                wire:model="organization_timezone"
                id="organization_timezone"
                name="organization_timezone"
                label="{{ __('Organization timezone') }}"
                :options="collect(\App\Support\Timezones::options())->map(fn($l, $v) => ['id' => $v, 'name' => $l])->values()->all()"
                required
            />
        @endif

        <x-mary-password
            wire:model="password"
            id="password"
            label="{{ __('Password') }}"
            name="password"
            required
            autocomplete="new-password"
            placeholder="Password"
        />

        <x-mary-password
            wire:model="password_confirmation"
            id="password_confirmation"
            label="{{ __('Confirm password') }}"
            name="password_confirmation"
            required
            autocomplete="new-password"
            placeholder="Confirm password"
        />

        <div class="flex items-center justify-end">
            <x-mary-button type="submit" class="btn btn-primary w-full" :label="__('Create account')" />
        </div>
    </form>

    <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Already have an account?
        <x-text-link href="{{ route('login') }}">Log in</x-text-link>
    </div>
</div>
