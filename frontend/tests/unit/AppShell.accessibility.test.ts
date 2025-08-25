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

describe('AppShell', () => {
  it('renders and toggles', async () => {
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
    const skip = div.querySelector('a[href="#main"]') as HTMLAnchorElement;
    expect(skip).not.toBeNull();
    const buttons = Array.from(div.querySelectorAll('button')) as HTMLButtonElement[];
    buttons.find((b) => b.textContent?.includes('Toggle Theme'))?.click();
    buttons.find((b) => b.textContent?.includes('Toggle Density'))?.click();
    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', metaKey: true }));
    await nextTick();
    const palette = div.querySelector('[role="dialog"]');
    expect(palette).not.toBeNull();
  });
});
