/* ═══════════════════════════════════════════════════════════════
   SUMAS SmartAttend — API Service
   Connects the frontend to the Laravel backend REST API.
   Base URL is configured here — change to your server address.
═══════════════════════════════════════════════════════════════ */
'use strict';

const API = {
  BASE: '/api', // Same-origin Laravel API (works on any host/port)

  /* ── Helper ── */
  async _req(method, path, data = null, auth = true) {
    const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
    if (auth) {
      const token = SessionStore.getToken();
      if (token) headers['Authorization'] = `Bearer ${token}`;
    }
    const opts = { method, headers };
    if (data && method !== 'GET') opts.body = JSON.stringify(data);
    try {
      const res = await fetch(`${this.BASE}${path}`, opts);
      const json = await res.json().catch(() => ({}));
      return { ok: res.ok, status: res.status, data: json };
    } catch (err) {
      return { ok: false, status: 0, data: { message: 'Network error. Please check your connection.' } };
    }
  },

  get:    (path, auth) => API._req('GET',    path, null, auth),
  post:   (path, data, auth) => API._req('POST',   path, data, auth),
  put:    (path, data, auth) => API._req('PUT',    path, data, auth),
  delete: (path, auth)  => API._req('DELETE', path, null, auth),

  /**
   * Stable device fingerprint used for the one-scan-per-device guard on the
   * smart-attendance endpoints. Persisted in localStorage and blended with
   * stable browser characteristics so clearing storage alone cannot forge a
   * "brand new device" for the next scan.
   */
  deviceId() {
    let d = null;
    try { d = localStorage.getItem('sumas_device_id'); } catch (e) {}
    if (!d) {
      d = (window.crypto && crypto.randomUUID) ? crypto.randomUUID()
        : 'dev-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
      try { localStorage.setItem('sumas_device_id', d); } catch (e) {}
    }
    const fp = [
      navigator.userAgent, navigator.language, navigator.platform,
      screen.width + 'x' + screen.height + 'x' + (screen.colorDepth || 24),
      new Date().getTimezoneOffset(), d
    ].join('|');
    let h = 0, i;
    for (i = 0; i < fp.length; i++) { h = ((h << 5) - h + fp.charCodeAt(i)) | 0; }
    return 'dev-' + Math.abs(h).toString(36) + '-' + String(d).slice(0, 8);
  },

  /**
   * Promise-based geolocation helper (returns null when unavailable/denied).
   */
  getPosition(timeoutMs) {
    return new Promise(resolve => {
      if (!navigator.geolocation) { resolve(null); return; }
      navigator.geolocation.getCurrentPosition(
        pos => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
        () => resolve(null),
        { enableHighAccuracy: true, timeout: timeoutMs || 8000, maximumAge: 30000 }
      );
    });
  },

  /* ══ AUTH ENDPOINTS ══ */
  auth: {
    async register(payload) {
      // Registration carries the student's documents as multipart FormData and
      // must NOT include a session token — students cannot sign in until approved.
      if (payload instanceof FormData) {
        return API._upload('/auth/register', payload, null, false);
      }
      return API.post('/auth/register', payload, false);
    },
    async login(matric, password) {
      return API.post('/auth/login', { matric, password }, false);
    },
    async adminLogin(username, password) {
      return API.post('/auth/admin-login', { username, password }, false);
    },
    async logout() {
      return API.post('/auth/logout');
    },
    async adminLogout() {
      return API.post('/admin/auth/logout');
    },
    async lecturerLogout() {
      return API.post('/lecturer/auth/logout');
    },
    async me() {
      return API.get('/auth/me');
    },
  },

  /* ── Multipart upload helper (FormData, with progress callback) ── */
  async _upload(path, formData, onProgress = null, auth = true) {
    const token = SessionStore.getToken();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return new Promise(resolve => {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', `${this.BASE}${path}`);
      if (auth && token) xhr.setRequestHeader('Authorization', `Bearer ${token}`);
      if (csrfToken) xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.upload.onprogress = e => {
        if (onProgress && e.lengthComputable) onProgress(Math.round((e.loaded / e.total) * 100));
      };
      xhr.onload = () => {
        let data = {};
        try { data = JSON.parse(xhr.responseText); } catch {}
        resolve({ ok: xhr.status >= 200 && xhr.status < 300, status: xhr.status, data });
      };
      xhr.onerror = () => resolve({ ok: false, status: 0, data: { message: 'Network error. Please check your connection.' } });
      xhr.send(formData);
    });
  },

  /* ══ STUDENT ENDPOINTS ══ */
  student: {
    async dashboard() {
      return API.get('/student/dashboard');
    },
    async updateProfile(data) {
      return API.put('/student/profile', data);
    },
    async checkStatus(matric) {
      return API.get(`/auth/check-status?matric=${encodeURIComponent(matric)}`, false);
    },
    async getDocuments() {
      return API.get('/student/documents');
    },
    async uploadDocument(docType, file, onProgress) {
      const fd = new FormData();
      fd.append('doc_type', docType);
      fd.append('file', file);
      return API._upload('/student/documents', fd, onProgress);
    },
    async deleteDocument(id) {
      return API.delete(`/student/documents/${id}`);
    },
    async courses(params = {}) {
      const q = new URLSearchParams(params).toString();
      return API.get(`/student/courses${q ? '?'+q : ''}`);
    },
    async attendance() {
      return API.get('/student/attendance');
    },
    async lectures() {
      return API.get('/student/lectures');
    },
    async notifications() {
      return API.get('/student/notifications');
    },
    async markNotificationRead(id) {
      return API.put(`/student/notifications/${id}/read`);
    },
    // Face-scan smart attendance (no longer used — lecturer scans the student)
    // These endpoints have been removed. Attendance is now driven by the
    // lecturer who scans students during lecture via the lecturer dashboard.
  },

  /* ══ LECTURER ENDPOINTS ══ */
  lecturer: {
    async login(email, password) {
      return API.post('/auth/lecturer-login', { email, password }, false);
    },
    async dashboard() {
      return API.get('/lecturer/dashboard');
    },
    async students(params = {}) {
      const q = new URLSearchParams(params).toString();
      return API.get(`/lecturer/students${q ? '?'+q : ''}`);
    },
    async courses() {
      return API.get('/lecturer/courses');
    },
    async createLecture(data) {
      return API.post('/lecturer/lectures', data);
    },
    async lectures(params = {}) {
      const q = new URLSearchParams(params).toString();
      return API.get(`/lecturer/lectures${q ? '?'+q : ''}`);
    },
    async recordAttendance(data) {
      return API.post('/lecturer/attendance', data);
    },
    async getAttendance(lectureId) {
      return API.get(`/lecturer/attendance/${lectureId}`);
    },
    async endLecture(id) {
      return API.post(`/lecturer/lectures/${id}/end`);
    },
    async liveCode(id) {
      return API.get(`/lecturer/lectures/${id}/live`);
    },
    async scanStudent(lectureId, payload) {
      return API.post(`/lecturer/lectures/${lectureId}/scan-student`, payload);
    },
    async confirmScanStudent(lectureId, studentId) {
      return API.post(`/lecturer/lectures/${lectureId}/scan-student`, { student_id: studentId, confirm: true });
    },
  },

  /* ══ ADMIN ENDPOINTS ══ */
  admin: {
    async stats() {
      return API.get('/admin/stats');
    },
    async getStudents(params = {}) {
      const q = new URLSearchParams(params).toString();
      return API.get(`/admin/students${q ? '?'+q : ''}`);
    },
    async getStudent(id) {
      return API.get(`/admin/students/${id}`);
    },
    async updateStatus(id, status) {
      return API.put(`/admin/students/${id}/status`, { status });
    },
    async faceRegister(id, faceImage, embedding) {
      const fd = new FormData();
      fd.append('face_image', faceImage); // Blob/File from webcam capture
      if (embedding) fd.append('face_embedding', JSON.stringify(embedding)); // 128-dim FaceNet descriptor
      return API._upload(`/admin/students/${id}/face-register`, fd);
    },
    async changePassword(data) {
      return API.post('/admin/password', data);
    },
    async deleteStudent(id) {
      return API.delete(`/admin/students/${id}`);
    },
    async exportCsv() {
      const token = SessionStore.getToken();
      const res = await fetch(`${API.BASE}/admin/export`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'text/csv' }
      });
      if (!res.ok) return null;
      return res.blob();
    },
    async courses() {
      return API.get('/admin/courses');
    },
    async createCourse(data) {
      return API.post('/admin/courses', data);
    },
    async updateCourse(id, data) {
      return API.put(`/admin/courses/${id}`, data);
    },
    async deleteCourse(id) {
      return API.delete(`/admin/courses/${id}`);
    },
    async assignLecturer(courseId, data) {
      return API.post(`/admin/courses/${courseId}/assign-lecturer`, data);
    },
    async removeLecturer(courseId, lecturerId) {
      return API.delete(`/admin/courses/${courseId}/lecturers/${lecturerId}`);
    },
    async lecturers() {
      return API.get('/admin/lecturers');
    },
    async createLecturer(data) {
      return API.post('/admin/lecturers', data);
    },
    async updateLecturer(id, data) {
      return API.put(`/admin/lecturers/${id}`, data);
    },
    async deleteLecturer(id) {
      return API.delete(`/admin/lecturers/${id}`);
    },
    async departments() {
      return API.get('/admin/departments');
    },
    async faculties() {
      return API.get('/admin/faculties');
    },
    async createFaculty(data) {
      return API.post('/admin/faculties', data);
    },
    async updateFaculty(id, data) {
      return API.put(`/admin/faculties/${id}`, data);
    },
    async deleteFaculty(id) {
      return API.delete(`/admin/faculties/${id}`);
    },
    async allDepartments() {
      return API.get('/admin/departments/all');
    },
    async createDepartment(data) {
      return API.post('/admin/departments', data);
    },
    async updateDepartment(id, data) {
      return API.put(`/admin/departments/${id}`, data);
    },
    async deleteDepartment(id) {
      return API.delete(`/admin/departments/${id}`);
    },
    async academicLevels() {
      return API.get('/admin/academic-levels');
    },
    async createAcademicLevel(data) {
      return API.post('/admin/academic-levels', data);
    },
    async updateAcademicLevel(id, data) {
      return API.put(`/admin/academic-levels/${id}`, data);
    },
    async deleteAcademicLevel(id) {
      return API.delete(`/admin/academic-levels/${id}`);
    },
  },
};

