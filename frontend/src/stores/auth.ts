import { defineStore } from 'pinia';
import api, { registerAuthStore } from '@/services/api';
import { useThemeSettingsStore } from './themeSettings';
import { useTenantStore } from '@/stores/tenant';
import { TENANTS_KEY } from '@/config/app';
import {
  getAccessToken,
  getRefreshToken,
  setTokens,
  clearTokens,
} from '@/services/authStorage';

const initialAccess = getAccessToken();
const initialRefresh = getRefreshToken();
if (initialAccess) {
  api.defaults.headers.common['Authorization'] = `Bearer ${initialAccess}`;
}

const CLIENT_SEGMENT = 'client';

function expandAbilityVariants(ability: string): string[] {
  if (!ability || !ability.includes('.')) {
    return [ability];
  }

  const parts = ability.split('.');
  const feature = parts[0];
  const rest = parts.slice(1);
  if (!feature || rest.length === 0) {
    return [ability];
  }

  const variants = new Set<string>([ability]);

  if (rest[0] === CLIENT_SEGMENT) {
    const suffix = rest.slice(1).join('.');
    if (suffix) {
      variants.add(`${feature}.${suffix}`);
    }
  } else {
    const suffix = rest.join('.');
    variants.add(`${feature}.${CLIENT_SEGMENT}.${suffix}`);
  }

  variants.add(`${feature}.manage`);
  variants.add(`${feature}.${CLIENT_SEGMENT}.manage`);

  return Array.from(variants);
}

function abilitySatisfied(
  ability: string,
  abilities: string[],
  clientAbilities: string[],
): boolean {
  return expandAbilityVariants(ability).some(
    (candidate) => abilities.includes(candidate) || clientAbilities.includes(candidate),
  );
}

function toClientAbility(ability: string): string {
  if (!ability.includes('.')) {
    return ability;
  }
  const parts = ability.split('.');
  if (parts.length < 2) {
    return ability;
  }
  if (parts[1] === CLIENT_SEGMENT) {
    return ability;
  }
  return [parts[0], CLIENT_SEGMENT, ...parts.slice(1)].join('.');
}

