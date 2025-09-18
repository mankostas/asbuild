<template>
  <div class="p-4">
    <Card class="max-w-3xl mx-auto">
      <template #header>
        <h1 class="text-lg font-semibold">
          {{ isEdit ? t('routes.clientEdit') : t('routes.clientCreate') }}
        </h1>
      </template>
      <p class="text-slate-600 mb-6">
        {{ t('routes.clients') }}
      </p>

      <div v-if="canAccess" class="space-y-6">
        <Alert v-if="loadError" type="danger-light">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <span>{{ loadError }}</span>
            <Button type="button" btnClass="btn-dark btn-sm" @click="reloadClient">
              {{ t('actions.retry') }}
            </Button>
          </div>
        </Alert>

        <div v-if="loading" class="grid gap-3">
          <Skeleton class="h-9 w-2/3" />
          <Skeleton class="h-9 w-2/3" />
          <Skeleton class="h-9 w-1/2" />
          <Skeleton class="h-32 w-full" />
        </div>

        <form v-else class="grid gap-4" @submit.prevent="submit">
          <Textinput
            v-model="form.name"
            :label="t('clients.form.name')"
            :placeholder="t('clients.form.name')"
            :error="errors.name"
          />

          <Textinput
            v-model="form.email"
            type="email"
            :label="t('clients.form.email')"
            :placeholder="t('clients.form.email')"
            :error="errors.email"
          />

          <Textinput
            v-model="form.phone"
            :label="t('clients.form.phone')"
            :placeholder="t('clients.form.phone')"
            :error="errors.phone"
          />

          <Select
            v-if="showTenantSelect"
            v-model="form.tenantId"
            :options="tenantOptions"
            :placeholder="t('common.select')"
            :label="t('clients.form.tenant')"
            :aria-label="t('clients.form.tenant')"
            :error="errors.tenant"
          />

          <Select
            v-if="showOwnerSelect"
            v-model="form.ownerId"
            :options="ownerOptions"
            :placeholder="ownerPlaceholder"
            :label="t('clients.form.owner')"
            :aria-label="t('clients.form.owner')"
            :disabled="ownerSelectDisabled || ownerLoading"
            :error="errors.owner"
            :description="ownerDescription"
          />
          <p v-if="ownerLoadError" class="text-sm text-danger-500">
            {{ ownerLoadError }}
          </p>

          <Textarea
            v-model="form.notes"
            :label="t('clients.form.notes')"
            :placeholder="t('clients.form.notesPlaceholder')"
            :rows="4"
            :aria-label="t('clients.form.notes')"
            :error="errors.notes"
          />

          <p v-if="serverError" class="text-sm text-danger-500">
            {{ serverError }}
          </p>

          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <Button
              v-if="isEdit && canTransfer"
              type="button"
              btnClass="btn-outline-secondary"
              :text="t('actions.transferOwnership')"
              @click="openTransfer"
            />

            <div class="flex gap-3 sm:ml-auto">
              <Button
                type="button"
                btnClass="btn-outline-secondary"
                :text="t('actions.cancel')"
                @click="goBack"
              />
              <Button
                type="submit"
                btnClass="btn-dark"
                :text="isEdit ? t('clients.form.submitUpdate') : t('clients.form.submitCreate')"
                :disabled="saving"
                :loading="saving"
              />
            </div>
          </div>
        </form>
      </div>
    </Card>

    <Modal
      v-if="canTransfer"
      :active-modal="transfer.open"
      centered
      :title="t('clients.transfer.title')"
      @close="closeTransfer"
    >
      <p class="text-sm text-slate-600 dark:text-slate-300">
        {{ t('clients.transfer.description') }}
      </p>
      <div class="mt-4 space-y-4">
        <Select
          v-model="transferOwnerId"
          :options="transferOwnerOptions"
          classInput="text-sm !h-10"
          :aria-label="t('clients.transfer.ownerLabel')"
          :placeholder="t('common.select')"
          :error="transferError"
        />
      </div>
      <template #footer>
        <Button
          type="button"
          btnClass="btn-outline-secondary"
          :text="t('actions.cancel')"
          @click="closeTransfer"
        />
        <Button
          type="button"
          btnClass="btn-primary"
          :text="t('clients.transfer.submit')"
          :disabled="transfer.loading"
          :loading="transfer.loading"
          @click="submitTransfer"
        />
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useI18n } from 'vue-i18n';
import Textinput from '@/components/ui/Textinput/index.vue';
import Textarea from '@/components/ui/Textarea/index.vue';
import Select from '@/components/ui/Select/index.vue';
import Button from '@/components/ui/Button/index.vue';
import Card from '@/components/ui/Card/index.vue';
import Alert from '@/components/ui/Alert/index.vue';
import Modal from '@/components/ui/Modal/index.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { useClientsStore } from '@/stores/clients';
import { useTenantStore } from '@/stores/tenant';
import { can, useAuthStore } from '@/stores/auth';
import { useNotify } from '@/plugins/notify';
import api, { extractData, extractFormErrors } from '@/services/api';
import type { Client } from '@/services/api/clients';

