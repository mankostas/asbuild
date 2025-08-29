<template>
  <div
    class="relative"
    :class="`${errorText ? 'has-error' : ''}  ${showSuccessIcon ? 'is-valid' : ''}`"
  >
    <label
      class="flex items-start"
      :class="disabled ? ' cursor-not-allowed opacity-40' : 'cursor-pointer'"
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
      <div
        :class="ck ? activeClass : 'bg-secondary-500'"
        class="relative inline-flex h-6 w-[46px] ltr:mr-3 rtl:ml-3 items-center rounded-full transition-all duration-150"
      >
        <span
          v-if="badge && ck"
          :class="icon ? 'text-sm' : ' text-[9px]'"
          class="absolute left-1 top-1/2 -translate-y-1/2 capitalize font-bold text-white tracking-[1px]"
        >
          <span v-if="!icon">on</span>

          <Icon v-if="icon" :icon="prevIcon" />
        </span>
        <span
          v-if="badge && !ck"
          :class="icon ? 'text-sm' : ' text-[9px]'"
          class="absolute right-1 top-1/2 -translate-y-1/2 capitalize font-bold text-slate-900 tracking-[1px]"
        >
          <Transition>
            <span v-if="!icon">Off</span>
          </Transition>
          <Transition>
            <Icon v-if="icon" :icon="nextIcon" />
          </Transition>
        </span>

        <span
          :class="
            ck
              ? 'ltr:translate-x-6 rtl:-translate-x-6'
              : 'ltr:translate-x-[2px] rtl:-translate-x-[2px]'
          "
          class="inline-block h-5 w-5 transform rounded-full bg-white transition-all duration-150"
        />
      </div>

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
      {{ $msg(errorText) }}
    </p>
    <p v-if="successText" class="mt-1 text-success-500 text-sm">
      {{ $msg(successText) }}
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
  name: "Checkbox",
  components: {
    Icon,
  },
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
    active: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    activeClass: { type: String, default: "bg-slate-900 dark:bg-slate-900 " },
    value: { type: null },
    badge: { type: Boolean, default: false },
    icon: { type: Boolean, default: false },
    prevIcon: { type: String, default: "heroicons-outline:volume-up" },
    nextIcon: { type: String, default: "heroicons-outline:volume-off" },
  },
  emits: ["update:modelValue", "input", "change"],

  setup(props, context) {
    const ck = ref(props.active);
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
