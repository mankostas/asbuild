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
      <div v-if="serverError" class="text-red-600 text-sm">{{ serverError }}</div>
      <Button type="submit" text="Save" btnClass="btn-dark" />
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { extractFormErrors } from '@/services/api';
import Textinput from '@/components/ui/Textinput/index.vue';
import Button from '@/components/ui/Button/index.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import vSelect from 'vue-select';
import { useForm } from 'vee-validate';
import { useTenantStore } from '@/stores/tenant';
import { can } from '@/stores/auth';

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
  }
});

const onSubmit = handleSubmit(async () => {
  serverError.value = '';
  if (!isEdit.value && form.value.features.length === 0) {
    form.value.features = ['appointments'];
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
</script>
