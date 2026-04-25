<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $timezone = 'UTC';
    public $avatar = null;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->timezone = Auth::user()->preferredTimezone();
    }

    public function updatedAvatar(): void
    {
        $this->validate([
            'avatar' => ['image', 'max:2048'],
        ]);

        $user = Auth::user();
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->avatar_path = $this->avatar->store('avatars', 'public');
        $user->save();

        $this->avatar = null;
        $this->dispatch('profile-updated', name: $user->name);
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
            'timezone' => ['required', 'timezone:all'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout heading="Profile" subheading="Update your name, email address, and local timezone">
        <div class="my-6 flex items-center gap-4">
            <img src="{{ auth()->user()->avatarUrl() }}" alt="avatar" class="h-16 w-16 rounded-full" />
            <div class="flex flex-col gap-2">
                <input type="file" wire:model="avatar" accept="image/*" />
                @error('avatar')<span class="text-sm text-red-600">{{ $message }}</span>@enderror
                @if(auth()->user()->avatar_path)
                    <button type="button" wire:click="removeAvatar" class="text-sm text-red-600 underline self-start">Remove avatar</button>
                @endif
            </div>
        </div>

        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <x-mary-input wire:model="name" label="{{ __('Name') }}" type="text" name="name" required autofocus autocomplete="name" />

            <div>
                <x-mary-input wire:model="email" label="{{ __('Email') }}" type="email" name="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <p class="mt-2 text-sm text-gray-800">
                            {{ __('Your email address is unverified.') }}

                            <button
                                wire:click.prevent="resendVerificationNotification"
                                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <x-mary-select
                wire:model="timezone"
                id="timezone"
                name="timezone"
                label="{{ __('Timezone') }}"
                hint="This controls how timestamps are shown to you across the app."
                :options="collect(\App\Support\Timezones::options())->map(fn($l, $v) => ['id' => $v, 'name' => $l])->values()->all()"
                required
            />

            @php
                $currentOrg = auth()->user()->defaultOrganization;
                $now = now();
            @endphp
            <div class="app-panel-muted rounded-2xl p-4">
                <div class="app-eyebrow">Time Conversion Preview</div>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <div>
                        <div class="text-sm font-medium text-neutral-200">Your local time</div>
                        <div class="mt-1 text-sm text-neutral-400">{{ auth()->user()->formatLocalTime($now) }}</div>
                    </div>
                    @if($currentOrg)
                        <div>
                            <div class="text-sm font-medium text-neutral-200">{{ $currentOrg->name }} time</div>
                            <div class="mt-1 text-sm text-neutral-400">{{ $currentOrg->formatLocalTime($now) }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <x-mary-button class="btn btn-primary w-full" type="submit" :label="__('Save')" />
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
