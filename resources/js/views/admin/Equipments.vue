<template>
    <div class="admin-equipments-page">
        <header>
            <h1>设备管理</h1>
            <nav>
                <router-link :to="{ name: 'home' }">设备大厅</router-link>
                <router-link :to="{ name: 'admin.bookings' }">申请审核</router-link>
                <router-link :to="{ name: 'admin.equipments' }">设备管理</router-link>
                <button @click="handleLogout">退出</button>
            </nav>
        </header>

        <main>
            <button class="add-btn" @click="showAddForm = !showAddForm">+ 新增设备</button>

            <div v-if="showAddForm" class="form-panel">
                <input v-model="form.name" placeholder="设备名称" />
                <input v-model="form.category" placeholder="分类" />
                <input v-model="form.description" placeholder="描述" />
                <input v-model.number="form.total_qty" type="number" placeholder="总库存" />
                <button @click="handleAdd">保存</button>
                <button @click="showAddForm = false">取消</button>
            </div>

            <table v-if="equipments.length > 0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>名称</th>
                        <th>分类</th>
                        <th>总库存</th>
                        <th>可借数</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in equipments" :key="item.id">
                        <td>{{ item.id }}</td>
                        <td>{{ item.name }}</td>
                        <td>{{ item.category }}</td>
                        <td>{{ item.total_qty }}</td>
                        <td>{{ item.available_qty }}</td>
                        <td>{{ item.status_text }}</td>
                        <td>
                            <button @click="handleToggleStatus(item)">
                                {{ item.status === 'available' ? '下架' : '上架' }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="empty">暂无设备</div>
        </main>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/stores/user';
import request from '@/api/request';

const router = useRouter();
const userStore = useUserStore();

const equipments = ref([]);
const showAddForm = ref(false);

const form = reactive({
    name: '',
    category: '',
    description: '',
    total_qty: 1,
});

async function fetchEquipments() {
    try {
        const res = await request.get('/admin/equipments');
        equipments.value = res.data || [];
    } catch (error) {
        alert(error.message);
    }
}

async function handleAdd() {
    try {
        await request.post('/admin/equipments', form);
        showAddForm.value = false;
        Object.assign(form, { name: '', category: '', description: '', total_qty: 1 });
        fetchEquipments();
    } catch (error) {
        alert(error.message);
    }
}

function handleToggleStatus(item) {
    const newStatus = item.status === 'available' ? 'maintenance' : 'available';
    request.put(`/admin/equipments/${item.id}`, { status: newStatus })
        .then(() => fetchEquipments())
        .catch((error) => alert(error.message));
}

function handleLogout() {
    userStore.logout();
    router.push({ name: 'login' });
}

onMounted(fetchEquipments);
</script>

<style scoped>
.admin-equipments-page {
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
.add-btn {
    margin: 20px 0;
    padding: 8px 16px;
    cursor: pointer;
}
.form-panel {
    margin: 20px 0;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 8px;
}
.form-panel input {
    padding: 8px;
    margin-right: 10px;
    margin-bottom: 10px;
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
.empty {
    text-align: center;
    padding: 40px;
    color: #999;
}
</style>
