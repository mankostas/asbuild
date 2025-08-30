export interface FieldValidations {
  required?: boolean;
  unique?: boolean;
  regex?: string;
  min?: number;
  max?: number;
  lengthMin?: number;
  lengthMax?: number;
  mime?: string[];
  size?: number; // bytes
}

export function validate(value: any, rules: FieldValidations = {}): string | null {
  if (rules.required) {
    if (
      value === undefined ||
      value === null ||
      value === '' ||
      (Array.isArray(value) && value.length === 0)
    ) {
      return 'Required';
    }
  }
  if (value !== undefined && value !== null && value !== '') {
    if (rules.regex && typeof value === 'string') {
      try {
        const re = new RegExp(rules.regex);
        if (!re.test(value)) return 'Invalid format';
      } catch {
        /* invalid regex ignored */
      }
    }
    if (typeof value === 'number') {
      if (rules.min !== undefined && value < rules.min) return `Min ${rules.min}`;
      if (rules.max !== undefined && value > rules.max) return `Max ${rules.max}`;
    }
    if (typeof value === 'string') {
      if (rules.lengthMin !== undefined && value.length < rules.lengthMin)
        return `Min length ${rules.lengthMin}`;
      if (rules.lengthMax !== undefined && value.length > rules.lengthMax)
        return `Max length ${rules.lengthMax}`;
    }
    if (rules.mime && value && typeof value === 'object' && 'mime' in value) {
      if (!rules.mime.includes((value as any).mime)) return 'Invalid file type';
    }
    if (rules.size && value && typeof value === 'object' && 'size' in value) {
      if ((value as any).size > rules.size) return 'File too large';
    }
  }
  return null;
}
