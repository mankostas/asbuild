/** @vitest-environment jsdom */
import { describe, it, expect, vi } from 'vitest';
import { createApp, nextTick } from 'vue';
import { createI18n } from 'vue-i18n';
import AppShell from '@/components/layout/AppShell.vue';
import en from '@/i18n/en.json';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import VueSweetalert2 from 'vue-sweetalert2';
import { notifyPlugin, useNotify } from '@/plugins/notify';
import Swal from 'sweetalert2';
import PrimeVue from 'primevue/config';

vi.mock('@/stores/branding', () => ({
  useBrandingStore: () => ({ branding: {}, load: vi.fn() }),
}));
vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({ user: null, roles: [] }),
}));
import { ref } from 'vue';
vi.mock('@/stores/drafts', () => ({ useDraftsStore: () => ({ queue: ref([]) }) }));
vi.mock('@/components/appointments/UploadQueue.vue', () => ({
  default: { name: 'UploadQueue', template: '<div />' },
}));

const i18n = createI18n({ legacy: false, locale: 'en', messages: { en } });

Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation((query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

describe('AppShell notifications', () => {
  it('triggers toast and confirm dialog', async () => {
    const app = createApp(AppShell);
    const router = createRouter({
      history: createWebHistory(),
      routes: [{ path: '/', component: { template: '<div />' } }],
    });
    app.use(router);
    app.use(createPinia());
    app.use(i18n);
    app.use(PrimeVue);
    app.use(VueSweetalert2);
    app.use(notifyPlugin);
    const div = document.createElement('div');
    document.body.appendChild(div);
    app.mount(div);
    await router.isReady();

    const notify = useNotify();
    notify.info('hello');
    await nextTick();
    await new Promise((r) => setTimeout(r, 0));
    const toast = document.body.querySelector('.Vue-Toastification__toast');
    expect(toast).not.toBeNull();

    await Swal.fire({ title: 'Are you sure?' });
    const dialog = document.body.querySelector('.swal2-container');
    expect(dialog).not.toBeNull();
  });
});
