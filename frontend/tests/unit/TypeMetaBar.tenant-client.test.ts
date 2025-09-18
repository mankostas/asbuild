/** @vitest-environment jsdom */
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { createApp, h } from 'vue';
import { createI18n } from 'vue-i18n';
import { createPinia, setActivePinia } from 'pinia';
import TypeMetaBar from '@/components/types/TypeMetaBar.vue';
import { useTenantStore } from '@/stores/tenant';

vi.mock('@/components/ui/Card/index.vue', () => ({
  default: { name: 'Card', template: '<div><slot /></div>' },
}));

vi.mock('@/components/ui/Textinput/index.vue', () => ({
  default: {
    name: 'Textinput',
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template:
      '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
  },
}));

vi.mock('@/components/ui/Select/VueSelect.vue', () => ({
  default: {
    name: 'VueSelect',
    props: ['label'],
    template: '<label class="vue-select"><slot :input-id="`${label}-input`" /></label>',
  },
}));

vi.mock('vue-select', () => ({
  default: {
    name: 'vSelect',
    inheritAttrs: false,
    props: ['modelValue'],
    emits: ['update:modelValue', 'search'],
    setup(props: any, { emit, attrs }: any) {
      const baseAttrs = { ...(attrs as Record<string, any>) };
      const options = baseAttrs.options;
      delete baseAttrs.options;
      return () =>
        h('select', {
          ...baseAttrs,
          value: props.modelValue ?? '',
          onChange: (event: Event) =>
            emit('update:modelValue', (event.target as HTMLSelectElement).value),
        },
        (Array.isArray(options) ? options : []).map((option: any) =>
          h(
            'option',
            { value: option?.value ?? option },
            option?.label ?? String(option?.label ?? option ?? ''),
          ),
        ));
    },
  },
}));

vi.mock('@/components/ui/Switch/index.vue', () => ({
  default: {
    name: 'Switch',
    props: ['modelValue', 'id'],
    emits: ['update:modelValue'],
    template:
      '<label :for="id"><input type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" /></label>',
  },
}));

describe('TypeMetaBar tenant and client selectors', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  function mountComponent(props: Record<string, unknown> = {}) {
    const i18n = createI18n({
      legacy: false,
      locale: 'en',
      messages: {
        en: {
          types: {
            form: {
              name: 'Name',
              tenant: 'Tenant',
              client: 'Client',
              global: 'Global',
              requireSubtasksComplete: 'Require',
            },
          },
        },
      },
    });

    const app = createApp(TypeMetaBar, {
      name: 'Example',
      tenantId: 1,
      clientId: '',
      clientOptions: [{ value: 10, label: 'Client 1' }],
      requireSubtasksComplete: false,
      showTenantSelect: true,
      ...props,
    });
    app.use(i18n);
    const container = document.createElement('div');
    const vm = app.mount(container);
    return { el: container, vm };
  }

  it('renders tenant and client dropdowns for super admins', () => {
    const tenantStore = useTenantStore();
    tenantStore.tenants = [{ id: 1, name: 'Alpha Corp' }] as any;
    const { el } = mountComponent();

    expect(el.querySelector('[data-testid="tenant-select"]')).not.toBeNull();
    expect(el.querySelector('[data-testid="client-select"]')).not.toBeNull();
  });

  it('shows locked tenant label when tenant selection is disabled', () => {
    const tenantStore = useTenantStore();
    tenantStore.tenants = [];
    const { el } = mountComponent({
      showTenantSelect: false,
      tenantId: 42,
      tenantName: 'Beta LLC',
    });

    expect(el.querySelector('[data-testid="tenant-select"]')).toBeNull();
    const display = el.querySelector('[data-testid="tenant-display"]');
    expect(display?.textContent).toContain('Beta LLC');
    expect(el.querySelector('[data-testid="client-select"]')).not.toBeNull();
  });
});
