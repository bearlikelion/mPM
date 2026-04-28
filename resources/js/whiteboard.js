import React from 'react';
import { createRoot } from 'react-dom/client';
import { Excalidraw, MainMenu } from '@excalidraw/excalidraw';
import '@excalidraw/excalidraw/index.css';

const SAVE_DEBOUNCE_MS = 1500;

const GRUVBOX_STROKE = ['#ebdbb2', '#fb4934', '#fe8019', '#fabd2f', '#b8bb26', '#8ec07c', '#83a598', '#d3869b'];
const GRUVBOX_BG = ['transparent', '#3c3836', '#504945', '#fb493422', '#fabd2f33', '#b8bb2633', '#83a59833', '#d3869b33'];

const DEFAULT_LIBRARY_ITEMS = [
    {
        status: 'published',
        id: 'sticky-note-yellow',
        created: 1,
        name: 'Sticky note',
        elements: [
            {
                type: 'rectangle',
                x: 0,
                y: 0,
                width: 200,
                height: 160,
                strokeColor: '#fabd2f',
                backgroundColor: '#fabd2f33',
                fillStyle: 'solid',
                strokeWidth: 1,
                strokeStyle: 'solid',
                roughness: 0,
                opacity: 100,
                roundness: { type: 3 },
                seed: 1,
                version: 1,
                versionNonce: 1,
                isDeleted: false,
                groupIds: ['sticky-1'],
                boundElements: null,
                updated: 1,
                link: null,
                locked: false,
            },
            {
                type: 'text',
                x: 16,
                y: 16,
                width: 168,
                height: 128,
                strokeColor: '#fbf1c7',
                backgroundColor: 'transparent',
                fillStyle: 'solid',
                strokeWidth: 1,
                strokeStyle: 'solid',
                roughness: 0,
                opacity: 100,
                seed: 2,
                version: 1,
                versionNonce: 2,
                isDeleted: false,
                groupIds: ['sticky-1'],
                boundElements: null,
                updated: 1,
                link: null,
                locked: false,
                fontSize: 16,
                fontFamily: 1,
                text: 'Sticky note',
                textAlign: 'left',
                verticalAlign: 'top',
                containerId: null,
                originalText: 'Sticky note',
                lineHeight: 1.25,
                baseline: 14,
            },
        ],
    },
];

function debounce(fn, wait) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), wait);
    };
}

function dispatchStatus(name) {
    window.dispatchEvent(new CustomEvent(name));
}

