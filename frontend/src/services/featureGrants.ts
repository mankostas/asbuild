import api from './api';
import type { components } from '@/types/api';

function abilityHash(abilities: string[]): string {
  const str = abilities.slice().sort().join(',');
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    const chr = str.charCodeAt(i);
    hash = (hash << 5) - hash + chr;
    hash |= 0;
  }
  return Math.abs(hash).toString(36);
}

export async function ensureHiddenRole(
  tenantId: string,
  feature: string,
  abilities: string[],
): Promise<components['schemas']['Role']> {
  const hash = abilityHash(abilities);
  const slug = `__fg__${tenantId}__${feature}__${hash}`;
  const { data } = await api.get('/roles', {
    params: { slug, tenant_id: tenantId, per_page: 1 },
  });
  const existing = (data.data || []).find((r: any) => r.slug === slug);
  if (existing) return existing;
  const payload = {
    name: slug,
    slug,
    abilities,
    tenant_id: tenantId,
    level: 1,
  };
  const res = await api.post('/roles', payload);
  return res.data;
}

export async function assignHiddenRoleToUser(
  userId: string,
  roleId: number,
): Promise<void> {
  await api.post(`/roles/${roleId}/assign`, { user_id: userId });
}

export async function removeHiddenRoleFromUser(
  userId: string,
  roleId: number,
): Promise<void> {
  await api.delete(`/roles/${roleId}/assign`, { data: { user_id: userId } });
}

export async function assignHiddenRoleToTeam(
  teamId: string,
  roleId: number,
): Promise<void> {
  await api.post(`/roles/${roleId}/assign`, { team_id: teamId });
}

export async function removeHiddenRoleFromTeam(
  teamId: string,
  roleId: number,
): Promise<void> {
  await api.delete(`/roles/${roleId}/assign`, { data: { team_id: teamId } });
}
