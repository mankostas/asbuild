<template>
  <div>
    <div
      role="tablist"
      class="lg:space-x-8 md:space-x-4 space-x-0 rtl:space-x-reverse flex"
    >
      <button
        v-for="t in tabs"
        :key="t.id"
        role="tab"
        :aria-selected="modelValue === t.id"
        class="text-sm font-medium mb-7 capitalize bg-white dark:bg-slate-800 ring-0 focus:ring-0 focus:outline-none px-2 transition duration-150 before:transition-all before:duration-150 relative before:absolute before:left-1/2 before:bottom-[-6px] before:h-[1.5px] before:bg-primary-500 before:-translate-x-1/2"
        :class="modelValue === t.id ? 'text-primary-500 before:w-full' : 'text-slate-500 before:w-0 dark:text-slate-300'"
        @click="update(t.id)"
      >
        {{ t.label }}
      </button>
    </div>
    <div class="text-slate-600 dark:text-slate-400 text-sm font-normal">
      <slot :active="modelValue" />
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{ modelValue: string; tabs: { id: string; label: string }[] }>(),
  {
    modelValue: '',
    tabs: () => [],
  },
)
const emit = defineEmits(['update:modelValue'])
function update(id: string) {
  emit('update:modelValue', id)
}
</script>
