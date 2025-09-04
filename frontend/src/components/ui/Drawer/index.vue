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
import { watch, onUnmounted } from 'vue';

interface Props {
  open: boolean;
}

const props = defineProps<Props>();
defineEmits(['close']);

const SCROLL_LOCK_ATTR = 'data-scroll-lock-count';

let locked = false;
let scrollTop = 0;

const lockBodyScroll = () => {
  const body = document.body;
  const count = Number(body.getAttribute(SCROLL_LOCK_ATTR) ?? 0);
  if (count === 0) {
    scrollTop = window.scrollY;
    body.style.top = `-${scrollTop}px`;
    body.style.position = 'fixed';
    body.style.width = '100%';
    body.classList.add('overflow-hidden');
  }
  body.setAttribute(SCROLL_LOCK_ATTR, String(count + 1));
};

const unlockBodyScroll = () => {
  const body = document.body;
  const count = Number(body.getAttribute(SCROLL_LOCK_ATTR) ?? 0);
  if (count <= 1) {
    body.classList.remove('overflow-hidden');
    body.style.position = '';
    body.style.top = '';
    body.style.width = '';
    window.scrollTo({ top: scrollTop });
    body.removeAttribute(SCROLL_LOCK_ATTR);
  } else {
    body.setAttribute(SCROLL_LOCK_ATTR, String(count - 1));
  }
};

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      lockBodyScroll();
      locked = true;
    } else if (locked) {
      unlockBodyScroll();
      locked = false;
    }
  },
);

onUnmounted(() => {
  if (locked) {
    unlockBodyScroll();
  }
});
</script>
