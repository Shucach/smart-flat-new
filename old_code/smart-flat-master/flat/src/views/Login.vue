<template>
    <div class="account-pages mt-5 mb-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card bg-pattern mt-5">
                        <div class="card-body p-4">
                            <div class="m-auto w-75 text-center">
                                <div class="auth-logo">
                                    <div class="logo logo-dark text-center">
                                        <span class="logo-lg">
                                            <img
                                                src="/images/logo.png"
                                                srcset="/images/logo@2x.png 2x"
                                                width="35"
                                            />
                                        </span>
                                    </div>
                                    <div class="logo logo-light text-center">
                                        <span class="logo-lg">
                                            <img
                                                src="/images/logo.png"
                                                srcset="/images/logo@2x.png 2x"
                                                width="35"
                                            />
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="login()">
                                <div class="form-group mb-3">
                                    <label for="emailaddress">Email</label>
                                    <input
                                        class="form-control"
                                        type="email"
                                        name="email"
                                        v-model="email"
                                        id="emailaddress"
                                        placeholder="Введи Email"
                                        required
                                    />
                                </div>

                                <div class="form-group mb-3">
                                    <label for="password">Пароль</label>
                                    <div class="input-group input-group-merge">
                                        <input
                                            type="password"
                                            id="password"
                                            name="password"
                                            v-model="password"
                                            class="form-control"
                                            placeholder="Введи пароль"
                                            required
                                        />
                                        <div
                                            class="input-group-append"
                                            data-password="false"
                                        >
                                            <div class="input-group-text">
                                                <span
                                                    class="password-eye"
                                                ></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0 text-center">
                                    <button
                                        class="btn btn-primary btn-block"
                                        type="submit"
                                    >
                                        <span
                                            v-if="isLoad"
                                            class="spinner-border spinner-border-sm mr-1"
                                            role="status"
                                            aria-hidden="true"
                                        ></span>
                                        Увійти
                                    </button>
                                </div>
                            </form>
                        </div>
                        <!-- end card-body -->
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
</template>

<script>
import { ref, onMounted, nextTick } from 'vue';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';

export default {
    name: 'Login',
    setup() {
        const store = useStore();
        const router = useRouter();

        onMounted(() => {
            store.dispatch('user/csrf');
        });

        const isLoad = ref(false);
        const email = ref('');
        const password = ref('');

        async function login() {
            await store
                .dispatch('user/login', {
                    email: email.value,
                    password: password.value,
                })
                .then(
                    (response) => {
                        if (response.status === 200) {
                            localStorage.setItem('token', response.data.token);
                            nextTick(() => {
                                location.reload();
                            });
                        } else {
                            alert(
                                'Auth error. Please call your administrator.',
                            );
                        }
                    },
                    () => {
                        alert('Auth error. Please call your administrator.');
                    },
                )
                .finally(() => {
                    isLoad.value = false;
                });
        }

        return {
            isLoad,
            email,
            password,
            login,
        };
    },
};
</script>

<style scoped></style>
