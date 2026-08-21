/* ═══════════════════════════════════════════════════
   SUMAS SmartAttend — Admin Panel JS
   Connects to Laravel backend API
═══════════════════════════════════════════════════ */
'use strict';

/* ── Loader ── */
window.addEventListener('load', () => {
  setTimeout(() => {
    const loader = document.getElementById('page-loader');
    if (loader) {
      loader.classList.add('exit');
      setTimeout(() => {
        if (loader.parentNode) {
          loader.style.display = 'none';
        }
      }, 500);
    }
  }, 500);
});

// Fallback: ensure loader is removed after 5 seconds regardless of other issues
setTimeout(() => {
  const loader = document.getElementById('page-loader');
  if (loader && loader.style.display !== 'none') {
    loader.style.display = 'none';
  }
}, 5000);

/* ── Theme ── */
function initTheme() {
  const t = localStorage.getItem('sumas-theme') || 'light';
  document.documentElement.setAttribute('data-theme', t);
  document.querySelectorAll('.theme-toggle, #theme-toggle').forEach(b => b.textContent = t === 'dark' ? '☀️' : '🌙');
}
initTheme();
document.querySelectorAll('.theme-toggle, #theme-toggle').forEach(btn => {
  btn.addEventListener('click', () => {
    const n = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', n);
    localStorage.setItem('sumas-theme', n);
    document.querySelectorAll('.theme-toggle, #theme-toggle').forEach(b => b.textContent = n === 'dark' ? '☀️' : '🌙');
  });
});

