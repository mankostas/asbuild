export function messageText(val: unknown): string {
  if (val == null || val === false) return '';
  if (typeof val === 'string') return val.trim();
  if (Array.isArray(val)) {
    const flat = val
      .flat(Infinity)
      .filter(Boolean)
      .map(String)
      .map((s) => s.trim())
      .filter(Boolean);
    return flat.join('\n');
  }
  if (typeof val === 'object') {
    const flat = Object.values(val as Record<string, unknown>)
      .flat(Infinity as unknown as number)
      .filter(Boolean)
      .map(String)
      .map((s) => s.trim())
      .filter(Boolean);
    return flat.join('\n');
  }
  return String(val).trim();
}
export default { install(app: any) { app.config.globalProperties.$msg = messageText; } };
