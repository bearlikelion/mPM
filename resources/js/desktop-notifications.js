const state = {
    channelName: null,
    permissionGranted: false,
    permissionChecked: false,
    plugin: null,
    trayActions: new Map(),
    trayListenerRegistered: false,
};

const isTauri = () => window.__TAURI_INTERNALS__ !== undefined;

const currentUserId = () => document.querySelector('meta[name="mpm-user-id"]')?.content;

const notificationText = (notification) => ({
    title: notification.title || 'mPM',
    body: notification.body || notification.task_title || 'You have a new notification.',
});

const notificationPlugin = async () => {
    state.plugin ??= await import('@tauri-apps/plugin-notification');

    return state.plugin;
};

const ensurePermission = async () => {
    if (state.permissionChecked) {
        return state.permissionGranted;
    }

    const { isPermissionGranted, requestPermission } = await notificationPlugin();

    state.permissionGranted = await isPermissionGranted();

    if (! state.permissionGranted) {
        state.permissionGranted = await requestPermission() === 'granted';
    }

    state.permissionChecked = true;

    return state.permissionGranted;
};

const sendDesktopNotification = async (notification) => {
    if (! await ensurePermission()) {
        return;
    }

    const { sendNotification } = await notificationPlugin();

    sendNotification(notificationText(notification));
};

const normalizeUrl = (url) => {
    const parsed = new URL(url, window.location.origin);

    if (parsed.origin !== window.location.origin) {
        return parsed.href;
    }

    return `${parsed.pathname}${parsed.search}${parsed.hash}`;
};

const sidebarTrayItems = () => Array.from(document.querySelectorAll('[data-desktop-tray-link][href]'))
    .map((link, index) => {
        const id = `nav_${index}`;
        const label = link.textContent.trim().replace(/\s+/g, ' ');
        const url = normalizeUrl(link.href);

        state.trayActions.set(id, { label, url });

        return { id, label };
    })
    .filter((item) => item.label.length > 0);

const openCreateTaskModal = () => {
    window.Livewire?.dispatch?.('open-create-task-modal');
    window.dispatchEvent(new CustomEvent('open-create-task-modal'));
};

const navigateFromTray = (url) => {
    if (url.startsWith('http://') || url.startsWith('https://')) {
        window.open(url, '_blank', 'noopener,noreferrer');

        return;
    }

    if (window.Livewire?.navigate) {
        window.Livewire.navigate(url);

        return;
    }

    window.location.assign(url);
};

const handleTrayAction = (id) => {
    if (id === 'create_task') {
        openCreateTaskModal();

        return;
    }

    const item = state.trayActions.get(id);

    if (item) {
        navigateFromTray(item.url);
    }
};

export const bootDesktopTray = async () => {
    if (! isTauri() || ! currentUserId()) {
        return;
    }

    const [{ invoke }, { listen }] = await Promise.all([
        import('@tauri-apps/api/core'),
        import('@tauri-apps/api/event'),
    ]);

    state.trayActions.clear();

    await invoke('set_tray_sidebar_items', {
        items: sidebarTrayItems(),
    });

    if (state.trayListenerRegistered) {
        return;
    }

    state.trayListenerRegistered = true;

    await listen('mpm-tray-action', (event) => {
        handleTrayAction(event.payload.id);
    });
};

export const bootDesktopNotifications = () => {
    if (! isTauri() || ! window.Echo) {
        return;
    }

    const userId = currentUserId();

    if (! userId) {
        return;
    }

    const channelName = `App.Models.User.${userId}`;

    if (state.channelName === channelName) {
        return;
    }

    if (state.channelName) {
        window.Echo.leave(state.channelName);
    }

    state.channelName = channelName;

    window.Echo.private(channelName).notification((notification) => {
        sendDesktopNotification(notification).catch((error) => {
            console.warn('Unable to send desktop notification.', error);
        });
    });
};
