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
        <!-- Name -->
        <div class="grid gap-2">
            <flux:input wire:model="name" id="name" label="{{ __('Name') }}" type="text" name="name" required autofocus autocomplete="name" placeholder="Full name" />
        </div>

        <!-- Email Address -->
        <div class="grid gap-2">
            <flux:input wire:model="email" id="email" label="{{ __('Email address') }}" type="email" name="email" required autocomplete="email" placeholder="email@example.com" />
        </div>

        <div class="grid gap-2">
            <label for="timezone" class="text-sm font-medium text-neutral-200">{{ __('Your timezone') }}</label>
            <select wire:model="timezone" id="timezone" name="timezone" class="app-select w-full" required>
                @foreach(\App\Support\Timezones::options() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if(\App\Models\SiteSetting::current()->org_creation_enabled)
        <div class="grid gap-2">
            <flux:input wire:model="organization_name" id="organization_name" label="{{ __('Organization name') }}" type="text" name="organization_name" required autocomplete="organization" placeholder="Nerdibear" />
        </div>

        <div class="grid gap-2">
            <label for="organization_timezone" class="text-sm font-medium text-neutral-200">{{ __('Organization timezone') }}</label>
            <select wire:model="organization_timezone" id="organization_timezone" name="organization_timezone" class="app-select w-full" required>
                @foreach(\App\Support\Timezones::options() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <!-- Password -->
        <div class="grid gap-2">
            <flux:input
                wire:model="password"
                id="password"
                label="{{ __('Password') }}"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Password"
            />
        </div>

        <!-- Confirm Password -->
        <div class="grid gap-2">
            <flux:input
                wire:model="password_confirmation"
                id="password_confirmation"
                label="{{ __('Confirm password') }}"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
            />
        </div>

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Create account') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Already have an account?
        <x-text-link href="{{ route('login') }}">Log in</x-text-link>
    </div>
</div>
