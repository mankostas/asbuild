/** @vitest-environment jsdom */
import { describe, it, expect, vi } from 'vitest';
import { createApp } from 'vue';
import ChartCard from '@/components/reports/ChartCard.vue';
import { createI18n } from 'vue-i18n';
import en from '@/i18n/en.json';

vi.mock('vue3-apexcharts', () => ({
  default: { name: 'apexchart', template: '<div />' },
}));

const i18n = createI18n({ locale: 'en', messages: { en } });

describe('ChartCard', () => {
  it('renders empty state', () => {
    const app = createApp(ChartCard, {
      title: 'Test',
      type: 'bar',
      series: [],
    });
    app.use(i18n);
    app.config.globalProperties.$store = {
      themeSettingsStore: { isDark: false },
    } as any;
    const div = document.createElement('div');
    app.mount(div);
    expect(div.querySelector('.animate-pulse')).toBeTruthy();
  });
  it('renders dataset', () => {
    const series = [{ label: 'A', data: [{ x: '1', y: 1 }] }];
    const app = createApp(ChartCard, { title: 'Test', type: 'bar', series });
    app.use(i18n);
    app.config.globalProperties.$store = {
      themeSettingsStore: { isDark: false },
    } as any;
    const div = document.createElement('div');
    app.mount(div);
    expect(div.querySelector('.animate-pulse')).toBeFalsy();
  });
});
