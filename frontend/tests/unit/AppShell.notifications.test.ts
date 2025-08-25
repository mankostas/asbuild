/** @vitest-environment jsdom */
import { describe, it, expect, vi } from 'vitest';
import { createApp, nextTick } from 'vue';
import { createI18n } from 'vue-i18n';
import AppShell from '@/components/layout/AppShell.vue';
import en from '@/i18n/en.json';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import ConfirmDialog from 'primevue/confirmdialog';
import { createRouter, createWebHistory } from 'vue-router';

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
    app.use(ToastService);
    app.use(ConfirmationService);
    app.component('ConfirmDialog', ConfirmDialog);
    const div = document.createElement('div');
    document.body.appendChild(div);
    app.mount(div);
    await router.isReady();

    app.config.globalProperties.$toast.add({ severity: 'info', summary: 'test', detail: 'hello' });
    await nextTick();
    await new Promise((r) => setTimeout(r, 0));
    const toast = document.body.querySelector('.p-toast-message');
    expect(toast).not.toBeNull();

    app.config.globalProperties.$confirm.require({
      message: 'Are you sure?',
      header: 'Confirm',
      accept: vi.fn(),
    });
    await nextTick();
    await new Promise((r) => setTimeout(r, 0));
    const dialog = document.body.querySelector('.p-confirm-dialog, .p-dialog');
    expect(dialog).not.toBeNull();
  });
});
