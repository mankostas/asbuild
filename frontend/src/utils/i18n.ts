export type I18nString = string | { en?: string; el?: string };

export function resolveI18n(val: I18nString | undefined, locale: string): string {
  if (!val) return '';
  if (typeof val === 'string') return val;
  return val[locale as 'en' | 'el'] || val.en || val.el || '';
}
