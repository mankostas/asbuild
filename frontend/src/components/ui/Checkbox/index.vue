<template>
  <div>
    <label
      :class="disabled ? ' cursor-not-allowed opacity-50' : 'cursor-pointer'"
      class="flex items-center"
    >
      <input
        :id="inputId"
        v-model="localValue"
        type="checkbox"
        class="hidden"
        :disabled="disabled"
        :name="name"
        :value="value"
        v-bind="$attrs"
        @change="onChange"
      />
      <span
        class="h-4 w-4 border flex-none border-slate-100 dark:border-slate-800 rounded inline-flex ltr:mr-3 rtl:ml-3 relative transition-all duration-150"
        :class="
          ck
            ? activeClass + ' ring-2 ring-offset-2 dark:ring-offset-slate-800 '
            : 'bg-slate-100 dark:bg-slate-600 dark:border-slate-600'
        "
      >
        <img
          v-if="ck"
          src="@/assets/images/icon/ck-white.svg"
          alt=""
          class="h-[10px] w-[10px] block m-auto"
        />
      </span>
      <span
        v-if="label"
        class="text-slate-500 dark:text-slate-400 text-sm leading-6"
      >
        {{ label }}
      </span>
      <slot name="labelHtml"></slot>
    </label>
  </div>
</template>
<script>
import { computed, defineComponent, ref } from "vue";
export default defineComponent({
  name: "Checkbox",
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
      default:
        " ring-black-500  bg-slate-900 dark:bg-slate-700 dark:ring-slate-700 ",
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
