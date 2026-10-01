/**
 * One Optics Clinic - API Client
 * This file connects the frontend to the Node.js/SQLite backend.
 */

const API_BASE = 'api';

// Helper to get auth token
function getToken() {
  return localStorage.getItem('adminToken');
}

// Helper for API requests
async function apiRequest(endpoint, method = 'GET', data = null, requireAuth = false) {
  const headers = {};
  if (data) {
    headers['Content-Type'] = 'application/json';
  }
  if (requireAuth) {
    const token = getToken();
    if (token) headers['Authorization'] = `Bearer ${token}`;
  }

  const config = { method, headers };
  if (data) config.body = JSON.stringify(data);

  try {
    const response = await fetch(`${API_BASE}${endpoint}`, config);
    if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
    }
    return await response.json();
  } catch (error) {
    console.error(`Error with ${method} ${endpoint}:`, error);
    throw error;
  }
}

// --- Product API ---
async function getProducts() {
  return await apiRequest('/products.php');
}

async function getProductById(id) {
  return await apiRequest(`/products.php?id=${id}`);
}

async function addProduct(product) {
  return await apiRequest('/products.php', 'POST', product, true);
}

async function updateProduct(id, updatedData) {
  return await apiRequest(`/products.php/${id}`, 'PUT', updatedData, true);
}

async function deleteProduct(id) {
  return await apiRequest(`/products.php/${id}`, 'DELETE', null, true);
}

// --- Doctor API ---
async function getDoctors() {
  return await apiRequest('/doctors.php');
}

async function getDoctorById(id) {
  const doctors = await getDoctors();
  return doctors.find(d => d.id === parseInt(id));
}

async function addDoctor(doctor) {
  return await apiRequest('/doctors.php', 'POST', doctor, true);
}

async function updateDoctor(id, updatedData) {
  return await apiRequest(`/doctors.php/${id}`, 'PUT', updatedData, true);
}

async function deleteDoctor(id) {
  return await apiRequest(`/doctors.php/${id}`, 'DELETE', null, true);
}

// --- Appointment API ---
async function getAppointments() {
  return await apiRequest('/appointments.php', 'GET', null, true);
}

async function bookAppointment(appointment) {
  return await apiRequest('/appointments.php', 'POST', appointment);
}

async function updateAppointmentStatus(id, status) {
  return await apiRequest(`/appointments.php/${id}/status`, 'PUT', { status }, true);
}

// --- Auth API ---
async function login(username, password) {
  const res = await apiRequest('/auth.php', 'POST', { username, password });
  if (res.token) {
      localStorage.setItem('adminToken', res.token);
      localStorage.setItem('adminAuth', 'true');
  }
  return res;
}

function logout() {
  localStorage.removeItem('adminToken');
  localStorage.removeItem('adminAuth');
}

// Export functions for global use
window.OpticsDB = {
  getProducts,
  getProductById,
  addProduct,
  updateProduct,
  deleteProduct,
  getDoctors,
  getDoctorById,
  addDoctor,
  updateDoctor,
  deleteDoctor,
  getAppointments,
  bookAppointment,
  updateAppointmentStatus,
  login,
  logout
};
