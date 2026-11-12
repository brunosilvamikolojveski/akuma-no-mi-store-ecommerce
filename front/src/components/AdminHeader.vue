<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '../services/auth';
import CartMergeNotice from './CartMergeNotice.vue';

const router = useRouter();
const route = useRoute();
const { user, loadCurrentUser, signOut } = useAuth();
const accountMenu = ref(null);
const accountMenuOpen = ref(false);

onMounted(() => {
  loadCurrentUser();
  document.addEventListener('click', closeAccountMenuOutside);
});

onBeforeUnmount(() => document.removeEventListener('click', closeAccountMenuOutside));
watch(() => route.fullPath, () => { accountMenuOpen.value = false; });

function closeAccountMenuOutside(event) {
  if (!accountMenu.value?.contains(event.target)) accountMenuOpen.value = false;
}

async function logout() {
  try {
    accountMenuOpen.value = false;
    await signOut();
    await router.push('/');
  } catch {
    window.alert('Não foi possível encerrar a sessão. Tente novamente.');
  }
}
</script>

<template>
  <header class="admin-header">
    <div class="admin-header-main container">
      <RouterLink class="admin-brand" to="/admin">
        <span class="admin-brand-mark">M</span>
        <span><strong>Mangáverse</strong><small>PAINEL ADMINISTRATIVO</small></span>
      </RouterLink>

      <nav class="admin-header-nav" aria-label="Navegação administrativa">
        <RouterLink to="/admin" exact-active-class="active">Visão geral</RouterLink>
        <RouterLink to="/admin/mangas" active-class="active">Catálogo</RouterLink>
        <RouterLink to="/admin/cadastros" active-class="active">Cadastros</RouterLink>
        <RouterLink to="/admin/pedidos" active-class="active">Pedidos</RouterLink>
      </nav>

      <div class="admin-header-user">
        <div ref="accountMenu" class="admin-account-menu">
          <button class="admin-account-trigger" :aria-expanded="accountMenuOpen" @click="accountMenuOpen = !accountMenuOpen">
            {{ user?.name || 'Administrador' }} <span aria-hidden="true">⌄</span>
          </button>
          <div v-if="accountMenuOpen" class="admin-account-dropdown">
            <RouterLink to="/">Ver loja</RouterLink>
            <button @click="logout">Sair</button>
          </div>
        </div>
      </div>
    </div>
    <CartMergeNotice />
  </header>
</template>
