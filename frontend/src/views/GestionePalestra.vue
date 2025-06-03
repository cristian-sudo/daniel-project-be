<template>
  <div class="gestione-palestra">
    <div class="container py-5">
      <h1 class="mb-4">Gestione Palestra</h1>
      
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Caricamento...</span>
        </div>
        <p class="mt-3">Caricamento dati...</p>
      </div>
      
      <div v-else>
        <!-- Nessuna palestra registrata -->
        <div v-if="!hasGyms" class="card shadow-sm mb-4">
          <div class="card-body text-center py-5">
            <i class="bi bi-building-add" style="font-size: 3rem; color: #0d6efd;"></i>
            <h2 class="h4 mt-3">Nessuna palestra registrata</h2>
            <p class="text-muted">Aggiungi la tua prima palestra per iniziare a gestire le prenotazioni</p>
            <button class="btn btn-primary mt-3" @click="showAddGymModal = true">
              <i class="bi bi-plus-circle me-2"></i>Aggiungi palestra
            </button>
          </div>
        </div>
        
        <!-- Lista palestre -->
        <div v-else>
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">Le mie palestre</h2>
            <button class="btn btn-primary" @click="showAddGymModal = true">
              <i class="bi bi-plus-circle me-2"></i>Aggiungi palestra
            </button>
          </div>
          
          <div class="row g-4">
            <div v-for="gym in myGyms" :key="gym.id" class="col-md-6">
              <div class="card h-100 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                  <h3 class="h5 mb-0">{{ gym.nome }}</h3>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                      <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                      <li><a class="dropdown-item" href="#" @click.prevent="editGym(gym)">Modifica</a></li>
                      <li><a class="dropdown-item" href="#" @click.prevent="confirmDeleteGym(gym)">Elimina</a></li>
                    </ul>
                  </div>
                </div>
                <img 
                  :src="gym.immagine_principale || '../assets/img/default-gym.jpg'" 
                  class="card-img-top" 
                  alt="Palestra"
                  style="height: 200px; object-fit: cover;"
                >
                <div class="card-body">
                  <p class="text-muted mb-2">
                    <i class="bi bi-geo-alt me-1"></i>{{ gym.citta }}
                  </p>
                  <p class="text-muted mb-2">
                    <i class="bi bi-clock me-1"></i>{{ formatTime(gym.orario_apertura) }} - {{ formatTime(gym.orario_chiusura) }}
                  </p>
                  <p class="text-muted mb-3">
                    <i class="bi bi-currency-euro me-1"></i>{{ gym.prezzo_mensile }}€/mese
                  </p>
                  <div class="d-flex justify-content-between">
                    <div>
                      <span class="badge bg-primary me-2">
                        <i class="bi bi-people me-1"></i>{{ gym.iscritti_count || 0 }} iscritti
                      </span>
                      <span class="badge bg-info">
                        <i class="bi bi-star me-1"></i>{{ gym.valutazione_media || 0 }}
                      </span>
                    </div>
                    <router-link :to="`/palestra/${gym.id}`" class="btn btn-sm btn-outline-primary">
                      Visualizza
                    </router-link>
                  </div>
                </div>
                <div class="card-footer bg-white">
                  <div class="d-grid gap-2">
                    <button class="btn btn-success" @click="viewBookings(gym)">
                      <i class="bi bi-calendar-check me-2"></i>Gestisci prenotazioni
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Sezione prenotazioni -->
        <div v-if="selectedGym" class="mt-5">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <div class="d-flex justify-content-between align-items-center">
                <h2 class="h4 mb-0">Prenotazioni per {{ selectedGym.nome }}</h2>
                <button class="btn btn-sm btn-outline-secondary" @click="selectedGym = null">
                  <i class="bi bi-x-lg me-1"></i>Chiudi
                </button>
              </div>
            </div>
            <div class="card-body">
              <div v-if="loadingBookings" class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Caricamento...</span>
                </div>
                <p class="mt-2">Caricamento prenotazioni...</p>
              </div>
              <div v-else-if="gymBookings.length > 0">
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Cliente</th>
                        <th>Data inizio</th>
                        <th>Data fine</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="booking in gymBookings" :key="booking.id">
                        <td>{{ booking.user_nome }} {{ booking.user_cognome }}</td>
                        <td>{{ formatDate(booking.data_inizio) }}</td>
                        <td>{{ booking.data_fine ? formatDate(booking.data_fine) : '-' }}</td>
                        <td>
                          <span class="badge" :class="getStatusClass(booking.stato)">
                            {{ getStatusLabel(booking.stato) }}
                          </span>
                        </td>
                        <td>
                          <div class="btn-group btn-group-sm">
                            <button 
                              v-if="booking.stato === 'in attesa'" 
                              class="btn btn-success" 
                              @click="updateBookingStatus(booking.id, 'confermata')"
                              :disabled="bookingActionLoading"
                            >
                              <i class="bi bi-check-lg"></i>
                            </button>
                            <button 
                              v-if="booking.stato === 'in attesa'" 
                              class="btn btn-danger" 
                              @click="updateBookingStatus(booking.id, 'rifiutata')"
                              :disabled="bookingActionLoading"
                            >
                              <i class="bi bi-x-lg"></i>
                            </button>
                            <button 
                              class="btn btn-info" 
                              @click="viewBookingDetails(booking)"
                            >
                              <i class="bi bi-eye"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div v-else class="text-center py-3">
                <p class="text-muted">Nessuna prenotazione disponibile</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modal Aggiungi/Modifica Palestra -->
      <div class="modal fade" id="gymModal" tabindex="-1" v-if="showAddGymModal || showEditGymModal" ref="gymModal">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ showEditGymModal ? 'Modifica palestra' : 'Aggiungi palestra' }}</h5>
              <button type="button" class="btn-close" @click="closeGymModal"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="submitGym" enctype="multipart/form-data">
                <div class="row mb-3">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <label for="nome" class="form-label">Nome palestra</label>
                    <input type="text" class="form-control" id="nome" v-model="gymData.nome" required>
                  </div>
                  <div class="col-md-6">
                    <label for="immagine" class="form-label">Immagine profilo</label>
                    <input 
                      type="file" 
                      class="form-control" 
                      id="immagine" 
                      @change="handleImageUpload"
                      accept="image/*"
                      :required="!showEditGymModal"
                    >
                    <div v-if="imagePreview" class="mt-2">
                      <img :src="imagePreview" alt="Anteprima" class="img-thumbnail" style="max-height: 100px">
                    </div>
                  </div>
                </div>
                
                <div class="row mb-3">
                  <div class="col-md-8 mb-3 mb-md-0">
                    <label for="indirizzo" class="form-label">Indirizzo</label>
                    <input type="text" class="form-control" id="indirizzo" v-model="gymData.indirizzo" required>
                  </div>
                  <div class="col-md-4">
                    <label for="cap" class="form-label">CAP</label>
                    <input type="text" class="form-control" id="cap" v-model="gymData.cap" required>
                  </div>
                </div>
                
                <div class="row mb-3">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <label for="orario_apertura" class="form-label">Orario apertura</label>
                    <input type="time" class="form-control" id="orario_apertura" v-model="gymData.orario_apertura" required>
                  </div>
                  <div class="col-md-6">
                    <label for="orario_chiusura" class="form-label">Orario chiusura</label>
                    <input type="time" class="form-control" id="orario_chiusura" v-model="gymData.orario_chiusura" required>
                  </div>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Giorni di apertura</label>
                  <div class="d-flex flex-wrap">
                    <div class="form-check me-3 mb-2" v-for="(day, index) in days" :key="index">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        :id="`day_${index + 1}`" 
                        :value="index + 1" 
                        v-model="selectedDays"
                      >
                      <label class="form-check-label" :for="`day_${index + 1}`">
                        {{ day }}
                      </label>
                    </div>
                  </div>
                </div>
                
                <div class="mb-3">
                  <label for="prezzo_mensile" class="form-label">Prezzo mensile (€)</label>
                  <input type="number" class="form-control" id="prezzo_mensile" v-model="gymData.prezzo_mensile" min="0" step="0.01" required>
                </div>
                
                <div class="row mb-3">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <label for="telefono" class="form-label">Telefono</label>
                    <input type="tel" class="form-control" id="telefono" v-model="gymData.telefono">
                  </div>
                  <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" v-model="gymData.email">
                  </div>
                </div>
                
                <div class="mb-3">
                  <label for="descrizione" class="form-label">Descrizione</label>
                  <textarea class="form-control" id="descrizione" rows="4" v-model="gymData.descrizione"></textarea>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeGymModal">Annulla</button>
              <button 
                type="button" 
                class="btn btn-primary" 
                @click="submitGym"
                :disabled="gymActionLoading"
              >
                <span v-if="gymActionLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                {{ showEditGymModal ? 'Aggiorna' : 'Aggiungi' }}
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modal Dettagli Prenotazione -->
      <div class="modal fade" id="bookingDetailsModal" tabindex="-1" v-show="selectedBooking" ref="bookingDetailsModal">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Dettagli prenotazione</h5>
              <button type="button" class="btn-close" @click="closeBookingDetails"></button>
            </div>
            <div class="modal-body" v-if="selectedBooking">
              <div class="mb-3">
                <h6>Cliente</h6>
                <p>{{ selectedBooking.user_nome }} {{ selectedBooking.user_cognome }}</p>
              </div>
              <div class="mb-3">
                <h6>Email</h6>
                <p>{{ selectedBooking.user_email }}</p>
              </div>
              <div class="mb-3">
                <h6>Data richiesta</h6>
                <p>{{ formatDateTime(selectedBooking.data_richiesta) }}</p>
              </div>
              <div class="mb-3">
                <h6>Periodo</h6>
                <p>Dal {{ formatDate(selectedBooking.data_inizio) }} 
                  {{ selectedBooking.data_fine ? `al ${formatDate(selectedBooking.data_fine)}` : '(senza scadenza)' }}</p>
              </div>
              <div class="mb-3">
                <h6>Stato</h6>
                <p>
                  <span class="badge" :class="getStatusClass(selectedBooking.stato)">
                    {{ getStatusLabel(selectedBooking.stato) }}
                  </span>
                </p>
              </div>
              <div class="mb-3" v-if="selectedBooking.data_risposta">
                <h6>Data risposta</h6>
                <p>{{ formatDateTime(selectedBooking.data_risposta) }}</p>
              </div>
              <div class="mb-3" v-if="selectedBooking.note">
                <h6>Note</h6>
                <p>{{ selectedBooking.note }}</p>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeBookingDetails">Chiudi</button>
              <button 
                v-if="selectedBooking && selectedBooking.stato === 'in attesa'" 
                type="button" 
                class="btn btn-success" 
                @click="updateBookingStatus(selectedBooking.id, 'confermata')"
                :disabled="bookingActionLoading"
              >
                <span v-if="bookingActionLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                Conferma
              </button>
              <button 
                v-if="selectedBooking && selectedBooking.stato === 'in attesa'" 
                type="button" 
                class="btn btn-danger" 
                @click="updateBookingStatus(selectedBooking.id, 'rifiutata')"
                :disabled="bookingActionLoading"
              >
                <span v-if="bookingActionLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                Rifiuta
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modal Conferma Eliminazione -->
      <div class="modal fade" id="deleteGymModal" tabindex="-1" v-if="gymToDelete" ref="deleteGymModal">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Conferma eliminazione</h5>
              <button type="button" class="btn-close" @click="gymToDelete = null"></button>
            </div>
            <div class="modal-body">
              <p>Sei sicuro di voler eliminare la palestra "{{ gymToDelete.nome }}"?</p>
              <p class="text-danger">Questa azione non può essere annullata e comporterà l'eliminazione di tutte le prenotazioni associate.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="gymToDelete = null">Annulla</button>
              <button 
                type="button" 
                class="btn btn-danger" 
                @click="deleteGym"
                :disabled="gymActionLoading"
              >
                <span v-if="gymActionLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                Elimina
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch, nextTick, onUnmounted } from 'vue';
import { useUserStore } from '../store/user';
import { useRouter } from 'vue-router';
import { gyms, users } from '../services/api';
import * as bootstrap from 'bootstrap';

