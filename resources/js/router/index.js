import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    // 公开页面
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/Login.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/views/Register.vue'),
        meta: { requiresAuth: false },
    },

    // 受保护页面 - 普通用户
    {
        path: '/',
        name: 'home',
        component: () => import('@/views/Home.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/my-bookings',
        name: 'my-bookings',
        component: () => import('@/views/MyBookings.vue'),
        meta: { requiresAuth: true },
    },

    // 管理员页面
    {
        path: '/admin/bookings',
        name: 'admin.bookings',
        component: () => import('@/views/admin/Bookings.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/equipments',
        name: 'admin.equipments',
        component: () => import('@/views/admin/Equipments.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },

    // 兜底路由
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// 路由守卫：鉴权拦截
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token');

    if (to.meta.requiresAuth && !token) {
        next({ name: 'login' });
    } else if (!to.meta.requiresAuth && token) {
        next({ name: 'home' });
    } else {
        next();
    }
});

export default router;
