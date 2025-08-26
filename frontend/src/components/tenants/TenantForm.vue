<template>
  <form @submit.prevent="submit" class="space-y-4">
    <Textinput label="Name" v-model="form.name" />
    <Textinput
      label="Storage Quota (MB)"
      type="number"
      v-model.number="form.quota_storage_mb"
    />
    <Textinput label="Phone" v-model="form.phone" />
    <Textinput label="Address" v-model="form.address" />
    <Textarea label="Features (JSON)" v-model="form.features" />
    <Button type="submit" text="Save" btnClass="btn-dark" />
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import api from '@/services/api';
import Textinput from '@/components/ui/Textinput/index.vue';
import Textarea from '@/components/ui/Textarea/index.vue';
import Button from '@/components/ui/Button/index.vue';

const emit = defineEmits(['saved']);

const form = ref({ name: '', quota_storage_mb: 0, phone: '', address: '', features: '{}' });

async function submit() {
  let payload = { ...form.value };
  try {
    payload.features = JSON.parse(form.value.features || '{}');
  } catch (e) {
    payload.features = {};
  }
  await api.post('/tenants', payload);
  emit('saved');
  form.value = { name: '', quota_storage_mb: 0, phone: '', address: '', features: '{}' };
}
</script>