const route = useRoute();
const router = useRouter();
const clientsStore = useClientsStore();
const tenantStore = useTenantStore();
const auth = useAuthStore();
const notify = useNotify();
const { t } = useI18n();

const { transfer } = storeToRefs(clientsStore);

const isEdit = computed(() => route.name === 'clients.edit');
const canAccess = computed(() =>
  isEdit.value ? can('clients.manage') : can('clients.create') || can('clients.manage'),
);
const canTransfer = computed(() => can('clients.manage'));
const isSuperAdmin = computed(() => auth.isSuperAdmin);
const isClientUser = computed(() => (auth.user as any)?.type === 'client');

const form = reactive({
  name: '',
  email: '',
  phone: '',
  notes: '',
  tenantId: '',
  ownerId: '',
});

const errors = reactive({
  name: '',
  email: '',
  phone: '',
  notes: '',
  tenant: '',
  owner: '',
});

const serverError = ref('');
const loadError = ref('');
const loading = ref(false);
const saving = ref(false);
const ownerLoading = ref(false);
const ownerLoadError = ref('');
const transferError = ref('');

const availableOwners = ref<Array<{ value: string; label: string }>>([]);
const currentClient = ref<Client | null>(null);

const tenantOptions = computed(() =>
  tenantStore.tenants.map((tenant: any) => ({
    value: String(tenant.id),
    label: tenant.name,
  })),
);

const ownerOptions = computed(() => {
  const options = availableOwners.value.map((option) => ({ ...option }));
  const hasEmpty = options.some((option) => option.value === '');
  if (!hasEmpty) {
    options.unshift({ value: '', label: t('clients.transfer.noOwner') });
  }
  return options;
});

const transferOwnerOptions = ownerOptions;

const showTenantSelect = computed(() => isSuperAdmin.value);
const showOwnerSelect = computed(() => !isClientUser.value);
const ownerSelectDisabled = computed(
  () => showOwnerSelect.value && isSuperAdmin.value && !form.tenantId,
);
const ownerDescription = computed(() => {
  if (ownerSelectDisabled.value) {
    return t('clients.form.ownerHelp');
  }
  if (ownerLoading.value) {
    return t('clients.form.loadingOwners');
  }
  return '';
});
const ownerPlaceholder = computed(() =>
  ownerSelectDisabled.value ? t('clients.form.selectTenantFirst') : t('clients.form.ownerPlaceholder'),
);

const tenantRequired = computed(() => isSuperAdmin.value && !isEdit.value);
const ownerRequired = computed(
  () => !isEdit.value && showOwnerSelect.value && !isSuperAdmin.value,
);

const transferOwnerId = computed({
  get: () =>
    transfer.value.ownerId === null || transfer.value.ownerId === undefined
      ? ''
      : String(transfer.value.ownerId),
  set: (value: string) => {
    const numeric = value === '' ? null : Number(value);
    clientsStore.setTransferOwner(
      numeric === null || Number.isNaN(numeric) ? null : numeric,
    );
    transferError.value = '';
  },
});

