/** @vitest-environment jsdom */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { defineComponent } from 'vue';
import { createPinia, setActivePinia } from 'pinia';

const route = {
  name: 'tenants.create',
  params: {} as Record<string, unknown>,
  meta: { modal: false as boolean | undefined },
};

const mocks = vi.hoisted(() => ({
  routerPush: vi.fn(),
  notifySuccess: vi.fn(),
  getMock: vi.fn(),
  postMock: vi.fn(),
  patchMock: vi.fn(),
}));

vi.mock('vue-router', () => ({
  useRoute: () => route,
  useRouter: () => ({ push: mocks.routerPush }),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@/plugins/notify', () => ({
  useNotify: () => ({ success: mocks.notifySuccess }),
}));

vi.mock('@/components/ui/Modal', () => ({
  default: defineComponent({
    name: 'ModalStub',
    emits: ['close'],
    template: '<div data-testid="modal"><slot /></div>',
  }),
}));

vi.mock('@/components/ui/Textinput/index.vue', () => ({
  default: defineComponent({
    name: 'TextinputStub',
    props: {
      modelValue: [String, Number],
      type: { type: String, default: 'text' },
    },
    emits: ['update:modelValue'],
    template:
      '<label><input :type="type" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" /></label>',
  }),
}));

vi.mock('@/components/ui/Switch/index.vue', () => ({
  default: defineComponent({
    name: 'SwitchStub',
    props: {
      modelValue: { type: Boolean, default: false },
      disabled: { type: Boolean, default: false },
    },
    emits: ['update:modelValue'],
    template:
      '<label data-testid="notify-switch"><input type="checkbox" :checked="modelValue" :disabled="disabled" @change="$emit(\'update:modelValue\', $event.target.checked)" /></label>',
  }),
}));

vi.mock('@/components/ui/Button/index.vue', () => ({
  default: defineComponent({
    name: 'ButtonStub',
    props: {
      text: { type: String, default: '' },
      type: { type: String, default: 'button' },
    },
    emits: ['click'],
    template: '<button :type="type" @click="$emit(\'click\')">{{ text }}</button>',
  }),
}));

vi.mock('@/components/ui/Select/VueSelect.vue', () => ({
  default: defineComponent({
    name: 'VueSelectStub',
    props: ['label', 'error'],
    template: '<div class="vue-select"><slot :inputId="\'select-input\'" /></div>',
  }),
}));

vi.mock('vue-select', () => ({
  default: defineComponent({
    name: 'VSelectStub',
    props: {
      modelValue: { type: [Array, String, Number], default: null },
      options: { type: Array, default: () => [] },
      multiple: { type: Boolean, default: false },
    },
    emits: ['update:modelValue'],
    template:
      '<select :multiple="multiple" @change="$emit(\'update:modelValue\', multiple ? Array.from($event.target.selectedOptions).map(o => o.value) : $event.target.value)"><option v-for="option in options" :key="option.value || option" :value="option.value || option">{{ option.label || option }}</option></select>',
  }),
}));

vi.mock('@/services/api', () => ({
  __esModule: true,
  default: {
    get: (...args: any[]) => mocks.getMock(...args),
    post: (...args: any[]) => mocks.postMock(...args),
    patch: (...args: any[]) => mocks.patchMock(...args),
  },
  extractFormErrors: (error: any) => error?.response?.data?.errors ?? {},
}));

vi.mock('@/utils/ability', () => ({
  __esModule: true,
  default: () => true,
}));

import TenantForm from '@/views/tenants/TenantForm.vue';
import { useTenantStore } from '@/stores/tenant';
import { useFeaturesStore } from '@/stores/features';

describe('TenantForm modal layout and payload', () => {
  let loadTenantsSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    mocks.routerPush.mockReset();
    mocks.notifySuccess.mockReset();
    mocks.getMock.mockReset();
    mocks.postMock.mockReset();
    mocks.patchMock.mockReset();

    route.name = 'tenants.create';
    route.meta.modal = false;
    route.params = {};

    mocks.getMock.mockImplementation((url: string) => {
      if (url === '/lookups/feature-map') {
        return Promise.resolve({
          data: {
            tasks: { label: 'Tasks', abilities: ['view'] },
          },
        });
      }
      if (url.startsWith('/tenants/')) {
        return Promise.resolve({
          data: {
            name: 'Tenant',
            quota_storage_mb: 0,
            phone: '',
            address: '',
            features: [],
            feature_abilities: {},
          },
        });
      }
      if (url === '/tenants') {
        return Promise.resolve({ data: { data: [] } });
      }
      return Promise.resolve({ data: {} });
    });

    mocks.postMock.mockResolvedValue({ data: {} });
    mocks.patchMock.mockResolvedValue({ data: {} });

    setActivePinia(createPinia());
    const tenantStore = useTenantStore();
    tenantStore.tenants = [] as any;
    loadTenantsSpy = vi.spyOn(tenantStore, 'loadTenants').mockResolvedValue({} as any);

    const featuresStore = useFeaturesStore();
    featuresStore.featureMap = {} as any;
  });

  afterEach(() => {
    loadTenantsSpy.mockRestore();
  });

  function mountComponent(props: Record<string, unknown> = {}) {
    return mount(TenantForm, {
      props,
    });
  }

  it('wraps the form in a modal when the route meta enables it', async () => {
    route.meta.modal = true;

    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.find('[data-testid="modal"]').exists()).toBe(true);
  });

  it('includes the notify_owner flag in the create payload when enabled', async () => {
    const wrapper = mountComponent();
    await flushPromises();

    const emailInput = wrapper.find('input[type="email"]');
    await emailInput.setValue('owner@example.com');

    const switchInput = wrapper.find('[data-testid="notify-switch"] input');
    await switchInput.setValue(true);

    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    expect(mocks.postMock).toHaveBeenCalledWith(
      '/tenants',
      expect.objectContaining({ notify_owner: true }),
    );
    expect(loadTenantsSpy).toHaveBeenCalled();
    expect(mocks.notifySuccess).toHaveBeenCalledWith('tenants.form.success.created');
    expect(mocks.routerPush).toHaveBeenCalledWith({ name: 'tenants.list' });
  });

  it('emits close instead of navigating when used as a forced modal', async () => {
    const wrapper = mountComponent({ forceModal: true });
    await flushPromises();

    const emailInput = wrapper.find('input[type="email"]');
    await emailInput.setValue('owner@example.com');
    const switchInput = wrapper.find('[data-testid="notify-switch"] input');
    await switchInput.setValue(true);

    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    expect(wrapper.emitted('close')).toBeTruthy();
    expect(mocks.routerPush).not.toHaveBeenCalled();
    expect(mocks.notifySuccess).toHaveBeenCalledWith('tenants.form.success.created');
  });
});