export default function whiteboard({ initial = null } = {}) {
    return {
        root: null,
        excalidrawApi: null,

        init() {
            this.mount(this.$el);
        },

        mount(rootEl) {
            const target = rootEl.querySelector('[data-excalidraw-root]');
            if (!target) {
                return;
            }

            const wireEl = this.$root.closest('[wire\\:id]') || rootEl.closest('[wire\\:id]');
            const wire = wireEl ? window.Livewire.find(wireEl.getAttribute('wire:id')) : null;

            const initialData = initial && initial.elements
                ? {
                    elements: initial.elements ?? [],
                    appState: { ...(initial.appState ?? {}), collaborators: [] },
                    files: initial.files ?? {},
                    libraryItems: initial.libraryItems ?? DEFAULT_LIBRARY_ITEMS,
                }
                : {
                    elements: [],
                    appState: {
                        viewBackgroundColor: 'transparent',
                        currentItemStrokeColor: '#ebdbb2',
                        currentItemBackgroundColor: 'transparent',
                    },
                    files: {},
                    libraryItems: DEFAULT_LIBRARY_ITEMS,
                };

            const save = debounce(async (elements, appState, files) => {
                if (!wire) return;
                dispatchStatus('whiteboard-saving');
                try {
                    await wire.call('save', {
                        elements,
                        appState: stripVolatile(appState),
                        files,
                    });
                    dispatchStatus('whiteboard-saved');
                } catch (e) {
                    console.error('whiteboard save failed', e);
                    dispatchStatus('whiteboard-error');
                }
            }, SAVE_DEBOUNCE_MS);

            const onChange = (elements, appState, files) => {
                save(elements, appState, files);
            };

            const onPasteOrDrop = async (data) => {
                if (!data || !data.files || data.files.length === 0 || !wire) {
                    return true;
                }
                for (const file of data.files) {
                    if (!file.type || !file.type.startsWith('image/')) continue;
                    try {
                        await wire.upload('pendingImage', file, () => {}, () => {}, () => {});
                        const result = await wire.call('commitImage');
                        if (result && result.url && this.excalidrawApi) {
                            const img = await fetchAsDataUrl(result.url);
                            this.insertImage(img, file.name);
                        }
                    } catch (e) {
                        console.error('image upload failed', e);
                        dispatchStatus('whiteboard-error');
                    }
                }
                return false;
            };

            this.root = createRoot(target);
            this.root.render(
                React.createElement(Excalidraw, {
                    initialData,
                    theme: 'dark',
                    UIOptions: {
                        canvasActions: {
                            saveToActiveFile: false,
                            loadScene: false,
                        },
                    },
                    onChange,
                    onPaste: (data) => {
                        onPasteOrDrop(data);
                        return true;
                    },
                    onDrop: (event) => {
                        const files = event?.dataTransfer?.files;
                        if (files && files.length) {
                            onPasteOrDrop({ files: Array.from(files) });
                        }
                    },
                    excalidrawAPI: (api) => {
                        this.excalidrawApi = api;
                    },
                    validateEmbeddable: true,
                    renderTopRightUI: () => null,
                    children: React.createElement(MainMenu, null,
                        React.createElement(MainMenu.DefaultItems.ToggleTheme, null),
                        React.createElement(MainMenu.DefaultItems.ChangeCanvasBackground, null),
                        React.createElement(MainMenu.DefaultItems.Export, null),
                        React.createElement(MainMenu.DefaultItems.SaveAsImage, null),
                    ),
                })
            );
        },

        async insertImage(dataUrl, name) {
            if (!this.excalidrawApi) return;
            const blob = await (await fetch(dataUrl)).blob();
            const reader = new FileReader();
            reader.readAsArrayBuffer(blob);
            const fileId = 'wb_' + Math.random().toString(36).slice(2, 11);

            this.excalidrawApi.addFiles([
                {
                    id: fileId,
                    dataURL: dataUrl,
                    mimeType: blob.type || 'image/png',
                    created: Date.now(),
                },
            ]);

            const elements = this.excalidrawApi.getSceneElements();
            const newImg = {
                type: 'image',
                id: fileId + '-el',
                x: 100,
                y: 100,
                width: 320,
                height: 240,
                angle: 0,
                strokeColor: 'transparent',
                backgroundColor: 'transparent',
                fillStyle: 'solid',
                strokeWidth: 1,
                strokeStyle: 'solid',
                roughness: 0,
                opacity: 100,
                seed: Math.floor(Math.random() * 100000),
                version: 1,
                versionNonce: Math.floor(Math.random() * 100000),
                isDeleted: false,
                groupIds: [],
                boundElements: null,
                updated: Date.now(),
                link: null,
                locked: false,
                status: 'saved',
                fileId,
                scale: [1, 1],
            };

            this.excalidrawApi.updateScene({
                elements: [...elements, newImg],
            });
        },

        destroy() {
            if (this.root) {
                this.root.unmount();
                this.root = null;
            }
        },
    };
}

function stripVolatile(appState) {
    if (!appState) return {};
    const { collaborators, ...rest } = appState;
    return rest;
}

async function fetchAsDataUrl(url) {
    const res = await fetch(url, { credentials: 'same-origin' });
    const blob = await res.blob();
    return await new Promise((resolve, reject) => {
        const fr = new FileReader();
        fr.onload = () => resolve(fr.result);
        fr.onerror = reject;
        fr.readAsDataURL(blob);
    });
}

export function registerWhiteboard(Alpine) {
    Alpine.data('whiteboard', whiteboard);
}
