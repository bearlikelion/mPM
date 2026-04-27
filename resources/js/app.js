/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
import { bootDesktopNotifications, bootDesktopTray } from './desktop-notifications';
import { registerTiptap } from './tiptap';

document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        registerTiptap(window.Alpine);
    }
});

const bootDesktop = () => {
    bootDesktopNotifications();
    bootDesktopTray().catch((error) => {
        console.warn('Unable to configure desktop tray.', error);
    });
};

bootDesktop();

document.addEventListener('livewire:navigated', bootDesktop);
