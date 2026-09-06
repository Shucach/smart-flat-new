<template>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Розумна рамка</h4>
                        </div>
                        <!--                        <div class="card-box" style="display: none;">-->
                        <!--                            <div class="d-flex flex-column align-items-start">-->
                        <!--                                <button data-toggle="modal" data-target="#confirm-reboot" type="button" class="btn btn-warning waves-effect waves-light mb-3">-->
                        <!--                                    <span class="btn-label"><i class="mdi mdi-alert"></i></span>Перезавантажити-->
                        <!--                                </button>-->
                        <!--                                <button data-toggle="modal" data-target="#confirm-shutdown" type="button" class="btn btn-danger waves-effect waves-light">-->
                        <!--                                    <span class="btn-label"><i class="mdi mdi-close-circle-outline"></i></span>Вимкнути-->
                        <!--                                </button>-->
                        <!--                            </div>-->
                        <!--                        </div>-->
                        <div class="card-box">
                            <form
                                action="/"
                                method="post"
                                class="dropzone dz-clickable"
                                id="drop-frame-zone"
                            >
                                <div class="fallback">
                                    <input
                                        name="images"
                                        ref="images_zone"
                                        type="file"
                                        multiple
                                        accept="image/*"
                                    />
                                </div>
                                <div class="dz-message needsclick">
                                    <i
                                        class="h1 text-muted dripicons-cloud-upload"
                                    ></i>
                                    <h3>Перетягнути файли або натиснути</h3>
                                    <span class="text-muted font-13"
                                        >(Рекомендована вертикальна орієнтація
                                        png, jpg, jpeg)</span
                                    >
                                </div>
                            </form>

                            <!-- Preview -->
                            <div
                                class="dropzone-previews mt-3 mb-3"
                                id="file-previews"
                            ></div>
                            <div class="form-group mb-0">
                                <button
                                    type="submit"
                                    @click="saveImages"
                                    class="btn btn-success waves-effect waves-light"
                                    :disabled="is_block_button"
                                >
                                    <span>Завантажити</span>
                                    <span
                                        v-if="is_block_button"
                                        class="spinner-border spinner-border-sm mr-1"
                                        role="status"
                                        aria-hidden="true"
                                    ></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Галерея</h4>
                        </div>
                        <div class="text-center" v-if="is_load_images">
                            <img src="/images/preloader.svg" width="60" />
                        </div>
                        <div class="mb-2" v-if="delete_names.length">
                            <button
                                type="button"
                                :disabled="is_deleting"
                                data-toggle="modal"
                                data-target="#confirm-delete-images"
                                class="btn btn-outline-danger waves-effect deleting-btn waves-light mr-2"
                            >
                                Видалити ({{ delete_names.length }})
                                <div
                                    v-if="is_deleting"
                                    class="spinner-border text-danger"
                                    role="status"
                                ></div>
                            </button>
                            <button
                                type="button"
                                :disabled="is_deleting"
                                @click="resetSelectedImage"
                                class="btn btn-outline-success waves-effect waves-light"
                            >
                                Відмінити
                            </button>
                        </div>
                        <p
                            class="alert alert-danger"
                            v-if="frame_error"
                            v-html="frame_error"
                        ></p>
                        <div
                            v-if="!is_load_images || all_images.length"
                            class="row"
                        >
                            <div
                                v-for="image in all_images"
                                class="col-md-1 col-xl-1 col-4"
                            >
                                <div
                                    class="card-box product-box p-1"
                                    :class="{ active: delete_names.length }"
                                >
                                    <div
                                        class="product-action d-flex align-items-center justify-content-between w-100 p-1"
                                    >
                                        <div class="checkbox checkbox-warning">
                                            <input
                                                @change="
                                                    selectImages(
                                                        $event,
                                                        image.name,
                                                    )
                                                "
                                                :id="image.name"
                                                :value="image.name"
                                                type="checkbox"
                                                :checked="
                                                    delete_names.includes(
                                                        image.name,
                                                    )
                                                "
                                            />
                                            <label :for="image.name"></label>
                                        </div>
                                        <a
                                            href="javascript: void(0);"
                                            v-if="!delete_names.length"
                                            @click="deleteByPath(image.name)"
                                            data-toggle="modal"
                                            data-target="#confirm-delete-image"
                                            class="btn btn-danger btn-xs waves-effect waves-light"
                                            ><i class="mdi mdi-close"></i>
                                        </a>
                                    </div>
                                    <div class="bg-light">
                                        <img
                                            class="img-fluid"
                                            :src="image.file"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="
                            pagination && pagination.page < pagination.maxPage
                        "
                        class="col-12"
                    >
                        <button
                            @click="nextPage"
                            :disabled="is_load_images"
                            type="button"
                            class="btn btn-success waves-effect waves-light"
                        >
                            Завантажити ще ({{ pagination.page }}/{{
                                pagination.maxPage
                            }})
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- file preview template -->
        <div class="d-none" id="uploadPreviewTemplate">
            <div class="card mt-1 mb-0 border shadow-none">
                <div class="p-2">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <img
                                data-dz-thumbnail
                                src="#"
                                class="avatar-sm bg-light rounded"
                                alt=""
                            />
                        </div>
                        <div class="col pl-0">
                            <a
                                href="javascript:void(0);"
                                class="text-muted font-weight-bold"
                                data-dz-name
                            ></a>
                            <p class="mb-0" data-dz-size></p>
                        </div>
                        <div class="col-auto">
                            <!-- Button -->
                            <a
                                href=""
                                class="btn btn-link btn-lg text-muted"
                                data-dz-remove
                            >
                                <i class="dripicons-cross"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmAction
            id_modal="confirm-delete-images"
            :title="
                'Підтвердити видалення виділених зображень? (' +
                delete_names.length +
                ')'
            "
        >
            <button
                @click="deleteImages"
                data-dismiss="modal"
                type="button"
                class="btn btn-danger waves-effect waves-light mr-2"
            >
                Так
            </button>
            <button
                @click="resetSelectedImage"
                data-dismiss="modal"
                type="button"
                class="btn btn-success waves-effect waves-light"
            >
                Ні
            </button>
        </ConfirmAction>

        <ConfirmAction
            id_modal="confirm-delete-image"
            title="Підтвердити видалення зображення?"
        >
            <button
                @click="deleteImage"
                data-dismiss="modal"
                type="button"
                class="btn btn-danger waves-effect waves-light"
            >
                Так
            </button>
            <button
                data-dismiss="modal"
                type="button"
                class="btn btn-success waves-effect waves-light"
            >
                Ні
            </button>
        </ConfirmAction>

        <ConfirmAction
            id_modal="confirm-reboot"
            title="Підтвердити перезавантаження Smart Frame?"
        >
            <button
                @click="reboot"
                data-dismiss="modal"
                type="button"
                class="btn btn-danger waves-effect waves-light"
            >
                Так
            </button>
            <button
                data-dismiss="modal"
                type="button"
                class="btn btn-success waves-effect waves-light"
            >
                Ні
            </button>
        </ConfirmAction>
        <ConfirmAction
            id_modal="confirm-shutdown"
            title="Підтвердити вимкнення Smart Frame?"
        >
            <button
                @click="shutDown"
                data-dismiss="modal"
                type="button"
                class="btn btn-danger waves-effect waves-light"
            >
                Так
            </button>
            <button
                data-dismiss="modal"
                type="button"
                class="btn btn-success waves-effect waves-light"
            >
                Ні
            </button>
        </ConfirmAction>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useStore } from 'vuex';
