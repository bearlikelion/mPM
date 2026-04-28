import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight';
import Mention from '@tiptap/extension-mention';
import { createLowlight } from 'lowlight';
import bash from 'highlight.js/lib/languages/bash';
import css from 'highlight.js/lib/languages/css';
import go from 'highlight.js/lib/languages/go';
import xml from 'highlight.js/lib/languages/xml';
import javascript from 'highlight.js/lib/languages/javascript';
import json from 'highlight.js/lib/languages/json';
import php from 'highlight.js/lib/languages/php';
import python from 'highlight.js/lib/languages/python';
import shell from 'highlight.js/lib/languages/shell';
import sql from 'highlight.js/lib/languages/sql';
import typescript from 'highlight.js/lib/languages/typescript';
import yaml from 'highlight.js/lib/languages/yaml';
import tippy from 'tippy.js';

const lowlight = createLowlight();
lowlight.register({ bash, css, go, html: xml, javascript, json, php, python, shell, sql, typescript, xml, yaml });

const mentionSuggestion = (orgIdGetter) => ({
    items: async ({ query }) => {
        const orgId = orgIdGetter();
        const params = new URLSearchParams({ q: query ?? '' });
        if (orgId) params.set('org', String(orgId));
        try {
            const response = await fetch(`/api/mentions/search?${params.toString()}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return [];
            return await response.json();
        } catch {
            return [];
        }
    },
    render: () => {
        let popup;
        let listEl;
        let activeIndex = 0;
        let currentItems = [];
        let currentCommand;

        const renderList = () => {
            if (!listEl) return;
            listEl.innerHTML = '';
            if (currentItems.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'mention-suggestion-empty';
                empty.textContent = 'no matches';
                listEl.appendChild(empty);
                return;
            }
            currentItems.forEach((item, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'mention-suggestion-item' + (index === activeIndex ? ' is-active' : '');
                button.innerHTML = `
                    <img src="${item.avatar}" alt="" class="mention-suggestion-avatar" />
                    <span class="mention-suggestion-meta">
                        <span class="mention-suggestion-name">${item.name}</span>
                        <span class="mention-suggestion-email">${item.email ?? ''}</span>
                    </span>
                `;
                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    select(index);
                });
                listEl.appendChild(button);
            });
        };

        const select = (index) => {
            const item = currentItems[index];
            if (!item || !currentCommand) return;
            currentCommand({ id: String(item.id), label: item.name });
        };

        return {
            onStart: (props) => {
                currentItems = props.items;
                currentCommand = props.command;
                activeIndex = 0;

                listEl = document.createElement('div');
                listEl.className = 'mention-suggestion-list';
                renderList();

                popup = tippy('body', {
                    getReferenceClientRect: props.clientRect,
                    appendTo: () => document.body,
                    content: listEl,
                    showOnCreate: true,
                    interactive: true,
                    trigger: 'manual',
                    placement: 'bottom-start',
                    theme: 'tiptap-mention',
                });
            },
            onUpdate: (props) => {
                currentItems = props.items;
                currentCommand = props.command;
                activeIndex = 0;
                renderList();
                popup?.[0]?.setProps({ getReferenceClientRect: props.clientRect });
            },
            onKeyDown: (props) => {
                if (props.event.key === 'ArrowDown') {
                    activeIndex = (activeIndex + 1) % Math.max(currentItems.length, 1);
                    renderList();
                    return true;
                }
                if (props.event.key === 'ArrowUp') {
                    activeIndex = (activeIndex - 1 + currentItems.length) % Math.max(currentItems.length, 1);
                    renderList();
                    return true;
                }
                if (props.event.key === 'Enter') {
                    select(activeIndex);
                    return true;
                }
                if (props.event.key === 'Escape') {
                    popup?.[0]?.hide();
                    return true;
                }
                return false;
            },
            onExit: () => {
                popup?.[0]?.destroy();
                popup = null;
                listEl = null;
            },
        };
    },
});

const buildEditor = (element, { content, placeholder, orgIdGetter, onUpdate }) => {
    return new Editor({
        element,
        extensions: [
            StarterKit.configure({
                codeBlock: false,
                link: false,
                heading: { levels: [2, 3] },
            }),
            Link.configure({
                openOnClick: false,
                autolink: true,
                HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
            }),
            Placeholder.configure({ placeholder: placeholder || 'write here…' }),
            CodeBlockLowlight.configure({ lowlight }),
            Mention.configure({
                HTMLAttributes: { class: 'mention', 'data-mention': 'true' },
                renderHTML: ({ options, node }) => [
                    'span',
                    {
                        class: 'mention',
                        'data-mention': 'true',
                        'data-user-id': node.attrs.id,
                    },
                    `@${node.attrs.label ?? node.attrs.id}`,
                ],
                suggestion: mentionSuggestion(orgIdGetter),
            }),
        ],
        content: content || '',
        onUpdate: ({ editor }) => onUpdate(editor.getHTML()),
    });
};

export const registerTiptap = (Alpine) => {
    Alpine.data('tiptap', (initial = '', placeholder = '', orgId = null) => ({
        editor: null,
        value: initial,
        init() {
            const root = this.$refs.editor;

            this.editor = buildEditor(root, {
                content: this.value || initial,
                placeholder,
                orgIdGetter: () => orgId,
                onUpdate: (html) => {
                    const normalized = html === '<p></p>' ? '' : html;
                    if (normalized !== this.value) {
                        this.value = normalized;
                    }
                },
            });

            this.$watch('value', (incoming) => {
                if (!this.editor) return;
                const current = this.editor.getHTML();
                const normalizedCurrent = current === '<p></p>' ? '' : current;
                if (incoming === normalizedCurrent) return;
                this.editor.commands.setContent(incoming || '', false);
            });
        },
        destroy() {
            this.editor?.destroy();
            this.editor = null;
        },
        run(name, ...args) {
            if (!this.editor) return;
            this.editor.chain()[name](...args).run();
        },
        toggleLink() {
            if (!this.editor) return;
            const previous = this.editor.getAttributes('link').href;
            const url = window.prompt('link url', previous || 'https://');
            if (url === null) return;
            if (url === '') {
                this.editor.chain().extendMarkRange('link').unsetLink().run();
                return;
            }
            this.editor.chain().extendMarkRange('link').setLink({ href: url }).run();
        },
        isActive(name, attrs = {}) {
            return this.editor?.isActive(name, attrs) ?? false;
        },
    }));
};
