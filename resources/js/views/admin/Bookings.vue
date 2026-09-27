<template>
    <div class="admin-bookings-page">
        <header>
            <h1>申请审核</h1>
            <nav>
                <router-link :to="{ name: 'home' }">设备大厅</router-link>
                <router-link :to="{ name: 'admin.bookings' }">申请审核</router-link>
                <router-link :to="{ name: 'admin.equipments' }">设备管理</router-link>
                <button @click="handleLogout">退出</button>
            </nav>
        </header>

        <main>
            <table v-if="bookings.length > 0">
                <thead>
                    <tr>
                        <th>申请人</th>
                        <th>设备名称</th>
                        <th>借用周期</th>
                        <th>用途</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in bookings" :key="item.id">
                        <td>{{ item.user_name }}</td>
                        <td>{{ item.equipment_name }}</td>
                        <td>{{ item.start_date }} ~ {{ item.end_date }}</td>
                        <td>{{ item.purpose || '无' }}</td>
                        <td>
                            <button class="approve" @click="handleApprove(item.id)">通过</button>
                            <button class="reject" @click="handleReject(item.id)">拒绝</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="empty">暂无待审核申请</div>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/stores/user';
import request from '@/api/request';

const router = useRouter();
const userStore = useUserStore();

const bookings = ref([]);

async function fetchBookings() {
    try {
        const res = await request.get('/admin/bookings/pending');
        bookings.value = res.data || [];
    } catch (error) {
        alert(error.message);
    }
}

function handleApprove(id) {
    request.put(`/admin/bookings/${id}/approve`).then(() => {
        alert('已通过');
        fetchBookings();
    }).catch((error) => alert(error.message));
}

function handleReject(id) {
    request.put(`/admin/bookings/${id}/reject`).then(() => {
        alert('已拒绝');
        fetchBookings();
    }).catch((error) => alert(error.message));
}

function handleLogout() {
    userStore.logout();
    router.push({ name: 'login' });
}

onMounted(fetchBookings);
</script>

<style scoped>
.admin-bookings-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}
nav a {
    margin-right: 15px;
    text-decoration: none;
    color: #333;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    padding: 10px;
    border: 1px solid #eee;
    text-align: left;
}
.approve { background: #d4edda; color: #155724; border: none; padding: 5px 10px; cursor: pointer; margin-right: 5px; }
.reject { background: #f8d7da; color: #721c24; border: none; padding: 5px 10px; cursor: pointer; }
.empty {
    text-align: center;
    padding: 40px;
    color: #999;
}
</style>
