/** @vitest-environment jsdom */
import { describe, it, expect, nextTick } from 'vitest';
import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';
import PrimeVue from 'primevue/config';
import en from '@/i18n/en.json';
import FormRenderer from '@/components/appointments/FormRenderer.vue';

describe('FormRenderer', () => {
  it('renders widgets based on schema', () => {
    const schema = {
      properties: {
        name: { type: 'string' },
        status: { type: 'string', enum: ['a', 'b'] },
        agree: { type: 'boolean' },
      },
    };
    const app = createApp(FormRenderer, {
      schema,
      modelValue: { name: '', status: '', agree: false },
    });
    app.use(createI18n({ legacy: false, locale: 'en', messages: { en } }));
    app.use(PrimeVue);
    const el = document.createElement('div');
    document.body.appendChild(el);
    app.mount(el);
    expect(el.querySelector('input.p-inputtext')).not.toBeNull();
    expect(el.querySelector('.p-dropdown')).not.toBeNull();
    expect(el.querySelector('.p-checkbox')).not.toBeNull();
  });

  it('validates required fields', async () => {
    const schema = {
      properties: { title: { type: 'string', title: 'appointments.form.title' } },
      required: ['title'],
    };
    const app = createApp(FormRenderer, { schema, modelValue: { title: '' } });
    app.use(createI18n({ legacy: false, locale: 'en', messages: { en } }));
    app.use(PrimeVue);
    const el = document.createElement('div');
    document.body.appendChild(el);
    app.mount(el);
    const input = el.querySelector('input') as HTMLInputElement;
    input.value = '';
    input.dispatchEvent(new Event('input'));
    await nextTick();
    expect(el.querySelector('.p-message-error')).not.toBeNull();
  });
});
