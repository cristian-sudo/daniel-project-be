import { createRouter, createWebHistory } from 'vue-router';
import { useUserStore } from '../store/user';

// Importazione delle viste
import Home from '../views/Home.vue';
import Palestre from '../views/Palestre.vue';
import PalestraDettaglio from '../views/PalestraDettaglio.vue';
import Login from '../views/Login.vue';
import Registrazione from '../views/Registrazione.vue';
import Profilo from '../views/Profilo.vue';
import GestionePalestra from '../views/GestionePalestra.vue';
import Prenotazioni from '../views/Prenotazioni.vue';
import ChiSiamo from '../views/ChiSiamo.vue';
import NotFound from '../views/NotFound.vue';


const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home
  },
  {
    path: '/palestre',
    name: 'Palestre',
    component: Palestre
  },
  {
    path: '/palestra/:id',
    name: 'PalestraDettaglio',
    component: PalestraDettaglio,
    props: true
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { guestOnly: true }
  },
  {
    path: '/registrazione',
    name: 'Registrazione',
    component: Registrazione,
    meta: { guestOnly: true }
  },
  {
    path: '/profilo',
    name: 'Profilo',
    component: Profilo,
    meta: { requiresAuth: true }
  },
  {
    path: '/gestione-palestra',
    name: 'GestionePalestra',
    component: GestionePalestra,
    meta: { requiresAuth: true, gymOwnerOnly: true }
  },
  {
    path: '/prenotazioni',
    name: 'Prenotazioni',
    component: Prenotazioni,
    meta: { requiresAuth: true }
  },
  {
    path: '/chi-siamo',
    name: 'ChiSiamo',
    component: ChiSiamo
  },
  
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFound
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    // Torna sempre all'inizio della pagina
    return { top: 0 };
  }
});

// Guardia di navigazione
router.beforeEach((to, from, next) => {
  const userStore = useUserStore();
  
  // Verifica se la rotta richiede autenticazione
  if (to.meta.requiresAuth && !userStore.isLoggedIn) {
    next({ name: 'Login', query: { redirect: to.fullPath } });
    return;
  }
  
  // Verifica se la rotta è solo per gestori palestra
  if (to.meta.gymOwnerOnly && !userStore.isGymOwner) {
    next({ name: 'Home' });
    return;
  }
  
  // Verifica se la rotta è solo per ospiti (non autenticati)
  if (to.meta.guestOnly && userStore.isLoggedIn) {
    next({ name: 'Home' });
    return;
  }
  
  next();
});

export default router;
