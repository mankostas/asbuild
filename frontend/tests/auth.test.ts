import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import MockAdapter from 'axios-mock-adapter';
import { setActivePinia, createPinia } from 'pinia';

let api: any;
let useAuthStore: any;
let injectStorage: any;

describe('auth store', () => {
  let mock: MockAdapter;
  let memory: { accessToken: string; refreshToken: string };
  let auth: any;

  beforeEach(async () => {
    vi.resetModules();
    const store: Record<string, string> = {};
    // @ts-ignore
    globalThis.localStorage = {
      getItem: (k: string) => (k in store ? store[k] : null),
      setItem: (k: string, v: string) => (store[k] = v),
      removeItem: (k: string) => delete store[k],
    };

    globalThis.window = {
      location: { href: '', pathname: '', search: '', hash: '' },
    } as any;

    ({ default: api } = await import('../src/services/api'));
    ({ useAuthStore } = await import('../src/stores/auth'));
    ({ injectStorage } = await import('../src/services/authStorage'));

    setActivePinia(createPinia());
    mock = new MockAdapter(api);
    mock.onGet('/sanctum/csrf-cookie').reply(204);
    memory = { accessToken: '', refreshToken: '' };
    injectStorage({
      getAccessToken: () => memory.accessToken,
      setAccessToken: (t: string) => (memory.accessToken = t || ''),
      getRefreshToken: () => memory.refreshToken,
      setRefreshToken: (t: string) => (memory.refreshToken = t || ''),
    });
    auth = useAuthStore();
  });

  afterEach(() => {
    mock.restore();
  });

  it('logs in and stores tokens', async () => {
    mock.onPost('/auth/login').reply(200, {
      access_token: 'access',
      refresh_token: 'refresh',
    });
    mock.onGet('/me').reply(200, { user: { id: 1 } });

    await auth.login({ email: 'a', password: 'b' });

    expect(auth.accessToken).toBe('access');
    expect(auth.refreshToken).toBe('refresh');
    expect(auth.user).toEqual({ id: 1 });
    expect(api.defaults.headers.common['Authorization']).toBe('Bearer access');
    expect(memory.accessToken).toBe('access');
    expect(memory.refreshToken).toBe('refresh');
  });

  it('sends authorization header on subsequent requests', async () => {
    mock.onPost('/auth/login').reply(200, {
      access_token: 'token',
      refresh_token: 'ref',
    });

    mock.onGet('/me').reply(200, { user: { id: 1 } });

    await auth.login({ email: 'a', password: 'b' });

    mock.onGet('/protected').reply((config) => {
      expect(config.headers['Authorization']).toBe('Bearer token');
      return [200, { ok: true }];
    });

    const { data } = await api.get('/protected');
    expect(data.ok).toBe(true);
  });

  it('redirects to login when refresh token is invalid', async () => {
    mock.onPost('/auth/login').reply(200, {
      access_token: 'token',
      refresh_token: 'ref',
    });
    mock.onGet('/me').reply(200, { user: { id: 1 } });

    await auth.login({ email: 'a', password: 'b' });

    mock.onGet('/protected').reply(401);
    mock.onPost('/auth/refresh').reply(401);

    const loc: any = {
      href: '/dashboard',
      pathname: '/dashboard',
      search: '',
      hash: '',
    };
    window.location = loc;

    const logoutSpy = vi.spyOn(auth, 'logout');

    await api.get('/protected').catch(() => {});

    expect(logoutSpy).toHaveBeenCalled();
    expect(window.location.href).toBe('/auth/login?redirect=%2Fdashboard');
  });
});

