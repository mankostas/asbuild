import { createHash } from 'node:crypto';

const CROCKFORD_BASE32 = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
const ULID_LENGTH = 26;
const ULID_PREFIX = '01';

/**
 * Generates a deterministic ULID-like identifier for tests based on a label.
 * The prefix ensures the string resembles Laravel's ULID output while
 * subsequent characters are derived from the label hash for stability.
 */
export function fakePublicId(label: string): string {
  const hash = createHash('sha256').update(label).digest();
  let identifier = ULID_PREFIX;
  let index = 0;

  while (identifier.length < ULID_LENGTH) {
    const byte = hash[index % hash.length];
    identifier += CROCKFORD_BASE32[byte % CROCKFORD_BASE32.length];
    index += 1;
  }

  return identifier;
}

export function fakeTenantId(label: string): string {
  return fakePublicId(`tenant:${label}`);
}

export function fakeUserId(label: string): string {
  return fakePublicId(`user:${label}`);
}

export function fakeRoleId(label: string): string {
  return fakePublicId(`role:${label}`);
}

export function fakeTaskId(label: string): string {
  return fakePublicId(`task:${label}`);
}

export function fakeClientId(label: string): string {
  return fakePublicId(`client:${label}`);
}

export function fakeStatusId(label: string): string {
  return fakePublicId(`status:${label}`);
}
