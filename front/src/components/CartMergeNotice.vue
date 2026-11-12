<script setup>
import { useAuth } from '../services/auth';

const { cartMergeNotice } = useAuth();
</script>

<template>
  <aside v-if="cartMergeNotice" class="merge-notice" role="status">
    <div class="container">
      <p><strong>Seu carrinho foi atualizado.</strong> {{ cartMergeNotice }}</p>
      <button @click="cartMergeNotice = ''">Entendi</button>
    </div>
  </aside>
</template>
