import axios from 'axios';

// Create axios instance with base URL
const api = axios.create({
    baseURL: 'http://localhost:8081/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    withCredentials: true
});

// Request interceptor to add auth token
api.interceptors.request.use(config => {
    const token = localStorage.getItem('token');
    console.log('Sending request with token:', token ? 'Bearer ' + token : 'no token');
    
    if (token) {
        // Assicurati che gli headers esistano
        config.headers = config.headers || {};
        // Imposta l'header Authorization
        config.headers.Authorization = `Bearer ${token}`;
        
        // Se è una richiesta multipart/form-data, non impostare Content-Type
        if (config.data instanceof FormData) {
            delete config.headers['Content-Type'];
        }
        
        // Log per debug
        console.log('Request headers:', config.headers);
    }
    return config;
}, error => {
    console.error('Request interceptor error:', error);
    return Promise.reject(error);
});

// Response interceptor to handle errors
api.interceptors.response.use(
    response => {
        console.log('Response received:', {
            url: response.config.url,
            status: response.status,
            data: response.data
        });
        return response;
    },
    error => {
        console.error('API Error:', {
            url: error.config?.url,
            status: error.response?.status,
            message: error.response?.data?.message,
            headers: error.config?.headers // Log headers for debugging
        });
        
        // Non rimuovere mai il token per errori 401 tranne che per login/register
        if (error.response?.status === 401 && 
            (error.config.url.includes('/login.php') || 
             error.config.url.includes('/register.php'))) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
        
        return Promise.reject(error);
    }
);

// Auth endpoints
export const auth = {
    login: (credentials) => api.post('/login.php', credentials),
    register: (userData) => api.post('/register.php', userData),
};

// Users endpoints
export const users = {
    getProfile: () => api.get('/users.php', { params: { action: 'me' } }),
};

// Gyms endpoints
export const gyms = {
    getAll: () => api.get('/gyms.php'),
    search: (citta) => api.get('/gyms.php', { 
        params: { action: 'search', citta: citta }
    }),
    getById: (id) => api.get('/gyms.php', { params: { id: id } }),
    create: (formData) => api.post('/gyms.php', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }),
    update: (formData) => api.post('/gyms.php', formData, {
        params: { action: 'update' },
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }),
    delete: (id) => api.delete('/gyms.php', { params: { id: id } }),
    getBookings: (gymId) => api.get('/bookings.php', { params: { gym_id: gymId } }),
    updateBookingStatus: (bookingId, status) => api.put('/bookings.php', 
        { stato: status }, 
        { params: { id: bookingId, action: 'status' } }
    )
};

// Reviews endpoints
export const reviews = {
    getByGym: (gymId) => api.get('/reviews.php', { params: { gym_id: gymId } }),
    create: (reviewData) => api.post('/reviews.php', reviewData),
    update: (id, reviewData) => api.put('/reviews.php', reviewData, { params: { id: id } }),
    delete: (id) => api.delete('/reviews.php', { params: { id: id } }),
};

// Bookings endpoints
export const bookings = {
    getByUser: () => api.get('/bookings.php'),
    create: (bookingData) => api.post('/bookings.php', bookingData),
    update: (id, bookingData) => api.put('/bookings.php', bookingData, { params: { id: id } }),
    delete: (id) => api.delete('/bookings.php', { params: { id: id } }),
};

// Images endpoints
export const images = {
    upload: (formData) => api.post('/images.php', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }),
};

export default api; 