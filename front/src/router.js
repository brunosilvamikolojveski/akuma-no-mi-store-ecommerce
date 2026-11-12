import { createRouter, createWebHistory } from 'vue-router';
import CartView from './views/CartView.vue';
import HomeView from './views/HomeView.vue';
import AuthView from './views/AuthView.vue';
import CheckoutView from './views/CheckoutView.vue';
import OrdersView from './views/OrdersView.vue';
import OrderDetailView from './views/OrderDetailView.vue';
import AdminOrdersView from './views/AdminOrdersView.vue';
import AdminMangasView from './views/AdminMangasView.vue';
import AdminDashboardView from './views/AdminDashboardView.vue';
import AdminRecordsView from './views/AdminRecordsView.vue';
import MangaDetailView from './views/MangaDetailView.vue';
import ProfileView from './views/ProfileView.vue';
import NotFoundView from './views/NotFoundView.vue';
import { useAuth } from './services/auth';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/carrinho', name: 'cart', component: CartView },
    { path: '/mangas/:id', name: 'manga-detail', component: MangaDetailView },
    { path: '/entrar', name: 'auth', component: AuthView },
    { path: '/finalizar', name: 'checkout', component: CheckoutView, meta: { requiresAuth: true } },
    { path: '/pedidos', name: 'orders', component: OrdersView, meta: { requiresAuth: true } },
    { path: '/pedidos/:id', name: 'order-detail', component: OrderDetailView, meta: { requiresAuth: true } },
    { path: '/perfil', name: 'profile', component: ProfileView, meta: { requiresAuth: true } },
    { path: '/admin/pedidos', name: 'admin-orders', component: AdminOrdersView, meta: { requiresAuth: true, requiresAdmin: true } },
    { path: '/admin/mangas', name: 'admin-mangas', component: AdminMangasView, meta: { requiresAuth: true, requiresAdmin: true } },
    { path: '/admin', name: 'admin-dashboard', component: AdminDashboardView, meta: { requiresAuth: true, requiresAdmin: true } },
    { path: '/admin/cadastros', name: 'admin-records', component: AdminRecordsView, meta: { requiresAuth: true, requiresAdmin: true } },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundView },
  ],
  scrollBehavior(to) {
    if (to.hash) {
      return { el: to.hash, behavior: 'smooth' };
    }

    return { top: 0 };
  },
});

router.beforeEach(async (to) => {
  if (!to.meta.requiresAuth) return true;

  const { user, isAdmin, loadCurrentUser } = useAuth();
  await loadCurrentUser();

  if (!user.value) {
    return { name: 'auth', query: { redirect: to.fullPath } };
  }

  if (to.meta.requiresAdmin && !isAdmin.value) {
    return { name: 'home' };
  }

  return true;
});

export default router;
