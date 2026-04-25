<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="'Join ' . $invite->organization->name"
            :description="'You were invited as ' . $invite->email"
        />

        @if(session('status'))
            <x-auth-session-status class="text-center" :status="session('status')" />
        @endif

        <form method="POST" action="{{ route('invite.accept', $invite->token) }}" class="flex flex-col gap-6">
            @csrf

            @guest
                <x-mary-input label="Name" name="name" required autofocus />
                <x-mary-input label="Email" type="email" value="{{ $invite->email }}" disabled />
                <x-mary-select
                    id="timezone"
                    name="timezone"
                    label="Your timezone"
                    :options="collect(\App\Support\Timezones::options())->map(fn($l, $v) => ['id' => $v, 'name' => $l])->values()->all()"
                    required
                />
                <x-mary-password label="Password" name="password" required />
                <x-mary-password label="Confirm password" name="password_confirmation" required />
            @else
                <input type="hidden" name="_existing" value="1" />
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    You're signed in as {{ auth()->user()->email }}. Accepting will add this account to {{ $invite->organization->name }}.
                </p>
            @endguest

            <x-mary-button class="btn btn-primary w-full" type="submit" label="Accept invite" />
        </form>
    </div>
</x-layouts.auth>