function formatOwnerLabel(owner: Client['owner'] | null | undefined): string {
  if (!owner) return t('clients.transfer.noOwner');
  return owner.name || owner.email || t('clients.table.unknownOwner', { id: owner.id });
}

function ensureOwnerOption(owner: Client['owner'] | null | undefined) {
  if (!owner) return;
  const value = String(owner.id);
  if (availableOwners.value.some((item) => item.value === value)) {
    return;
  }
  availableOwners.value = [
    ...availableOwners.value,
    { value, label: formatOwnerLabel(owner) },
  ];
}

function resetErrors() {
  errors.name = '';
  errors.email = '';
  errors.phone = '';
  errors.notes = '';
  errors.tenant = '';
  errors.owner = '';
  serverError.value = '';
}

function applyServerErrors(formErrors: Record<string, string[]>) {
  const format = (value?: string[] | string) => {
    if (!value) return '';
    if (Array.isArray(value)) {
      return value.filter(Boolean).join('\n');
    }
    return String(value);
  };

  errors.name = format(formErrors.name);
  errors.email = format(formErrors.email);
  errors.phone = format(formErrors.phone);
  errors.notes = format(formErrors.notes);
  errors.tenant = format(formErrors.tenant_id);
  errors.owner = format(formErrors.owner_id);
}

function validateForm(): boolean {
  resetErrors();

  if (!form.name.trim()) {
    errors.name = t('clients.form.errors.name');
  }

  if (tenantRequired.value && !form.tenantId) {
    errors.tenant = t('clients.form.errors.tenant');
  }

  if (ownerRequired.value && !form.ownerId) {
    errors.owner = t('clients.form.errors.owner');
  }

  return !errors.name && !errors.tenant && !errors.owner;
}

function resolveTenantForOwners(): string | number | null {
  if (isSuperAdmin.value) {
    return form.tenantId || null;
  }
  return tenantStore.currentTenantId || (auth.user?.tenant_id ? String(auth.user.tenant_id) : null);
}

async function loadOwners() {
  if (!showOwnerSelect.value) {
    availableOwners.value = [];
    return;
  }

  const tenantId = resolveTenantForOwners();
  if (!tenantId) {
    availableOwners.value = [];
    return;
  }

  ownerLoading.value = true;
  ownerLoadError.value = '';
  try {
    const params: Record<string, unknown> = { per_page: 100, tenant_id: tenantId };
    const response = await api.get('/employees', { params });
    const data = extractData<any[]>(response.data) || [];
    const map = new Map<string, { value: string; label: string }>();
    data.forEach((employee: any) => {
      const value = String(employee.id);
      const label = employee.name || employee.email || t('clients.table.unknownOwner', { id: employee.id });
      map.set(value, { value, label });
    });
    if (form.ownerId && !map.has(form.ownerId) && currentClient.value?.owner) {
      map.set(form.ownerId, { value: form.ownerId, label: formatOwnerLabel(currentClient.value.owner) });
    }
    availableOwners.value = Array.from(map.values());
  } catch (error: any) {
    ownerLoadError.value = error?.message || t('clients.form.ownerLoadError');
    availableOwners.value = [];
  } finally {
    ownerLoading.value = false;
  }
}

async function loadClient() {
  const idParam = route.params.id;
  if (!isEdit.value || !idParam) {
    currentClient.value = null;
    return;
  }
  const numericId = Number(idParam);
  if (!Number.isFinite(numericId)) {
    loadError.value = t('clients.form.loadError');
    return;
  }

  loading.value = true;
  loadError.value = '';
  try {
    const client = await clientsStore.get(numericId);
    if (!client) {
      loadError.value = t('clients.form.loadError');
      return;
    }
    currentClient.value = client;
    form.name = client.name || '';
    form.email = client.email || '';
    form.phone = client.phone || '';
    form.notes = client.notes || '';
    if (isSuperAdmin.value) {
      form.tenantId = client.tenant_id !== null && client.tenant_id !== undefined ? String(client.tenant_id) : '';
    }
    if (showOwnerSelect.value) {
      form.ownerId = client.owner?.id ? String(client.owner.id) : '';
    } else if (isClientUser.value && auth.user?.id) {
      form.ownerId = String(auth.user.id);
    }
    ensureOwnerOption(client.owner);
  } catch (error: any) {
    loadError.value = error?.message || t('clients.form.loadError');
  } finally {
    loading.value = false;
  }
}

