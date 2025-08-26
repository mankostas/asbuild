<template>
  <div class="max-w-sm mx-auto py-10">
    <form @submit.prevent="submit" class="space-y-4">
      <h1 class="text-xl font-bold">Sign in</h1>
      <div>
        <label class="block text-sm">Email</label>
        <input v-model="email" type="email" class="border p-2 w-full" required />
      </div>
      <div>
        <label class="block text-sm">Password</label>
        <input v-model="password" type="password" class="border p-2 w-full" required />
      </div>
      <div v-if="error" class="text-red-500 text-sm">{{ error }}</div>
      <button type="submit" :disabled="loading" class="bg-blue-500 text-white px-4 py-2 w-full">
        {{ loading ? '...' : 'Sign in' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const auth = useAuthStore();

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

const submit = async () => {
  error.value = '';
  loading.value = true;
  try {
    await auth.login({ email: email.value, password: password.value });
    await auth.fetchUser();
    router.push('/');
  } catch (e: any) {
    error.value = e.message || 'Invalid credentials';
  } finally {
    loading.value = false;
  }
};
</script>
