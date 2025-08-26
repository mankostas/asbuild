<template>
  <LoginIndex @submit="submit" />
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useNotify } from '@/plugins/notify';
import { useAuthStore } from '@/stores/auth';
import LoginIndex from './dashcode/LoginIndex.vue';

const router = useRouter();
const notify = useNotify();
const auth = useAuthStore();

const submit = async ({ email, password }: { email: string; password: string }) => {
  try {
    await auth.login({ email, password });
    router.push('/');
  } catch (e: any) {
    notify.error(e.message || 'Invalid credentials');
  }
};
</script>
