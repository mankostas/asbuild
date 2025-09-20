<template>
  <Modal
    v-if="isModal"
    :activeModal="true"
    :title="modalTitle"
    sizeClass="max-w-lg"
    @close="handleClose"
  >
    <div v-if="hasAccess" class="max-w-md">
      <div v-if="loading" class="py-2 text-sm text-slate-500">
        {{ t('tenants.table.loading') }}
      </div>
      <div v-else-if="tenant" class="grid gap-2">
        <div><strong>{{ t('tenants.details.id') }}:</strong> {{ tenant.public_id || tenant.id }}</div>
        <div><strong>{{ t('tenants.details.name') }}:</strong> {{ tenant.name }}</div>
        <div><strong>{{ t('tenants.details.phone') }}:</strong> {{ tenant.phone || '—' }}</div>
        <div><strong>{{ t('tenants.details.address') }}:</strong> {{ tenant.address || '—' }}</div>
        <div class="mt-4 flex gap-2">
          <Button
            v-if="canEditTenant"
            btnClass="btn-primary btn-sm"
            :text="t('actions.edit')"
            :to="tenantEditParams(tenant)"
          />
          <Button
            btnClass="btn-secondary btn-sm"
            :text="t('actions.close')"
            type="button"
            @click="handleClose"
          />
        </div>
      </div>
      <div v-else-if="error" class="text-sm text-danger-500">
        {{ error }}
      </div>
      <div v-else class="text-sm text-slate-500">
        {{ t('tenants.table.empty') }}
      </div>
    </div>
  </Modal>

  <div v-else class="p-4">
    <div v-if="hasAccess" class="max-w-md">
      <div v-if="loading" class="py-2 text-sm text-slate-500">
        {{ t('tenants.table.loading') }}
      </div>
      <div v-else-if="tenant" class="grid gap-2">
        <div><strong>{{ t('tenants.details.id') }}:</strong> {{ tenant.public_id || tenant.id }}</div>
        <div><strong>{{ t('tenants.details.name') }}:</strong> {{ tenant.name }}</div>
        <div><strong>{{ t('tenants.details.phone') }}:</strong> {{ tenant.phone || '—' }}</div>
        <div><strong>{{ t('tenants.details.address') }}:</strong> {{ tenant.address || '—' }}</div>
        <div class="mt-4 flex gap-2">
          <Button
            v-if="canEditTenant"
            btnClass="btn-primary btn-sm"
            :text="t('actions.edit')"
            :to="tenantEditParams(tenant)"
          />
          <Button
            btnClass="btn-secondary btn-sm"
            :text="t('actions.back')"
            :to="{ name: 'tenants.list' }"
          />
        </div>
      </div>
      <div v-else-if="error" class="text-sm text-danger-500">
        {{ error }}
      </div>
      <div v-else class="text-sm text-slate-500">
        {{ t('tenants.table.empty') }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { extractData } from '@/services/api';
import Button from '@/components/ui/Button/index.vue';
import Modal from '@/components/ui/Modal';
import { can } from '@/stores/auth';
import hasAbility from '@/utils/ability';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ forceModal?: boolean; tenantId?: string | null }>();
const emit = defineEmits<{ (event: 'close'): void }>();

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const tenant = ref<Record<string, any> | null>(null);
const loading = ref(false);
const error = ref('');

const isForcedModal = computed(() => Boolean(props.forceModal));
const isModal = computed(() => isForcedModal.value || Boolean(route.meta?.modal));
const modalTitle = computed(() => t('routes.tenantDetail'));

const hasAccess = computed(
  () => hasAbility('tenants.view') || hasAbility('tenants.manage'),
);

const canEditTenant = computed(
  () => can('tenants.update') || can('tenants.manage'),
);

const effectiveId = computed(() => {
  if (props.tenantId !== undefined && props.tenantId !== null) {
    return props.tenantId || null;
  }
  const param = route.params.id;
  if (Array.isArray(param)) {
    return param[0] ?? null;
  }
  if (typeof param === 'string') {
    return param;
  }
  return param !== undefined && param !== null ? String(param) : null;
});

async function loadTenant(id: string | null) {
  if (!id || !hasAccess.value) {
    tenant.value = null;
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const response = await api.get(`/tenants/${id}`);
    const data = extractData<any>(response.data) ?? response.data ?? null;
    tenant.value = data;
  } catch (err: any) {
    tenant.value = null;
    error.value = err?.message || t('common.error');
  } finally {
    loading.value = false;
  }
}

watch(
  () => effectiveId.value,
  (id) => {
    loadTenant(id as string | null);
  },
  { immediate: true },
);

watch(
  () => hasAccess.value,
  (canAccess) => {
    if (canAccess) {
      loadTenant(effectiveId.value as string | null);
    } else {
      tenant.value = null;
    }
  },
);

function handleClose() {
  if (isForcedModal.value) {
    emit('close');
  } else {
    router.push({ name: 'tenants.list' });
  }
}

function tenantEditParams(target: Record<string, any> | null) {
  const id = target ? String(target.public_id ?? target.id ?? '') : '';
  return { name: 'tenants.edit', params: { id } };
}
</script>
