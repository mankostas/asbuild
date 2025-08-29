<template>
  <div
    class="relative"
    :class="`${errorText ? 'has-error' : ''}  ${showSuccessIcon ? 'is-valid' : ''}`"
  >
    <label
      :for="inputId"
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
      v-if="description"
      class="block text-secondary-500 font-light leading-4 text-xs mt-2"
      >{{ description }}</span
    >
  </div>
</template>
<script>
import Icon from "@/components/Icon";
import { computed, defineComponent, ref } from "vue";
export default defineComponent({
  name: "Radio",
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
    activeClass: { type: String, default: "ring-slate-500 dark:ring-slate-400" },
    value: { type: null, default: "" },
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
      localValue,
      ck,
      onChange,
      inputId,
      errorText,
      successText,
      showSuccessIcon,
      description: props.description,
    };
  },
});
</script>
<style lang=""></style>
