<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <nav class="flex gap-4 border-b pb-2">
      <RouterLink
        to="/settings/profile"
        class="text-blue-600 underline"
        >Profile</RouterLink
      >
      <RouterLink
        v-if="isAdmin"
        to="/settings/branding"
        class="text-blue-600 underline"
        >Branding</RouterLink
      >
    </nav>
    <ProfileForm />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import ProfileForm from '@/components/settings/ProfileForm.vue';

const auth = useAuthStore();
const isAdmin = computed(() =>
  auth.user?.roles?.some((r: any) => ['ClientAdmin', 'SuperAdmin'].includes(r.name)),
);
</script>
