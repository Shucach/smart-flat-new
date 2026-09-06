<template>
    <div
        :id="id_modal"
        class="modal fade"
        tabindex="-1"
        role="dialog"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body p-4">
                    <div class="text-center">
                        <i class="dripicons-information h1 text-primary"></i>
                        <h4 class="mt-2">{{ title }}</h4>
                        <p v-if="text" class="mt-3">{{ text }}</p>
                        <slot></slot>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ConfirmAction',
    props: {
        id_modal: {
            type: String,
            required: true,
        },
        title: {
            type: String,
            required: true,
        },
        text: {
            type: String,
            required: false,
        },
    },
    setup() {
        //
    },
};
</script>

<style scoped></style>
