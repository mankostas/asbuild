<template>
  <div role="group" :aria-label="ariaLabel" class="flex flex-col gap-1">
    <label v-for="opt in options" :key="opt" class="inline-flex items-center gap-2" :for="id(opt)">
      <input
        :id="id(opt)"
        type="checkbox"
        :name="name"
        :value="opt"
        :checked="selected.has(opt)"
        :disabled="readonly"
        @change="toggle(opt, $event.target.checked)"
      />
      <span>{{ opt }}</span>
    </label>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{ modelValue: string[]; options: string[]; readonly?: boolean; ariaLabel: string; name: string }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: string[]): void }>();

const selected = ref(new Set(props.modelValue));

watch(
  () => props.modelValue,
  (v) => {
    selected.value = new Set(v);
  }
);

function toggle(opt: string, checked: boolean) {
  if (props.readonly) return;
  if (checked) {
    selected.value.add(opt);
  } else {
    selected.value.delete(opt);
  }
  emit('update:modelValue', Array.from(selected.value));
}

function id(opt: string) {
  return `${props.name}-${opt}`;
}
</script>