import ConfirmAction from '@/components/ConfirmAction.vue';

export default {
    name: 'SmartFrameView',
    components: {
        ConfirmAction,
    },
    setup() {
        const store = useStore();

        const is_block_button = ref(false);
        const is_load_images = ref(true);
        const is_deleting = ref(false);

        const all_images = ref([]);
        const pagination = ref([]);
        const current_page = ref(1);
        const pre_page = ref(3);

        const frame_error = ref('');
        const delete_name = ref('');
        const delete_names = ref([]);

        const filesUploaded = ref([]);
        const photoDropzone = ref('');

        // Image actions
        async function selectImages(event, name) {
            if (event.target.checked) {
                delete_names.value.push(name);
            } else {
                delete_names.value.splice(
                    delete_names.value.findIndex((val) => {
                        return val === name;
                    }),
                    1,
                );
            }
        }
        async function resetSelectedImage() {
            delete_names.value = [];
        }

        function nextPage() {
            current_page.value = current_page.value + 1;
            getImages(true);
        }
        async function getImages(isAppend = false) {
            is_load_images.value = true;
            await store
                .dispatch('frame/getSmartFrameList', {
                    page: current_page.value,
                    prePage: pre_page.value,
                })
                .then(
                    (response) => {
                        const resData = response.data;
                        if (resData.success) {
                            if (isAppend) {
                                all_images.value = all_images.value.concat(
                                    resData.data.images,
                                );
                            } else {
                                all_images.value = resData.data.images;
                            }
                            pagination.value = resData.data.pagination;
                        } else {
                            frame_error.value = resData.message;
                        }
                    },
                    () => {
                        alert('Load image error');
                    },
                )
                .finally(() => {
                    is_load_images.value = false;
                });
        }
        async function deleteByPath(name) {
            delete_name.value = name;
        }
        async function deleteImage() {
            let params = [delete_name.value];
            await store
                .dispatch('frame/postSmartFrameDelete', { names: params })
                .then(
                    (response) => {
                        const resData = response.data;
                        console.log(resData);
                        if (resData.success) {
                            all_images.value = all_images.value.filter(
                                (item) => {
                                    return !params.includes(item.name);
                                },
                            );
                        } else {
                            alert('Error delete image');
                        }
                    },
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    //
                });
        }
        async function deleteImages() {
            is_deleting.value = true;
            let params = delete_names.value;
            await store
                .dispatch('frame/postSmartFrameDelete', { names: params })
                .then(
                    (response) => {
                        const resData = response.data;
                        if (resData.success) {
                            all_images.value = all_images.value.filter(
                                (item) => {
                                    return !params.includes(item.name);
                                },
                            );
                            delete_names.value = [];
                        } else {
                            alert('Error delete image');
                        }
                    },
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    is_deleting.value = false;
                });
        }
        async function saveImages() {
            let formData = new FormData();
            let files = $('#drop-frame-zone')
                .get(0)
                .dropzone.getAcceptedFiles();
            for (let i = 0; i < files.length; i++) {
                formData.append('images[]', files[i]);
            }

            is_block_button.value = true;
            let $this = this;

            await store
                .dispatch('frame/postSmartFrameSave', formData)
                .then(
                    (response) => {
                        const resData = response.data;
                        if (resData.success) {
                            $this.photoDropzone[0].dropzone.removeAllFiles();

                            current_page.value = 1;
                            getImages();
                        }
                    },
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    is_block_button.value = false;
                });
        }

        onMounted(() => {
            photoDropzone.value = $('#drop-frame-zone').dropzone({
                previewsContainer: '#file-previews',
                previewTemplate: document.querySelector(
                    '#uploadPreviewTemplate',
                ).innerHTML,
            });

            current_page.value = 1;
            getImages();
        });

        // Server frame actions
        async function reboot() {
            await store
                .dispatch('frame/postSmartFrameReboot')
                .then(
                    (response) => {
                        console.log(response);
                    },
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    //
                });
        }
        async function shutDown() {
            await store
                .dispatch('frame/postSmartFrameShutDown')
                .then(
                    (response) => {
                        console.log(response);
                    },
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    //
                });
        }

        return {
            is_block_button,
            is_load_images,
            is_deleting,
            all_images,
            frame_error,
            filesUploaded,
            photoDropzone,
            delete_names,
            pagination,
            selectImages,
            resetSelectedImage,
            getImages,
            deleteByPath,
            deleteImage,
            deleteImages,
            saveImages,
            reboot,
            shutDown,
            nextPage,
        };
    },
};
</script>

<style scoped>
.product-action .checkbox label {
    position: absolute;
}
.product-action .checkbox label::before {
    margin-left: -10px;
}
.product-action .checkbox input[type='checkbox']:checked + label::after {
    left: 14px;
}
.product-box:hover .product-action,
.product-box.active .product-action {
    opacity: 1;
    visibility: visible;
    transform: translateX(0);
}
.deleting-btn .spinner-border {
    width: 20px;
    height: 20px;
}
</style>
