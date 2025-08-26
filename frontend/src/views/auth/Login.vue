<template>
  <div class="max-w-sm mx-auto py-10 px-4">
    <form @submit.prevent="submit" class="space-y-4">
      <h1 class="text-xl font-bold text-gray-800">Sign in</h1>
      <div class="space-y-1">
        <label class="block text-sm text-gray-700">Email</label>
        <input
          v-model="email"
          type="email"
          class="border border-gray-300 rounded-md px-3 py-2 w-full focus-ring"
          required
        />
      </div>
      <div class="space-y-1">
        <label class="block text-sm text-gray-700">Password</label>
        <input
          v-model="password"
          type="password"
          class="border border-gray-300 rounded-md px-3 py-2 w-full focus-ring"
          required
        />
      </div>
      <div v-if="error" class="text-danger-500 text-sm">{{ error }}</div>
      <button
        type="submit"
        :disabled="loading"
        class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 w-full rounded-md disabled:opacity-50"
      >
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
