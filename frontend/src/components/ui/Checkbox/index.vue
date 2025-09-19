<template>
  <div
    class="relative"
    :class="`${errorText ? 'has-error' : ''}  ${showSuccessIcon ? 'is-valid' : ''}`"
  >
    <label
      :for="inputId"
      class="flex items-center"
      :class="disabled ? ' cursor-not-allowed opacity-50' : 'cursor-pointer'"
    >
      <input
        :id="inputId"
        v-model="localValue"
        type="checkbox"
        class="hidden"
        :name="name"
        :value="value"
        :disabled="disabled"
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
      <span v-if="showSuccessIcon" class="text-success-500 ltr:ml-2 rtl:mr-2">
        <Icon icon="bi:check-lg" />
      </span>
    </label>
    <p
      v-if="errorText"
      class="mt-1 text-danger-500 text-sm whitespace-pre-line"
    >
      {{ errorText }}
    </p>
    <p v-if="successText" class="mt-1 text-success-500 text-sm">
      {{ successText }}
    </p>
    <span
      v-if="descriptionText"
      class="block text-secondary-500 font-light leading-4 text-xs mt-2"
      >{{ descriptionText }}</span
    >
  </div>
</template>
<script>
import Icon from "@/components/Icon";
import { computed, defineComponent } from "vue";
export default defineComponent({
  name: "Checkbox",
  components: { Icon },
  inheritAttrs: false,
  props: {
    modelValue: {
      type: [String, Number, Boolean, Object, Array, Date],
      default: "",
    },
    // incoming error could be string or array from backend/lib
    error: { type: [String, Array, Object, Boolean], default: "" },
    // legacy: sometimes boolean, sometimes string/array -> normalize
    validate: { type: [Boolean, String, Array, Object], default: false },
    // NEW (preferred): explicit flags/messages
    showValidation: { type: Boolean, default: false },
    successMessage: { type: String, default: "" },
    label: { type: String, default: "" },
    name: { type: String, default: "checkbox" },
    id: { type: String, default: "" },
    description: { type: String, default: "" },
    checked: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    activeClass: {
      type: String,
      default:
        " ring-black-500  bg-slate-900 dark:bg-slate-700 dark:ring-slate-700 ",
    },
    value: { type: null, default: "" },
  },
  emits: ["update:modelValue", "input", "change"],

  setup(props, context) {
    const uid = `fld-${Math.random().toString(36).slice(2)}`;
    const inputId = computed(() => props.id || uid);

    const onChange = (e) => {
      context.emit("change", e);
      context.emit("input", e);
    };

    const localValue = computed({
      get: () => props.modelValue,
      set: (newValue) => context.emit("update:modelValue", newValue),
    });

    const checkboxValue = computed(() => {
      const value = localValue.value;

      if (Array.isArray(value)) {
        return value;
      }

      if (value === undefined || value === null || value === "") {
        return props.checked;
      }

      return value;
    });

    const ck = computed(() => {
      const value = checkboxValue.value;

      if (Array.isArray(value)) {
        return value.some((item) => item === props.value);
      }

      return Boolean(value);
    });

    // normalize error to string
    const errorText = computed(() => {
      const e = props.error;
      if (!e) return "";
      if (Array.isArray(e)) return e.filter(Boolean).join("\n");
      if (typeof e === "object")
        return Object.values(e).flat().filter(Boolean).join("\n");
      return String(e);
    });
    // determine whether validation UI should show
    const validationEnabled = computed(
      () => props.showValidation || !!props.validate
    );
    // success text comes only from dedicated prop or legacy when string
    const successText = computed(() => {
      if (props.successMessage) return props.successMessage;
      if (typeof props.validate === "string") return props.validate;
      return "";
    });
    // success icon shows only if validation is enabled and there is no error
    const showSuccessIcon = computed(
      () => validationEnabled.value && !errorText.value
    );

    return {
      ck,
      localValue,
      onChange,
      inputId,
      errorText,
      successText,
      showSuccessIcon,
      descriptionText: props.description,
    };
  },
});
</script>
<style lang=""></style>
