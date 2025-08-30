<template>
  <div role="radiogroup" :aria-label="ariaLabel" class="flex flex-col gap-1">
    <label v-for="opt in options" :key="opt" class="inline-flex items-center gap-2" :for="id(opt)">
      <input
        :id="id(opt)"
        type="radio"
        :name="name"
        :value="opt"
        :checked="modelValue === opt"
        :disabled="readonly"
        @change="update(opt)"
      />
      <span>{{ opt }}</span>
    </label>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ modelValue: string | null; options: string[]; readonly?: boolean; ariaLabel: string; name: string }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: string | null): void }>();

function update(val: string) {
  if (props.readonly) return;
  emit('update:modelValue', val);
}

function id(opt: string) {
  return `${props.name}-${opt}`;
}
</script>
