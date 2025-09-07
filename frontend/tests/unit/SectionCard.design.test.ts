/** @vitest-environment jsdom */
import { describe, it, expect, vi } from 'vitest';
import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';
import SectionCard from '@/components/tasks/SectionCard.vue';

vi.mock('@/components/tasks/AssigneePicker.vue', () => ({
  default: { name: 'AssigneePicker', template: '<div />' },
}));
vi.mock('@/components/fields/ReviewerPicker.vue', () => ({
  default: { name: 'ReviewerPicker', template: '<div />' },
}));
vi.mock('@/components/fields/RichText.vue', () => ({
  default: { name: 'RichText', template: '<div />' },
}));
vi.mock('@/components/fields/MarkdownInput.vue', () => ({
  default: { name: 'MarkdownInput', template: '<div />' },
}));
vi.mock('@/components/fields/PhotoUpload.vue', () => ({
  default: { name: 'PhotoUpload', template: '<div />' },
}));
vi.mock('@/components/fields/PhotoRepeater.vue', () => ({
  default: { name: 'PhotoRepeater', template: '<div />' },
}));
vi.mock('@/components/fields/ChipsInput.vue', () => ({
  default: { name: 'ChipsInput', template: '<div />' },
}));
vi.mock('@/components/fields/RadioGroup.vue', () => ({
  default: { name: 'RadioGroup', template: '<div />' },
}));
vi.mock('@/components/fields/CheckboxGroup.vue', () => ({
  default: { name: 'CheckboxGroup', template: '<div />' },
}));
vi.mock('@/components/fields/DateInput.vue', () => ({
  default: { name: 'DateInput', template: '<div />' },
}));
vi.mock('@/components/fields/TimeInput.vue', () => ({
  default: { name: 'TimeInput', template: '<div />' },
}));
vi.mock('@/components/fields/DateTimeInput.vue', () => ({
  default: { name: 'DateTimeInput', template: '<div />' },
}));
vi.mock('@/components/fields/DurationInput.vue', () => ({
  default: { name: 'DurationInput', template: '<div />' },
}));
vi.mock('@/components/ui/Tabs/index.vue', () => ({
  default: { name: 'UiTabs', template: '<div><slot name="list" /><slot name="panel" /></div>' },
}));
vi.mock('@headlessui/vue', () => ({
  Tab: { name: 'Tab', template: '<div><slot /></div>' },
  TabPanel: { name: 'TabPanel', template: '<div><slot /></div>' },
}));
vi.mock('@/services/uploader', () => ({
  uploadFile: vi.fn(),
}));

const i18n = createI18n({ locale: 'en', messages: { en: {}, el: {} } });

describe('SectionCard design settings', () => {
  it('applies font size to label', () => {
    const section = {
      label: { en: 'Section', el: 'Section' },
      fields: [
        {
          key: 'name',
          label: { en: 'Name', el: 'Name' },
          type: 'text',
          'x-styles': { fontSize: 'text-lg' },
        },
      ],
    } as any;

    const app = createApp(SectionCard, {
      section,
      form: {},
      errors: {},
      taskId: 1,
      readonly: false,
      visible: new Set(),
      required: new Set(),
      showTargets: new Set(),
    });
    app.use(i18n);
    const div = document.createElement('div');
    app.mount(div);

    const label = div.querySelector('span');
    expect(label?.className).toContain('text-lg');
  });
});

