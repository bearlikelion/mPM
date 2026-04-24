const state = {
    channelName: null,
    permissionGranted: false,
    permissionChecked: false,
    plugin: null,
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
