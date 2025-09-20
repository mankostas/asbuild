/** @vitest-environment jsdom */
import { describe, it, expect, vi } from 'vitest';
import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';
import CanvasSection from '@/components/types/CanvasSection.vue';
import { fakePublicId } from '../utils/publicIds';

vi.mock('vuedraggable', () => ({
  default: { name: 'draggable', template: '<div><slot /></div>' },
}));
vi.mock('@/components/ui/Icon/index.vue', () => ({
  default: { name: 'Icon', template: '<i />' },
}));
vi.mock('@/components/ui/Textinput/index.vue', () => ({
  default: { name: 'Textinput', template: '<input />' },
}));
vi.mock('@/components/ui/Button/index.vue', () => ({
  default: { name: 'Button', template: '<button><slot /></button>' },
}));
vi.mock('@/components/ui/Card/index.vue', () => ({
  default: { name: 'Card', template: '<div><slot /></div>' },
}));
vi.mock('@/components/ui/Dropdown/index.vue', () => ({
  default: { name: 'Dropdown', template: '<div><slot /><slot name="menus" /></div>' },
}));
vi.mock('@/components/ui/Select/index.vue', () => ({
  default: { name: 'Select', template: '<select><slot /></select>' },
}));
vi.mock('@/components/ui/Tabs/index.vue', () => ({
  default: { name: 'UiTabs', template: '<div><slot name="list" /><slot name="panel" /></div>' },
}));
vi.mock('@headlessui/vue', () => ({
  MenuItem: { name: 'MenuItem', template: '<div><slot /></div>' },
  Tab: { name: 'Tab', template: '<div><slot /></div>' },
  TabPanel: { name: 'TabPanel', template: '<div><slot /></div>' },
}));

describe('CanvasSection addTab', () => {
  it('moves existing fields into first tab', () => {
    const section = {
      label: { en: 'Section', el: 'Section' },
      cols: 1,
      fields: [{ id: fakePublicId('canvas-field'), key: 'a', label: { en: 'A', el: 'A' } }],
      tabs: [],
    } as any;
    const i18n = createI18n({ locale: 'en', messages: { en: {}, el: {} } });
    const app = createApp(CanvasSection, { section });
    app.use(i18n);
    const div = document.createElement('div');
    const vm = app.mount(div) as any;
    vm.$.setupState.addTab();
    expect(section.tabs).toHaveLength(1);
    expect(section.tabs[0].fields).toHaveLength(1);
    expect(section.fields).toHaveLength(0);
  });
});
