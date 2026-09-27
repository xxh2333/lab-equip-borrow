<template>
    <div class="register-page">
        <h1>注册</h1>
        <form @submit.prevent="handleRegister">
            <div class="form-group">
                <label>学号/邮箱</label>
                <input v-model="form.account" type="text" required />
            </div>
            <div class="form-group">
                <label>姓名</label>
                <input v-model="form.name" type="text" required />
            </div>
            <div class="form-group">
                <label>密码</label>
                <input v-model="form.password" type="password" required />
            </div>
            <button type="submit">注册</button>
        </form>
        <p>已有账号？<router-link :to="{ name: 'login' }">去登录</router-link></p>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import request from '@/api/request';

const router = useRouter();

const form = reactive({
    account: '',
    name: '',
    password: '',
});

async function handleRegister() {
    try {
        await request.post('/register', form);
        alert('注册成功，请登录');
        router.push({ name: 'login' });
    } catch (error) {
        alert(error.message);
    }
}
</script>

<style scoped>
.register-page {
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
