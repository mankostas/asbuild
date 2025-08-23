<template>
  <form @submit.prevent="save" class="space-y-4">
    <div>
      <label class="block">Name</label>
      <input v-model="form.name" class="border p-2 w-full" />
    </div>
    <div>
      <label class="block">Email</label>
      <input type="email" v-model="form.email" class="border p-2 w-full" />
    </div>
    <div>
      <label class="block">Password</label>
      <input type="password" v-model="form.password" class="border p-2 w-full" />
    </div>
    <div>
      <label class="block">Confirm Password</label>
      <input type="password" v-model="form.password_confirmation" class="border p-2 w-full" />
    </div>
    <button type="submit" class="bg-blue-500 text-white px-4 py-2">Save Profile</button>
  </form>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';

const auth = useAuthStore();
const form = reactive({
  name: auth.user?.name || '',
  email: auth.user?.email || '',
  password: '',
  password_confirmation: '',
});

async function save() {
  const { data } = await api.put('/settings/profile', form);
  auth.user = data;
  form.password = '';
  form.password_confirmation = '';
}
</script>
