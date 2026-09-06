import Api from '@/api/api';

// initial state
const state = () => ({
    //
});

const actions = {
    async reboot({ commit }, params) {
        return Api.__POST('/api/server/reboot').then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },
    async shutDown({ commit }, params) {
        return Api.__POST('/api/server/shut-down').then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },
    async getSystemLoad({ commit }, params) {
        return Api.__POST('/api/server/get-system-load').then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },
    async getDiskSpace({ commit }, params) {
        return Api.__POST('/api/server/disk-info').then(
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
