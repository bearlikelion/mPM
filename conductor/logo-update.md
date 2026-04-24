# Add mPM Logo

## Changes
- **README.md**: Add `![mPM Logo](./mPM_logo.png)` just below the `# mPM` header to display the project logo.
- **Public Assets**: Copy the repository root `mPM_logo.png` into the `public/mPM_logo.png` path for web serving.
- **welcome.blade.php**: Replace the text-based `<span class="app-brand-mark">mPM</span>` with `<img src="{{ asset('mPM_logo.png') }}" alt="mPM Logo" class="h-8 w-auto" />` to render the graphic logo.
- **Tauri Application & Tray Icon**: Run `npx @tauri-apps/cli icon ../../mPM_logo.png` (from within the `desktop/` directory, or equivalent generator command) to produce all necessary app and tray icons in `desktop/src-tauri/icons/`.
- **Favicon**: Copy the newly generated `desktop/src-tauri/icons/icon.ico` to replace `public/favicon.ico`.

## Verification
- Verify the logo is visible in the `README.md` preview.
- Run `vendor/bin/sail npm run build` and visually check `welcome.blade.php` to ensure the new logo fits properly in the header.
- Confirm `public/favicon.ico` has been replaced successfully.
- Ensure all expected Tauri icon artifacts (`icon.icns`, `icon.ico`, various PNG sizes) have been updated in `desktop/src-tauri/icons/`.