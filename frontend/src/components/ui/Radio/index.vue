<template>
  <div>
    <label
      :class="disabled ? ' cursor-not-allowed opacity-50' : 'cursor-pointer'"
      class="flex items-center"
    >
      <input
        :id="inputId"
        v-model="localValue"
        type="radio"
        class="hidden"
        :disabled="disabled"
        :name="name"
        :value="value"
        v-bind="$attrs"
        @change="onChange"
      />
      <span
        :class="
          localValue === value
            ? activeClass +
              ' ring-[6px]  ring-inset ring-offset-2 dark:ring-offset-slate-600  dark:ring-offset-4 border-slate-700'
            : 'border-slate-400 dark:border-slate-600 dark:ring-slate-700'
        "
        class="h-[18px] w-[18px] rounded-full border inline-flex bg-white dark:bg-slate-500 ltr:mr-3 rtl:ml-3 relative transition-all duration-150"
      >
      </span>
      <span
        v-if="label"
        class="text-slate-500 dark:text-slate-400 text-sm leading-6"
      >
        {{ label }}
      </span>
    </label>
  </div>
</template>
<script>
import { computed, defineComponent, ref } from "vue";
export default defineComponent({
  name: "Radio",
  inheritAttrs: false,
  props: {
    modelValue: {
      type: [String, Number, Boolean, Object, Array],
      default: "",
    },
    label: {
      type: String,
      default: "",
    },
    name: {
      type: String,
      default: "checkbox",
    },
    id: {
      type: String,
      default: "",
    },
    checked: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    activeClass: {
      type: String,
      default: "ring-slate-500 dark:ring-slate-400",
    },
    value: {
      type: null,
    },
  },
  emits: ["update:modelValue", "input", "change"],

  setup(props, context) {
    const ck = ref(props.checked);
    const uid = `fld-${Math.random().toString(36).slice(2)}`;
    const inputId = computed(() => props.id || uid);

    const onChange = (e) => {
      ck.value = !ck.value;
      context.emit("change", e);
      context.emit("input", e);
    };

    const localValue = computed({
      get: () => props.modelValue,
      set: (newValue) => context.emit("update:modelValue", newValue),
    });

    return { localValue, ck, onChange, inputId };
  },
});
</script>
<style lang=""></style>
