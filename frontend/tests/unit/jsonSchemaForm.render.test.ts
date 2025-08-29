/** @vitest-environment jsdom */
import { describe, it, expect, vi } from 'vitest';
import { createApp } from 'vue';
import JsonSchemaForm from '@/components/forms/JsonSchemaForm.vue';

vi.mock('@/components/tasks/AssigneePicker.vue', () => ({
  default: { name: 'AssigneePicker', template: '<div class="assignee-picker"></div>' },
}));
vi.mock('@/components/tasks/PhotoField.vue', () => ({
  default: { name: 'PhotoField', template: '<div class="photo-field"></div>' },
}));
vi.mock('@/components/tasks/PhotoRepeater.vue', () => ({
  default: { name: 'PhotoRepeater', template: '<div class="photo-repeater"></div>' },
}));

describe('JsonSchemaForm', () => {
  it('renders inputs for all field types', () => {
    const schema = {
      sections: [
        {
          key: 'main',
          label: 'Main',
          fields: [
            { key: 't', label: 'T', type: 'text' },
            { key: 'ta', label: 'Ta', type: 'textarea' },
            { key: 'n', label: 'N', type: 'number' },
            { key: 'd', label: 'D', type: 'date' },
            { key: 'ti', label: 'Ti', type: 'time' },
            { key: 'dt', label: 'DT', type: 'datetime' },
            { key: 'b', label: 'B', type: 'boolean' },
            { key: 's', label: 'S', type: 'select', enum: ['a'] },
            { key: 'm', label: 'M', type: 'multiselect', enum: ['a'] },
            { key: 'a', label: 'A', type: 'assignee' },
            { key: 'f', label: 'F', type: 'file' },
          ],
          photos: [],
        },
      ],
    };
    const app = createApp(JsonSchemaForm, { schema, modelValue: {} });
    const div = document.createElement('div');
    app.mount(div);
    expect(div.querySelector('input[type="text"]')).toBeTruthy();
    expect(div.querySelector('textarea')).toBeTruthy();
    expect(div.querySelector('input[type="number"]')).toBeTruthy();
    expect(div.querySelector('input[type="date"]')).toBeTruthy();
    expect(div.querySelector('input[type="time"]')).toBeTruthy();
    expect(div.querySelector('input[type="datetime-local"]')).toBeTruthy();
    expect(div.querySelector('input[type="checkbox"]')).toBeTruthy();
    expect(div.querySelector('select')).toBeTruthy();
    expect(div.querySelector('input[type="file"]')).toBeTruthy();
    expect(div.querySelector('.assignee-picker')).toBeTruthy();
  });
});
