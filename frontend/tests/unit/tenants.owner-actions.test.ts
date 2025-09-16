/** @vitest-environment jsdom */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

const { notifySuccess, notifyError, swalFire, swalShowValidationMessage } = vi.hoisted(() => ({
  notifySuccess: vi.fn(),
  notifyError: vi.fn(),
  swalFire: vi.fn(),
  swalShowValidationMessage: vi.fn(),
}));

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@/plugins/notify', () => ({
  useNotify: () => ({ success: notifySuccess, error: notifyError }),
}));

vi.mock('sweetalert2', () => ({
  __esModule: true,
  default: {
    fire: swalFire,
    showValidationMessage: swalShowValidationMessage,
  },
}));

import TenantsList from '@/views/tenants/TenantsList.vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';

function createSetup() {
  const ctx = { attrs: {}, slots: {}, emit: () => {}, expose: () => {} };
  return (TenantsList as any).setup?.(undefined, ctx) as any;
}

describe('TenantsList owner management actions', () => {
  let getSpy: ReturnType<typeof vi.spyOn>;
  let postSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    setActivePinia(createPinia());
    notifySuccess.mockReset();
    notifyError.mockReset();
    swalFire.mockReset();
    swalShowValidationMessage.mockReset();

    const auth = useAuthStore();
    auth.abilities = ['tenants.manage'];
    auth.user = { roles: [] } as any;

    const tenantStore = useTenantStore();
    tenantStore.tenants = [];
    tenantStore.currentTenantId = '' as any;
    tenantStore.loadTenants = vi.fn().mockResolvedValue(undefined) as any;

    getSpy = vi.spyOn(api, 'get').mockImplementation((url: string) => {
      if (url === '/tenants') {
        return Promise.resolve({
          data: {
            data: [
              {
                id: 1,
                name: 'Tenant One',
                feature_count: null,
                features_count: null,
                features: null,
                phone: null,
                address: null,
              },
            ],
            meta: { last_page: 1 },
          },
        } as any);
      }
      if (url === '/tenants/1/owner') {
        return Promise.resolve({ data: { data: { id: 99, email: 'owner@example.com' } } } as any);
      }
      return Promise.resolve({ data: { data: [] } } as any);
    });
    postSpy = vi.spyOn(api, 'post').mockResolvedValue({ data: {} } as any);
  });

  afterEach(() => {
    getSpy.mockRestore();
    postSpy.mockRestore();
    vi.clearAllMocks();
  });

  it('resends tenant owner invite', async () => {
    const setup = createSetup();

    await setup.resendOwnerInvite(3);

    expect(postSpy).toHaveBeenCalledWith('/tenants/3/owner/invite-resend');
    expect(notifySuccess).toHaveBeenCalledWith('tenants.owner.inviteResent');
  });

  it('sends tenant owner password reset email', async () => {
    const setup = createSetup();

    await setup.sendOwnerPasswordReset(4);

    expect(postSpy).toHaveBeenCalledWith('/tenants/4/owner/password-reset');
    expect(notifySuccess).toHaveBeenCalledWith('tenants.owner.passwordReset.success');
  });

  it('submits tenant owner email reset', async () => {
    swalFire.mockResolvedValue({ isConfirmed: true, value: 'updated@example.com' });

    const setup = createSetup();
    setup.all.value = [
      {
        id: 5,
        name: 'Tenant Five',
        owner: { id: 6, email: 'old@example.com' },
      },
    ];

    await setup.resetOwnerEmail(5);

    expect(swalFire).toHaveBeenCalled();
    expect(postSpy).toHaveBeenCalledWith('/tenants/5/owner/email-reset', { email: 'updated@example.com' });
    expect(notifySuccess).toHaveBeenCalledWith('tenants.owner.resetEmail.success');
  });
});
