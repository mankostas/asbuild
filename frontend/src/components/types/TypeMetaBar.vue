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
            @search="onTenantSearch"
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
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Card from '@/components/ui/Card/index.vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import vSelect from 'vue-select';
import Switch from '@/components/ui/Switch/index.vue';
import { useTenantStore } from '@/stores/tenant';

interface Option {
  value: number | null;
  label: string;
}

const props = withDefaults(
  defineProps<{
    name: string;
    tenantId: number | '';
    showTenantSelect?: boolean;
    requireSubtasksComplete: boolean;
  }>(),
  { showTenantSelect: true, requireSubtasksComplete: false },
);
const emit = defineEmits<{
  (e: 'update:name', value: string): void;
  (e: 'update:tenantId', value: number | ''): void;
  (e: 'update:requireSubtasksComplete', value: boolean): void;
}>();

const { t } = useI18n();
const tenantStore = useTenantStore();

const tenantOptions = ref<Option[]>([]);

watch(
  () => tenantStore.tenants,
  (tenants) => {
    tenantOptions.value = [
      { value: null, label: 'Global' },
      ...tenants.map((t: any) => ({ value: t.id, label: t.name })),
    ];
  },
  { immediate: true },
);

const localName = computed({
  get: () => props.name,
  set: (v: string) => emit('update:name', v),
});

const localTenantId = computed<any>({
  get: () => (props.tenantId === '' ? null : props.tenantId),
  set: (v: number | null) => emit('update:tenantId', v === null ? '' : Number(v)),
});

const localRequireSubtasksComplete = computed({
  get: () => props.requireSubtasksComplete,
  set: (v: boolean) => emit('update:requireSubtasksComplete', v),
});

async function onTenantSearch(search: string) {
  if (search.length >= 3) {
    await tenantStore.searchTenants(search);
  } else if (search.length === 0) {
    await tenantStore.loadTenants({ per_page: 100 });
  }
}
</script>
