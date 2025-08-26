<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <Tabs v-model="active" :tabs="tabs">
      <template #default>
        <ProfileForm />
      </template>
    </Tabs>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import ProfileForm from '@/components/settings/ProfileForm.vue';
import Tabs from '@/components/ui/Tabs.vue';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const tabs = computed(() => {
  const t = [{ id: '/settings/profile', label: 'Profile' }];
  if (
    auth.user?.roles?.some((r: any) => ['ClientAdmin', 'SuperAdmin'].includes(r.name))
  ) {
    t.push({ id: '/settings/branding', label: 'Branding' });
  }
  return t;
});

const active = computed({
  get: () => route.path,
  set: (v: string) => router.push(v),
});
</script>