/* ══════════════════════════════════════════
   SESSION STORE — manages tokens + user data
══════════════════════════════════════════ */
const SessionStore = {
  KEYS: {
    TOKEN: 'sumas_token',
    USER:  'sumas_user',
    ROLE:  'sumas_role', // 'student' | 'admin'
  },

  save(token, user, role) {
    try {
      sessionStorage.setItem(this.KEYS.TOKEN, token);
      sessionStorage.setItem(this.KEYS.USER,  JSON.stringify(user));
      sessionStorage.setItem(this.KEYS.ROLE,  role);
      // Mirror to localStorage so the QR check-in page — which the camera app
      // opens in a NEW tab (a fresh sessionStorage) — can reuse the session
      // instead of forcing a re-login.
      localStorage.setItem(this.KEYS.TOKEN, token);
      localStorage.setItem(this.KEYS.USER,  JSON.stringify(user));
      localStorage.setItem(this.KEYS.ROLE,  role);
    } catch {}
  },

  getToken() { return sessionStorage.getItem(this.KEYS.TOKEN) || localStorage.getItem(this.KEYS.TOKEN) || ''; },
  getUser()  {
    try { return JSON.parse(sessionStorage.getItem(this.KEYS.USER) || localStorage.getItem(this.KEYS.USER) || 'null'); } catch { return null; }
  },
  getRole()  { return sessionStorage.getItem(this.KEYS.ROLE) || localStorage.getItem(this.KEYS.ROLE) || ''; },

  isLoggedIn()     { return !!this.getToken() && !!this.getUser(); },
  isStudent()      { return this.getRole() === 'student'; },
  isAdmin()        { return this.getRole() === 'admin'; },
  isLecturer()     { return this.getRole() === 'lecturer'; },

  clear() {
    Object.values(this.KEYS).forEach(k => {
      sessionStorage.removeItem(k);
      localStorage.removeItem(k);
    });
  },

  requireStudent(redirect = '/login') {
    if (!this.isLoggedIn() || !this.isStudent()) window.location.href = redirect;
  },
  requireAdmin(redirect = '/admin/login') {
    if (!this.isLoggedIn() || !this.isAdmin()) window.location.href = redirect;
  },
  requireLecturer(redirect = '/lecturer/login') {
    if (!this.isLoggedIn() || !this.isLecturer()) window.location.href = redirect;
  },

  /* ── Login-page bounces (session-aware) ──
     Login now starts a real backend session, and the protected dashboards
     redirect unauthenticated visitors server-side. So before bouncing an
     already-"logged in" visitor from a login page to their dashboard, confirm
     the backend session is actually active. If the session was deleted or
     expired, clear the stale local token — otherwise the server redirect and
     this bounce would send the user back and forth forever. */
  async _bounceIfSession(role, redirect) {
    if (!this.isLoggedIn() || this.getRole() !== role) return;
    const res = await API.get('/session/status', false);
    if (!res.ok) return; // network blip — leave the login form in control
    if (res.data.authenticated && res.data.role === role) {
      window.location.href = redirect;
    } else if (!res.data.authenticated) {
      this.clear();
    }
  },
  redirectIfStudent(redirect = '/dashboard') {
    return this._bounceIfSession('student', redirect);
  },
  redirectIfAdmin(redirect = '/admin/dashboard') {
    return this._bounceIfSession('admin', redirect);
  },
  redirectIfLecturer(redirect = '/lecturer/dashboard') {
    return this._bounceIfSession('lecturer', redirect);
  },

  /**
   * Server-side session check — call on page reload. If the session was
   * revoked or expired, clear local storage and redirect to the role's login
   * page. Returns true when the session is still valid.
   */
  async verify(role = 'student', redirect = '/login') {
    const path = role === 'admin'    ? '/admin/auth/me'
               : role === 'lecturer' ? '/lecturer/auth/me'
               : '/auth/me';
    const res = await API.get(path);
    // Only a genuine auth failure (401) or a revoked/denied token (403) means
    // the session is dead. Network blips (status 0) or 5xx should NOT wipe the
    // session — the dashboard's own loaders handle those gracefully.
    if (res.status === 401 || res.status === 403) {
      this.clear();
      window.location.href = redirect;
      return false;
    }
    return true;
  },
};

window.API = API;
window.SessionStore = SessionStore;
