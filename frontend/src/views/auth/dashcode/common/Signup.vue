<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <Textinput
      v-model="name"
      :id="ids.name"
      label="Full name"
      type="text"
      placeholder="Full Name"
      name="name"
      :error="nameError"
      classInput="h-[48px]"
    />
    <Textinput
      v-model="email"
      :id="ids.email"
      label="Email"
      type="email"
      placeholder="Type your email"
      name="emil"
      :error="emailError"
      classInput="h-[48px]"
    />
    <Textinput
      v-model="password"
      :id="ids.password"
      label="Password"
      type="password"
      placeholder="8+ characters, 1 capitat letter "
      name="password"
      :error="passwordError"
      hasicon
      classInput="h-[48px]"
    />

    <input
      :id="ids.accept"
      type="checkbox"
      class="hidden"
      @change="() => (checkbox = !checkbox)"
    />
    <label :for="ids.accept" class="cursor-pointer flex items-start">
      <span
        class="h-4 w-4 border rounded inline-flex mr-3 relative flex-none top-1 transition-all duration-150"
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
        >You accept our Terms and Conditions and Privacy Policy</span
      >
    </label>

    <button type="submit" class="btn btn-dark block w-full text-center">
      Create an account
    </button>
  </form>
</template>
<script>
import Textinput from "@/components/Textinput";
import { useField, useForm } from "vee-validate";
import * as yup from "yup";

import { inject } from "vue";
import { useRouter } from "vue-router";
import { useNotify } from "@/plugins/notify";
export default {
  components: {
    Textinput,
  },
  setup() {
    // Define a validation schema
    const schema = yup.object({
      email: yup.string().required(" Email is required").email(),
      password: yup.string().required("Password is  required").min(8),
      name: yup.string().required("Full name is required"),
    });
    const swal = inject("$swal");
    const notify = useNotify();
    const router = useRouter();

    // Create a form context with the validation schema
    const users = [];
    const { handleSubmit } = useForm({
      validationSchema: schema,
    });
    // No need to define rules for fields

    const { value: email, errorMessage: emailError } = useField("email");
    const { value: name, errorMessage: nameError } = useField("name");
    const { value: password, errorMessage: passwordError } =
      useField("password");

    const onSubmit = handleSubmit((values) => {
      // add value into user array if same email not found
      if (!users.find((user) => user.email === values.email)) {
        users.push(values);
        localStorage.setItem("users", JSON.stringify(users));
        router.push("/");
        notify.success("Account Create successfully", {
          timeout: 2000,
        });
      } else {
        // use sweetalert 2
        swal.fire({
          title: "Email already exists",
          text: "Please try another email",
          icon: "error",
          confirmButtonText: "Ok",
        });
      }
    });

    return {
      email,
      name,
      nameError,
      emailError,
      password,
      passwordError,
      onSubmit,
    };
  },
  data() {
    return {
      checkbox: false,
      ids: {
        name: 'signup-name',
        email: 'signup-email',
        password: 'signup-password',
        accept: 'signup-accept',
      },
    };
  },
};
</script>
<style lang="scss"></style>
