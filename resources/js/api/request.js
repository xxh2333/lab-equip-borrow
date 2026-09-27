import axios from 'axios';
import router from '@/router';

const request = axios.create({
    baseURL: '/api',
    timeout: 10000,
});

// 请求拦截器：注入 Token
request.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// 响应拦截器：统一处理错误
request.interceptors.response.use(
    (response) => response.data,
    (error) => {
        if (error.response) {
            const status = error.response.status;

            if (status === 401) {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                router.push({ name: 'login' });
                return Promise.reject(new Error('登录已过期，请重新登录'));
            }

            if (status === 403) {
                return Promise.reject(new Error('没有权限执行此操作'));
            }

            const message = error.response.data?.message || '请求失败，请稍后重试';
            return Promise.reject(new Error(message));
        }

        return Promise.reject(new Error('网络异常，请检查网络连接'));
    }
);

export default request;
