<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AdminHeader from '../components/AdminHeader.vue';
import { apiRequest } from '../services/api';
import { useAuth } from '../services/auth';

const { user, loadCurrentUser } = useAuth();
const mangas = ref([]);
const orders = ref([]);
const loading = ref(true);
const errorMessage = ref('');
const isAdmin = computed(() => user.value?.role === 'admin');
const activeMangaCount = computed(() => mangas.value.filter((manga) => manga.is_active).length);
const pendingOrders = computed(() => orders.value.filter((order) => order.status === 'pending').length);
const processingOrders = computed(() => orders.value.filter((order) => ['paid', 'shipped'].includes(order.status)).length);

onMounted(loadDashboard);

async function loadDashboard() {
  await loadCurrentUser();

  if (!isAdmin.value) {
    loading.value = false;
    return;
  }

  try {
    const [mangaResponse, orderResponse] = await Promise.all([
      apiRequest('/api/admin/mangas?per_page=50'),
      apiRequest('/api/admin/orders'),
    ]);

    mangas.value = mangaResponse.data;
    orders.value = orderResponse.data;
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="site-shell">
    <AdminHeader />

    <main class="admin-dashboard-background">
      <div class="container admin-dashboard-page">
        <section v-if="loading && !isAdmin" class="state-message" role="status">Verificando acesso…</section>
        <section v-else-if="!isAdmin" class="checkout-message">
          <h1>Acesso restrito</h1>
          <p>Entre com uma conta administrativa para acessar este painel.</p>
          <RouterLink class="retry-button" :to="{ name: 'auth', query: { redirect: '/admin' } }">Entrar como administrador</RouterLink>
        </section>
        <template v-else>
          <header class="admin-dashboard-heading">
            <span class="eyebrow">Resumo da loja</span>
            <h1>Olá, {{ user.name }}</h1>
            <p>Escolha uma área para continuar gerenciando a Mangáverse.</p>
          </header>

          <p v-if="errorMessage" class="checkout-error" role="alert">{{ errorMessage }}</p>
          <p v-if="loading" class="state-message" role="status">Atualizando informações…</p>

          <section class="admin-dashboard-stats" aria-label="Indicadores da loja">
            <article>
              <span>Volumes ativos</span>
              <strong>{{ activeMangaCount }}</strong>
              <small>Visíveis no catálogo</small>
            </article>
            <article class="dashboard-pending">
              <span>Aguardando pagamento</span>
              <strong>{{ pendingOrders }}</strong>
              <small>Pedidos pendentes</small>
            </article>
            <article class="dashboard-processing">
              <span>Em andamento</span>
              <strong>{{ processingOrders }}</strong>
              <small>Pagos ou enviados</small>
            </article>
          </section>

          <section class="admin-dashboard-links" aria-label="Áreas administrativas">
            <RouterLink to="/admin/cadastros" class="admin-dashboard-card">
              <span class="admin-dashboard-icon records-icon">◫</span>
              <span class="admin-dashboard-card-copy">
                <strong>Séries, autores e categorias</strong>
                <small>Organize os dados usados para cadastrar os volumes do catálogo.</small>
              </span>
              <span class="admin-dashboard-arrow">→</span>
            </RouterLink>
            <RouterLink to="/admin/mangas" class="admin-dashboard-card">
              <span class="admin-dashboard-icon">▤</span>
              <span class="admin-dashboard-card-copy">
                <strong>Gerenciar catálogo</strong>
                <small>Cadastre volumes, ajuste preço e estoque, ou altere a visibilidade.</small>
              </span>
              <span class="admin-dashboard-arrow">→</span>
            </RouterLink>
            <RouterLink to="/admin/pedidos" class="admin-dashboard-card">
              <span class="admin-dashboard-icon orders-icon">▣</span>
              <span class="admin-dashboard-card-copy">
                <strong>Gerenciar pedidos</strong>
                <small>Revise pedidos e atualize o envio e a entrega.</small>
              </span>
              <span class="admin-dashboard-arrow">→</span>
            </RouterLink>
          </section>

          <RouterLink class="admin-dashboard-store-link" to="/">Abrir a loja como cliente</RouterLink>
        </template>
      </div>
    </main>

    <footer class="footer cart-footer">
      <div class="container footer-bottom">
        <span>© {{ new Date().getFullYear() }} Mangáverse</span>
        <span>Painel administrativo</span>
      </div>
    </footer>
  </div>
</template>
