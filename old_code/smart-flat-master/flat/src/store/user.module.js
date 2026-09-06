import Api from '@/api/api';

// initial state
const state = () => ({
    user: null,
});

// getters
const getters = {
    isAuth: (state, getters) => {
        if (localStorage.token) {
            return true;
        }
        return false;
    },
};

// actions
const actions = {
    csrf({ commit }) {
        return Api.__GET('/sanctum/csrf-cookie').then(
            (response) => {
                return response;
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },
    async login({ commit }, params) {
        return Api.__POST('/api/login', params).then(
            (response) => {
                return Promise.resolve(response);
            },
            (error) => {
                return Promise.reject(error);
            },
        );
    },
};

// mutations
const mutations = {
    // setProducts (state, products) {
    //     state.all = products
    // },
    //
    // decrementProductInventory (state, { id }) {
    //     const product = state.all.find(product => product.id === id)
    //     product.inventory--
    // }
};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations,
};
