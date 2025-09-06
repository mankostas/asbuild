<template>
  <div v-if="canAccess">
    <form class="max-w-md grid gap-4" @submit.prevent="onSubmit">
      <Textinput v-model="form.name" label="Name" />
      <div v-if="errors.name" class="text-red-600 text-sm">{{ errors.name }}</div>
      <Textinput
        v-model.number="form.quota_storage_mb"
        label="Storage Quota (MB)"
        type="number"
      />
      <Textinput v-model="form.phone" label="Phone" />
      <Textinput v-model="form.address" label="Address" />
      <Textinput v-if="!isEdit" v-model="form.user_name" label="Admin Name" />
      <div v-if="!isEdit && errors.user_name" class="text-red-600 text-sm">{{ errors.user_name }}</div>
      <Textinput
        v-if="!isEdit"
        v-model="form.user_email"
        label="Admin Email"
        type="email"
      />
      <div v-if="!isEdit && errors.user_email" class="text-red-600 text-sm">{{ errors.user_email }}</div>
      <VueSelect label="Features" :error="errors.features">
        <template #default="{ inputId }">
          <vSelect
            :id="inputId"
            v-model="form.features"
            :options="featureOptions"
            multiple
            :reduce="(f: any) => f.value"
          />
        </template>
      </VueSelect>
      <div v-if="form.features.length" class="grid gap-4">
        <h3 class="font-medium">Abilities per feature</h3>
        <VueSelect
          v-for="f in form.features"
          :key="f"
          :label="featureMap[f]?.label || f"
        >
          <template #default="{ inputId }">
            <vSelect
              :id="inputId"
              v-model="featureAbilities[f]"
              :options="abilityOptionsFor(f)"
              multiple
              :reduce="(a: any) => a.value"
            />
          </template>
        </VueSelect>
      </div>
      <div v-if="serverError" class="text-red-600 text-sm">{{ serverError }}</div>
      <Button type="submit" text="Save" btnClass="btn-dark" />
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { extractFormErrors } from '@/services/api';
import Textinput from '@/components/ui/Textinput/index.vue';
import Button from '@/components/ui/Button/index.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import vSelect from 'vue-select';
import { useForm } from 'vee-validate';
import { useTenantStore } from '@/stores/tenant';
import { can } from '@/stores/auth';
import { featureMap } from '@/constants/featureMap';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => route.name === 'tenants.edit');
const tenantStore = useTenantStore();

const canAccess = computed(
  () => can('tenants.create') || can('tenants.update') || can('tenants.manage'),
);

const form = ref({
  name: '',
  quota_storage_mb: 0,
  phone: '',
  address: '',
  features: [] as string[],
  user_name: '',
  user_email: '',
});

const featureOptions = ref<{ label: string; value: string }[]>([]);
const featureAbilities = ref<Record<string, string[]>>({});

const serverError = ref('');
const { handleSubmit, setErrors, errors } = useForm();

onMounted(async () => {
  try {
    const { data: features } = await api.get('/lookups/features');
    featureOptions.value = features.map((f: any) => ({
      label: f.label,
      value: f.slug,
    }));
  } catch (e) {
    featureOptions.value = [];
  }
  if (isEdit.value) {
    const { data } = await api.get(`/tenants/${route.params.id}`);
    form.value = {
      name: data.name || '',
      quota_storage_mb: data.quota_storage_mb || 0,
      phone: data.phone || '',
      address: data.address || '',
      features: Array.isArray(data.features) ? data.features : [],
      user_name: '',
      user_email: '',
    };
    const stored = tenantStore.tenantAllowedAbilities(route.params.id as string);
    featureAbilities.value = { ...stored };
    form.value.features.forEach((f: string) => {
      if (!featureAbilities.value[f]) {
        featureAbilities.value[f] = [...(featureMap[f]?.abilities || [])];
      }
    });
    tenantStore.setTenantFeatures(route.params.id as string, form.value.features);
    tenantStore.setAllowedAbilities(route.params.id as string, featureAbilities.value);
  }
});

const onSubmit = handleSubmit(async () => {
  serverError.value = '';
  if (!isEdit.value && form.value.features.length === 0) {
    form.value.features = ['tasks'];
  }
  const payload: any = {
    name: form.value.name,
    quota_storage_mb: form.value.quota_storage_mb,
    phone: form.value.phone,
    address: form.value.address,
    features: form.value.features,
  };
  if (!isEdit.value) {
    payload.user_name = form.value.user_name;
    payload.user_email = form.value.user_email;
  }
  try {
    if (isEdit.value) {
      await api.patch(`/tenants/${route.params.id}`, payload);
    } else {
      await api.post('/tenants', payload);
    }
    await tenantStore.loadTenants();
    router.push({ name: 'tenants.list' });
  } catch (e: any) {
    const errs = extractFormErrors(e);
    if (Object.keys(errs).length) {
      setErrors(errs);
    } else {
      serverError.value = e.message || 'Failed to save';
    }
  }
});

function abilityOptionsFor(feature: string) {
  return (featureMap[feature]?.abilities || []).map((a) => ({
    label: a,
    value: a,
  }));
}

watch(
  () => form.value.features,
  (features) => {
    const selected = new Set(features);
    Object.keys(featureAbilities.value).forEach((f) => {
      if (!selected.has(f)) {
        delete featureAbilities.value[f];
      }
    });
    features.forEach((f) => {
      if (!featureAbilities.value[f]) {
        featureAbilities.value[f] = [...(featureMap[f]?.abilities || [])];
      }
    });
    if (isEdit.value) {
      tenantStore.setTenantFeatures(route.params.id as string, features);
      tenantStore.setAllowedAbilities(
        route.params.id as string,
        featureAbilities.value,
      );
    }
  },
  { immediate: false },
);

watch(
  featureAbilities,
  (val) => {
    if (isEdit.value) {
      tenantStore.setAllowedAbilities(route.params.id as string, val);
    }
  },
  { deep: true },
);
</script>
