<template>
  <form @submit.prevent="submit" class="border p-4 mt-4">
    <div class="mb-2">
      <label class="block">Name</label>
      <input v-model="form.name" class="border p-2 w-full" />
    </div>
    <div class="mb-2">
      <label class="block">Email</label>
      <input v-model="form.email" class="border p-2 w-full" type="email" />
    </div>
    <div class="mb-2">
      <label class="block">Roles</label>
      <select v-model="form.roles" multiple class="border p-2 w-full">
        <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
      </select>
    </div>
    <button class="bg-blue-600 text-white px-4 py-2">Invite</button>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import api from '@/services/api';

const emit = defineEmits(['saved']);

const roles = ['ClientAdmin', 'ClientUser'];

const form = ref({ name: '', email: '', roles: [] as string[] });

async function submit() {
  await api.post('/employees', form.value);
  emit('saved');
  form.value = { name: '', email: '', roles: [] };
}
</script>

