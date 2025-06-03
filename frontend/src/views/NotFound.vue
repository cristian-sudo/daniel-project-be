<template>
  <div class="container mt-5 text-center">
    <h1 class="display-1">404</h1>
    <h2 class="mb-4">Pagina Non Trovata</h2>
    <p class="lead mb-4">
      La pagina che stai cercando non esiste o è stata spostata.
    </p>
    <router-link to="/" class="btn btn-primary">
      Torna alla Home
    </router-link>
  </div>
</template>

<script>
export default {
  name: 'NotFound'
}
</script>

<style scoped>
.display-1 {
  font-size: 6rem;
  font-weight: 700;
  color: var(--primary-color);
}

.lead {
  font-size: 1.25rem;
  color: var(--secondary-color);
}
</style> 