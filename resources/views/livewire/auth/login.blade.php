<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Log in to your account" description="Enter your email and password below to log in" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <x-mary-input wire:model="email" label="{{ __('Email address') }}" type="email" name="email" required autofocus autocomplete="email" placeholder="email@example.com" />

        <!-- Password -->
        <div class="relative">
            <x-mary-password
                wire:model="password"
                label="{{ __('Password') }}"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Password"
            />

            @if (Route::has('password.request'))
                <x-text-link class="absolute right-0 top-0" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </x-text-link>
            @endif
        </div>

        <!-- Remember Me -->
        <x-mary-checkbox wire:model="remember" label="{{ __('Remember me') }}" />

        <div class="flex items-center justify-end">
            <x-mary-button class="btn btn-primary w-full" type="submit" :label="__('Log in')" />
    </form>

    @if(config('services.google.client_id') || config('services.discord.client_id') || config('services.steam.api_key'))
        <div class="relative my-2 flex items-center">
            <div class="flex-grow border-t border-zinc-200 dark:border-zinc-700"></div>
            <span class="mx-3 flex-shrink text-xs uppercase tracking-wider text-zinc-500">or continue with</span>
            <div class="flex-grow border-t border-zinc-200 dark:border-zinc-700"></div>
        </div>

        <div class="flex gap-2">
            @if(config('services.google.client_id'))
                <a href="{{ route('socialite.redirect', 'google') }}" class="flex-1 rounded-md border border-zinc-200 px-4 py-2 text-center text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">Google</a>
            @endif
            @if(config('services.discord.client_id'))
                <a href="{{ route('socialite.redirect', 'discord') }}" class="flex-1 rounded-md border border-zinc-200 px-4 py-2 text-center text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">Discord</a>
            @endif
            @if(config('services.steam.api_key'))
                <a href="{{ route('socialite.redirect', 'steam') }}" class="flex-1 rounded-md border border-zinc-200 px-4 py-2 text-center text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">Steam</a>
            @endif
        </div>
    @endif

    <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Don't have an account?
        <x-text-link href="{{ route('register') }}">Sign up</x-text-link>
    </div>
</div>
