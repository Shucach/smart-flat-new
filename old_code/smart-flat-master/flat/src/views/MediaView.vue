<template>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Медіа</h4>
                        </div>
                        <div class="card-box">
                            <form
                                ref="setDownloadForm"
                                @submit.prevent="onSetDownload"
                                method="post"
                            >
                                <div class="form-group mb-3">
                                    <h5 class="mt-0">Папка для завантаження</h5>
                                    <select
                                        name="folder"
                                        class="form-control"
                                        required
                                    >
                                        <optgroup label="Фільми">
                                            <option selected value="Films/HD/">
                                                HD
                                            </option>
                                            <option value="Serials">
                                                Серіали
                                            </option>
                                        </optgroup>
                                        <optgroup label="Мультфільми">
                                            <option value="Cartoons/All">
                                                Повнометражні
                                            </option>
                                            <option value="Cartoons/Serials">
                                                Серіали
                                            </option>
                                        </optgroup>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <h5 class="mt-0">Torrent файл</h5>
                                    <div class="input-group">
                                        <div class="form-group">
                                            <input
                                                required
                                                type="file"
                                                name="torrent"
                                                id="torrent-file"
                                                class="form-control-file"
                                                accept=".torrent"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <button
                                        type="submit"
                                        class="btn btn-success waves-effect waves-light"
                                        :disabled="is_set_torrent"
                                    >
                                        <span>Розпочати</span>
                                        <span
                                            v-if="is_set_torrent"
                                            class="spinner-border spinner-border-sm mr-1"
                                            role="status"
                                            aria-hidden="true"
                                        ></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="card-box">
                            <h5 class="mt-0">Завантаження</h5>
                            <div class="table-responsive">
                                <table
                                    v-if="download_data.length"
                                    class="table-borderless table-nowrap table-hover table-centered m-0 table"
                                >
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Дії</th>
                                            <th>Статус</th>
                                            <th>Назва</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(
                                                item, index
                                            ) in download_data"
                                            :key="index"
                                        >
                                            <td>
                                                <button
                                                    @click="
                                                        setDeleteDownload(
                                                            item.id,
                                                        )
                                                    "
                                                    data-toggle="modal"
                                                    data-target="#confirm-delete-download"
                                                    class="btn btn-danger btn-xs danger mr-2"
                                                >
                                                    <i
                                                        class="fas fa-trash-alt"
                                                    ></i>
                                                </button>
                                                <button
                                                    :disabled="
                                                        is_toggle_download
                                                    "
                                                    @click="
                                                        stopDownload(item.id)
                                                    "
                                                    v-if="item.process_id"
                                                    class="btn btn-warning btn-xs"
                                                >
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                                <button
                                                    :disabled="
                                                        is_toggle_download
                                                    "
                                                    @click="
                                                        runDownload(item.id)
                                                    "
                                                    v-if="!item.process_id"
                                                    class="btn btn-success btn-xs"
                                                >
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            </td>
                                            <td>
                                                <span
                                                    v-if="
                                                        item.loaded_percent <
                                                        100
                                                    "
                                                    class="badge bg-soft-warning text-warning"
                                                    >Завантаження:
                                                    <strong
                                                        >{{
                                                            item.loaded_percent
                                                        }}%</strong
                                                    ></span
                                                >
                                                <span
                                                    v-if="
                                                        item.loaded_percent ==
                                                        100
                                                    "
                                                    class="badge bg-soft-success text-success"
                                                    >Завантажений</span
                                                >
                                                <span
                                                    style="display: none"
                                                    class="badge bg-soft-danger text-danger"
                                                    >Роздача</span
                                                >
                                            </td>

                                            <td>
                                                <h5
                                                    class="font-weight-normal m-0"
                                                >
                                                    {{ item.torrent_name }}
                                                </h5>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p v-if="!download_data.length">
                                    Актині завантаження відсутні 😮‍
                                </p>
                            </div>
                            <!-- end .table-responsive-->
                        </div>

                        <div class="card-box">
                            <h5 class="mt-0">Фільмотека</h5>
                            <div class="row">
                                <div class="col-12" id="basicTree">
                                    <div
                                        class="text-center"
                                        v-if="is_loader_tree && !is_loaded_data"
                                    >
                                        <img
                                            src="/images/preloader.svg"
                                            width="60"
                                        />
                                    </div>
                                    <!-- Left sidebar -->
                                    <div v-if="false" class="inbox-leftbar">
                                        <div class="btn-group d-block mb-2">
                                            <button
                                                type="button"
                                                class="btn btn-success btn-block waves-effect waves-light dropdown-toggle"
                                                data-toggle="dropdown"
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                            >
                                                <i class="mdi mdi-plus"></i>
                                                Create New
                                            </button>
                                            <div class="dropdown-menu">
                                                <a
                                                    class="dropdown-item"
                                                    href="#"
                                                    ><i
                                                        class="mdi mdi-folder-plus-outline mr-1"
                                                    ></i>
                                                    Folder</a
                                                >
                                                <a
                                                    class="dropdown-item"
                                                    href="#"
                                                    ><i
                                                        class="mdi mdi-file-plus-outline mr-1"
                                                    ></i>
                                                    File</a
                                                >
                                                <a
                                                    class="dropdown-item"
                                                    href="#"
                                                    ><i
                                                        class="mdi mdi-file-document mr-1"
                                                    ></i>
                                                    Document</a
                                                >
                                                <a
                                                    class="dropdown-item"
                                                    href="#"
                                                    ><i
                                                        class="mdi mdi-upload mr-1"
                                                    ></i>
                                                    Choose File</a
                                                >
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Left sidebar -->

                                    <div
                                        v-if="!is_loader_tree && is_loaded_data"
                                    >
                                        <div class="mt-3">
                                            <div class="table-responsive">
                                                <table
                                                    v-if="folder_data.length"
                                                    class="table-centered table-nowrap mb-0 table"
                                                >
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th
                                                                style="
                                                                    width: 100px;
                                                                "
                                                                class="border-0"
                                                            >
                                                                Назва
                                                            </th>
                                                            <th
                                                                class="border-0"
                                                            >
                                                                Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(
                                                                item, index
                                                            ) in folder_data"
                                                            :key="index"
                                                        >
                                                            <td
                                                                class="border-0"
                                                            >
                                                                <button
                                                                    @click="
                                                                        deleteByPath(
                                                                            item.path,
                                                                        )
                                                                    "
                                                                    v-if="
                                                                        item.is_can_delete
                                                                    "
                                                                    type="button"
                                                                    data-toggle="modal"
                                                                    data-target="#confirm-delete-path"
                                                                    class="btn btn-danger waves-effect waves-light"
                                                                >
                                                                    <i
                                                                        class="fas fa-trash-alt"
                                                                    ></i>
                                                                </button>
                                                            </td>
                                                            <td
                                                                @click="
                                                                    openFolder(
                                                                        item.path,
                                                                    )
                                                                "
                                                                v-if="
                                                                    item.is_folder
                                                                "
                                                                class="border-0"
                                                            >
                                                                <i
                                                                    class="fas fa-folder-open"
                                                                ></i>
                                                                <span
                                                                    class="font-weight-semibold ml-2"
                                                                >
                                                                    {{
                                                                        item.name
                                                                    }}
                                                                </span>
                                                            </td>
                                                            <td
                                                                v-else
                                                                class="border-0"
                                                            >
                                                                <i
                                                                    class="far fa-file-video"
                                                                ></i>
                                                                <span
                                                                    class="font-weight-semibold ml-2"
                                                                >
                                                                    {{
                                                                        item.name
                                                                    }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <span v-else
                                                    >Фільми відсутні 😮‍</span
                                                >
                                            </div>
                                        </div>
                                        <!-- end .mt-3-->
                                    </div>

                                    <p
                                        v-if="
                                            !is_loader_tree && !is_loaded_data
                                        "
                                    >
                                        Дані відсутні!
                                    </p>
                                </div>
                            </div>
                            <!-- end row -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmAction
            id_modal="confirm-delete-path"
            :text="delete_path"
            title="Підтвердити видалення ресурсу!?"
        >
            <button
                @click="confirmDelete"
                data-dismiss="modal"
                type="button"
                class="btn btn-danger waves-effect waves-light mr-2"
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
            id_modal="confirm-delete-download"
            :text="delete_path"
            title="Підтвердити видалення закачки?"
        >
            <button
                @click="deleteDownload"
                data-dismiss="modal"
                type="button"
                class="btn btn-danger waves-effect waves-light mr-2"
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
    name: 'MediaView',
    components: {
        ConfirmAction,
    },
    setup() {
        const store = useStore();
        const setDownloadForm = ref(null);

        const is_loader_tree = ref(true);
        const is_loaded_data = ref(false);

        const delete_path = ref('');
        const selected_path = ref('');

        const download_data = ref([]);
        const folder_data = ref([]);
        const folder = ref('');
        const torrent = ref('');

        const is_toggle_download = ref(false);
        const is_set_torrent = ref(false);
        const delete_torrent_id = ref(false);

        // Folders actions
        async function getFolders() {
            await store
                .dispatch('media/postMediaPage')
                .then(
                    (response) => {
                        const resData = response.data;
                        if (resData.success) {
                            folder_data.value = resData.data.folders;
                            is_loaded_data.value = true;
                        }
                    },
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    is_loader_tree.value = false;
                });
        }
        async function openFolder(path) {
            is_loader_tree.value = true;
            is_loaded_data.value = false;

            await store
                .dispatch('media/postMediaPage', { path: path })
                .then(
                    (response) => {
                        const resData = response.data;
                        if (resData.success) {
                            folder_data.value = resData.data.folders;
                            is_loaded_data.value = true;
                            selected_path.value = path;
                        }
                    },
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    is_loader_tree.value = false;
                });
        }
        async function deleteByPath(path) {
            delete_path.value = path;
        }
        async function confirmDelete(e) {
            //e.target.disabled = true;
            await store
                .dispatch('media/postMediaDelete', { path: delete_path.value })
                .then(
                    (response) => {
                        const resData = response.data;
                        if (resData.success) {
                            if (selected_path.value) {
                                openFolder(selected_path.value);
                            } else {
                                getFolders();
                            }
                        } else {
                            alert('Error');
                        }
                    },
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    //e.target.disabled = false;
                });
        }

        async function stopDownload(id) {
            is_toggle_download.value = true;
            await store
                .dispatch('media/postMediaDownloadStop', { id: id })
                .then(
                    (response) => {
                        const resData = response.data;
                        if (resData.success) {
                            download_data.value = resData.data.downloads;
                        } else {
                            alert('Error stop download');
                        }
                    },
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    is_toggle_download.value = false;
                });
        }
        async function runDownload(id) {
            is_toggle_download.value = true;
            await store
                .dispatch('media/postMediaDownloadRun', { id: id })
                .then(
                    (response) => {
                        const resData = response.data;
                        if (resData.success) {
                            download_data.value = resData.data.downloads;
                        } else {
                            alert('Error stop download');
                        }
                    },
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    is_toggle_download.value = false;
                });
        }

        onMounted(() => {
            getFolders();
        });

        // Download actions
        async function onSetDownload(e) {
            e.preventDefault();
            let $formData = new FormData(setDownloadForm.value);
            is_set_torrent.value = true;

            await store
                .dispatch('media/postMediaDownload', $formData)
                .then(
                    (response) => {
                        const resData = response.data;
                        if (resData.success) {
                            download_data.value = resData.data.downloads;
                        } else {
                            alert('Error set download');
                        }
                    },
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    is_set_torrent.value = false;
                });
        }
        async function getActiveDownload() {
            await store
                .dispatch('media/postMediaDownloadList')
                .then(
                    (response) => {
                        const resData = response.data;
                        if (resData.success) {
                            download_data.value = resData.data.downloads;
                        } else {
                            alert('Error get download list');
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
        async function setDeleteDownload(id) {
            delete_torrent_id.value = id;
        }
        async function deleteDownload() {
            if (delete_torrent_id.value) {
                await store
                    .dispatch('media/postMediaDownloadDelete', {
                        id: delete_torrent_id.value,
                    })
                    .then(
                        (response) => {
                            const resData = response.data;
                            if (resData.success) {
                                alert(resData.data.result);
                                download_data.value = resData.data.downloads;
                            } else {
                                alert('Error media delete');
                            }
                        },
                        () => {
                            alert(
                                'Auth error. Please call to your administrator.',
                            );
                        },
                    )
                    .finally(() => {
                        //
                    });
            }
        }
        onMounted(() => {
            getActiveDownload();
        });

        return {
            setDownloadForm,
            is_loader_tree,
            is_loaded_data,
            delete_path,
            selected_path,
            download_data,
            folder_data,
            folder,
            torrent,
            is_set_torrent,
            delete_torrent_id,
            is_toggle_download,
            getFolders,
            openFolder,
            deleteByPath,
            confirmDelete,
            onSetDownload,
            getActiveDownload,
            setDeleteDownload,
            deleteDownload,
            stopDownload,
            runDownload,
        };
    },
};
</script>
