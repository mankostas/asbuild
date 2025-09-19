<template>
  <Modal
    v-if="isModal"
    :activeModal="true"
    :title="modalTitle"
    sizeClass="max-w-3xl"
    @close="closeModal"
  >
    <div
      v-if="initializing"
      class="flex justify-center py-10"
      role="status"
      aria-live="polite"
    >
      <span class="sr-only">{{ t('clients.table.loading') }}</span>
      <span
        class="h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-primary-500"
        aria-hidden="true"
      />
    </div>

    <div v-else-if="canAccess" class="space-y-6">
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

        <Switch
          v-if="!isEdit"
          v-model="notifyClient"
          :label="t('clients.form.notify.label')"
          :description="t('clients.form.notify.description')"
          :disabled="notifyDisabled"
          :error="errors.notify"
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
              :isDisabled="saving"
              :isLoading="saving"
            />
          </div>
        </div>
      </form>
    </div>
  </Modal>

  <div v-else class="p-4">
    <Card class="max-w-3xl mx-auto">
      <template #header>
        <h1 class="text-lg font-semibold">
          {{ modalTitle }}
        </h1>
      </template>
      <p class="text-slate-600 mb-6">
        {{ t('routes.clients') }}
      </p>

      <div
        v-if="initializing"
        class="flex justify-center py-16"
        role="status"
        aria-live="polite"
      >
        <span class="sr-only">{{ t('clients.table.loading') }}</span>
        <span
          class="h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-primary-500"
          aria-hidden="true"
        />
      </div>

      <div v-else-if="canAccess" class="space-y-6">
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

          <Switch
            v-if="!isEdit"
            v-model="notifyClient"
            :label="t('clients.form.notify.label')"
            :description="t('clients.form.notify.description')"
            :disabled="notifyDisabled"
            :error="errors.notify"
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
                :isDisabled="saving"
                :isLoading="saving"
              />
            </div>
          </div>
        </form>
      </div>
    </Card>

  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import Textinput from '@/components/ui/Textinput/index.vue';
import Textarea from '@/components/ui/Textarea/index.vue';
import Select from '@/components/ui/Select/index.vue';
import Button from '@/components/ui/Button/index.vue';
import Card from '@/components/ui/Card/index.vue';
import Alert from '@/components/ui/Alert/index.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Switch from '@/components/ui/Switch/index.vue';
import Modal from '@/components/ui/Modal';
import { useClientsStore } from '@/stores/clients';
import { useTenantStore } from '@/stores/tenant';
import { can, useAuthStore } from '@/stores/auth';
import { useNotify } from '@/plugins/notify';
import { extractFormErrors } from '@/services/api';

const route = useRoute();
const router = useRouter();
const clientsStore = useClientsStore();
const tenantStore = useTenantStore();
const auth = useAuthStore();
const notify = useNotify();
const { t } = useI18n();

const isEdit = computed(() => route.name === 'clients.edit');
const isModal = computed(() => Boolean(route.meta?.modal));
const canAccess = computed(() =>
  isEdit.value ? can('clients.manage') : can('clients.create') || can('clients.manage'),
);
const isSuperAdmin = computed(() => auth.isSuperAdmin);
const modalTitle = computed(() =>
  isEdit.value ? t('routes.clientEdit') : t('routes.clientCreate'),
);

const form = reactive({
  name: '',
  email: '',
  phone: '',
  notes: '',
  tenantId: '',
});

const notifyClient = ref(false);

const errors = reactive({
  name: '',
  email: '',
  phone: '',
  notes: '',
  tenant: '',
  notify: '',
});

const serverError = ref('');
const loadError = ref('');
const loading = ref(false);
const saving = ref(false);
const initializing = ref(true);
const tenantOptions = computed(() =>
  tenantStore.tenants.map((tenant: any) => ({
    value: String(tenant.id),
    label: tenant.name,
  })),
);
const showTenantSelect = computed(() => isSuperAdmin.value);

const tenantRequired = computed(() => isSuperAdmin.value && !isEdit.value);
const notifyDisabled = computed(() => !form.email);

function resetErrors() {
  errors.name = '';
  errors.email = '';
  errors.phone = '';
  errors.notes = '';
  errors.tenant = '';
  errors.notify = '';
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
  errors.notify = format(formErrors.notify_client);
}

function validateForm(): boolean {
  resetErrors();

  if (!form.name.trim()) {
    errors.name = t('clients.form.errors.name');
  }

  if (tenantRequired.value && !form.tenantId) {
    errors.tenant = t('clients.form.errors.tenant');
  }

  return !errors.name && !errors.tenant;
}

async function loadClient() {
  const idParam = route.params.id;
  if (!isEdit.value || !idParam) {
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
    form.name = client.name || '';
    form.email = client.email || '';
    form.phone = client.phone || '';
    form.notes = client.notes || '';
    if (isSuperAdmin.value) {
      form.tenantId = client.tenant_id !== null && client.tenant_id !== undefined ? String(client.tenant_id) : '';
    }
  } catch (error: any) {
    loadError.value = error?.message || t('clients.form.loadError');
  } finally {
    loading.value = false;
  }
}

async function reloadClient() {
  await loadClient();
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

    if (isSuperAdmin.value) {
      if (form.tenantId) {
        payload.tenant_id = form.tenantId;
      }
    }

    if (isEdit.value) {
      await clientsStore.update(route.params.id as string | number, payload);
      notify.success(t('clients.form.success.updated'));
    } else {
      payload.notify_client = notifyClient.value;
      await clientsStore.create(payload);
      notify.success(t('clients.form.success.created'));
    }
    navigateToList();
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

function navigateToList() {
  if (route.name !== 'clients.list') {
    router.push({ name: 'clients.list' });
  }
}

function closeModal() {
  navigateToList();
}

function goBack() {
  if (isModal.value) {
    navigateToList();
    return;
  }
  router.back();
}

watch(
  () => form.tenantId,
  () => {
    if (errors.tenant) {
      errors.tenant = '';
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

watch(
  () => form.email,
  (value) => {
    if (!value) {
      notifyClient.value = false;
    }
    if (errors.email) {
      errors.email = '';
    }
    if (errors.notify) {
      errors.notify = '';
    }
  },
);

watch(
  () => notifyClient.value,
  () => {
    if (errors.notify) {
      errors.notify = '';
    }
  },
);

onMounted(async () => {
  if (!canAccess.value) {
    initializing.value = false;
    return;
  }

  try {
    if (isSuperAdmin.value) {
      await tenantStore.loadTenants({ per_page: 100 }).catch(() => {});
    }
    await loadClient();
    if (!isEdit.value && isSuperAdmin.value && tenantStore.currentTenantId) {
      form.tenantId = String(tenantStore.currentTenantId);
    }
  } finally {
    initializing.value = false;
  }
});
</script>
