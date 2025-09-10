import { useAuthStore } from '@/stores/auth';

/**
 * Check if the current user has the given ability.
 * Components should consult this before calling privileged endpoints.
 */
export function hasAbility(ability: string): boolean {
  return useAuthStore().can(ability);
}

export default hasAbility;
