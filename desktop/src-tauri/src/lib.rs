use serde::{Deserialize, Serialize};
use tauri::{
    menu::{Menu, MenuBuilder},
    tray::{MouseButton, MouseButtonState, TrayIconBuilder, TrayIconEvent},
    Emitter, Manager, Runtime, WindowEvent,
};

const TRAY_ID: &str = "main";
const CREATE_TASK_ID: &str = "create_task";
const SHOW_ID: &str = "show";
const QUIT_ID: &str = "quit";

#[derive(Clone, Deserialize)]
#[serde(rename_all = "camelCase")]
struct TraySidebarItem {
    id: String,
    label: String,
}

#[derive(Clone, Serialize)]
#[serde(rename_all = "camelCase")]
struct TrayAction {
    id: String,
}

#[tauri::command]
fn greet(name: &str) -> String {
    format!("Hello, {}! You've been greeted from Rust!", name)
}

#[tauri::command]
fn set_tray_sidebar_items(app: tauri::AppHandle, items: Vec<TraySidebarItem>) -> tauri::Result<()> {
    if let Some(tray) = app.tray_by_id(TRAY_ID) {
        tray.set_menu(Some(build_tray_menu(&app, &items)?))?;
    }

    Ok(())
}

fn show_main_window(app: &tauri::AppHandle) {
    if let Some(window) = app.get_webview_window("main") {
        let _ = window.unminimize();
        let _ = window.show();
        let _ = window.set_focus();
    }
}

fn emit_tray_action(app: &tauri::AppHandle, id: &str) {
    show_main_window(app);

    let _ = app.emit_to(TRAY_ID, "mpm-tray-action", TrayAction { id: id.into() });
}

fn build_tray_menu<R: Runtime, M: Manager<R>>(
    manager: &M,
    items: &[TraySidebarItem],
) -> tauri::Result<Menu<R>> {
    let mut menu = MenuBuilder::new(manager)
        .text(CREATE_TASK_ID, "CREATE NEW TASK")
        .separator();

    for item in items.iter().take(16) {
        menu = menu.text(
            item.id.clone(),
            item.label.trim().chars().take(80).collect::<String>(),
        );
    }

    menu.separator()
        .text(SHOW_ID, "Show mPM")
        .text(QUIT_ID, "Quit")
        .build()
}

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .plugin(tauri_plugin_opener::init())
        .plugin(tauri_plugin_notification::init())
        .setup(|app| {
            let menu = build_tray_menu(app, &[])?;

            TrayIconBuilder::with_id(TRAY_ID)
                .menu(&menu)
                .show_menu_on_left_click(false)
                .on_menu_event(|app, event| match event.id().as_ref() {
                    SHOW_ID => show_main_window(app),
                    QUIT_ID => app.exit(0),
                    id => emit_tray_action(app, id),
                })
                .on_tray_icon_event(|tray, event| {
                    if let TrayIconEvent::Click {
                        button: MouseButton::Left,
                        button_state: MouseButtonState::Up,
                        ..
                    } = event
                    {
                        show_main_window(&tray.app_handle());
                    }
                })
                .build(app)?;

            Ok(())
        })
        .on_window_event(|window, event| {
            if let WindowEvent::CloseRequested { api, .. } = event {
                api.prevent_close();
                let _ = window.hide();
            }
        })
        .invoke_handler(tauri::generate_handler![greet, set_tray_sidebar_items])
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