/* ── Toast ── */
function showToast(type, title, message = '', duration = 4000) {
  const c = document.getElementById('toast-container'); if (!c) return;
  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span class="toast-icon">${icons[type] || '💬'}</span><div style="flex:1"><div class="toast-title">${title}</div>${message ? `<div class="toast-body">${message}</div>` : ''}</div><button class="toast-x" onclick="this.closest('.toast').remove()">✕</button>`;
  c.appendChild(t);
  setTimeout(() => { t.style.transition = 'all .4s'; t.style.opacity = '0'; t.style.transform = 'translateX(110%)'; setTimeout(() => t.remove(), 400); }, duration);
}
window.showToast = showToast;

function fmtDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' });
}

function setupDropdown(btnId, ddId) {
  const btn = document.getElementById(btnId), dd = document.getElementById(ddId);
  if (!btn || !dd) return;
  btn.addEventListener('click', e => { e.stopPropagation(); dd.classList.toggle('open'); });
  document.addEventListener('click', () => dd?.classList.remove('open'));
}

/* ════════════════════════════════
   ADMIN LOGIN PAGE
════════════════════════════════ */
if (document.getElementById('admin-login-page')) {
  SessionStore.redirectIfAdmin('/admin/dashboard');

  document.getElementById('admin-toggle-pw')?.addEventListener('click', function () {
    const pw = document.getElementById('admin-password');
    pw.type = pw.type === 'password' ? 'text' : 'password';
    this.textContent = pw.type === 'text' ? '🙈' : '👁️';
  });

  const loginBtn = document.getElementById('admin-login-btn');

  function doAdminLogin() {
    const username = document.getElementById('admin-username')?.value.trim();
    const password = document.getElementById('admin-password')?.value;
    if (!username || !password) { showToast('error', 'Required', 'Please enter admin credentials.'); return; }

    loginBtn.disabled = true;
    loginBtn.textContent = '⏳ Verifying…';

    API.auth.adminLogin(username, password).then(res => {
      if (res.ok && res.data.token) {
        SessionStore.save(res.data.token, res.data.admin, 'admin');
        showToast('success', 'Welcome, Admin!', 'Loading dashboard…');
        setTimeout(() => window.location.href = '/admin/dashboard', 700);
      } else {
        showToast('error', 'Access Denied', res.data.message || 'Invalid admin credentials.');
        loginBtn.disabled = false;
        loginBtn.textContent = 'Sign In to Admin Panel';
      }
    });
  }

  loginBtn?.addEventListener('click', doAdminLogin);
  document.getElementById('admin-password')?.addEventListener('keydown', e => e.key === 'Enter' && doAdminLogin());
  document.getElementById('admin-username')?.addEventListener('keydown', e => e.key === 'Enter' && doAdminLogin());
}

/* ════════════════════════════════
   ADMIN DASHBOARD
════════════════════════════════ */
if (document.getElementById('admin-dashboard')) {
  SessionStore.requireAdmin('/admin/login');

  let currentFilter  = 'all';
  let currentSearch  = '';
  let allStudents    = [];
  let selectedId     = null;
  let selectedStudent = null;
  let sortBy = 'date', sortDir = 'desc';

  const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || '—'; };

  /* ── Modal helpers ──
     .modal-box is hidden (opacity:0) until its parent .student-modal
     gets the .open class — a bare display swap leaves it invisible. */
  const ADMIN_MODALS = ['course-modal','lecturer-modal','faculty-modal','department-modal','level-modal','assign-lecturer-modal','student-edit-modal'];

  function openAdminModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.style.display = 'flex';        // flex so overlay centers the box
    void modal.offsetWidth;              // force reflow so the transition plays
    modal.classList.add('open');         // reveals .modal-box (opacity 1)
  }

  function closeAdminModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    setTimeout(() => { modal.style.display = 'none'; }, 300);
  }

  // Clicking the dark backdrop closes the modal
  ADMIN_MODALS.forEach(id => {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.addEventListener('click', e => { if (e.target === modal) closeAdminModal(id); });
  });

  // Escape key closes any open modal
  document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    const openModal = ADMIN_MODALS.find(id => document.getElementById(id)?.classList.contains('open'));
    if (openModal) closeAdminModal(openModal);
    const studentModal = document.getElementById('student-modal');
    if (studentModal?.classList.contains('open')) window.closeStudentModal?.();
  });

  /* ── Student Management ── */
  let allFaculties = [];
  let allDepartments = [];

  async function loadFacultiesAndDepartments() {
    const [facRes, deptRes] = await Promise.all([
      API.get('/faculties'),
      API.get('/departments')
    ]);
    if (facRes.ok) allFaculties = facRes.data.faculties || [];
    if (deptRes.ok) allDepartments = deptRes.data.departments || [];
  }

  function populateFacultySelect(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;
    select.innerHTML = '<option value="">Select Faculty</option>';
    allFaculties.forEach(faculty => {
      const option = document.createElement('option');
      option.value = faculty.id;
      option.textContent = faculty.name;
      select.appendChild(option);
    });
  }

  function populateDepartmentSelect(selectId, facultyId = null) {
    const select = document.getElementById(selectId);
    if (!select) return;
    select.innerHTML = '<option value="">Select Department</option>';
    
    const filteredDepts = facultyId 
      ? allDepartments.filter(dept => dept.faculty_id === parseInt(facultyId))
      : allDepartments;
    
    filteredDepts.forEach(dept => {
      const option = document.createElement('option');
      option.value = dept.id;
      option.textContent = dept.name;
      select.appendChild(option);
    });
  }

  // Create student button
  document.getElementById('create-student-btn')?.addEventListener('click', () => {
    document.getElementById('student-edit-id').value = '';
    document.getElementById('student-edit-name').value = '';
    document.getElementById('student-edit-matric').value = '';
    document.getElementById('student-edit-email').value = '';
    document.getElementById('student-edit-phone').value = '';
    document.getElementById('student-edit-password').value = '';
    document.getElementById('student-edit-faculty').value = '';
    document.getElementById('student-edit-dept').value = '';
    document.getElementById('student-edit-level').value = '';
    document.getElementById('student-edit-gender').value = '';
    document.getElementById('student-edit-dob').value = '';
    document.getElementById('student-edit-state').value = '';
    document.getElementById('student-edit-status').value = 'Pending';
    document.getElementById('student-edit-modal-title').textContent = 'Create Student';
    populateFacultySelect('student-edit-faculty');
    populateDepartmentSelect('student-edit-dept');
    openAdminModal('student-edit-modal');
  });

  // Faculty change handler for student edit modal
  document.getElementById('student-edit-faculty')?.addEventListener('change', function() {
    populateDepartmentSelect('student-edit-dept', this.value);
  });

  // Modal close
  document.getElementById('student-edit-modal-close')?.addEventListener('click', () => {
    closeAdminModal('student-edit-modal');
  });

  // Save student
  document.getElementById('student-edit-save-btn')?.addEventListener('click', async () => {
    const id = document.getElementById('student-edit-id').value;
    const payload = {
      name: document.getElementById('student-edit-name').value,
      matric: document.getElementById('student-edit-matric').value,
      email: document.getElementById('student-edit-email').value,
      phone: document.getElementById('student-edit-phone').value,
      department_id: document.getElementById('student-edit-dept').value,
      level: document.getElementById('student-edit-level').value,
      gender: document.getElementById('student-edit-gender').value,
      dob: document.getElementById('student-edit-dob').value,
      state_of_origin: document.getElementById('student-edit-state').value,
      status: document.getElementById('student-edit-status').value,
    };
    
    const password = document.getElementById('student-edit-password').value;
    if (password) payload.password = password;

    const res = id 
      ? await API.admin.updateStudent(id, payload)
      : await API.admin.createStudent(payload);
    
    if (res.ok) {
      showToast('success', 'Saved', 'Student saved successfully.');
      closeAdminModal('student-edit-modal');
      loadStudents(currentFilter);
    } else {
      showToast('error', 'Error', res.data.message || 'Could not save student.');
    }
  });

  /* ── Section navigation ── */
  window.adminShowSection = function (sec) {
    document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.dash-nav-link[data-section]').forEach(l => l.classList.remove('active'));
    document.getElementById('section-' + sec)?.classList.add('active');
    document.querySelector(`.dash-nav-link[data-section="${sec}"]`)?.classList.add('active');
    setText('admin-page-title', {
      overview: 'Overview', students: 'All Students',
      approved: 'Approved Students', pending: 'Pending Review',
      rejected: 'Rejected', settings: 'Settings',
      courses: 'Course Management', lecturers: 'Lecturer Management',
      faculties: 'Faculty Management', departments: 'Department Management',
      levels: 'Academic Levels',
    }[sec] || sec);
    if (sec === 'overview') loadOverview();
    else if (['students','approved','pending','rejected'].includes(sec)) {
      loadStudents(sec);
      loadFacultiesAndDepartments();
    }
    else if (sec === 'courses') {
      loadCourses();
      loadDepartments();
    }
    else if (sec === 'lecturers') {
      loadLecturers();
      loadDepartments();
    }
    else if (sec === 'faculties') {
      loadFacultiesMgmt();
    }
    else if (sec === 'departments') {
      loadDepartmentsMgmt();
      loadFacultiesMgmt();
    }
    else if (sec === 'levels') {
      loadAcademicLevels();
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  document.querySelectorAll('.dash-nav-link[data-section]').forEach(l => {
    l.addEventListener('click', e => { e.preventDefault(); adminShowSection(l.dataset.section); });
  });

  /* ── Overview ── */
  async function loadOverview() {
    const res = await API.admin.stats();
    if (!res.ok) {
      // Session expired or revoked server-side — send the admin back to login.
      if (res.status === 401) { SessionStore.clear(); window.location.href = '/admin/login'; return; }
      showToast('error', 'Error', 'Could not load stats.'); 
      return; 
    }
    const s = res.data;
    setText('ov-total',    s.total    || 0);
    setText('ov-pending',  s.pending  || 0);
    setText('ov-approved', s.approved || 0);
    setText('ov-rejected', s.rejected || 0);
    setText('ov-verified', s.verified || 0);
    renderRecentTable(s.recent || []);
    renderDeptChart(s.by_dept || {});
    const pb = document.getElementById('nav-pending-badge');
    if (pb) pb.textContent = s.pending || 0;
  }

  function renderRecentTable(recent) {
    const tbody = document.getElementById('recent-tbody'); if (!tbody) return;
    if (!recent.length) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--t-muted);font-style:italic">No registrations yet.</td></tr>';
      return;
    }
    tbody.innerHTML = recent.map(u => `
      <tr onclick="openStudentModal('${u.id}')" class="tbl-row" style="cursor:pointer">
        <td><div style="font-weight:700;font-size:.875rem">${escHtml(u.name)}</div><div style="font-size:.72rem;color:var(--t-muted)">${escHtml(u.matric)}</div></td>
        <td>${escHtml(u.dept)}</td>
        <td>${escHtml(u.level)}</td>
        <td>${fmtDate(u.created_at)}</td>
        <td><span class="badge ${statusBadge(u.status)}">${u.status}</span></td>
      </tr>`).join('');
  }

  function renderDeptChart(byDept) {
    const wrap = document.getElementById('dept-chart'); if (!wrap) return;
    const entries = Object.entries(byDept).sort((a, b) => b[1] - a[1]).slice(0, 8);
    const max = entries[0]?.[1] || 1;
    wrap.innerHTML = entries.length
      ? entries.map(([dept, count]) => `
          <div style="display:flex;align-items:center;gap:var(--s3);margin-bottom:var(--s3)">
            <div style="font-size:.78rem;color:var(--t-secondary);min-width:160px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escHtml(dept)}</div>
            <div style="flex:1;background:var(--bdr-light);border-radius:999px;height:8px;overflow:hidden">
              <div style="height:100%;width:${Math.round((count/max)*100)}%;background:var(--brand);border-radius:999px;transition:width .6s ease"></div>
            </div>
            <div style="font-size:.78rem;font-weight:700;color:var(--brand);min-width:28px;text-align:right">${count}</div>
          </div>`).join('')
      : '<p style="color:var(--t-muted);font-size:.85rem;font-style:italic">No department data yet.</p>';
  }

  /* ── Student table ── */
  async function loadStudents(filter) {
    // Sidebar sections use lowercase ids ('pending'), tabs use capitalized ('Pending').
    const norm = { students: 'all', pending: 'Pending', approved: 'Approved', rejected: 'Rejected', all: 'all' }[filter] || filter;
    currentFilter = norm;
    const res = await API.admin.getStudents(norm !== 'all' ? { status: norm } : {});
    if (!res.ok) {
      if (res.status === 401) { SessionStore.clear(); window.location.href = '/admin/login'; return; }
      showToast('error', 'Error', 'Could not load students.'); 
      return; 
    }
    allStudents = res.data.students || [];
    renderTable();
  }

  function sectionTargets(filter) {
    // Each filtered section renders into its own tbody/search/count elements.
    const map = {
      all:      { tbody: 'students-tbody',  search: 'student-search',  count: 'table-count' },
      Pending:  { tbody: 'pending-tbody',   search: 'pending-search',  count: 'pending-count' },
      Approved: { tbody: 'approved-tbody',  search: 'approved-search', count: 'approved-count' },
      Rejected: { tbody: 'rejected-tbody',  search: 'rejected-search', count: 'rejected-count' },
    };
    return map[filter] || map.all;
  }

  function renderTable() {
    let data = [...allStudents];
    if (currentSearch) {
      const q = currentSearch.toLowerCase();
      data = data.filter(u =>
        u.name.toLowerCase().includes(q) ||
        u.matric.toLowerCase().includes(q) ||
        (u.dept || '').toLowerCase().includes(q) ||
        (u.faculty || '').toLowerCase().includes(q) ||
        (u.email || '').toLowerCase().includes(q)
      );
    }
    data.sort((a, b) => {
      let v = 0;
      if (sortBy === 'name')   v = a.name.localeCompare(b.name);
      if (sortBy === 'status') v = a.status.localeCompare(b.status);
      if (sortBy === 'date')   v = new Date(a.created_at || 0) - new Date(b.created_at || 0);
      return sortDir === 'asc' ? v : -v;
    });
    const t = sectionTargets(currentFilter);
    const tbody = document.getElementById(t.tbody); if (!tbody) return;
    const countEl = document.getElementById(t.count);
    if (countEl) countEl.textContent = `${data.length} student${data.length !== 1 ? 's' : ''}`;
    if (!data.length) {
      tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">${currentSearch ? `No results for "${escHtml(currentSearch)}"` : 'No students found.'}</td></tr>`;
      return;
    }
    tbody.innerHTML = data.map(u => `
      <tr onclick="openStudentModal('${u.id}')" class="tbl-row" style="cursor:pointer">
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--brand);color:white;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;flex-shrink:0">${escHtml(u.name[0])}</div>
            <div>
              <div style="font-weight:700;font-size:.875rem;color:var(--t-primary)">${escHtml(u.name)}</div>
              <div style="font-size:.72rem;color:var(--t-muted)">${escHtml(u.email || '')}</div>
            </div>
          </div>
        </td>
        <td style="font-size:.83rem;font-weight:600">${escHtml(u.matric)}</td>
        <td style="font-size:.83rem">${escHtml(u.faculty || '—')}</td>
        <td style="font-size:.83rem">${escHtml(u.dept)}</td>
        <td style="font-size:.83rem">${escHtml(u.level)}</td>
        <td><span class="badge ${u.verified ? 'badge-green' : 'badge-gold'}">${u.verified ? '✓ Yes' : '⏳ No'}</span></td>
        <td style="font-size:.82rem">${fmtDate(u.created_at)}</td>
        <td><span class="badge ${statusBadge(u.status)}">${escHtml(u.status)}</span></td>
      </tr>`).join('');
  }

  function statusBadge(s) { return { Pending: 'badge-gold', Approved: 'badge-green', Rejected: 'badge-red' }[s] || 'badge-gray'; }
  function escHtml(str) { return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  /* ── Search (per-section) ── */
  ['student-search', 'pending-search', 'approved-search', 'rejected-search'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', function () {
      currentSearch = this.value; renderTable();
    });
  });
  document.getElementById('header-search')?.addEventListener('input', function () {
    const t = sectionTargets(currentFilter);
    const ss = document.getElementById(t.search);
    if (ss) { ss.value = this.value; ss.dispatchEvent(new Event('input')); }
    if (this.value) adminShowSection(currentFilter === 'all' ? 'students' : currentFilter.toLowerCase());
  });

  /* ── Sort ── */
  document.querySelectorAll('[data-sort]').forEach(th => {
    th.style.cursor = 'pointer';
    th.addEventListener('click', () => {
      const col = th.dataset.sort;
      if (sortBy === col) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
      else { sortBy = col; sortDir = 'asc'; }
      document.querySelectorAll('[data-sort]').forEach(h => h.style.fontWeight = '');
      th.style.fontWeight = '800';
      renderTable();
    });
  });

  /* ── Filter tabs ── */
  document.querySelectorAll('[data-filter]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentSearch = '';
      const ss = document.getElementById('student-search'); if (ss) ss.value = '';
      loadStudents(btn.dataset.filter);
    });
  });

  /* ── Student modal ── */
  window.openStudentModal = async function (id) {
    selectedId = id;
    selectedStudent = null; // never approve against a stale student if the load fails
    const res = await API.admin.getStudent(id);
    if (!res.ok) { showToast('error', 'Error', 'Could not load student details.'); return; }
    const u = res.data.user || res.data;
    selectedStudent = u;
    const modal = document.getElementById('student-modal'); if (!modal) return;

    document.getElementById('m-avatar').textContent = escHtml(u.name[0]);
    document.getElementById('m-name').textContent    = escHtml(u.name);
    document.getElementById('m-matric').textContent  = escHtml(u.matric);
    document.getElementById('m-faculty').textContent = escHtml(u.faculty || '—');
    document.getElementById('m-dept').textContent    = escHtml(u.dept);
    document.getElementById('m-level').textContent   = escHtml(u.level);
    document.getElementById('m-email').textContent   = escHtml(u.email);
    document.getElementById('m-phone').textContent   = escHtml(u.phone || '—');
    document.getElementById('m-gender').textContent  = escHtml(u.gender || '—');
    document.getElementById('m-state').textContent   = escHtml(u.state_of_origin || '—');
    document.getElementById('m-submitted').textContent = fmtDate(u.created_at);
    document.getElementById('m-verified').textContent  = u.face_enrolled_at
      ? '✅ Enrolled (' + fmtDate(u.face_enrolled_at) + ')'
      : (u.verified ? '✅ Enrolled' : '⏳ Not enrolled');
    document.getElementById('m-docs').textContent      = (u.docs_count || 0) + ' file(s)';
    document.getElementById('m-id').textContent        = escHtml(String(u.id));
    const sb = document.getElementById('m-status-badge');
    if (sb) { sb.textContent = u.status; sb.className = 'badge badge-lg ' + statusBadge(u.status); }

    const approveBtn = document.getElementById('m-approve-btn');
    const rejectBtn  = document.getElementById('m-reject-btn');
    if (approveBtn) approveBtn.style.display = u.status === 'Approved'  ? 'none' : '';
    if (rejectBtn)  rejectBtn.style.display  = u.status === 'Rejected'  ? 'none' : '';

    updateFaceEnrollPanel(u);
    renderModalDocs(res.data.documents || []);

    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('open'), 10);
  };

  /* ── Face enrollment panel state ── */
  function updateFaceEnrollPanel(u) {
    const status = document.getElementById('fep-status');
    const photo  = document.getElementById('fep-photo');
    const enrollBtn = document.getElementById('fep-enroll-btn');
    if (status) {
      status.textContent = u.face_enrolled_at
        ? '✅ Face enrolled on ' + fmtDate(u.face_enrolled_at)
        : (u.verified ? '✅ Face enrolled' : '⏳ Not enrolled yet');
      status.className = 'fep-status ' + (u.face_enrolled_at || u.verified ? 'ok' : '');
    }
    if (photo) {
      if (u.face_photo) { photo.src = u.face_photo; photo.style.display = 'block'; }
      else photo.style.display = 'none';
    }
    if (enrollBtn) enrollBtn.disabled = true;
    stopCamera();
  }

  function renderModalDocs(docs) {
    const wrap = document.getElementById('m-doc-list'); if (!wrap) return;
    if (!docs.length) {
      wrap.innerHTML = '<p style="color:var(--t-muted);font-size:.82rem;font-style:italic">No documents uploaded.</p>';
      return;
    }
    wrap.innerHTML = docs.map(d => `
      <div class="md-item">
        <span class="md-item-icon">📄</span>
        <div class="md-item-info"><div class="md-item-name">${escHtml(d.label)}</div><div class="md-item-sub">${escHtml(d.original_name)} · ${(d.size/1024).toFixed(0)} KB</div></div>
        <a class="md-item-link" href="${d.url}" target="_blank" rel="noopener">View →</a>
      </div>`).join('');
  }

  /* ── Webcam face enrollment ── */
  let camStream = null;
  let capturedBlob = null;
  let capturedEmbedding = null; // 128-dim FaceNet signature from the capture
  let faceModelsReady = false;

  async function ensureFaceModels() {
    if (faceModelsReady) return true;
    try {
      await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('/assets/models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('/assets/models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('/assets/models'),
      ]);
      faceModelsReady = true;
      return true;
    } catch (e) {
      return false;
    }
  }

  async function computeEmbedding(canvas) {
    if (!(await ensureFaceModels())) return null;
    try {
      const det = await faceapi.detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }))
        .withFaceLandmarks()
        .withFaceDescriptor();
      return det ? Array.from(det.descriptor) : null;
    } catch (e) {
      return null;
    }
  }

  function stopCamera() {
    if (camStream) { camStream.getTracks().forEach(t => t.stop()); camStream = null; }
    const video = document.getElementById('fep-video');
    if (video) { video.srcObject = null; video.style.display = 'none'; }
    const ph = document.getElementById('fep-cam-ph'); if (ph) ph.style.display = 'flex';
    const startBtn = document.getElementById('fep-start-btn'); if (startBtn) { startBtn.disabled = false; startBtn.textContent = '🎥 Start Camera'; }
    const capBtn = document.getElementById('fep-capture-btn'); if (capBtn) capBtn.disabled = true;
    const canvas = document.getElementById('fep-canvas'); if (canvas) canvas.style.display = 'none';
  }

  document.getElementById('fep-start-btn')?.addEventListener('click', async () => {
    const video = document.getElementById('fep-video');
    const ph = document.getElementById('fep-cam-ph');
    try {
      camStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } });
      if (video) { video.srcObject = camStream; video.style.display = 'block'; }
      if (ph) ph.style.display = 'none';
      const startBtn = document.getElementById('fep-start-btn');
      if (startBtn) { startBtn.disabled = true; startBtn.textContent = '🔴 Camera Active'; }
      document.getElementById('fep-capture-btn')?.removeAttribute('disabled');
      showToast('info', 'Camera On', 'Position the student\'s face in the frame.');
    } catch (err) {
      showToast('error', 'Camera Unavailable', 'Allow webcam access in your browser, or connect a camera.');
    }
  });

  document.getElementById('fep-capture-btn')?.addEventListener('click', () => {
    const video = document.getElementById('fep-video');
    const canvas = document.getElementById('fep-canvas');
    if (!video || !canvas) return;
    const w = video.videoWidth || 640, h = video.videoHeight || 480;
    canvas.width = w; canvas.height = h;
    canvas.getContext('2d').drawImage(video, 0, 0, w, h);
    canvas.toBlob(async blob => {
      capturedBlob = blob;
      capturedEmbedding = null;
      canvas.style.display = 'block';
      document.getElementById('fep-enroll-btn')?.setAttribute('disabled', 'disabled');
      const capBtn = document.getElementById('fep-capture-btn');
      if (capBtn) capBtn.textContent = '⏳ Creating face signature…';
      // Compute the FaceNet identity signature from the captured frame — this
      // is what students' face scans are verified against at check-in.
      const embedding = await computeEmbedding(canvas);
      capturedEmbedding = embedding;
      if (capBtn) capBtn.textContent = '✅ Captured';
      if (embedding) {
        document.getElementById('fep-enroll-btn')?.removeAttribute('disabled');
        showToast('success', 'Face Detected', 'Identity signature created. Review the image, then enroll the face.');
      } else {
        showToast('error', 'No Face Detected', 'No face was found in the capture. Re-capture with the face clearly in the frame.');
      }
    }, 'image/jpeg', 0.92);
  });

  document.getElementById('fep-enroll-btn')?.addEventListener('click', async () => {
    if (!capturedBlob || !selectedId) return;
    if (!capturedEmbedding) {
      showToast('error', 'No Face Signature', 'Re-capture the student face so a FaceNet signature can be created — it is required for face-scan check-in.');
      return;
    }
    const btn = document.getElementById('fep-enroll-btn');
    btn.disabled = true; btn.textContent = '⏳ Enrolling…';
    const res = await API.admin.faceRegister(selectedId, capturedBlob, capturedEmbedding);
    btn.disabled = false; btn.textContent = '✅ Enroll Face';
    if (res.ok) {
      showToast('success', 'Face Enrolled', res.data.message || 'Facial registration recorded.');
      capturedBlob = null;
      stopCamera();
      refreshAll();
      // Re-open modal with fresh data
      openStudentModal(selectedId);
    } else {
      showToast('error', 'Enrollment Failed', res.data.message || 'Could not enroll face.');
    }
  });

  /* Stop camera when modal closes */
  window.closeStudentModal = function () {
    stopCamera();
    capturedBlob = null; capturedEmbedding = null; // never carry a face capture into the next student's modal
    const modal = document.getElementById('student-modal');
    modal?.classList.remove('open');
    setTimeout(() => { if (modal) modal.style.display = 'none'; selectedId = null; selectedStudent = null; }, 300);
  };

  document.getElementById('student-modal')?.addEventListener('click', function (e) {
    if (e.target === this) closeStudentModal();
  });
  document.getElementById('m-close-btn')?.addEventListener('click', closeStudentModal);

  /* Approve — webcam face enrollment is required before approval */
  document.getElementById('m-approve-btn')?.addEventListener('click', async () => {
    if (!selectedId) return;
    const btn = document.getElementById('m-approve-btn');
    const alreadyEnrolled = !!(selectedStudent && (selectedStudent.face_enrolled_at || selectedStudent.verified));

    // Face capture not available and not previously enrolled → require it
    if (!alreadyEnrolled && !capturedBlob) {
      showToast('warning', 'Face Enrollment Required',
        'Capture and enroll the student\'s face before approving. Starting the camera…');
      document.getElementById('fep-start-btn')?.click();
      return;
    }

    btn.disabled = true;
    btn.textContent = '⏳ Approving…';

    // Save the webcam capture first — records biometric verification + face photo
    if (!alreadyEnrolled && capturedBlob) {
      if (!capturedEmbedding) {
        showToast('error', 'No Face Signature', 'Capture the student face so a FaceNet signature can be created before approving.');
        btn.disabled = false; btn.textContent = '✅ Approve Registration';
        return;
      }
      const faceRes = await API.admin.faceRegister(selectedId, capturedBlob, capturedEmbedding);
      if (!faceRes.ok) {
        btn.disabled = false;
        btn.textContent = '✅ Approve Registration';
        showToast('error', 'Enrollment Failed', faceRes.data.message || 'Could not save the face capture.');
        return;
      }
      capturedBlob = null;
    }

    const res = await API.admin.updateStatus(selectedId, 'Approved');
    btn.disabled = false;
    btn.textContent = '✅ Approve Registration';
    if (res.ok) {
      showToast('success', 'Approved',
        alreadyEnrolled
          ? 'Student approved. Face verification already recorded.'
          : 'Student approved and face enrolled. Biometric status updated.');
      closeStudentModal(); refreshAll();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not approve.');
    }
  });

  /* Reject */
  document.getElementById('m-reject-btn')?.addEventListener('click', async () => {
    if (!selectedId) return;
    if (!confirm('Reject this registration?')) return;
    const res = await API.admin.updateStatus(selectedId, 'Rejected');
    if (res.ok) {
      showToast('warning', 'Rejected', '');
      closeStudentModal(); refreshAll();
    } else showToast('error', 'Error', res.data.message || 'Could not reject.');
  });

  /* Delete */
  document.getElementById('m-delete-btn')?.addEventListener('click', async () => {
    if (!selectedId) return;
    if (!confirm('PERMANENTLY DELETE this student? This cannot be undone.')) return;
    const res = await API.admin.deleteStudent(selectedId);
    if (res.ok) {
      showToast('error', 'Deleted', '');
      closeStudentModal(); refreshAll();
    } else showToast('error', 'Error', res.data.message || 'Could not delete.');
  });

  /* Export CSV */
  document.getElementById('export-csv-btn')?.addEventListener('click', async () => {
    showToast('info', 'Exporting…', '');
    const blob = await API.admin.exportCsv();
    if (!blob) { showToast('error', 'Export Failed', ''); return; }
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `sumas-students-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click(); URL.revokeObjectURL(url);
    showToast('success', 'Exported!', '');
  });

  /* Bulk approve */
  document.getElementById('bulk-approve-btn')?.addEventListener('click', async () => {
    const pending = allStudents.filter(u => u.status === 'Pending');
    if (!pending.length) { showToast('warning', 'None Pending', ''); return; }
    if (!confirm(`Approve all ${pending.length} pending registrations?`)) return;
    await Promise.all(pending.map(u => API.admin.updateStatus(u.id, 'Approved')));
    showToast('success', 'Bulk Approved', `${pending.length} registrations approved.`);
    refreshAll();
  });

  /* Settings — change admin password */
  document.getElementById('settings-save-btn')?.addEventListener('click', async () => {
    const current = document.getElementById('settings-current-pw')?.value || '';
    const nw  = document.getElementById('settings-new-pw')?.value || '';
    const cf  = document.getElementById('settings-confirm-pw')?.value || '';
    if (!current) { showToast('error', 'Required', 'Enter your current password.'); return; }
    if (nw.length < 8) { showToast('error', 'Weak Password', 'New password must be at least 8 characters.'); return; }
    if (nw !== cf) { showToast('error', 'Mismatch', 'New password and confirmation do not match.'); return; }
    const btn = document.getElementById('settings-save-btn');
    btn.disabled = true; btn.textContent = 'Saving…';
    const res = await API.admin.changePassword({ current_password: current, new_password: nw, new_password_confirmation: cf });
    btn.disabled = false; btn.textContent = 'Save Changes';
    if (res.ok) {
      showToast('success', 'Password Updated', 'Your admin password has been changed.');
      ['settings-current-pw','settings-new-pw','settings-confirm-pw'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
      });
    } else {
      showToast('error', 'Update Failed', res.data.message || res.data.errors?.new_password?.[0] || 'Could not change password.');
    }
  });

  /* Logout */
  document.querySelectorAll('[data-admin-logout]').forEach(el => el.addEventListener('click', async () => {
    await API.auth.adminLogout();
    SessionStore.clear();
    setTimeout(() => window.location.href = '/admin/login', 500);
  }));

  /* Dropdowns */
  setupDropdown('user-menu-btn', 'user-menu-dd');

  function refreshAll() {
    loadOverview();
    loadStudents(currentFilter);
  }

  /* ── Courses Management ── */
  let allCourses = [];
  allDepartments = [];

  async function loadDepartments() {
    const [deptRes, facRes] = await Promise.all([
      API.admin.departments(),
      API.get('/faculties')
    ]);
    if (deptRes.ok) {
      allDepartments = deptRes.data.departments;
      populateDepartmentSelects();
    }
    if (facRes.ok) {
      allFaculties = facRes.data.faculties;
      populateFacultySelects();
    }
  }

  function populateDepartmentSelects() {
    const selects = ['lecturer-dept'];
    selects.forEach(selectId => {
      const select = document.getElementById(selectId);
      if (select) {
        select.innerHTML = '<option value="">Select Department</option>';
        allDepartments.forEach(dept => {
          const option = document.createElement('option');
          option.value = dept.id;
          option.textContent = dept.name;
          select.appendChild(option);
        });
      }
    });
  }

  function populateFacultySelects() {
    const selects = ['course-faculty', 'lecturer-faculty'];
    selects.forEach(selectId => {
      const select = document.getElementById(selectId);
      if (select) {
        select.innerHTML = '<option value="">Select Faculty</option>';
        allFaculties.forEach(faculty => {
          const option = document.createElement('option');
          option.value = faculty.id;
          option.textContent = faculty.name;
          select.appendChild(option);
        });
      }
    });
  }

  function populateCourseDepartmentSelect(facultyId = null) {
    const select = document.getElementById('course-dept');
    if (!select) return;
    select.innerHTML = '<option value="">Select Department</option>';
    
    const filteredDepts = facultyId 
      ? allDepartments.filter(dept => dept.faculty_id === parseInt(facultyId))
      : allDepartments;
    
    filteredDepts.forEach(dept => {
      const option = document.createElement('option');
      option.value = dept.id;
      option.textContent = dept.name;
      select.appendChild(option);
    });
  }

  function populateLecturerDepartmentSelect(facultyId = null) {
    const select = document.getElementById('lecturer-dept');
    if (!select) return;
    select.innerHTML = '<option value="">Select Department</option>';
    
    const filteredDepts = facultyId 
      ? allDepartments.filter(dept => dept.faculty_id === parseInt(facultyId))
      : allDepartments;
    
    filteredDepts.forEach(dept => {
      const option = document.createElement('option');
      option.value = dept.id;
      option.textContent = dept.name;
      select.appendChild(option);
    });
  }

  async function ensureFacultiesAndDepartmentsLoaded() {
    if (allFaculties.length && allDepartments.length) return;
    await loadDepartments();
  }

  async function loadCourses() {
    const res = await API.admin.courses();
    if (res.ok) {
      allCourses = res.data.courses;
      renderCourses();
      document.getElementById('courses-count').textContent = `${allCourses.length} courses`;
    }
  }

  function renderCourses() {
    const tbody = document.getElementById('courses-tbody');
    if (!allCourses.length) {
      tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--t-muted)">No courses yet.</td></tr>';
      return;
    }
    tbody.innerHTML = allCourses.map(c => `
      <tr>
        <td><strong>${c.code}</strong></td>
        <td>${c.name}</td>
        <td>${c.faculty_name || '—'}</td>
        <td>${c.department_name || c.department || '—'}</td>
        <td>${c.level}</td>
        <td>${c.credit_units}</td>
        <td>${c.lecturers?.map(l => l.name).join(', ') || '—'}</td>
        <td><span class="badge ${c.is_active ? 'badge-green' : 'badge-gray'}">${c.is_active ? 'Active' : 'Inactive'}</span></td>
        <td>
          <button class="btn btn-sm btn-secondary" onclick="editCourse(${c.id})">Edit</button>
          <button class="btn btn-sm btn-primary" onclick="openAssignLecturer(${c.id})">Assign</button>
          <button class="btn btn-sm btn-danger" onclick="deleteCourse(${c.id})">Delete</button>
        </td>
      </tr>
    `).join('');
  }

  window.editCourse = function(id) {
    const course = allCourses.find(c => c.id === id);
    if (!course) return;
    document.getElementById('course-id').value = course.id;
    document.getElementById('course-code').value = course.code;
    document.getElementById('course-name').value = course.name;
    document.getElementById('course-faculty').value = course.faculty_id || '';
    populateCourseDepartmentSelect(course.faculty_id);
    document.getElementById('course-dept').value = course.department_id || course.department || '';
    document.getElementById('course-desc').value = course.description || '';
    document.getElementById('course-credits').value = course.credit_units;
    document.getElementById('course-level').value = course.level;
    document.getElementById('course-active').value = course.is_active ? 'true' : 'false';
    document.getElementById('course-modal-title').textContent = 'Edit Course';
    openAdminModal('course-modal');
  };

  window.deleteCourse = async function(id) {
    if (!confirm('Delete this course?')) return;
    const res = await API.admin.deleteCourse(id);
    if (res.ok) {
      showToast('success', 'Deleted', 'Course deleted successfully.');
      loadCourses();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not delete course.');
    }
  };

  window.openAssignLecturer = async function(courseId) {
    document.getElementById('assign-course-id').value = courseId;
    const res = await API.admin.lecturers();
    if (res.ok) {
      const select = document.getElementById('assign-lecturer-select');
      select.innerHTML = '<option value="">-- Select lecturer --</option>' +
        res.data.lecturers.map(l => `<option value="${l.id}">${l.name} (${l.department})</option>`).join('');
    }
    openAdminModal('assign-lecturer-modal');
  };

  document.getElementById('create-course-btn')?.addEventListener('click', () => {
    document.getElementById('course-id').value = '';
    document.getElementById('course-code').value = '';
    document.getElementById('course-name').value = '';
    document.getElementById('course-dept').value = '';
    document.getElementById('course-desc').value = '';
    document.getElementById('course-credits').value = 3;
    document.getElementById('course-level').value = '100';
    document.getElementById('course-active').value = 'true';
    document.getElementById('course-modal-title').textContent = 'Create Course';
    openAdminModal('course-modal');
  });

  document.getElementById('course-modal-close')?.addEventListener('click', () => {
    closeAdminModal('course-modal');
  });

  document.getElementById('course-faculty')?.addEventListener('change', function() {
    populateCourseDepartmentSelect(this.value);
  });

  document.getElementById('lecturer-faculty')?.addEventListener('change', function() {
    populateLecturerDepartmentSelect(this.value);
  });

  document.getElementById('course-save-btn')?.addEventListener('click', async () => {
    const id = document.getElementById('course-id').value;
    const payload = {
      code: document.getElementById('course-code').value,
      name: document.getElementById('course-name').value,
      department_id: parseInt(document.getElementById('course-dept').value),
      description: document.getElementById('course-desc').value,
      credit_units: parseInt(document.getElementById('course-credits').value),
      level: parseInt(document.getElementById('course-level').value),
      is_active: document.getElementById('course-active').value === 'true',
    };

    const res = id ? await API.admin.updateCourse(id, payload) : await API.admin.createCourse(payload);
    if (res.ok) {
      showToast('success', 'Saved', 'Course saved successfully.');
      closeAdminModal('course-modal');
      loadCourses();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not save course.');
    }
  });

  document.getElementById('assign-lecturer-close')?.addEventListener('click', () => {
    closeAdminModal('assign-lecturer-modal');
  });

  document.getElementById('assign-lecturer-btn')?.addEventListener('click', async () => {
    const courseId = document.getElementById('assign-course-id').value;
    const lecturerId = document.getElementById('assign-lecturer-select').value;
    if (!lecturerId) {
      showToast('error', 'Required', 'Please select a lecturer.');
      return;
    }
    const res = await API.admin.assignLecturer(courseId, { lecturer_id: parseInt(lecturerId) });
    if (res.ok) {
      showToast('success', 'Assigned', 'Lecturer assigned successfully.');
      closeAdminModal('assign-lecturer-modal');
      loadCourses();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not assign lecturer.');
    }
  });

  /* ── Lecturers Management ── */
  let allLecturers = [];

  async function loadLecturers() {
    const res = await API.admin.lecturers();
    if (res.ok) {
      allLecturers = res.data.lecturers;
      renderLecturers();
      document.getElementById('lecturers-count').textContent = `${allLecturers.length} lecturers`;
    }
  }

  function renderLecturers() {
    const tbody = document.getElementById('lecturers-tbody');
    if (!allLecturers.length) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--t-muted)">No lecturers yet.</td></tr>';
      return;
    }
    tbody.innerHTML = allLecturers.map(l => `
      <tr>
        <td><strong>${l.name}</strong></td>
        <td>${l.email}</td>
        <td>${l.faculty_name || '—'}</td>
        <td>${l.department_name || l.department || '—'}</td>
        <td>${l.phone || '—'}</td>
        <td>${l.courses_count}</td>
        <td><span class="badge ${l.is_active ? 'badge-green' : 'badge-gray'}">${l.is_active ? 'Active' : 'Inactive'}</span></td>
        <td>
          <button class="btn btn-sm btn-secondary" onclick="editLecturer(${l.id})">Edit</button>
          <button class="btn btn-sm btn-danger" onclick="deleteLecturer(${l.id})">Delete</button>
        </td>
      </tr>
    `).join('');
  }

  window.editLecturer = async function(id) {
    const lecturer = allLecturers.find(l => l.id === id);
    if (!lecturer) return;
    await ensureFacultiesAndDepartmentsLoaded();
    populateFacultySelects();
    document.getElementById('lecturer-id').value = lecturer.id;
    document.getElementById('lecturer-name').value = lecturer.name;
    document.getElementById('lecturer-email').value = lecturer.email;
    document.getElementById('lecturer-password').value = '';
    document.getElementById('lecturer-phone').value = lecturer.phone || '';
    document.getElementById('lecturer-faculty').value = lecturer.faculty_id || '';
    populateLecturerDepartmentSelect(lecturer.faculty_id);
    document.getElementById('lecturer-dept').value = lecturer.department_id || '';
    document.getElementById('lecturer-bio').value = lecturer.bio || '';
    document.getElementById('lecturer-active').value = lecturer.is_active ? 'true' : 'false';
    document.getElementById('lecturer-modal-title').textContent = 'Edit Lecturer';
    openAdminModal('lecturer-modal');
  };

  window.deleteLecturer = async function(id) {
    if (!confirm('Delete this lecturer?')) return;
    const res = await API.admin.deleteLecturer(id);
    if (res.ok) {
      showToast('success', 'Deleted', 'Lecturer deleted successfully.');
      loadLecturers();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not delete lecturer.');
    }
  };

  document.getElementById('create-lecturer-btn')?.addEventListener('click', async () => {
    document.getElementById('lecturer-id').value = '';
    document.getElementById('lecturer-name').value = '';
    document.getElementById('lecturer-email').value = '';
    document.getElementById('lecturer-password').value = '';
    document.getElementById('lecturer-phone').value = '';
    document.getElementById('lecturer-faculty').value = '';
    document.getElementById('lecturer-dept').value = '';
    document.getElementById('lecturer-bio').value = '';
    document.getElementById('lecturer-active').value = 'true';
    document.getElementById('lecturer-modal-title').textContent = 'Create Lecturer';
    await ensureFacultiesAndDepartmentsLoaded();
    populateFacultySelects();
    populateDepartmentSelects();
    openAdminModal('lecturer-modal');
  });

  document.getElementById('lecturer-modal-close')?.addEventListener('click', () => {
    closeAdminModal('lecturer-modal');
  });

  document.getElementById('lecturer-save-btn')?.addEventListener('click', async () => {
    const id = document.getElementById('lecturer-id').value;
    const payload = {
      name: document.getElementById('lecturer-name').value,
      email: document.getElementById('lecturer-email').value,
      phone: document.getElementById('lecturer-phone').value,
      department_id: parseInt(document.getElementById('lecturer-dept').value),
      bio: document.getElementById('lecturer-bio').value,
      is_active: document.getElementById('lecturer-active').value === 'true',
    };

    if (!id) {
      payload.password = document.getElementById('lecturer-password').value;
      if (!payload.password || payload.password.length < 8) {
        showToast('error', 'Required', 'Password must be at least 8 characters.');
        return;
      }
    }

    const res = id ? await API.admin.updateLecturer(id, payload) : await API.admin.createLecturer(payload);
    if (res.ok) {
      showToast('success', 'Saved', 'Lecturer saved successfully.');
      closeAdminModal('lecturer-modal');
      loadLecturers();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not save lecturer.');
    }
  });

  /* ── Faculties Management ── */
  let allFacultiesMgmt = [];

  async function loadFacultiesMgmt() {
    const res = await API.admin.faculties();
    if (res.ok) {
      allFacultiesMgmt = res.data.faculties || [];
      renderFacultiesMgmt();
      const c = document.getElementById('faculties-count');
      if (c) c.textContent = `${allFacultiesMgmt.length} faculty${allFacultiesMgmt.length !== 1 ? 'ies' : 'y'}`;
    }
  }

  function renderFacultiesMgmt() {
    const tbody = document.getElementById('faculties-tbody');
    if (!tbody) return;
    if (!allFacultiesMgmt.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">No faculties yet.</td></tr>';
      return;
    }
    tbody.innerHTML = allFacultiesMgmt.map(f => `
      <tr>
        <td><strong>${escHtml(f.name)}</strong></td>
        <td><span class="badge badge-gray">${escHtml(f.code)}</span></td>
        <td style="font-size:.83rem;max-width:280px">${escHtml(f.description || '—')}</td>
        <td style="text-align:center">${f.departments_count ?? 0}</td>
        <td><span class="badge ${f.is_active ? 'badge-green' : 'badge-gray'}">${f.is_active ? 'Active' : 'Inactive'}</span></td>
        <td style="white-space:nowrap">
          <button class="btn btn-sm btn-secondary" onclick="editFaculty(${f.id})">Edit</button>
          <button class="btn btn-sm btn-danger" onclick="deleteFaculty(${f.id})">Delete</button>
        </td>
      </tr>`).join('');
  }

  window.editFaculty = function (id) {
    const f = allFacultiesMgmt.find(x => x.id === id);
    if (!f) return;
    document.getElementById('faculty-id').value = f.id;
    document.getElementById('faculty-name').value = f.name;
    document.getElementById('faculty-code').value = f.code;
    document.getElementById('faculty-desc').value = f.description || '';
    document.getElementById('faculty-active').value = f.is_active ? 'true' : 'false';
    document.getElementById('faculty-modal-title').textContent = 'Edit Faculty';
    openAdminModal('faculty-modal');
  };

  window.deleteFaculty = async function (id) {
    if (!confirm('Delete this faculty? Departments referencing it will be detached.')) return;
    const res = await API.admin.deleteFaculty(id);
    if (res.ok) {
      showToast('success', 'Deleted', 'Faculty deleted successfully.');
      loadFacultiesMgmt();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not delete faculty.');
    }
  };

  document.getElementById('create-faculty-btn')?.addEventListener('click', () => {
    document.getElementById('faculty-id').value = '';
    document.getElementById('faculty-name').value = '';
    document.getElementById('faculty-code').value = '';
    document.getElementById('faculty-desc').value = '';
    document.getElementById('faculty-active').value = 'true';
    document.getElementById('faculty-modal-title').textContent = 'Create Faculty';
    openAdminModal('faculty-modal');
  });

  document.getElementById('faculty-modal-close')?.addEventListener('click', () => {
    closeAdminModal('faculty-modal');
  });

  document.getElementById('faculty-save-btn')?.addEventListener('click', async () => {
    const id = document.getElementById('faculty-id').value;
    const payload = {
      name: document.getElementById('faculty-name').value,
      code: document.getElementById('faculty-code').value,
      description: document.getElementById('faculty-desc').value,
      is_active: document.getElementById('faculty-active').value === 'true',
    };
    const res = id
      ? await API.admin.updateFaculty(id, payload)
      : await API.admin.createFaculty(payload);
    if (res.ok) {
      showToast('success', 'Saved', 'Faculty saved successfully.');
      closeAdminModal('faculty-modal');
      loadFacultiesMgmt();
      if (document.getElementById('admin-page-title').textContent.includes('Department')) loadDepartmentsMgmt();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not save faculty.');
    }
  });

  /* ── Departments Management ── */
  let allDepartmentsMgmt = [];

  async function loadDepartmentsMgmt() {
    const res = await API.admin.allDepartments();
    if (res.ok) {
      allDepartmentsMgmt = res.data.departments || [];
      renderDepartmentsMgmt();
      const c = document.getElementById('departments-count');
      if (c) c.textContent = `${allDepartmentsMgmt.length} departments`;
    }
  }

  function populateMgmtFacultySelect(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;
    select.innerHTML = '<option value="">Select Faculty</option>';
    (allFacultiesMgmt.length ? allFacultiesMgmt : allFaculties).forEach(f => {
      const option = document.createElement('option');
      option.value = f.id;
      option.textContent = f.name;
      select.appendChild(option);
    });
  }

  function renderDepartmentsMgmt() {
    const tbody = document.getElementById('departments-tbody');
    if (!tbody) return;
    if (!allDepartmentsMgmt.length) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">No departments yet.</td></tr>';
      return;
    }
    tbody.innerHTML = allDepartmentsMgmt.map(d => `
      <tr>
        <td><strong>${escHtml(d.name)}</strong></td>
        <td><span class="badge badge-gray">${escHtml(d.code)}</span></td>
        <td style="font-size:.83rem">${escHtml(d.faculty_name || '—')}</td>
        <td style="text-align:center">${d.courses_count ?? 0}</td>
        <td style="text-align:center">${d.lecturers_count ?? 0}</td>
        <td><span class="badge ${d.is_active ? 'badge-green' : 'badge-gray'}">${d.is_active ? 'Active' : 'Inactive'}</span></td>
        <td style="white-space:nowrap">
          <button class="btn btn-sm btn-secondary" onclick="editDepartment(${d.id})">Edit</button>
          <button class="btn btn-sm btn-danger" onclick="deleteDepartment(${d.id})">Delete</button>
        </td>
      </tr>`).join('');
  }

  window.editDepartment = function (id) {
    const d = allDepartmentsMgmt.find(x => x.id === id);
    if (!d) return;
    document.getElementById('department-id').value = d.id;
    document.getElementById('department-name').value = d.name;
    document.getElementById('department-code').value = d.code;
    populateMgmtFacultySelect('department-faculty');
    document.getElementById('department-faculty').value = d.faculty_id || '';
    document.getElementById('department-desc').value = d.description || '';
    document.getElementById('department-active').value = d.is_active ? 'true' : 'false';
    document.getElementById('department-modal-title').textContent = 'Edit Department';
    openAdminModal('department-modal');
  };

  window.deleteDepartment = async function (id) {
    if (!confirm('Delete this department? Lecturers and courses referencing it will be detached.')) return;
    const res = await API.admin.deleteDepartment(id);
    if (res.ok) {
      showToast('success', 'Deleted', 'Department deleted successfully.');
      loadDepartmentsMgmt();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not delete department.');
    }
  };

  document.getElementById('create-department-btn')?.addEventListener('click', () => {
    document.getElementById('department-id').value = '';
    document.getElementById('department-name').value = '';
    document.getElementById('department-code').value = '';
    populateMgmtFacultySelect('department-faculty');
    document.getElementById('department-faculty').value = '';
    document.getElementById('department-desc').value = '';
    document.getElementById('department-active').value = 'true';
    document.getElementById('department-modal-title').textContent = 'Create Department';
    openAdminModal('department-modal');
  });

  document.getElementById('department-modal-close')?.addEventListener('click', () => {
    closeAdminModal('department-modal');
  });

  document.getElementById('department-save-btn')?.addEventListener('click', async () => {
    const id = document.getElementById('department-id').value;
    const payload = {
      name: document.getElementById('department-name').value,
      code: document.getElementById('department-code').value,
      faculty_id: parseInt(document.getElementById('department-faculty').value) || null,
      description: document.getElementById('department-desc').value,
      is_active: document.getElementById('department-active').value === 'true',
    };
    if (!payload.faculty_id) {
      showToast('error', 'Required', 'Please select a faculty.');
      return;
    }
    const res = id
      ? await API.admin.updateDepartment(id, payload)
      : await API.admin.createDepartment(payload);
    if (res.ok) {
      showToast('success', 'Saved', 'Department saved successfully.');
      closeAdminModal('department-modal');
      loadDepartmentsMgmt();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not save department.');
    }
  });

  /* ── Academic Levels Management ── */
  let allLevels = [];

  async function loadAcademicLevels() {
    const res = await API.admin.academicLevels();
    if (res.ok) {
      allLevels = res.data.levels || [];
      renderLevels();
      const c = document.getElementById('levels-count');
      if (c) c.textContent = `${allLevels.length} levels`;
    }
  }

  function renderLevels() {
    const tbody = document.getElementById('levels-tbody');
    if (!tbody) return;
    if (!allLevels.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">No academic levels yet.</td></tr>';
      return;
    }
    tbody.innerHTML = allLevels.map(lv => `
      <tr>
        <td><strong>${escHtml(lv.name)}</strong></td>
        <td><span class="badge badge-gray">${escHtml(lv.code)}</span></td>
        <td style="text-align:center">${lv.sort_order ?? 0}</td>
        <td style="font-size:.83rem;max-width:280px">${escHtml(lv.description || '—')}</td>
        <td><span class="badge ${lv.is_active ? 'badge-green' : 'badge-gray'}">${lv.is_active ? 'Active' : 'Inactive'}</span></td>
        <td style="white-space:nowrap">
          <button class="btn btn-sm btn-secondary" onclick="editLevel(${lv.id})">Edit</button>
          <button class="btn btn-sm btn-danger" onclick="deleteLevel(${lv.id})">Delete</button>
        </td>
      </tr>`).join('');
  }

  window.editLevel = function (id) {
    const lv = allLevels.find(x => x.id === id);
    if (!lv) return;
    document.getElementById('level-id').value = lv.id;
    document.getElementById('level-name').value = lv.name;
    document.getElementById('level-code').value = lv.code;
    document.getElementById('level-order').value = lv.sort_order ?? 0;
    document.getElementById('level-desc').value = lv.description || '';
    document.getElementById('level-active').value = lv.is_active ? 'true' : 'false';
    document.getElementById('level-modal-title').textContent = 'Edit Level';
    openAdminModal('level-modal');
  };

  window.deleteLevel = async function (id) {
    if (!confirm('Delete this academic level?')) return;
    const res = await API.admin.deleteAcademicLevel(id);
    if (res.ok) {
      showToast('success', 'Deleted', 'Academic level deleted successfully.');
      loadAcademicLevels();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not delete academic level.');
    }
  };

  document.getElementById('create-level-btn')?.addEventListener('click', () => {
    document.getElementById('level-id').value = '';
    document.getElementById('level-name').value = '';
    document.getElementById('level-code').value = '';
    document.getElementById('level-order').value = 0;
    document.getElementById('level-desc').value = '';
    document.getElementById('level-active').value = 'true';
    document.getElementById('level-modal-title').textContent = 'Create Level';
    openAdminModal('level-modal');
  });

  document.getElementById('level-modal-close')?.addEventListener('click', () => {
    closeAdminModal('level-modal');
  });

  document.getElementById('level-save-btn')?.addEventListener('click', async () => {
    const id = document.getElementById('level-id').value;
    const payload = {
      name: document.getElementById('level-name').value,
      code: document.getElementById('level-code').value,
      sort_order: parseInt(document.getElementById('level-order').value) || 0,
      description: document.getElementById('level-desc').value,
      is_active: document.getElementById('level-active').value === 'true',
    };
    const res = id
      ? await API.admin.updateAcademicLevel(id, payload)
      : await API.admin.createAcademicLevel(payload);
    if (res.ok) {
      showToast('success', 'Saved', 'Academic level saved successfully.');
      closeAdminModal('level-modal');
      loadAcademicLevels();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not save academic level.');
    }
  });

  /* ── Init — verify the session on reload, then load the dashboard ── */
  (async function init() {
    if (!(await SessionStore.verify('admin', '/admin/login'))) return;
    adminShowSection('overview');
  })();
}
