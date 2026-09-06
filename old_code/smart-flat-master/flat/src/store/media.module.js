import Api from '@/api/api';

// initial state
const state = () => ({
    //
});

const actions = {
    async postMediaPage({ commit }, params) {
        return Api.__POST('/api/media/page', params).then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },

    async postMediaDelete({ commit }, params) {
        return Api.__POST('/api/media/delete', params).then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },

    async postMediaDownload({ commit }, params) {
        return Api.__POST('/api/media/download', params).then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },

    async postMediaDownloadList({ commit }, params) {
        return Api.__POST('/api/media/download/list', params).then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },

    async postMediaDownloadDelete({ commit }, params) {
        return Api.__POST('/api/media/download/delete', params).then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },

    async postMediaDownloadStop({ commit }, params) {
        return Api.__POST('/api/media/download/stop', params).then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },

    async postMediaDownloadRun({ commit }, params) {
        return Api.__POST('/api/media/download/run', params).then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },
};

export default {
    namespaced: true,
    state,
    actions,
};
