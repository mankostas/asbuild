<template>
  <div
    v-if="items.length"
    class="relative"
    @mouseenter="open = true"
    @mouseleave="handleMouseLeave"
  >
    <span
      class="lg:h-[32px] lg:w-[32px] lg:bg-slate-100 lg:dark:bg-slate-900 dark:text-white cursor-pointer rounded-full text-[20px] flex items-center justify-center"
    >
      <Icon icon="heroicons-outline:plus" />
    </span>
    <div
      v-show="open"
      class="absolute left-0 top-[58px] w-[180px] rounded bg-white dark:bg-slate-800 shadow-dropdown border border-slate-100 dark:border-slate-700 z-[9999]"
    >
      <div
        v-for="(item, i) in items"
        :key="i"
        class="px-4 py-2 text-sm flex items-center space-x-2 rtl:space-x-reverse cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 first:rounded-t last:rounded-b text-slate-600 dark:text-slate-300"
        @click="go(item.link)"
      >
        <Icon :icon="item.icon" class="text-lg" />
        <span class="flex-1">{{ item.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import Icon from '@/components/Icon';
import { addNewOptions } from '@/constant/data';
import { useAuthStore } from '@/stores/auth';

const open = ref(false);
const router = useRouter();
const auth = useAuthStore();

const roles = computed(() => auth.user?.roles?.map((r: any) => r.name) || []);

const items = computed(() =>
  addNewOptions.filter((i) => {
    if (!i.admin) return true;
    return roles.value.some((r) => ['ClientAdmin', 'SuperAdmin'].includes(r));
  })
);

const go = (name: string) => {
  open.value = false;
  router.push({ name });
};

const handleMouseLeave = (e: MouseEvent) => {
  const current = e.currentTarget as HTMLElement;
  const related = e.relatedTarget as Node | null;
  if (related && current.contains(related)) {
    return;
  }
  open.value = false;
};
</script>

<style scoped>
</style>

