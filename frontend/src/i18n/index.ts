import { createI18n } from 'vue-i18n';
import el from './el.json';
import en from './en.json';

const i18n = createI18n({
  legacy: false,
  locale: 'el',
  fallbackLocale: 'en',
  messages: {
    el,
    en,
  },
});

export default i18n;
