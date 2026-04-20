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
                <flux:input label="Name" name="name" required autofocus />
                <flux:input label="Email" type="email" value="{{ $invite->email }}" disabled />
                <flux:input label="Password" type="password" name="password" required />
                <flux:input label="Confirm password" type="password" name="password_confirmation" required />
            @else
                <input type="hidden" name="_existing" value="1" />
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    You're signed in as {{ auth()->user()->email }}. Accepting will add this account to {{ $invite->organization->name }}.
                </p>
            @endguest

            <flux:button variant="primary" type="submit" class="w-full">
                Accept invite
            </flux:button>
        </form>
    </div>
</x-layouts.auth>
