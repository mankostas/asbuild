<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-start justify-center p-4"
    role="dialog"
    aria-modal="true"
    @keydown.esc="emit('close')"
  >
    <button
      type="button"
      class="fixed inset-0 bg-black/50"
      @click="emit('close')"
    ></button>
    <div
      ref="panel"
      class="relative z-10 w-full max-w-md rounded-2xl bg-background p-4 shadow-lg"
    >
      <input
        ref="input"
        v-model="query"
        type="text"
        :placeholder="t('commandPalette.placeholder')"
        class="mb-2 w-full rounded border border-foreground/20 bg-background px-3 py-2 focus-ring"
      />
      <ul class="max-h-60 overflow-auto">
        <li
          v-for="(a, i) in filtered"
          :key="a.id"
        >
          <button
            type="button"
            :class="[
              'cursor-pointer rounded px-2 py-1',
              i === index ? 'bg-foreground/10' : '',
            ]"
            @mouseenter="index = i"
            @focusin="index = i"
            @click="select(a)"
          >
            {{ a.label }}
          </button>
        </li>
        <li v-if="!filtered.length" class="p-2 text-sm text-foreground/50">
          {{ t('commandPalette.empty') }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Action } from '@/composables/useCommandPalette';

const props = defineProps<{ open: boolean; actions: Action[] }>();
const emit = defineEmits(['close']);
const { t } = useI18n();

const query = ref('');
const index = ref(0);
const input = ref<HTMLInputElement>();
const panel = ref<HTMLDivElement>();

const filtered = computed(() =>
  props.actions.filter((a) =>
    a.label.toLowerCase().includes(query.value.toLowerCase()),
  ),
);

watch(
  () => props.open,
  (val) => {
    if (val) {
      query.value = '';
      index.value = 0;
      requestAnimationFrame(() => input.value?.focus());
    }
  },
);

function select(a: Action) {
  if (a.run) a.run();
  else if (a.to) window.location.href = a.to;
  emit('close');
}

function onKey(e: KeyboardEvent) {
  if (!props.open) return;
  if (e.key === 'ArrowDown') {
    e.preventDefault();
    index.value = (index.value + 1) % filtered.value.length;
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    index.value =
      (index.value - 1 + filtered.value.length) % filtered.value.length;
  } else if (e.key === 'Enter') {
    e.preventDefault();
    const a = filtered.value[index.value];
    if (a) select(a);
  }
}

onMounted(() => window.addEventListener('keydown', onKey));
</script>
