<template>
  <div class="palestra-dettaglio">
    <div v-if="loading" class="container py-5 text-center">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Caricamento...</span>
      </div>
      <p class="mt-3">Caricamento informazioni palestra...</p>
    </div>
    
    <div v-else-if="gym" class="container py-5">
      <!-- Header della palestra -->
      <div class="row mb-4">
        <div class="col-md-8">
          <h1 class="mb-2">{{ gym.nome }}</h1>
          <p class="text-muted mb-2">
            <i class="bi bi-geo-alt me-1"></i>{{ gym.indirizzo }}, {{ gym.citta }}
          </p>
          <div class="rating mb-3">
            <i v-for="n in 5" :key="n" class="bi" :class="n <= Math.round(gym.valutazione_media) ? 'bi-star-fill text-warning' : 'bi-star'"></i>
            <span class="ms-1">{{ gym.valutazione_media }}</span>
            <span class="text-muted">({{ gym.recensioni_count }} recensioni)</span>
          </div>
        </div>
        <div class="col-md-4 text-md-end">
          <div class="price-badge mb-2">
            <span class="badge bg-primary fs-5">{{ gym.prezzo_mensile }}€/mese</span>
          </div>
          <button 
            class="btn btn-success" 
            @click="openBookingModal"
            v-if="isLoggedIn && isCustomer"
          >
            <i class="bi bi-calendar-plus me-2"></i>Prenota
          </button>
        </div>
      </div>
      
      <!-- Galleria immagini -->
      <div class="row mb-5">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body p-0">
              <div v-if="gym.immagini && gym.immagini.length > 0" class="gym-gallery">
                <div class="main-image mb-3">
                  <img 
                    :src="selectedImage || gym.immagini[0].percorso_immagine" 
                    class="img-fluid rounded" 
                    alt="Palestra"
                  >
                </div>
                <div class="thumbnails d-flex overflow-auto">
                  <div 
                    v-for="image in gym.immagini" 
                    :key="image.id" 
                    class="thumbnail-item me-2"
                    @click="selectedImage = image.percorso_immagine"
                  >
                    <img 
                      :src="image.percorso_immagine" 
                      class="img-thumbnail" 
                      :class="{'active': selectedImage === image.percorso_immagine}"
                      alt="Thumbnail"
                      style="width: 100px; height: 70px; object-fit: cover; cursor: pointer;"
                    >
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-5">
                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                <p class="mt-2 text-muted">Nessuna immagine disponibile</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Informazioni e recensioni -->
      <div class="row">
        <div class="col-md-8">
          <!-- Descrizione -->
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
              <h2 class="h5 mb-0">Descrizione</h2>
            </div>
            <div class="card-body">
              <p v-if="gym.descrizione">{{ gym.descrizione }}</p>
              <p v-else class="text-muted">Nessuna descrizione disponibile</p>
            </div>
          </div>
          
          <!-- Recensioni -->
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
              <h2 class="h5 mb-0">Recensioni</h2>
              <button 
                v-if="isLoggedIn && isCustomer && !hasReviewed" 
                class="btn btn-sm btn-outline-primary"
                @click="openReviewModal"
              >
                <i class="bi bi-pencil me-1"></i>Scrivi recensione
              </button>
              <button 
                v-if="isLoggedIn && isCustomer && hasReviewed" 
                class="btn btn-sm btn-outline-secondary"
                @click="editReview"
              >
                <i class="bi bi-pencil me-1"></i>Modifica recensione
              </button>
            </div>
            <div class="card-body">
              <div v-if="loadingReviews" class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Caricamento...</span>
                </div>
                <p class="mt-2">Caricamento recensioni...</p>
              </div>
              <div v-else-if="reviews.length > 0">
                <div v-for="review in reviews" :key="review.id" class="review-item mb-4">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h5 class="mb-1">{{ review.user_nome }} {{ review.user_cognome }}</h5>
                      <div class="rating mb-2">
                        <i v-for="n in 5" :key="n" class="bi" :class="n <= review.valutazione ? 'bi-star-fill text-warning' : 'bi-star'"></i>
                      </div>
                    </div>
                    <small class="text-muted">{{ formatDate(review.data_recensione) }}</small>
                  </div>
                  <p>{{ review.commento }}</p>
                  <hr v-if="review !== reviews[reviews.length - 1]">
                </div>
              </div>
              <div v-else class="text-center py-3">
                <p class="text-muted">Nessuna recensione disponibile</p>
                <button 
                  v-if="isLoggedIn && isCustomer" 
                  class="btn btn-outline-primary mt-2"
                  @click="openReviewModal"
                >
                  Scrivi la prima recensione
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <!-- Informazioni -->
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
              <h2 class="h5 mb-0">Informazioni</h2>
            </div>
            <div class="card-body">
              <ul class="list-unstyled">
                <li class="mb-3">
                  <i class="bi bi-clock me-2 text-primary"></i>
                  <strong>Orari:</strong> {{ formatTime(gym.orario_apertura) }} - {{ formatTime(gym.orario_chiusura) }}
                </li>
                <li class="mb-3">
                  <i class="bi bi-calendar-week me-2 text-primary"></i>
                  <strong>Giorni apertura:</strong> {{ formatDays(gym.giorni_apertura) }}
                </li>
                <li class="mb-3" v-if="gym.telefono">
                  <i class="bi bi-telephone me-2 text-primary"></i>
                  <strong>Telefono:</strong> {{ gym.telefono }}
                </li>
                <li class="mb-3" v-if="gym.email">
                  <i class="bi bi-envelope me-2 text-primary"></i>
                  <strong>Email:</strong> {{ gym.email }}
                </li>
                <li v-if="gym.cap">
                  <i class="bi bi-geo me-2 text-primary"></i>
                  <strong>CAP:</strong> {{ gym.cap }}
                </li>
              </ul>
            </div>
          </div>
          
          <!-- Mappa -->
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <h2 class="h5 mb-0">Posizione</h2>
            </div>
            <div class="card-body p-0">
              <div class="map-placeholder bg-light text-center py-5">
                <i class="bi bi-map text-muted" style="font-size: 3rem;"></i>
                <p class="mt-2 text-muted">{{ gym.indirizzo }}, {{ gym.citta }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else class="container py-5 text-center">
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Palestra non trovata
      </div>
      <router-link to="/palestre" class="btn btn-primary mt-3">
        Torna alla ricerca
      </router-link>
    </div>
    
    <!-- Modal Prenotazione -->
    <div class="modal fade" id="bookingModal" tabindex="-1" v-show="showBookingModal" ref="bookingModalRef">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Prenota iscrizione</h5>
            <button type="button" class="btn-close" @click="closeBookingModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitBooking">
              <div class="mb-3">
                <label for="startDate" class="form-label">Data inizio</label>
                <input type="date" class="form-control" id="startDate" v-model="booking.data_inizio" required>
              </div>
              <div class="mb-3">
                <label for="endDate" class="form-label">Data fine (opzionale)</label>
                <input type="date" class="form-control" id="endDate" v-model="booking.data_fine">
              </div>
              <div class="mb-3">
                <label for="notes" class="form-label">Note (opzionale)</label>
                <textarea class="form-control" id="notes" rows="3" v-model="booking.note"></textarea>
              </div>
              <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                La tua prenotazione sarà in attesa di conferma da parte del gestore della palestra.
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeBookingModal">Annulla</button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="submitBooking"
              :disabled="bookingLoading"
            >
              <span v-if="bookingLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
              Conferma prenotazione
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal Recensione -->
    <div class="modal fade" id="reviewModal" tabindex="-1" v-show="showReviewModal" ref="reviewModalRef">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingReview ? 'Modifica recensione' : 'Scrivi recensione' }}</h5>
            <button type="button" class="btn-close" @click="closeReviewModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitReview">
              <div class="mb-3">
                <label class="form-label">Valutazione</label>
                <div class="rating-input">
                  <i 
                    v-for="n in 5" 
                    :key="n" 
                    class="bi" 
                    :class="n <= review.valutazione ? 'bi-star-fill text-warning' : 'bi-star'" 
                    style="font-size: 2rem; cursor: pointer;"
                    @click="review.valutazione = n"
                  ></i>
                </div>
              </div>
              <div class="mb-3">
                <label for="comment" class="form-label">Commento</label>
                <textarea class="form-control" id="comment" rows="4" v-model="review.commento" required></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeReviewModal">Annulla</button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="submitReview"
              :disabled="reviewLoading"
            >
              <span v-if="reviewLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
              {{ editingReview ? 'Aggiorna recensione' : 'Pubblica recensione' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch, onUnmounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import { useGymStore } from '../store/gym';
import { useReviewStore } from '../store/review';
import { useBookingStore } from '../store/booking';
import { useUserStore } from '../store/user';

export default {
  name: 'PalestraDettaglio',
  props: {
    id: {
      type: String,
      required: true
    }
  },
  setup(props) {
    const gymStore = useGymStore();
    const reviewStore = useReviewStore();
    const bookingStore = useBookingStore();
    const userStore = useUserStore();
    const route = useRoute();
    
    const selectedImage = ref(null);
    const showBookingModal = ref(false);
    const showReviewModal = ref(false);
    const editingReview = ref(false);
    const userReviewId = ref(null);
    const bookingModalRef = ref(null);
    const reviewModalRef = ref(null);
    
    const booking = ref({
      data_inizio: new Date().toISOString().split('T')[0],
      data_fine: null,
      note: ''
    });
    
    const review = ref({
      valutazione: 5,
      commento: ''
    });
    
    // Gestione modali con Bootstrap
    let bookingModal = null;
    let reviewModal = null;

    const initializeModals = async () => {
      console.log('Initializing modals...');
      await nextTick();
      
      console.log('bookingModalRef:', bookingModalRef.value);
      console.log('reviewModalRef:', reviewModalRef.value);

      if (typeof window.bootstrap === 'undefined') {
        console.error('Bootstrap is not loaded!');
        return;
      }

      try {
        if (bookingModalRef.value) {
          bookingModal = new window.bootstrap.Modal(bookingModalRef.value, {
            backdrop: 'static',
            keyboard: false
          });
          console.log('Booking modal initialized');
        }
        if (reviewModalRef.value) {
          reviewModal = new window.bootstrap.Modal(reviewModalRef.value, {
            backdrop: 'static',
            keyboard: false
          });
          console.log('Review modal initialized');
        }
      } catch (error) {
        console.error('Error initializing modals:', error);
      }
    };

    onMounted(async () => {
      console.log('Component mounted');
      try {
        // Carica i dati della palestra
        console.log('Loading gym with ID:', props.id);
        const success = await gymStore.getGymById(props.id);
        console.log('Gym loaded:', success, 'Current gym:', gymStore.currentGym);
        
        if (success) {
          await loadReviews();
        }

        // Initialize modals after component is mounted
        await initializeModals();
      } catch (error) {
        console.error('Error in onMounted:', error);
      }
    });

    const openBookingModal = async () => {
      console.log('Opening booking modal');
      if (!bookingModal) {
        console.log('Reinitializing booking modal...');
        await initializeModals();
      }
      
      showBookingModal.value = true;
      if (bookingModal) {
        bookingModal.show();
      } else {
        console.error('Booking modal not initialized!');
      }
    };

    const openReviewModal = async () => {
      console.log('Opening review modal');
      if (!reviewModal) {
        console.log('Reinitializing review modal...');
        await initializeModals();
      }
      
      showReviewModal.value = true;
      if (reviewModal) {
        reviewModal.show();
      } else {
        console.error('Review modal not initialized!');
      }
    };

    const closeBookingModal = () => {
      console.log('Closing booking modal');
      showBookingModal.value = false;
      if (bookingModal) {
        bookingModal.hide();
      }
    };

    const closeReviewModal = () => {
      console.log('Closing review modal');
      showReviewModal.value = false;
      if (reviewModal) {
        reviewModal.hide();
      }
    };

    // Carica le recensioni
    const loadReviews = async () => {
      await reviewStore.getGymReviews(props.id);
      
      // Verifica se l'utente ha già recensito questa palestra
      if (isLoggedIn.value) {
        const userReview = reviewStore.reviews.find(r => r.user_id === userStore.user.id);
        if (userReview) {
          userReviewId.value = userReview.id;
        }
      }
    };
    
    // Gestione prenotazione
    const submitBooking = async () => {
      const bookingData = {
        gym_id: parseInt(props.id),
        data_inizio: booking.value.data_inizio,
        data_fine: booking.value.data_fine,
        note: booking.value.note
      };
      
      const result = await bookingStore.createBooking(bookingData);
      if (result) {
        showBookingModal.value = false;
        alert('Prenotazione inviata con successo! Attendi la conferma del gestore.');
      } else {
        alert('Errore durante l\'invio della prenotazione: ' + bookingStore.error);
      }
    };
    
    // Gestione recensione
    const editReview = () => {
      const userReview = reviewStore.reviews.find(r => r.user_id === userStore.user.id);
      if (userReview) {
        review.value.valutazione = userReview.valutazione;
        review.value.commento = userReview.commento;
        editingReview.value = true;
        showReviewModal.value = true;
      }
    };
    
    const submitReview = async () => {
      const reviewData = {
        gym_id: parseInt(props.id),
        valutazione: review.value.valutazione,
        commento: review.value.commento
      };
      
      let result;
      if (editingReview.value && userReviewId.value) {
        result = await reviewStore.updateReview(userReviewId.value, reviewData);
      } else {
        result = await reviewStore.createReview(reviewData);
      }
      
      if (result) {
        showReviewModal.value = false;
        await loadReviews();
        await gymStore.getGymById(props.id); // Aggiorna la valutazione media
      } else {
        alert('Errore durante l\'invio della recensione: ' + reviewStore.error);
      }
    };
    
    // Formattazione
    const formatTime = (time) => {
      if (!time) return '';
      return time.substring(0, 5);
    };
    
    const formatDays = (days) => {
      if (!days) return '';
      
      const daysMap = {
        '1': 'Lunedì',
        '2': 'Martedì',
        '3': 'Mercoledì',
        '4': 'Giovedì',
        '5': 'Venerdì',
        '6': 'Sabato',
        '7': 'Domenica'
      };
      
      const daysList = days.split(',');
      return daysList.map(day => daysMap[day]).join(', ');
    };
    
    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('it-IT');
    };
    
    // Computed properties
    const gym = computed(() => gymStore.currentGym);
    const loading = computed(() => gymStore.loading);
    const error = computed(() => gymStore.error);
    const isLoggedIn = computed(() => userStore.isLoggedIn);
    const isCustomer = computed(() => userStore.user?.tipo_utente === 'cliente');
    const hasReviewed = computed(() => userReviewId.value !== null);
    
    return {
      gym,
      reviews: computed(() => reviewStore.reviews),
      loading,
      loadingReviews: computed(() => reviewStore.loading),
      bookingLoading: computed(() => bookingStore.loading),
      reviewLoading: computed(() => reviewStore.loading),
      isLoggedIn,
      isCustomer,
      hasReviewed,
      selectedImage,
      showBookingModal,
      showReviewModal,
      editingReview,
      booking,
      review,
      submitBooking,
      editReview,
      submitReview,
      formatTime,
      formatDays,
      formatDate,
      bookingModalRef,
      reviewModalRef,
      closeBookingModal,
      closeReviewModal,
      openBookingModal,
      openReviewModal
    };
  }
}
</script>

<style scoped>
.modal {
  display: none;
}

.modal.show {
  display: block;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1040;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-dialog {
  margin: 1.75rem auto;
  max-width: 500px;
}

.thumbnail-item img.active {
  border: 2px solid #0d6efd;
}

.rating-input i {
  margin-right: 5px;
}

.map-placeholder {
  height: 200px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}
</style>
