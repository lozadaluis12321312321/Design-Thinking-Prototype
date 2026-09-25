/**
 * One Optics Clinic - LocalStorage Database Simulation
 * This file simulates a backend database using browser LocalStorage.
 * It's designed to make the project functional without needing a real server.
 */

const DB_KEY = 'oneOpticsDB';

// Initial Sample Data
const initialData = {
  products: [
    { id: 1, name: 'Classic Aviator', type: 'Full Rim', style: 'Aviator', color: 'Gold', price: 2500, description: 'Timeless classic aviator frames suitable for all face shapes.', availability: 'In Stock', image: 'https://via.placeholder.com/400x300/f8f9fa/333333?text=Classic+Aviator' },
    { id: 2, name: 'Modern Rectangle', type: 'Half Rim', style: 'Rectangle', color: 'Black', price: 1800, description: 'Sleek and professional rectangle frames.', availability: 'In Stock', image: 'https://via.placeholder.com/400x300/f8f9fa/333333?text=Modern+Rectangle' },
    { id: 3, name: 'Vintage Round', type: 'Full Rim', style: 'Round', color: 'Tortoiseshell', price: 2200, description: 'Retro-inspired round frames for a distinct look.', availability: 'Low Stock', image: 'https://via.placeholder.com/400x300/f8f9fa/333333?text=Vintage+Round' },
    { id: 4, name: 'Elegant Cat-Eye', type: 'Full Rim', style: 'Cat-Eye', color: 'Red', price: 2800, description: 'Bold and elegant cat-eye frames.', availability: 'In Stock', image: 'https://via.placeholder.com/400x300/f8f9fa/333333?text=Elegant+Cat-Eye' },
    { id: 5, name: 'Minimalist Rimless', type: 'Rimless', style: 'Rectangle', color: 'Silver', price: 3000, description: 'Ultra-lightweight rimless glasses.', availability: 'Out of Stock', image: 'https://via.placeholder.com/400x300/f8f9fa/333333?text=Minimalist+Rimless' },
    { id: 6, name: 'Bold Square', type: 'Full Rim', style: 'Square', color: 'Clear', price: 2100, description: 'Contemporary clear square frames.', availability: 'In Stock', image: 'https://via.placeholder.com/400x300/f8f9fa/333333?text=Bold+Square' }
  ],
  doctors: [
    { id: 1, name: 'Doctor Bong Tayamora', role: 'Optometrist / Doctor', image: 'https://via.placeholder.com/150/f8f9fa/333333?text=Dr.+Bong' }
  ],
  appointments: []
};

// Initialize DB if it doesn't exist
function initDB() {
  if (!localStorage.getItem(DB_KEY)) {
    localStorage.setItem(DB_KEY, JSON.stringify(initialData));
  }
}

// Get entire database
function getDB() {
  return JSON.parse(localStorage.getItem(DB_KEY));
}

// Save entire database
function saveDB(data) {
  localStorage.setItem(DB_KEY, JSON.stringify(data));
}

// --- Product API ---
function getProducts() {
  return getDB().products;
}

function getProductById(id) {
  const products = getProducts();
  return products.find(p => p.id === parseInt(id));
}

function addProduct(product) {
  const db = getDB();
  product.id = db.products.length > 0 ? Math.max(...db.products.map(p => p.id)) + 1 : 1;
  db.products.push(product);
  saveDB(db);
  return product;
}

function updateProduct(id, updatedData) {
  const db = getDB();
  const index = db.products.findIndex(p => p.id === parseInt(id));
  if (index !== -1) {
    db.products[index] = { ...db.products[index], ...updatedData, id: parseInt(id) };
    saveDB(db);
    return true;
  }
  return false;
}

function deleteProduct(id) {
  const db = getDB();
  db.products = db.products.filter(p => p.id !== parseInt(id));
  saveDB(db);
}

// --- Doctor API ---
function getDoctors() {
  return getDB().doctors || [];
}

function getDoctorById(id) {
  const doctors = getDoctors();
  return doctors.find(d => d.id === parseInt(id));
}

// --- Appointment API ---
function getAppointments() {
  return getDB().appointments;
}

function bookAppointment(appointment) {
  const db = getDB();
  appointment.id = db.appointments.length > 0 ? Math.max(...db.appointments.map(a => a.id)) + 1 : 1;
  appointment.status = 'Pending';
  appointment.createdDate = new Date().toISOString();
  db.appointments.push(appointment);
  saveDB(db);
  return appointment;
}

function updateAppointmentStatus(id, status) {
  const db = getDB();
  const index = db.appointments.findIndex(a => a.id === parseInt(id));
  if (index !== -1) {
    db.appointments[index].status = status;
    saveDB(db);
    return true;
  }
  return false;
}

// Initialize on load
initDB();

// Export functions for global use
window.OpticsDB = {
  getProducts,
  getProductById,
  addProduct,
  updateProduct,
  deleteProduct,
  getDoctors,
  getDoctorById,
  getAppointments,
  bookAppointment,
  updateAppointmentStatus
};
