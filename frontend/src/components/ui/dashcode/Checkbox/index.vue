<template>
  <div>
    <label
      :for="checkboxId"
      class="flex items-center"
      :class="disabled ? ' cursor-not-allowed opacity-50' : 'cursor-pointer'"
    >
      <input
        :id="checkboxId"
        v-model="localValue"
        type="checkbox"
        class="hidden"
        :value="value"
        :disabled="disabled"
        :name="name"
        v-bind="$attrs"
        @change="onChange"
      />

      <span
        class="h-4 w-4 border flex-none border-slate-100 dark:border-slate-800 rounded inline-flex ltr:mr-3 rtl:ml-3 relative transition-all duration-150"
        :class="
          ck
            ? `${activeClass} ring-2 ring-offset-2 dark:ring-offset-slate-800`
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

let checkboxIdCounter = 0;
export default defineComponent({
  name: "Checkbox",
  inheritAttrs: false,
  props: {
    label: {
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
    name: {
      type: String,
      default: "checkbox",
    },
    activeClass: {
      type: String,
      default:
        " ring-black-500  bg-slate-900 dark:bg-slate-700 dark:ring-slate-700 ",
    },
    id: {
      type: String,
      default: "",
    },
    value: {
      type: null,
      default: null,
    },
    modelValue: {
      type: null,
      default: null,
    },
  },
  emits: {
    "update:modelValue": (newValue) => ({
      modelValue: newValue,
    }),
    // use newValue
    // "update:checked": (newValue) => true,
  },

  setup(props, context) {
    const instanceId = checkboxIdCounter += 1;
    const checkboxId = computed(() => props.id || `checkbox-${instanceId}`);
    const ck = ref(props.checked);

    // on change event
    const onChange = () => {
      ck.value = !ck.value;
    };

    const localValue = computed({
      get: () => props.modelValue,
      set: (newValue) => context.emit("update:modelValue", newValue),
    });

    return { localValue, ck, onChange, checkboxId };
  },
});
</script>
<style lang=""></style>
