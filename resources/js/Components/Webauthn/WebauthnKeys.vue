<script setup>
import { ref, nextTick, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Button from '@/Components/PrimaryButton.vue';
import RegisterKey from './RegisterKey.vue';
import DeleteKeyModal from './DeleteKeyModal.vue';
import UpdateKey from './UpdateKey.vue';
import { webAuthnNotSupportedMessage } from '@/methods.js';
import { startRegistration, browserSupportsWebAuthn } from '@simplewebauthn/browser';

const props = defineProps({
  webauthnKeys: Array,
  publicKey: Object,
});

const isSupported = ref(true);
const errorMessage = ref('');

const register = ref(false);
const registerForm = useForm({
  name: '',
});
const keyBeingDeleted = ref(null);
const keyBeingUpdated = ref(null);

const nameUpdate = computed(() =>
  keyBeingUpdated.value > 0 ? props.webauthnKeys.find((key) => key.id === keyBeingUpdated.value).name : '',
);

onMounted(() => {
  if (!browserSupportsWebAuthn()) {
    isSupported.value = false;
    errorMessage.value = webAuthnNotSupportedMessage();
  }

  if (props.publicKey) {
    showRegisterModal();
    nextTick().then(() => registerWaitForKey(props.publicKey));
  }
});

const _errorMessage = (name, message) => {
  switch (name) {
    case 'InvalidStateError':
      return 'This key is already registered. It’s not necessary to register it again.';
    case 'NotAllowedError':
      return 'The operation either timed out or was not allowed.';
    default:
      return message;
  }
};

const showRegisterModal = () => {
  errorMessage.value = '';
  register.value = true;
};

const start = () => {
  errorMessage.value = '';
  registerForm.clearErrors();
};

const registerWaitForKey = (publicKey) => {
  startRegistration(publicKey)
    .then((data) => webauthnRegisterCallback(data))
    .catch((error) => {
      errorMessage.value = _errorMessage(error.name, error.message);
    });
};

const webauthnRegisterCallback = (data) => {
  registerForm
    .transform((form) => ({
      ...form,
      ...data,
    }))
    .post(route('webauthn.store'), {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        register.value = false;
        registerForm.reset();
      },
      onError: (error) => {
        errorMessage.value = error.email ?? error.data.errors.webauthn;
      },
    });
};
</script>

<template>
    <div>

      <div v-if="!isSupported">
        {{ webAuthnNotSupportedMessage() }}
      </div>

      <div v-else-if="register" class="p-6 sm:px-20 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800">
        <RegisterKey
          :error-message="errorMessage"
          :form="registerForm"
          :name="registerForm.name"
          @update:name="registerForm.name = $event"
          @start="start"
          @stop="register = false"
          @register="registerWaitForKey" />
      </div>

      <div v-else-if="keyBeingUpdated > 0" class="p-6 sm:px-20 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800">
        <UpdateKey :keyid="keyBeingUpdated" :name-update="nameUpdate" @close="keyBeingUpdated = null" />
      </div>

      <div v-else class="pt-5">
            <div class="shadow dark:shadow-gray-700 overflow-hidden border-b border-gray-200 dark:border-gray-800 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-slate-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                Dispositivo
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider hidden md:block">
                                Último Uso
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                        <tr v-if="webauthnKeys.length === 0">
                            <td colspan="3" class="px-6 py-4 whitespace-nowrap text-center">
                                <em>No keys registered yet</em>
                            </td>
                        </tr>
                        <tr v-for="key in webauthnKeys" :key="key.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <strong class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                    {{ key.name }}
                                </strong>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap time hidden md:block">
                                <span class="text-sm text-gray-500 dark:text-gray-300">
                                    {{ key.last_active }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <SecondaryButton class="pointer text-indigo-400 dark:text-indigo-600 hover:text-indigo-600 hover:dark:text-indigo-400" href="" @click="keyBeingUpdated = key.id">
                                    Update
                                </SecondaryButton>

                                <SecondaryButton class="ml-2 pointer text-indigo-400 dark:text-indigo-600 hover:text-indigo-600 hover:dark:text-indigo-400" @click="keyBeingDeleted = key.id">
                                    Delete
                                </SecondaryButton>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-8 text-2xl">

                <Button type="button" @click="showRegisterModal">
                    Registrar Navegador
                </Button>
            </div>
        </div>
      <DeleteKeyModal :keyid="keyBeingDeleted" @close="keyBeingDeleted = null" />
    </div>
</template>
