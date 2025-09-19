<template>
  <Dropdown v-if="items.length" classMenuItems="w-[180px] top-[58px]">
    <button
      type="button"
      class="lg:h-[32px] lg:w-[32px] lg:bg-slate-100 lg:dark:bg-slate-900 dark:text-white cursor-pointer rounded-full text-[20px] flex items-center justify-center"
    >
      <Icon icon="heroicons-outline:plus" />
    </button>
    <template #menus>
      <MenuItem v-for="(item, i) in items" #default="{ active }" :key="i">
        <button
          type="button"
          :class="[
            'px-4 py-2 text-sm align flex space-x-2 rtl:space-x-reverse  w-full cursor-pointer first:rounded-t last:rounded-b',
            active
              ? 'bg-slate-100 dark:bg-slate-700 dark:bg-opacity-70 text-slate-900 dark:text-slate-300'
              : 'text-slate-600 dark:text-slate-300'
          ]"
          @click="go(item.link)"
        >
          <Icon :icon="item.icon" class="text-lg" />
          <span class="flex-1">{{ item.label }}</span>
        </button>
      </MenuItem>
    </template>
  </Dropdown>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { MenuItem } from '@headlessui/vue';
import { useRouter } from 'vue-router';
import Dropdown from '@/components/Dropdown';
import Icon from '@/components/Icon';
import { addNewOptions } from '@/constants/menu';
import { useAuthStore } from '@/stores/auth';
import { useClientModalStore } from '@/stores/clientModal';

const router = useRouter();
const auth = useAuthStore();
const clientModal = useClientModalStore();

const items = computed(() =>
  addNewOptions.filter((i) => {
    const features = i.requiredFeatures || [];
    if (!features.every((f) => auth.features.includes(f))) {
      return false;
    }
    const req = i.requiredAbilities || [];
    return i.requireAllAbilities ? auth.hasAll(req) : auth.hasAny(req);
  })
);

const go = (name: string) => {
  if (name === 'clients.create') {
    clientModal.open();
    return;
  }

  router.push({ name });
};
</script>

<style scoped></style>

