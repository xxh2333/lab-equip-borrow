<template>
    <div class="login-page">
        <h1>登录</h1>
        <form @submit.prevent="handleLogin">
            <div class="form-group">
                <label>账号（学号/邮箱）</label>
                <input v-model="form.account" type="text" required />
            </div>
            <div class="form-group">
                <label>密码</label>
                <input v-model="form.password" type="password" required />
            </div>
            <button type="submit">登录</button>
        </form>
        <p>还没有账号？<router-link :to="{ name: 'register' }">去注册</router-link></p>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/stores/user';
import request from '@/api/request';

const router = useRouter();
const userStore = useUserStore();

const form = reactive({
    account: '',
    password: '',
});

async function handleLogin() {
    try {
        const res = await request.post('/login', form);
        userStore.setAuth(res.data.user, res.data.token);
        router.push({ name: 'home' });
    } catch (error) {
        alert(error.message);
    }
}
</script>

<style scoped>
.login-page {
    max-width: 400px;
    margin: 100px auto;
    padding: 20px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
}
.form-group input {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
}
button {
    width: 100%;
    padding: 10px;
    cursor: pointer;
}
</style>
