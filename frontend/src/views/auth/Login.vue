<template>
  <LoginIndex @submit="submit" />
</template>

<script setup lang="ts">
import { useRouter, useRoute } from 'vue-router';
import { useNotify } from '@/plugins/notify';
import { useAuthStore } from '@/stores/auth';
import LoginIndex from './dashcode/LoginIndex.vue';

const router = useRouter();
const route = useRoute();
const notify = useNotify();
const auth = useAuthStore();

const submit = async ({ email, password }: { email: string; password: string }) => {
  try {
    await auth.login({ email, password });
    const redirect =
      typeof route.query.redirect === 'string' && route.query.redirect
        ? decodeURIComponent(route.query.redirect as string)
        : '/';
    router.push(redirect);
  } catch (e: any) {
    notify.error(e.message || 'Invalid credentials');
  }
};
</script>
