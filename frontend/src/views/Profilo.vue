<template>
  <div class="profilo-page">
    <div class="container py-5">
      <h1 class="mb-4">Il mio profilo</h1>
      
      <div class="row">
        <div class="col-md-4 mb-4">
          <div class="card shadow-sm">
            <div class="card-body text-center">
              <div class="avatar mb-3">
                <i class="bi bi-person-circle" style="font-size: 5rem;"></i>
              </div>
              <h2 class="h4">{{ user.nome }} {{ user.cognome }}</h2>
              <p class="text-muted">
                <span class="badge" :class="userTypeClass">{{ userTypeLabel }}</span>
              </p>
              <p class="text-muted">
                <i class="bi bi-envelope me-2"></i>{{ user.email }}
              </p>
              <p class="text-muted">
                <i class="bi bi-person me-2"></i>{{ user.username }}
              </p>
            </div>
          </div>
        </div>
        
        <div class="col-md-8">
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
              <h2 class="h5 mb-0">Informazioni profilo</h2>
            </div>
            <div class="card-body">
              <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                  <label class="form-label">Nome</label>
                  <p class="form-control-plaintext">{{ user.nome }}</p>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Cognome</label>
                  <p class="form-control-plaintext">{{ user.cognome }}</p>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Email</label>
                <p class="form-control-plaintext">{{ user.email }}</p>
              </div>

              <div class="mb-3">
                <label class="form-label">Username</label>
                <p class="form-control-plaintext">{{ user.username }}</p>
              </div>

              <div class="mb-3">
                <label class="form-label">Tipo account</label>
                <p class="form-control-plaintext">
                  <span class="badge" :class="userTypeClass">{{ userTypeLabel }}</span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { users } from '../services/api';
import { useUserStore } from '../store/user';
import { useRouter } from 'vue-router';

export default {
  name: 'Profilo',
  setup() {
    const userStore = useUserStore();
    const router = useRouter();
    
    // Use the stored user data
    const user = ref(userStore.user || {});
    
    const userTypeClass = computed(() => {
      return user.value.tipo_utente === 'palestra' ? 'bg-primary' : 'bg-success';
    });
    
    const userTypeLabel = computed(() => {
      return user.value.tipo_utente === 'palestra' ? 'Gestore Palestra' : 'Cliente';
    });
    
    // Inizializza i dati del profilo
    onMounted(async () => {
      try {
        const response = await users.getProfile();
        if (response.data.success) {
          const userData = response.data.data.user;
          user.value = userData;
        }
      } catch (error) {
        console.error('Errore nel caricamento del profilo:', error);
      }
    });
    
    return {
      user,
      userTypeClass,
      userTypeLabel
    };
  }
};
</script>

<style scoped>
.avatar {
  width: 100px;
  height: 100px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0d6efd;
}

.form-control-plaintext {
  padding-top: 0.375rem;
  padding-bottom: 0.375rem;
  margin-bottom: 0;
  line-height: 1.5;
  color: #212529;
}

.profilo-page {
  min-height: calc(100vh - 300px);
}
</style>
