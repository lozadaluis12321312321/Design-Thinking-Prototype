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
        let errorMsg = `API Error: ${response.status}`;
        try {
            const errData = await response.json();
            if (errData.error) errorMsg = errData.error;
        } catch (e) {}
        throw new Error(errorMsg);
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

async function getUnavailableTimes(doctorId, date) {
  return await apiRequest(`/appointments.php?action=unavailable_times&doctorId=${doctorId}&date=${date}`, 'GET');
}

async function bookAppointment(appointment) {
  return await apiRequest('/appointments.php', 'POST', appointment);
}

async function updateAppointmentStatus(id, status) {
  return await apiRequest(`/appointments.php/${id}/status`, 'PUT', { status }, true);
}

// --- Archive / restore (admin): records are never deleted, only set inactive ---
async function setArchived(resource, id, archive = true) {
  return await apiRequest(`/${resource}.php/${id}/${archive ? 'archive' : 'restore'}`, 'PUT', null, true);
}

const archiveAppointment = (id) => setArchived('appointments', id, true);
const restoreAppointment = (id) => setArchived('appointments', id, false);
const archiveOrder = (id) => setArchived('orders', id, true);
const restoreOrder = (id) => setArchived('orders', id, false);
const archiveDoctor = (id) => setArchived('doctors', id, true);
const restoreDoctor = (id) => setArchived('doctors', id, false);

// --- Customer account + patient record API ---
function getCustToken() {
  return localStorage.getItem('custToken');
}

function getCustUser() {
  try { return JSON.parse(localStorage.getItem('custUser')); } catch (e) { return null; }
}

// Like apiRequest, but authenticates as the signed-in customer instead of admin.
async function custRequest(endpoint, method = 'GET', data = null) {
  const headers = {};
  const token = getCustToken();
  if (token) headers['Authorization'] = `Bearer ${token}`;
  if (data) headers['Content-Type'] = 'application/json';
  try {
    const response = await fetch(`${API_BASE}${endpoint}`, { method, headers, body: data ? JSON.stringify(data) : undefined });
    if (!response.ok) {
      let err = { error: `API Error: ${response.status}` };
      try { err = await response.json(); } catch (e) {}
      const e = new Error(err.error || `API Error: ${response.status}`);
      e.status = response.status;
      e.field = err.field;
      if (response.status === 401) { localStorage.removeItem('custToken'); localStorage.removeItem('custUser'); }
      throw e;
    }
    return await response.json();
  } catch (error) {
    console.error(`Error with ${method} ${endpoint}:`, error);
    throw error;
  }
}

function storeCustSession(res) {
  if (res && res.token) {
    localStorage.setItem('custToken', res.token);
    localStorage.setItem('custUser', JSON.stringify(res.customer));
  }
}

async function customerRegister(payload) {
  const res = await apiRequest('/customer_auth.php?action=register', 'POST', payload);
  storeCustSession(res);
  return res;
}

async function customerLogin(email, password) {
  const res = await apiRequest('/customer_auth.php?action=login', 'POST', { email, password });
  storeCustSession(res);
  return res;
}

async function customerMe() {
  return await custRequest('/customer_auth.php?action=me');
}

async function customerLogout() {
  try { await custRequest('/customer_auth.php?action=logout', 'POST'); } catch (e) { /* already invalid */ }
  localStorage.removeItem('custToken');
  localStorage.removeItem('custUser');
}

async function getMyRecord() {
  return await custRequest('/patient_records.php');
}

async function updateMyRecord(data) {
  const rec = await custRequest('/patient_records.php', 'PUT', data);
  // Keep the cached display name/contact in sync.
  const c = getCustUser();
  if (c) localStorage.setItem('custUser', JSON.stringify({ ...c, fullName: rec.fullName, contactNumber: rec.contactNumber }));
  return rec;
}

// --- Patient records (admin) ---
async function getPatientRecords(q) {
  return await apiRequest(`/patient_records.php${q ? `?q=${encodeURIComponent(q)}` : ''}`, 'GET', null, true);
}

async function getPatientRecord(id) {
  return await apiRequest(`/patient_records.php/${id}`, 'GET', null, true);
}

async function updatePatientRecord(id, data) {
  return await apiRequest(`/patient_records.php/${id}`, 'PUT', data, true);
}

const archivePatientRecord = (id) => setArchived('patient_records', id, true);
const restorePatientRecord = (id) => setArchived('patient_records', id, false);

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
  getUnavailableTimes,
  bookAppointment,
  updateAppointmentStatus,
  archiveAppointment,
  restoreAppointment,
  archiveOrder,
  restoreOrder,
  archiveDoctor,
  restoreDoctor,
  archivePatientRecord,
  restorePatientRecord,
  login,
  logout,
  getCustToken,
  getCustUser,
  customerRegister,
  customerLogin,
  customerMe,
  customerLogout,
  getMyRecord,
  updateMyRecord,
  getPatientRecords,
  getPatientRecord,
  updatePatientRecord
};
