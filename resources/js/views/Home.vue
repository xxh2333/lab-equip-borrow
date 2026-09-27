<template>
    <div class="home-page">
        <header>
            <h1>实验室设备借用系统</h1>
            <nav>
                <router-link :to="{ name: 'home' }">设备大厅</router-link>
                <router-link :to="{ name: 'my-bookings' }">我的借用</router-link>
                <router-link v-if="userStore.isAdmin" :to="{ name: 'admin.bookings' }">申请审核</router-link>
                <router-link v-if="userStore.isAdmin" :to="{ name: 'admin.equipments' }">设备管理</router-link>
                <button @click="handleLogout">退出</button>
            </nav>
        </header>

        <main>
            <h2>设备大厅</h2>
            <div class="filter-bar">
                <input v-model="keyword" type="text" placeholder="搜索设备名称..." />
                <select v-model="category">
                    <option value="">全部分类</option>
                </select>
            </div>

            <div v-if="equipments.length === 0" class="empty">暂无设备</div>

            <div class="equipment-list">
                <div v-for="item in equipments" :key="item.id" class="equipment-card">
                    <h3>{{ item.name }}</h3>
                    <p>分类：{{ item.category }}</p>
                    <p>描述：{{ item.description }}</p>
                    <p>可借：{{ item.available_qty }} / {{ item.total_qty }}</p>
                    <span :class="['status', item.status]">{{ item.status_text }}</span>
                    <button :disabled="item.available_qty <= 0" @click="handleBorrow(item)">
                        借用
                    </button>
                </div>
            </div>
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

const equipments = ref([]);
const keyword = ref('');
const category = ref('');

async function fetchEquipments() {
    try {
        const res = await request.get('/equipments', {
            params: { keyword: keyword.value, category: category.value },
        });
        equipments.value = res.data || [];
    } catch (error) {
        alert(error.message);
    }
}

function handleBorrow(item) {
    const startDate = prompt('请输入借用开始日期（YYYY-MM-DD）：');
    if (!startDate) return;
    const endDate = prompt('请输入借用结束日期（YYYY-MM-DD）：');
    if (!endDate) return;

    request.post('/bookings', {
        equipment_id: item.id,
        start_date: startDate,
        end_date: endDate,
    }).then(() => {
        alert('申请已提交，等待审核');
    }).catch((error) => {
        alert(error.message);
    });
}

function handleLogout() {
    userStore.logout();
    router.push({ name: 'login' });
}

onMounted(fetchEquipments);
</script>

<style scoped>
.home-page {
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
.filter-bar {
    margin: 20px 0;
}
.filter-bar input,
.filter-bar select {
    padding: 8px;
    margin-right: 10px;
}
.equipment-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}
.equipment-card {
    border: 1px solid #eee;
    padding: 15px;
    border-radius: 8px;
}
.status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
}
.status.available {
    background: #d4edda;
    color: #155724;
}
.status.maintenance {
    background: #fff3cd;
    color: #856404;
}
.empty {
    text-align: center;
    padding: 40px;
    color: #999;
}
</style>