interface LoginPayload {
  email: string;
  password: string;
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as any,
    accessToken: initialAccess as string | null,
    refreshToken: initialRefresh as string | null,
    impersonatedTenant: localStorage.getItem('impersonatingTenant') || '',
    impersonator: JSON.parse(localStorage.getItem('impersonator') || 'null') as any,
    abilities: [] as string[],
    clientAbilities: [] as string[],
    features: [] as string[],
    permittedClientIds: [] as string[],
  }),
  getters: {
    isAuthenticated: (state) => !!state.accessToken,
    isImpersonating: (state) => !!state.impersonatedTenant,
    isSuperAdmin: (state) =>
      state.abilities.includes('*') ||
      state.user?.roles?.some(
        (r: any) => r.name === 'SuperAdmin' || r.slug === 'super_admin',
      ) || false,
    can(state) {
      return (ability: string) => {
        if (this.isSuperAdmin) {
          return true;
        }
        return abilitySatisfied(ability, state.abilities, state.clientAbilities);
      };
    },
    hasAny(state) {
      return (abilities: string[]) => {
        if (abilities.length === 0 || this.isSuperAdmin) {
          return true;
        }
        return abilities.some((ability) =>
          abilitySatisfied(ability, state.abilities, state.clientAbilities),
        );
      };
    },
    hasAll(state) {
      return (abilities: string[]) => {
        if (abilities.length === 0 || this.isSuperAdmin) {
          return true;
        }
        return abilities.every((ability) =>
          abilitySatisfied(ability, state.abilities, state.clientAbilities),
        );
      };
    },
    canClient(state) {
      return (ability: string) => {
        if (this.isSuperAdmin) {
          return true;
        }
        return abilitySatisfied(toClientAbility(ability), state.abilities, state.clientAbilities);
      };
    },
    userId: (state) => state.user?.id,
    allowedClientParams(state) {
      return <T extends Record<string, any>>(params: T = {} as T) => {
        if (!state.permittedClientIds?.length) {
          return { ...params };
        }
        return {
          ...params,
          client_ids: [...state.permittedClientIds],
        } as T & { client_ids: string[] };
      };
    },
  },
  actions: {
    async login(payload: LoginPayload) {
      const { data } = await api.post('/auth/login', payload);
      if (data.access_token) {
        this.accessToken = data.access_token;
        this.refreshToken = data.refresh_token;
        setTokens(data.access_token, data.refresh_token);
        api.defaults.headers.common['Authorization'] =
          `Bearer ${data.access_token}`;
        await this.fetchUser();
        await useThemeSettingsStore().load();
      }
    },
    async fetchUser() {
      const { data } = await api.get('/me');
      this.user = data.user;
      this.abilities = data.abilities || [];
      this.clientAbilities = data.client_abilities || [];
      this.features = data.features || [];
      this.permittedClientIds = Array.isArray(data.permitted_client_ids)
        ? data.permitted_client_ids.map((id: string | number) => String(id))
        : [];
      const tenantStore = useTenantStore();
      const tenantId = data.user?.tenant_id || '';
      tenantStore.setTenant(this.isSuperAdmin ? '' : tenantId);
      if (this.isSuperAdmin || this.isImpersonating) {
        await tenantStore.loadTenants();
      }
    },
    async logout(skipServer = false) {
      if (!skipServer) {
        try {
          await api.post('/auth/logout');
        } catch (e) {}
      }
      this.accessToken = '';
      this.refreshToken = '';
      this.user = null;
      this.abilities = [];
      this.clientAbilities = [];
      this.permittedClientIds = [];
      clearTokens();
      delete api.defaults.headers.common['Authorization'];
      this.impersonatedTenant = '';
      localStorage.removeItem('impersonatingTenant');
      this.impersonator = null;
      localStorage.removeItem('impersonator');
      localStorage.removeItem(TENANTS_KEY);
      const tenantStore = useTenantStore();
      tenantStore.setTenant('');
      tenantStore.tenants = [];
    },
    async refresh() {
      if (!this.refreshToken) return;
      const { data } = await api.post('/auth/refresh', {
        refresh_token: this.refreshToken,
      });
      this.accessToken = data.access_token;
      this.refreshToken = data.refresh_token;
      setTokens(data.access_token, data.refresh_token);
      api.defaults.headers.common['Authorization'] =
        `Bearer ${data.access_token}`;
    },
    async requestPasswordReset(email: string) {
      await api.post('/auth/password/email', { email });
    },
    async resetPassword(payload: Record<string, any>) {
      await api.post('/auth/password/reset', payload);
    },
    async impersonate(tenantId: string, tenantName: string) {
      if (!this.isImpersonating) {
        localStorage.setItem('impersonator', JSON.stringify(this.user));
        localStorage.setItem(
          'impersonatorAccessToken',
          this.accessToken || '',
        );
        localStorage.setItem(
          'impersonatorRefreshToken',
          this.refreshToken || '',
        );
        this.impersonator = this.user;
      }
      const { data } = await api.post(`/tenants/${tenantId}/impersonate`);
      this.accessToken = data.access_token;
      this.refreshToken = data.refresh_token;
      setTokens(data.access_token, data.refresh_token);
      api.defaults.headers.common['Authorization'] =
        `Bearer ${data.access_token}`;
      this.impersonatedTenant = tenantName;
      localStorage.setItem('impersonatingTenant', tenantName);
      await this.fetchUser();
    },
    async unimpersonate() {
      const access = localStorage.getItem('impersonatorAccessToken');
      const refresh = localStorage.getItem('impersonatorRefreshToken');
      const userJson = localStorage.getItem('impersonator');
      if (access && refresh && userJson) {
        this.accessToken = access;
        this.refreshToken = refresh;
        setTokens(access, refresh);
        api.defaults.headers.common['Authorization'] = `Bearer ${access}`;
        this.user = JSON.parse(userJson);
      } else {
        await this.logout();
      }
      this.impersonatedTenant = '';
      this.impersonator = null;
      localStorage.removeItem('impersonatingTenant');
      localStorage.removeItem('impersonator');
      localStorage.removeItem('impersonatorAccessToken');
      localStorage.removeItem('impersonatorRefreshToken');
      if (access && refresh && userJson) {
        await this.fetchUser();
      }
    },
  },
});

export function can(ability: string): boolean {
  return useAuthStore().can(ability);
}

export function hasAny(abilities: string[]): boolean {
  return useAuthStore().hasAny(abilities);
}

export function hasAll(abilities: string[]): boolean {
  return useAuthStore().hasAll(abilities);
}

export function hasFeature(feature: string): boolean {
  return useAuthStore().features.includes(feature);
}

export function userId(): string | number | undefined {
  return useAuthStore().userId;
}

export function canClient(ability: string): boolean {
  return useAuthStore().canClient(ability);
}

export function allowedClientParams<T extends Record<string, any>>(params?: T) {
  return useAuthStore().allowedClientParams(params ?? ({} as T));
}

registerAuthStore(() => useAuthStore());
