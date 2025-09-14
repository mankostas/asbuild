import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import MockAdapter from 'axios-mock-adapter';
import { setActivePinia, createPinia } from 'pinia';

let api: any;
let TENANT_HEADER: any;
let useTenantStore: any;

describe('tenant switching', () => {
  let mock: MockAdapter;

  beforeEach(async () => {
    vi.resetModules();
    const store: Record<string, string> = {};
    // @ts-ignore
    globalThis.localStorage = {
      getItem: (k: string) => (k in store ? store[k] : null),
      setItem: (k: string, v: string) => (store[k] = v),
      removeItem: (k: string) => delete store[k],
    };

    ({ default: api } = await import('../src/services/api'));
    ({ TENANT_HEADER } = await import('../src/config/app'));
    ({ useTenantStore } = await import('../src/stores/tenant'));

    setActivePinia(createPinia());
    mock = new MockAdapter(api);
  });

  afterEach(() => {
    mock.restore();
  });

  it('attaches tenant header and exposes abilities on switch', async () => {
    mock.onGet('/tenants').reply(200, {
      data: [
        { id: 1, feature_abilities: { tasks: ['create'] } },
        { id: 2, feature_abilities: { tasks: ['edit'] } },
      ],
      meta: {},
    });

    const store = useTenantStore();
    await store.loadTenants();

    // switch to tenant 1
    store.setTenant('1');
    mock.onGet('/check').reply((config) => {
      expect(config.headers[TENANT_HEADER]).toBe('1');
      return [200, { ok: true }];
    });
    await api.get('/check');
    expect(store.tenantAllowedAbilities('1')).toEqual({ tasks: ['create'] });

    // switch to tenant 2
    store.setTenant('2');
    mock.onGet('/check').reply((config) => {
      expect(config.headers[TENANT_HEADER]).toBe('2');
      return [200, { ok: true }];
    });
    await api.get('/check');
    expect(store.tenantAllowedAbilities('2')).toEqual({ tasks: ['edit'] });
  });
});
