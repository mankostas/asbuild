/** @vitest-environment jsdom */
import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { h, defineComponent, nextTick } from 'vue';
import TenantsTable from '@/components/tenants/TenantsTable.vue';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@/stores/auth', () => ({
  can: (ability: string) =>
    ['tenants.view', 'tenants.manage', 'tenants.delete'].includes(ability),
}));

vi.mock('@headlessui/vue', () => {
  const stub = (name: string) =>
    defineComponent({
      name,
      props: { as: { type: String, default: 'div' } },
      setup(_, { slots }) {
        return () => h('div', slots.default?.());
      },
    });

  return {
    Menu: stub('Menu'),
    MenuButton: stub('MenuButton'),
    MenuItems: stub('MenuItems'),
    MenuItem: stub('MenuItem'),
  };
});

vi.mock('vue-good-table-next', () => {
  const component = defineComponent({
    name: 'VueGoodTable',
    props: {
      columns: { type: Array, default: () => [] },
      rows: { type: Array, default: () => [] },
    },
    emits: ['on-page-change', 'on-per-page-change', 'on-sort-change', 'selected-rows-change'],
    setup(props, { slots, emit }) {
      return () =>
        h('div', { class: 'vue-good-table-stub' }, [
          slots.default?.({ rows: props.rows, columns: props.columns }),
          slots['selected-row-actions']?.({}),
          slots['pagination-bottom']?.({
            pageChanged: (payload: any) => emit('on-page-change', payload),
            perPageChanged: (payload: any) => emit('on-per-page-change', payload),
          }),
        ]);
    },
  });
  return { VueGoodTable: component };
});

describe('TenantsTable remote interactions', () => {
  function mountComponent() {
    const InputGroupStub = defineComponent({
      name: 'InputGroup',
      props: { modelValue: { type: String, default: '' } },
      emits: ['update:modelValue'],
      setup(props, { emit }) {
        return () =>
          h('input', {
            class: 'input-group-stub',
            value: props.modelValue ?? '',
            onInput: (event: Event) =>
              emit('update:modelValue', (event.target as HTMLInputElement).value),
          });
      },
    });

    const SelectStub = defineComponent({
      name: 'Select',
      props: {
        modelValue: { type: [String, Number], default: undefined },
        options: { type: Array, default: () => [] },
      },
      emits: ['update:modelValue', 'update:model-value'],
      setup(props, { emit }) {
        return () =>
          h(
            'select',
            {
              class: 'select-stub',
              value: props.modelValue as any,
              onChange: (event: Event) => {
                const value = (event.target as HTMLSelectElement).value;
                emit('update:modelValue', value);
                emit('update:model-value', value);
              },
            },
            (props.options as Array<{ value: unknown; label: string }>).map((option) =>
              h('option', { value: option.value as any }, option.label),
            ),
          );
      },
    });

    const slotSpy = vi.fn();

    const wrapper = mount(TenantsTable, {
      props: {
        rows: [
          {
            id: 1,
            name: 'Alpha',
            feature_count: 3,
            archived_at: null,
            deleted_at: null,
          },
          {
            id: 2,
            name: 'Beta',
            archived_at: '2024-01-01',
            deleted_at: null,
          },
        ],
        total: 2,
        page: 1,
        perPage: 10,
        search: '',
        sort: 'name',
        direction: 'asc',
        selectable: true,
      },
      slots: {
        'selected-row-actions': (slotProps: Record<string, unknown>) => {
          slotSpy(slotProps);
          return h('div', { class: 'slot-content' });
        },
      },
      global: {
        stubs: {
          Card: { template: '<div><slot /></div>' },
          Breadcrumbs: { template: '<nav />' },
          InputGroup: InputGroupStub,
          Dropdown: { template: '<div><slot /><slot name="menus" /></div>' },
          Icon: { template: '<span />' },
          Pagination: { template: '<div class="pagination-stub" />' },
          Select: SelectStub,
          Badge: { template: '<span><slot /></span>' },
        },
        mocks: {
          $route: { meta: { hide: false } },
        },
      },
    });

    return { wrapper, slotSpy };
  }

  it('emits remote update events for pagination, sorting, search, and selection', async () => {
    const { wrapper, slotSpy } = mountComponent();

    const searchInput = wrapper.find('input');
    await searchInput.setValue('Gamma');
    expect(wrapper.emitted('update:search')?.[0]).toEqual(['Gamma']);

    await wrapper.setProps({ search: 'Updated' });
    await nextTick();
    expect((wrapper.find('input').element as HTMLInputElement).value).toBe('Updated');

    const table = wrapper.findComponent({ name: 'VueGoodTable' });
    table.vm.$emit('on-page-change', { currentPage: 3 });
    await nextTick();
    expect(wrapper.emitted('update:page')?.[0]).toEqual([3]);

    table.vm.$emit('on-per-page-change', { currentPerPage: 25, currentPage: 2 });
    await nextTick();
    expect(wrapper.emitted('update:per-page')?.[0]).toEqual([25]);
    expect(wrapper.emitted('update:page')?.[1]).toEqual([2]);

    table.vm.$emit('on-sort-change', [{ field: 'name', type: 'desc' }]);
    await nextTick();
    expect(wrapper.emitted('update:sort')?.[0]).toEqual([{ sort: 'name', direction: 'desc' }]);

    table.vm.$emit('selected-rows-change', {
      selectedRows: [{ id: 1 }, { id: 2 }],
    });
    await nextTick();
    expect(wrapper.emitted('selection-change')?.[0]).toEqual([[1, 2]]);
    expect(slotSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        selectedIds: [1, 2],
        archivableIds: [1],
      }),
    );

    const perPageSelect = wrapper.find('select');
    await perPageSelect.setValue('50');
    await nextTick();
    const perPageEvents = wrapper.emitted('update:per-page') ?? [];
    expect(perPageEvents[1]).toEqual([50]);
    const pageEvents = wrapper.emitted('update:page') ?? [];
    expect(pageEvents[2]).toEqual([1]);
  });
});
