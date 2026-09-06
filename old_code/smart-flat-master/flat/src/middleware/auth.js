import store from '@/store';

export default (to, from, next) => {
    if (!store.getters['user/isAuth']) {
        next({ name: 'login' });
    } else {
        next();
    }
};
