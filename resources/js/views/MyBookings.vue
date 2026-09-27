<template>
    <div class="my-bookings-page">
        <header>
            <h1>我的借用记录</h1>
            <nav>
                <router-link :to="{ name: 'home' }">设备大厅</router-link>
                <router-link :to="{ name: 'my-bookings' }">我的借用</router-link>
                <button @click="handleLogout">退出</button>
            </nav>
        </header>

        <main>
            <table v-if="bookings.length > 0">
                <thead>
                    <tr>
                        <th>设备名称</th>
                        <th>申请时间</th>
                        <th>借用周期</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in bookings" :key="item.id">
                        <td>{{ item.equipment_name }}</td>
                        <td>{{ item.created_at }}</td>
                        <td>{{ item.start_date }} ~ {{ item.end_date }}</td>
                        <td>
                            <span :class="['status', item.status]">{{ item.status_text }}</span>
                        </td>
                        <td>
                            <button v-if="item.status === 'approved'" @click="handleReturn(item.id)">
                                归还
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="empty">暂无借用记录</div>
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
        const res = await request.get('/bookings/my');
        bookings.value = res.data || [];
    } catch (error) {
        alert(error.message);
    }
}

function handleReturn(id) {
    if (!confirm('确认归还该设备？')) return;

    request.put(`/bookings/${id}/return`).then(() => {
        alert('归还成功');
        fetchBookings();
    }).catch((error) => {
        alert(error.message);
    });
}

function handleLogout() {
    userStore.logout();
    router.push({ name: 'login' });
}

onMounted(fetchBookings);
</script>

<style scoped>
.my-bookings-page {
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
.status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
}
.status.pending { background: #fff3cd; color: #856404; }
.status.approved { background: #d4edda; color: #155724; }
.status.rejected { background: #f8d7da; color: #721c24; }
.status.returned { background: #d1ecf1; color: #0c5460; }
.empty {
    text-align: center;
    padding: 40px;
    color: #999;
}
</style>