async function reloadClient() {
  await loadClient();
  await loadOwners();
}

async function submit() {
  if (!validateForm()) {
    return;
  }

  saving.value = true;
  try {
    const payload: Record<string, any> = {
      name: form.name.trim(),
      email: form.email || null,
      phone: form.phone || null,
      notes: form.notes || null,
    };

    if (showOwnerSelect.value) {
      payload.owner_id = form.ownerId ? Number(form.ownerId) : null;
    }
    if (isSuperAdmin.value) {
      if (form.tenantId) {
        payload.tenant_id = form.tenantId;
      }
    }

    const client = isEdit.value
      ? await clientsStore.update(route.params.id as string | number, payload)
      : await clientsStore.create(payload);

    const message = isEdit.value
      ? t('clients.form.success.updated')
      : t('clients.form.success.created');
    notify.success(message);
    currentClient.value = client;
    router.push({ name: 'clients.list' });
  } catch (error: any) {
    const formErrors = extractFormErrors(error);
    if (Object.keys(formErrors).length) {
      applyServerErrors(formErrors);
      return;
    }
    serverError.value = error?.message || t('common.error');
  } finally {
    saving.value = false;
  }
}

function openTransfer() {
  if (!currentClient.value) return;
  const ownerId = form.ownerId ? Number(form.ownerId) : null;
  clientsStore.openTransfer(currentClient.value.id, ownerId);
  transferError.value = '';
}

function closeTransfer() {
  clientsStore.closeTransfer();
  transferError.value = '';
}

async function submitTransfer() {
  transferError.value = '';
  try {
    const client = await clientsStore.submitTransfer();
    form.ownerId = client.owner?.id ? String(client.owner.id) : '';
    ensureOwnerOption(client.owner);
    notify.success(t('clients.form.success.transfer'));
    await loadOwners();
  } catch (error: any) {
    const formErrors = extractFormErrors(error);
    if (formErrors.owner_id) {
      const message = Array.isArray(formErrors.owner_id)
        ? formErrors.owner_id.join('\n')
        : String(formErrors.owner_id);
      transferError.value = message;
      return;
    }
    notify.error(error?.message || t('common.error'));
  }
}

function goBack() {
  router.back();
}

watch(
  () => form.tenantId,
  (value, oldValue) => {
    if (!showOwnerSelect.value) return;
    if (value === oldValue) return;
    if (!value) {
      availableOwners.value = [];
      if (!isEdit.value) {
        form.ownerId = '';
      }
      errors.tenant = '';
      return;
    }
    if (!isEdit.value) {
      form.ownerId = '';
    }
    errors.tenant = '';
    loadOwners();
  },
);

watch(
  () => form.ownerId,
  () => {
    if (errors.owner) {
      errors.owner = '';
    }
  },
);

watch(
  () => form.name,
  () => {
    if (errors.name) {
      errors.name = '';
    }
  },
);

onMounted(async () => {
  if (!canAccess.value) return;
  if (isSuperAdmin.value) {
    await tenantStore.loadTenants({ per_page: 100 }).catch(() => {});
  }
  if (isClientUser.value && auth.user?.id) {
    form.ownerId = String(auth.user.id);
  }
  await loadClient();
  if (!isEdit.value && isSuperAdmin.value && tenantStore.currentTenantId) {
    form.tenantId = String(tenantStore.currentTenantId);
  }
  await loadOwners();
});
</script>
