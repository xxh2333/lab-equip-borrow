import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useUserStore = defineStore('user', () => {
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'));
    const token = ref(localStorage.getItem('token') || '');

    const isLoggedIn = computed(() => !!token.value);
    const isAdmin = computed(() => user.value?.role === 'admin');

    function setAuth(userData, authToken) {
        user.value = userData;
        token.value = authToken;
        localStorage.setItem('user', JSON.stringify(userData));
        localStorage.setItem('token', authToken);
    }

    function logout() {
        user.value = null;
        token.value = '';
        localStorage.removeItem('user');
        localStorage.removeItem('token');
    }

    return { user, token, isLoggedIn, isAdmin, setAuth, logout };
});
