<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    //
}; ?>

<div class="flex flex-col items-start">
    @include('partials.settings-heading')

    <x-settings.layout heading="Appearance" subheading="Update your account's appearance settings">
        <div
            x-data="{
                appearance: localStorage.getItem('appearance') || 'system',
                apply() {
                    const useDark = this.appearance === 'dark' ||
                        (this.appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.classList.toggle('dark', useDark);
                    localStorage.setItem('appearance', this.appearance);
                },
            }"
            x-init="apply()"
            class="join"
        >
            <button type="button" class="btn join-item" :class="appearance === 'light' ? 'btn-primary' : ''" @click="appearance = 'light'; apply()">
                <x-mary-icon name="o-sun" class="h-4 w-4" /> Light
            </button>
            <button type="button" class="btn join-item" :class="appearance === 'dark' ? 'btn-primary' : ''" @click="appearance = 'dark'; apply()">
                <x-mary-icon name="o-moon" class="h-4 w-4" /> Dark
            </button>
            <button type="button" class="btn join-item" :class="appearance === 'system' ? 'btn-primary' : ''" @click="appearance = 'system'; apply()">
                <x-mary-icon name="o-computer-desktop" class="h-4 w-4" /> System
            </button>
        </div>
    </x-settings.layout>
</div>