export default {
  name: 'GestionePalestra',
  setup() {
    const loading = ref(true);
    const myGyms = ref([]);
    const showAddGymModal = ref(false);
    const showEditGymModal = ref(false);
    const selectedGym = ref(null);
    const selectedBooking = ref(null);
    const loadingBookings = ref(false);
    const gymBookings = ref([]);
    const bookingActionLoading = ref(false);
    const gymModal = ref(null);
    const deleteGymModal = ref(null);
    const bookingDetailsModal = ref(null);
    const router = useRouter();
    const userStore = useUserStore();
    let bookingModalInstance = null;
    let gymModalInstance = null;
    
    const gymData = ref({
      nome: '',
      citta: '',
      indirizzo: '',
      cap: '',
      orario_apertura: '',
      orario_chiusura: '',
      giorni_apertura: '',
      prezzo_mensile: 0,
      telefono: '',
      email: '',
      descrizione: ''
    });
    
    const selectedDays = ref([]);
    const days = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
    const gymActionLoading = ref(false);
    const gymToDelete = ref(null);
    const imagePreview = ref('');
    const selectedImage = ref(null);
    
    // Funzioni di formattazione
    const formatTime = (time) => {
      if (!time) return '';
      return time.substring(0, 5); // Ritorna solo ore e minuti (HH:mm)
    };
    
    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('it-IT');
    };

    const formatDateTime = (dateTimeString) => {
      if (!dateTimeString) return '';
      const date = new Date(dateTimeString);
      return date.toLocaleString('it-IT');
    };

    // Funzioni di stato
    const getStatusLabel = (status) => {
      switch (status) {
        case 'in attesa': return 'In attesa';
        case 'confermata': return 'Confermata';
        case 'rifiutata': return 'Rifiutata';
        default: return status;
      }
    };

    const getStatusClass = (status) => {
      switch (status) {
        case 'in attesa': return 'bg-warning';
        case 'confermata': return 'bg-success';
        case 'rifiutata': return 'bg-danger';
        default: return 'bg-secondary';
      }
    };
    
    // Carica le palestre dell'utente
    const loadMyGyms = async () => {
      loading.value = true;
      try {
        const response = await gyms.getAll();
        console.log('LoadMyGyms response:', response.data);
        if (response.data.success) {
          myGyms.value = response.data.data.gyms;
          console.log('Loaded gyms:', myGyms.value);
        }
      } catch (error) {
        console.error('Errore nel caricamento delle palestre:', error);
      } finally {
        loading.value = false;
      }
    };

    onMounted(async () => {
      console.log('GestionePalestra mounted');
      await loadMyGyms();
    });

    // Watch for changes in showAddGymModal and showEditGymModal
    watch([showAddGymModal, showEditGymModal], ([newShowAdd, newShowEdit]) => {
      nextTick(() => {
        const modalEl = document.getElementById('gymModal');
        if (modalEl) {
          // Distruggi l'istanza precedente se esiste
          if (gymModalInstance) {
            gymModalInstance.dispose();
          }
          // Crea una nuova istanza
          gymModalInstance = new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: false
          });
          if (newShowAdd || newShowEdit) {
            gymModalInstance.show();
          } else {
            gymModalInstance.hide();
          }
        }
      });
    });

    const closeGymModal = () => {
      // Chiudi il modal usando l'istanza Bootstrap
      if (gymModalInstance) {
        gymModalInstance.hide();
        // Rimuovi manualmente il backdrop se è rimasto
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
          backdrop.remove();
        }
        // Rimuovi la classe modal-open dal body
        document.body.classList.remove('modal-open');
        // Resetta l'istanza
        gymModalInstance.dispose();
        gymModalInstance = null;
      }
      
      showAddGymModal.value = false;
      showEditGymModal.value = false;
      gymData.value = {
        nome: '',
        citta: '',
        indirizzo: '',
        cap: '',
        orario_apertura: '',
        orario_chiusura: '',
        giorni_apertura: '',
        prezzo_mensile: 0,
        telefono: '',
        email: '',
        descrizione: ''
      };
      selectedDays.value = [];
      imagePreview.value = '';
      selectedImage.value = null;
    };
    
    const handleImageUpload = (event) => {
      const file = event.target.files[0];
      if (file) {
        selectedImage.value = file;
        // Crea un'anteprima dell'immagine
        const reader = new FileReader();
        reader.onload = (e) => {
          imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    };
    
    const submitGym = async () => {
      gymActionLoading.value = true;
      try {
        // Crea un FormData object per inviare anche il file
        const formData = new FormData();
        
        // Aggiungi tutti i dati della palestra
        Object.keys(gymData.value).forEach(key => {
          if (gymData.value[key] !== null && gymData.value[key] !== undefined) {
            formData.append(key, gymData.value[key]);
          }
        });
        
        // Aggiungi l'immagine se presente
        if (selectedImage.value) {
          formData.append('immagine', selectedImage.value);
        }
        
        // Aggiungi i giorni di apertura
        formData.append('giorni_apertura', selectedDays.value.join(','));
        
        let response;
        if (showEditGymModal.value) {
          formData.append('id', gymData.value.id);
          response = await gyms.update(formData);
        } else {
          response = await gyms.create(formData);
        }
        
        if (response.data.success) {
          await loadMyGyms();
          closeGymModal();
          alert('Palestra salvata con successo!');
        } else {
          throw new Error(response.data.message || 'Errore durante il salvataggio della palestra');
        }
      } catch (error) {
        console.error('Errore dettagliato:', error);
        if (error.response?.status === 401) {
          alert('Sessione scaduta. Effettua nuovamente il login.');
          userStore.logout();
          router.push('/login');
        } else {
          alert(error.response?.data?.message || error.message || 'Errore durante il salvataggio della palestra');
        }
      } finally {
        gymActionLoading.value = false;
      }
    };
    
    // Computed properties
    const hasGyms = computed(() => myGyms.value.length > 0);
    
    const viewBookings = async (gym) => {
      selectedGym.value = gym;
      loadingBookings.value = true;
      try {
        const response = await gyms.getBookings(gym.id);
        console.log('Bookings response:', response.data);
        if (response.data.success) {
          gymBookings.value = response.data.data.bookings || [];
          console.log('Loaded bookings:', gymBookings.value);
        } else {
          console.error('Failed to load bookings:', response.data.message);
          gymBookings.value = [];
        }
      } catch (error) {
        console.error('Errore nel caricamento delle prenotazioni:', error);
        gymBookings.value = [];
      } finally {
        loadingBookings.value = false;
      }
    };

    const viewBookingDetails = async (booking) => {
      selectedBooking.value = booking;
      await nextTick();
      
      try {
        const modalEl = document.getElementById('bookingDetailsModal');
        if (modalEl) {
          // Destroy existing modal instance if it exists
          if (bookingModalInstance) {
            bookingModalInstance.dispose();
          }
          // Create new modal instance
          bookingModalInstance = new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: false
          });
          bookingModalInstance.show();
        } else {
          console.error('Modal element not found');
        }
      } catch (error) {
        console.error('Error showing modal:', error);
      }
    };

    const closeBookingDetails = () => {
      try {
        if (bookingModalInstance) {
          bookingModalInstance.hide();
          bookingModalInstance.dispose();
          bookingModalInstance = null;
        }
        selectedBooking.value = null;
      } catch (error) {
        console.error('Error closing modal:', error);
      }
    };

    // Cleanup on component unmount
    onUnmounted(() => {
      if (gymModalInstance) {
        gymModalInstance.dispose();
      }
      if (bookingModalInstance) {
        bookingModalInstance.dispose();
      }
      // Rimuovi manualmente eventuali backdrop rimasti
      const backdrop = document.querySelector('.modal-backdrop');
      if (backdrop) {
        backdrop.remove();
      }
      // Rimuovi la classe modal-open dal body
      document.body.classList.remove('modal-open');
    });

    const updateBookingStatus = async (bookingId, newStatus) => {
      bookingActionLoading.value = true;
      try {
        console.log('Updating booking status:', { bookingId, newStatus });
        const response = await gyms.updateBookingStatus(bookingId, newStatus);
        console.log('Update response:', response.data);
        
        if (response.data.success) {
          // Refresh bookings list
          await viewBookings(selectedGym.value);
          // Refresh gym data to update subscriber count
          await loadMyGyms();
          if (selectedBooking.value && selectedBooking.value.id === bookingId) {
            closeBookingDetails();
          }
        }
      } catch (error) {
        console.error('Errore durante l\'aggiornamento della prenotazione:', error);
        if (error.response?.status === 401) {
          const userStore = useUserStore();
          userStore.logout();
          router.push('/login');
        }
      } finally {
        bookingActionLoading.value = false;
      }
    };
    
    // Gestione palestre
    const editGym = (gym) => {
      gymData.value = { ...gym };
      selectedDays.value = gym.giorni_apertura ? gym.giorni_apertura.split(',').map(Number) : [];
      showEditGymModal.value = true;
    };
    
    const confirmDeleteGym = (gym) => {
      gymToDelete.value = gym;
    };
    
    const deleteGym = async () => {
      gymActionLoading.value = true;
      try {
        const response = await gyms.delete(gymToDelete.value.id);
        if (response.data.success) {
          await loadMyGyms();
          gymToDelete.value = null;
          if (deleteGymModal.value) deleteGymModal.value.hide();
        }
      } catch (error) {
        console.error('Errore durante l\'eliminazione della palestra:', error);
      } finally {
        gymActionLoading.value = false;
      }
    };
    
    return {
      loading,
      myGyms,
      hasGyms,
      showAddGymModal,
      showEditGymModal,
      selectedGym,
      selectedBooking,
      loadingBookings,
      gymBookings,
      bookingActionLoading,
      gymModal,
      deleteGymModal,
      bookingDetailsModal,
      gymData,
      selectedDays,
      days,
      gymActionLoading,
      gymToDelete,
      editGym,
      confirmDeleteGym,
      deleteGym,
      closeGymModal,
      submitGym,
      formatTime,
      formatDate,
      formatDateTime,
      getStatusLabel,
      getStatusClass,
      viewBookings,
      viewBookingDetails,
      closeBookingDetails,
      updateBookingStatus,
      imagePreview,
      handleImageUpload
    };
  }
};
</script>

<style scoped>
.gestione-palestra {
  min-height: calc(100vh - 56px);
  padding-bottom: 2rem;
}

.modal-backdrop {
  opacity: 0.5;
}

.modal.fade .modal-dialog {
  transition: transform .3s ease-out;
}

.modal.show .modal-dialog {
  transform: none;
}
</style>
