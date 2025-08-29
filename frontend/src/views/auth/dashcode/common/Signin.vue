<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <Textinput
      v-model="email"
      label="Email"
      type="email"
      placeholder="Type your email"
      name="emil"
      :error="emailError"
      classInput="h-[48px]"
    />
    <Textinput
      v-model="password"
      label="Password"
      type="password"
      placeholder="8+ characters, 1 capitat letter "
      name="password"
      :error="passwordError"
      hasicon
      classInput="h-[48px]"
    />

    <div class="flex justify-between">
      <label class="cursor-pointer flex items-start">
        <input
          type="checkbox"
          class="hidden"
          @change="() => (checkbox = !checkbox)"
        />
        <span
          class="h-4 w-4 border rounded flex-none inline-flex mr-3 relative top-1 transition-all duration-150"
          :class="
            checkbox
              ? 'ring-2 ring-black-500 dark:ring-offset-slate-600 dark:ring-slate-900  dark:bg-slate-900 ring-offset-2 bg-slate-900'
              : 'bg-slate-100 dark:bg-slate-600 border-slate-100 dark:border-slate-600 '
          "
        >
          <img
            v-if="checkbox"
            src="@/assets/images/icon/ck-white.svg"
            alt=""
            class="h-[10px] w-[10px] block m-auto"
          />
        </span>
        <span class="text-slate-500 dark:text-slate-400 text-sm leading-6"
          >Keep me signed in</span
        >
      </label>
      <router-link
        to="/auth/forgot-password"
        class="text-sm text-slate-800 dark:text-slate-400 leading-6 font-medium"
        >Forgot Password?</router-link
      >
    </div>

    <button type="submit" class="btn btn-dark block w-full text-center">
      Sign in
    </button>
  </form>
</template>
<script>
import Textinput from "@/components/Textinput";
import { useField, useForm } from "vee-validate";
import * as yup from "yup";

export default {
  components: {
    Textinput,
  },
  emits: ["submit"],
  setup(_, { emit }) {
    // Define a validation schema
    const schema = yup.object({
      email: yup.string().required("Email is required").email(),
      password: yup.string().required("Password is required").min(8),
    });

    const { handleSubmit } = useForm({ validationSchema: schema });

    const { value: email, errorMessage: emailError } = useField("email");
    const { value: password, errorMessage: passwordError } =
      useField("password");

    const onSubmit = handleSubmit((values) => {
      emit("submit", values);
    });

    return {
      email,
      emailError,
      password,
      passwordError,
      onSubmit,
    };
  },
  data() {
    return {
      checkbox: false,
    };
  },
};
</script>
<style lang="scss"></style>
