<template>
  <Card bodyClass="p-4">
    <div class="flex flex-wrap items-end gap-4">
      <Textinput
        id="typeName"
        v-model="localName"
        :label="t('types.form.name')"
        class="flex-1 min-w-[150px]"
        classInput="text-sm"
      />
      <VueSelect
        v-if="props.showTenantSelect"
        class="flex-1 min-w-[150px]"
        :label="t('types.form.tenant')"
      >
        <template #default="{ inputId }">
          <vSelect
            :id="inputId"
            v-model="localTenantId"
            :options="tenantOptions"
            :reduce="(o: Option) => o.value"
            label="label"
            :clearable="true"
            data-testid="tenant-select"
            @search="onTenantSearch"
          />
        </template>
      </VueSelect>
      <div
        v-else
        class="flex-1 min-w-[150px]"
      >
        <span
          class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1"
        >
          {{ t('types.form.tenant') }}
        </span>
        <div
          data-testid="tenant-display"
          class="text-sm text-slate-900 dark:text-slate-200"
        >
          {{ tenantDisplayName }}
        </div>
      </div>
      <VueSelect
        class="flex-1 min-w-[150px]"
        :label="t('types.form.client')"
      >
        <template #default="{ inputId }">
          <vSelect
            :id="inputId"
            v-model="localClientId"
            :options="props.clientOptions"
            :reduce="(o: Option) => o.value"
            label="label"
            :clearable="true"
            :disabled="isClientDisabled"
            :loading="props.loadingClients"
            data-testid="client-select"
          />
        </template>
      </VueSelect>
      <Switch
        id="requireSubtasksComplete"
        v-model="localRequireSubtasksComplete"
        :label="t('types.form.requireSubtasksComplete')"
      />
    </div>
  </Card>
</template>

<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import Card from '@/components/ui/Card/index.vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import vSelect from 'vue-select';
import Switch from '@/components/ui/Switch/index.vue';
import { useTenantStore } from '@/stores/tenant';

interface Option {
  value: string | null;
  label: string;
}

const props = withDefaults(
  defineProps<{
    name: string;
    tenantId: string;
    clientId?: string;
    showTenantSelect?: boolean;
    requireSubtasksComplete: boolean;
    clientOptions?: Option[];
    loadingClients?: boolean;
    tenantName?: string;
  }>(),
  {
    showTenantSelect: true,
    requireSubtasksComplete: false,
    tenantId: '',
    clientId: '',
    clientOptions: () => [],
    loadingClients: false,
    tenantName: '',
  },
);
const emit = defineEmits<{
  (e: 'update:name', value: string): void;
  (e: 'update:tenantId', value: string): void;
  (e: 'update:clientId', value: string): void;
  (e: 'update:requireSubtasksComplete', value: boolean): void;
}>();

const { t } = useI18n();
const tenantStore = useTenantStore();

const tenantOptions = ref<Option[]>([]);
watchEffect(() => {
  const tenants = tenantStore.tenants;
  tenantOptions.value = [
    { value: null, label: t('types.form.global') },
    ...tenants.map((tenant: any) => ({ value: String(tenant.id), label: tenant.name })),
  ];
});

const localName = computed({
  get: () => props.name,
  set: (v: string) => emit('update:name', v),
});

const localTenantId = computed<any>({
  get: () => (props.tenantId === '' ? null : props.tenantId),
  set: (v: string | null) => emit('update:tenantId', v === null ? '' : String(v)),
});

const localClientId = computed<any>({
  get: () => (props.clientId === '' ? null : props.clientId),
  set: (v: string | null) => emit('update:clientId', v === null ? '' : String(v)),
});

const localRequireSubtasksComplete = computed({
  get: () => props.requireSubtasksComplete,
  set: (v: boolean) => emit('update:requireSubtasksComplete', v),
});

const isClientDisabled = computed(() => localTenantId.value === null);

const tenantDisplayName = computed(() => {
  if (localTenantId.value === null) {
    return t('types.form.global');
  }
  const option = tenantOptions.value.find(
    (item) => item.value !== null && String(item.value) === String(localTenantId.value),
  );
  if (option) {
    return option.label;
  }
  if (props.tenantName) {
    return props.tenantName;
  }
  return '';
});

async function onTenantSearch(search: string) {
  if (search.length >= 3) {
    await tenantStore.searchTenants(search);
  } else if (search.length === 0) {
    await tenantStore.loadTenants({ per_page: 100 });
  }
}
</script>
