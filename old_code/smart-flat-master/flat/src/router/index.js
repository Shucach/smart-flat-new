import { createRouter, createWebHistory } from 'vue-router';
import auth from '@/middleware/auth';
import notAuth from '@/middleware/notAuth';

import Login from '@/views/Login.vue';
import MediaView from '@/views/MediaView.vue';
import HomeView from '@/views/HomeView.vue';
import SmartFrameView from '@/views/SmartFrameView.vue';
import SystemView from '@/views/SystemView.vue';

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: Login,
            beforeEnter: [notAuth],
        },
        {
            path: '/',
            name: 'home',
            component: HomeView,
            beforeEnter: [auth],
        },
        {
            path: '/media',
            name: 'media',
            component: MediaView,
            beforeEnter: [auth],
        },
        {
            path: '/smart-frame',
            name: 'smart_frame',
            component: SmartFrameView,
            beforeEnter: [auth],
        },
        {
            path: '/system',
            name: 'system',
            component: SystemView,
            beforeEnter: [auth],
        },
    ],
});

export default router;
