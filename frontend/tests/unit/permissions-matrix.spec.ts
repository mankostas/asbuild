/** @vitest-environment jsdom */
import { describe, it, expect } from 'vitest';
import { createApp, ref, nextTick } from 'vue';
import { createI18n } from 'vue-i18n';
import PermissionsMatrix from '@/components/types/PermissionsMatrix.vue';
import en from '@/i18n/en.json';

const TestWrapper = {
  components: { PermissionsMatrix },
  template: '<PermissionsMatrix ref="pm" v-model="perms" :roles="roles" :can-manage="true" :status-count="0" :features="features" />',
  setup() {
    const perms = ref({});
    const roles = ref([] as any[]);
    const features = ref<string[]>(['tasks']);
    const pm = ref();
    return { perms, roles, features, pm };
  },
};

describe('PermissionsMatrix', () => {
  it('filters abilities when features change', async () => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp(TestWrapper);
    const i18n = createI18n({ locale: 'en', messages: { en } });
    app.use(i18n);
    const vm: any = app.mount(el);
    const pm: any = vm.pm;
    await nextTick();
    const headerCount = pm.$el.querySelectorAll('thead th').length - 1;
    expect(headerCount).toBeGreaterThan(0);
    vm.features = [];
    await nextTick();
    const newCount = pm.$el.querySelectorAll('thead th').length - 1;
    expect(newCount).toBe(0);
    app.unmount();
  });
});
