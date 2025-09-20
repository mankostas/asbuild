/** @vitest-environment jsdom */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { fakeUserId } from '../utils/publicIds';

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

import EmployeesList from '@/views/employees/EmployeesList.vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';

function createSetup() {
  const ctx = { attrs: {}, slots: {}, emit: () => {}, expose: () => {} };
  return (EmployeesList as any).setup?.(undefined, ctx) as any;
}

const firstEmployeeId = fakeUserId('employees-first');
const secondEmployeeId = fakeUserId('employees-second');

describe('EmployeesList user actions', () => {
  let getSpy: ReturnType<typeof vi.spyOn>;
  let postSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    setActivePinia(createPinia());
    notifySuccess.mockReset();
    notifyError.mockReset();
    swalFire.mockReset();
    swalShowValidationMessage.mockReset();

    const auth = useAuthStore();
    auth.abilities = ['employees.manage'];
    auth.user = { roles: [] } as any;

    const tenantStore = useTenantStore();
    tenantStore.tenants = [];
    tenantStore.currentTenantId = '' as any;
    tenantStore.loadTenants = vi.fn().mockResolvedValue(undefined) as any;

    getSpy = vi.spyOn(api, 'get').mockResolvedValue({ data: { data: [] } } as any);
    postSpy = vi.spyOn(api, 'post').mockResolvedValue({ data: {} } as any);
  });

  afterEach(() => {
    getSpy.mockRestore();
    postSpy.mockRestore();
    vi.clearAllMocks();
  });

  it('sends reset email request with provided address', async () => {
    swalFire.mockResolvedValue({ isConfirmed: true, value: 'new@example.com' });

    const setup = createSetup();
    setup.all.value = [{ id: firstEmployeeId, email: 'old@example.com' }];

    await setup.resetEmail(firstEmployeeId);

    expect(swalFire).toHaveBeenCalled();
    expect(postSpy).toHaveBeenCalledWith(
      `/employees/${firstEmployeeId}/email-reset`,
      { email: 'new@example.com' },
      { params: {} },
    );
    expect(notifySuccess).toHaveBeenCalledWith('employees.resetEmail.success');
  });

  it('sends password reset request', async () => {
    const setup = createSetup();

    await setup.sendPasswordReset(secondEmployeeId);

    expect(postSpy).toHaveBeenCalledWith(
      `/employees/${secondEmployeeId}/password-reset`,
      {},
      { params: {} },
    );
    expect(notifySuccess).toHaveBeenCalledWith('employees.passwordReset.success');
  });
});
