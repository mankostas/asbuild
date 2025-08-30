<template>
  <TransitionRoot :show="open" as="template">
    <Dialog as="div" class="relative z-[99999]" @close="$emit('close')">
      <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 bg-slate-900/50" />
      </TransitionChild>
      <div class="fixed inset-0 flex justify-end">
        <TransitionChild
          as="template"
          enter="transform transition ease-in-out duration-300"
          enter-from="translate-x-full"
          enter-to="translate-x-0"
          leave="transform transition ease-in-out duration-300"
          leave-from="translate-x-0"
          leave-to="translate-x-full"
        >
          <DialogPanel class="w-full max-w-md h-full bg-white dark:bg-slate-800 shadow-xl rounded-l-2xl overflow-y-auto">
            <slot />
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { TransitionRoot, TransitionChild, Dialog, DialogPanel } from '@headlessui/vue';
import { ref, watch } from 'vue';

interface Props {
  open: boolean;
}

const props = defineProps<Props>();
defineEmits(['close']);

const scrollY = ref(0);

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      scrollY.value = window.scrollY;
      const body = document.body;
      const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
      body.style.top = `-${scrollY.value}px`;
      body.style.position = 'fixed';
      body.style.width = '100%';
      body.style.paddingRight = `${scrollbarWidth}px`;
    } else {
      const body = document.body;
      body.style.position = '';
      body.style.top = '';
      body.style.width = '';
      body.style.paddingRight = '';
      window.scrollTo(0, scrollY.value);
    }
  },
);
</script>
