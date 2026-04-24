# Tauri + Vanilla

This template should help get you started developing with Tauri in vanilla HTML, CSS and Javascript.

## Development

Run the app with:

```sh
npm run tauri -- dev
```

The npm script applies `WEBKIT_DISABLE_DMABUF_RENDERER=1` on Linux before
launching Tauri. This avoids WebKitGTK Wayland protocol crashes on affected
drivers/compositors while keeping the command portable.

## Recommended IDE Setup

- [VS Code](https://code.visualstudio.com/) + [Tauri](https://marketplace.visualstudio.com/items?itemName=tauri-apps.tauri-vscode) + [rust-analyzer](https://marketplace.visualstudio.com/items?itemName=rust-lang.rust-analyzer)
