<template>
  <form @submit.prevent="submit" class="border p-4 mt-4">
    <div class="mb-2">
      <label class="block">Name</label>
      <input v-model="form.name" class="border p-2 w-full" />
    </div>
    <div class="mb-2">
      <label class="block">Storage Quota (MB)</label>
      <input v-model.number="form.quota_storage_mb" type="number" class="border p-2 w-full" />
    </div>
    <div class="mb-2">
      <label class="block">Features (JSON)</label>
      <textarea v-model="form.features" class="border p-2 w-full"></textarea>
    </div>
    <button class="bg-blue-600 text-white px-4 py-2">Save</button>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import api from '@/services/api';

const emit = defineEmits(['saved']);

const form = ref({ name: '', quota_storage_mb: 0, features: '{}' });

async function submit() {
  let payload = { ...form.value };
  try {
    payload.features = JSON.parse(form.value.features || '{}');
  } catch (e) {
    payload.features = {};
  }
  await api.post('/tenants', payload);
  emit('saved');
  form.value = { name: '', quota_storage_mb: 0, features: '{}' };
}
</script>
