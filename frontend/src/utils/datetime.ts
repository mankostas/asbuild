export function parseISO(value: string | number | Date): Date {
  return value instanceof Date ? value : new Date(value);
}

export function toISO(value: string | number | Date): string {
  return parseISO(value).toISOString();
}

export function formatDisplay(value: string | number | Date, options?: Intl.DateTimeFormatOptions): string {
  return parseISO(value).toLocaleString(undefined, options);
}
