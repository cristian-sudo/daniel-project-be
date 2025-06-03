<template>
  <div class="container mt-4">
    <h1>Palestre</h1>
    
    <div v-if="loading" class="text-center">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Caricamento...</span>
      </div>
    </div>

    <div v-else-if="error" class="alert alert-danger" role="alert">
      {{ error }}
    </div>

    <div v-else class="row g-4">
      <div v-for="gym in gyms" :key="gym.id" class="col-md-4">
        <div class="card h-100">
          <img 
            :src="gym.immagine_principale || '../assets/img/default-gym.jpg'" 
            class="card-img-top" 
            :alt="gym.nome"
          >
          <div class="card-body">
            <h5 class="card-title">{{ gym.nome }}</h5>
            <p class="card-text">{{ gym.descrizione }}</p>
            <p class="card-text">
              <small class="text-muted">
                <i class="bi bi-geo-alt"></i> {{ gym.indirizzo }}, {{ gym.citta }}
              </small>
            </p>
            <div class="d-flex justify-content-between align-items-center">
              <div class="rating">
                <i v-for="n in 5" :key="n" class="bi" :class="n <= Math.round(gym.valutazione_media) ? 'bi-star-fill text-warning' : 'bi-star'"></i>
                <span class="ms-1">{{ gym.valutazione_media }}</span>
                <span class="text-muted">({{ gym.recensioni_count }})</span>
              </div>
              <span class="badge bg-primary">{{ gym.prezzo_mensile }}€/mese</span>
            </div>
            <router-link :to="`/palestra/${gym.id}`" class="btn btn-primary mt-3 w-100">
              Dettagli
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { gyms } from '../services/api';

export default {
  name: 'GymList',
  setup() {
    const loading = ref(true);
    const error = ref(null);
    const gyms = ref([]);

    const fetchGyms = async () => {
      try {
        loading.value = true;
        error.value = null;
        const response = await gyms.getAll();
        console.log('API Response:', response.data);
        if (response.data.success) {
          gyms.value = response.data.data.gyms;
        } else {
          error.value = response.data.message || 'Errore nel caricamento delle palestre';
          gyms.value = [];
        }
      } catch (err) {
        error.value = 'Errore nel caricamento delle palestre. Riprova più tardi.';
        console.error('Error fetching gyms:', err);
        gyms.value = [];
      } finally {
        loading.value = false;
      }
    };

    onMounted(() => {
      fetchGyms();
    });

    return {
      gyms,
      loading,
      error
    };
  }
};
</script>

<style scoped>
.card-img-top {
  height: 200px;
  object-fit: cover;
}

.rating {
  font-size: 0.9rem;
}
</style> 