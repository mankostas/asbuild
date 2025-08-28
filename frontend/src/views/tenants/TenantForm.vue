<template>
  <div>
    <form @submit.prevent="onSubmit" class="max-w-md grid gap-4">
      <Textinput label="Name" v-model="form.name" />
      <div v-if="errors.name" class="text-red-600 text-sm">{{ errors.name }}</div>
      <Textinput
        label="Storage Quota (MB)"
        type="number"
        v-model.number="form.quota_storage_mb"
      />
      <Textinput label="Phone" v-model="form.phone" />
      <Textinput label="Address" v-model="form.address" />
      <VueSelect label="Features" :error="errors.features">
        <vSelect v-model="form.features" :options="featureOptions" multiple />
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

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => route.name === 'tenants.edit');

const form = ref({
  name: '',
  quota_storage_mb: 0,
  phone: '',
  address: '',
  features: [] as string[],
});

const featureOptions = ref<string[]>([]);

const serverError = ref('');
const { handleSubmit, setErrors, errors } = useForm();

onMounted(async () => {
  try {
    const { data: featureData } = await api.get('/lookups/features');
    featureOptions.value = featureData;
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
    };
  }
});

const onSubmit = handleSubmit(async () => {
  serverError.value = '';
  const payload: any = {
    name: form.value.name,
    quota_storage_mb: form.value.quota_storage_mb,
    phone: form.value.phone,
    address: form.value.address,
    features: form.value.features,
  };
  try {
    if (isEdit.value) {
      await api.patch(`/tenants/${route.params.id}`, payload);
    } else {
      await api.post('/tenants', payload);
    }
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
