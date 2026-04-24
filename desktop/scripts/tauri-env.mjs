import { spawn } from 'node:child_process';

const env = { ...process.env };

if (process.platform === 'linux') {
  env.WEBKIT_DISABLE_DMABUF_RENDERER ??= '1';
}

const child = spawn('tauri', process.argv.slice(2), {
  env,
  shell: process.platform === 'win32',
  stdio: 'inherit',
});

child.on('exit', (code, signal) => {
  if (signal) {
    process.kill(process.pid, signal);
  }

  process.exit(code ?? 1);
});

child.on('error', (error) => {
  console.error(error.message);
  process.exit(1);
});
