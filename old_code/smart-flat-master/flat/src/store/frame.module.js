import Api from '@/api/api';

// initial state
const state = () => ({
    //
});

const actions = {
    async getSmartFrameList({ commit }, params) {
        return Api.__GET('/api/smart-frame/list', params).then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },

    async postSmartFrameDelete({ commit }, params) {
        return Api.__POST('/api/smart-frame/delete', params).then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },

    async postSmartFrameSave({ commit }, params) {
        return Api.__POST('/api/smart-frame/save', params).then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },

    async postSmartFrameReboot({ commit }, params) {
        return Api.__POST('/api/smart-frame/reboot', params).then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },
    async postSmartFrameShutDown({ commit }, params) {
        return Api.__POST('/api/smart-frame/shut-down', params).then(
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
