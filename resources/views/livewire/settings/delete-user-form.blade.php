<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';
    public bool $showDeleteModal = false;

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h3 class="text-lg font-semibold text-[color:var(--gv-fg0)]">{{ __('Delete Account') }}</h3>
        <p class="text-sm text-[color:var(--gv-fg3)]">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <x-mary-button class="btn btn-error" wire:click="$set('showDeleteModal', true)" :label="__('Delete Account')" />

    <x-mary-modal wire:model="showDeleteModal" :title="__('Are you sure you want to delete your account?')" separator>
        <form wire:submit="deleteUser" class="space-y-6">
            <p class="text-sm text-[color:var(--gv-fg3)]">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <x-mary-password wire:model="password" id="password" label="{{ __('Password') }}" name="password" />

            <x-slot:actions>
                <x-mary-button :label="__('Cancel')" wire:click="$set('showDeleteModal', false)" />
                <x-mary-button class="btn btn-error" type="submit" :label="__('Delete Account')" />
            </x-slot:actions>
        </form>
    </x-mary-modal>
</section>
