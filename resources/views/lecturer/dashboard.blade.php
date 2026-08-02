<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"/>
  <meta name="theme-color" content="#6B3318"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Lecturer Dashboard — SUMAS SmartAttend</title>
  <link rel="stylesheet" href="{{ asset('assets/css/design-system.css') }}"/>
  <link rel="stylesheet" href="{{ asset('assets/css/pages.css') }}"/>
  <style>
    /* clip (not hidden) so the sticky sidebar keeps sticking while clipping horizontal overflow */
    #lecturer-dashboard { overflow-x: clip; }
    .dash-dropdown.open { display: block !important; }
    .dash-sidebar { position: sticky; top: 0; height: 100vh; overflow-y: auto; }
    @media(max-width:900px){ .dash-header{ position:sticky; top:52px!important; z-index:50; } }
    @media(max-width:640px){ .dash-header{ top:44px!important; } }
  </style>
</head>
<body id="lecturer-dashboard">

<div id="page-loader"><div class="ldr-wrap">
  <div class="ldr-logo-row">
    <div class="ldr-crest"><img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS"/></div>
    <div class="ldr-text-col">
      <div class="ldr-uni">SUMAS SmartAttend</div>
      <div class="ldr-name">Lecturer <em>Panel</em></div>
      <div class="ldr-tag">Loading dashboard…</div>
    </div>
  </div>
  <div class="ldr-divider"></div>
  <div class="ldr-bar-wrap"><div class="ldr-bar-track"><div class="ldr-bar-fill"></div></div></div>
  <div class="ldr-dots"><div class="ldr-dot"></div><div class="ldr-dot"></div><div class="ldr-dot"></div></div>
</div></div>
<div id="toast-container"></div>

<div class="dash-root lecturer-dash">

  <!-- ══ SIDEBAR ══ -->
  <aside class="dash-sidebar" role="navigation" aria-label="Dashboard navigation">
    <div class="dash-sb-brand">
      <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS" class="dash-sb-logo"/>
      <div><div class="dash-sb-name">Lecturer Panel</div><div class="dash-sb-sub">SmartAttend</div></div>
    </div>
    <nav class="dash-sb-nav">
      <div class="dash-sb-section">Main</div>
      <a class="dash-nav-link active" data-section="overview" href="#"><span class="dash-nav-icon">📊</span> Overview</a>
      <a class="dash-nav-link" data-section="students" href="#"><span class="dash-nav-icon">👨‍🎓</span> Students</a>
      <a class="dash-nav-link" data-section="courses" href="#"><span class="dash-nav-icon">📚</span> My Courses</a>
      <a class="dash-nav-link" data-section="lectures" href="#"><span class="dash-nav-icon">📝</span> Lectures</a>
      <a class="dash-nav-link" data-section="attendance" href="#"><span class="dash-nav-icon">✅</span> Attendance</a>
      <div class="dash-sb-section">Account</div>
      <a class="dash-nav-link" href="#" data-logout><span class="dash-nav-icon">🔓</span> Sign Out</a>
    </nav>
    <div class="dash-sb-footer">
      <div class="dash-sb-user">
        <div class="dash-sb-avatar" data-avatar>L</div>
        <div><div class="dash-sb-user-name" data-user-name>Lecturer</div><div class="dash-sb-user-role">Faculty Member</div></div>
      </div>
    </div>
  </aside>

  <!-- ══ MAIN ══ -->
  <div class="dash-main">

    <!-- Header -->
    <header class="dash-header">
      <div class="dash-header-logo-row">
        <a href="{{ route('lecturer.dashboard') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none">
          <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS" class="dash-header-logo"/>
          <div>
            <div class="dash-header-page-name" id="dash-page-title-text">Overview</div>
            <div style="font-size:.6rem;color:var(--t-light);letter-spacing:.5px">SUMAS Faculty Console</div>
          </div>
        </a>
      </div>
      <div class="dash-header-right">
        <button class="dash-hdr-btn" id="theme-toggle" aria-label="Toggle theme">🌙</button>
        <div style="position:relative">
          <div class="dash-hdr-avatar" id="user-menu-btn" style="cursor:pointer" data-avatar role="button" tabindex="0" aria-haspopup="true">L</div>
          <div class="dash-dropdown" id="user-menu-dd" style="display:none">
            <div class="dd-head" data-user-name>Lecturer</div>
            <div class="dd-divider"></div>
            <div class="dd-item danger" data-logout><span>🔓</span> Sign Out</div>
          </div>
        </div>
      </div>
    </header>

    <!-- Content -->
    <div class="dash-content">

      <!-- ════ OVERVIEW ════ -->
      <section class="dash-section active" id="section-overview">
        <div class="wb">
          <div class="wb-bg"></div><div class="wb-glow"></div>
          <div class="wb-inner">
            <div class="wb-left">
              <div class="wb-eyebrow">SUMAS SmartAttend · Faculty Console</div>
              <h2 class="wb-title">Welcome back, <em data-user-name>Lecturer</em> 👋</h2>
              <p class="wb-sub">Manage your courses, students, and take attendance.</p>
            </div>
            <div class="wb-right">
              <div class="wb-stat"><div class="wb-stat-icon">📚</div><div class="wb-stat-val" id="stat-courses">0</div><div class="wb-stat-lbl">My Courses</div></div>
              <div class="wb-stat"><div class="wb-stat-icon">👨‍🎓</div><div class="wb-stat-val" id="stat-students">0</div><div class="wb-stat-lbl">Approved Students</div></div>
              <div class="wb-stat"><div class="wb-stat-icon">📝</div><div class="wb-stat-val" id="stat-lectures">0</div><div class="wb-stat-lbl">Recent Lectures</div></div>
            </div>
          </div>
        </div>

        <div class="metric-grid" style="margin-bottom:var(--s6)">
          <div class="metric-card mc-brand"><div class="metric-card-icon">📚</div><div class="metric-card-val" id="mc-courses">0</div><div class="metric-card-lbl">Assigned Courses</div></div>
          <div class="metric-card mc-green"><div class="metric-card-icon">👨‍🎓</div><div class="metric-card-val" id="mc-students">0</div><div class="metric-card-lbl">Approved Students</div></div>
          <div class="metric-card mc-gold"><div class="metric-card-icon">📝</div><div class="metric-card-val" id="mc-lectures">0</div><div class="metric-card-lbl">Total Lectures</div></div>
          <div class="metric-card mc-gray"><div class="metric-card-icon">✅</div><div class="metric-card-val" id="mc-active">0</div><div class="metric-card-lbl">Active Lectures</div></div>
        </div>

        <div class="dash-grid-2">
          <div class="widget">
            <div class="widget-head">
              <div class="widget-head-left"><div class="widget-head-icon">🕒</div><span class="widget-title">Recent Lectures</span></div>
              <span class="widget-action" onclick="showSection('lectures')" style="cursor:pointer">View all →</span>
            </div>
            <div style="overflow-x:auto">
              <table class="admin-table">
                <thead><tr><th>Lecture</th><th>Scheduled</th><th>Status</th></tr></thead>
                <tbody id="recent-lectures-tbody"><tr><td colspan="3" style="text-align:center;padding:24px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
              </table>
            </div>
          </div>
          <div class="widget">
            <div class="widget-head">
              <div class="widget-head-left"><div class="widget-head-icon">📚</div><span class="widget-title">My Courses</span></div>
              <span class="widget-action" onclick="showSection('courses')" style="cursor:pointer">View all →</span>
            </div>
            <div style="overflow-x:auto">
              <table class="admin-table">
                <thead><tr><th>Code</th><th>Course</th><th>Level</th></tr></thead>
                <tbody id="overview-courses-tbody"><tr><td colspan="3" style="text-align:center;padding:24px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- ════ STUDENTS ════ -->
      <section class="dash-section" id="section-students">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">Students</div><div class="dash-page-title-sub">Approved students in your department</div></div>
        </div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <div class="admin-table-search"><input type="text" id="student-search" placeholder="Search name, matric, email…"/></div>
            <span style="font-size:.78rem;color:var(--t-muted)" id="students-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Student</th><th>Matric</th><th>Department</th><th>Level</th><th>Verified</th><th>Status</th></tr></thead>
              <tbody id="students-tbody"><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ MY COURSES ════ -->
      <section class="dash-section" id="section-courses">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">My Courses</div><div class="dash-page-title-sub">Courses assigned to you</div></div>
        </div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <span style="font-size:.78rem;color:var(--t-muted)" id="courses-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Code</th><th>Course</th><th>Department</th><th>Level</th><th>Credits</th><th>Status</th></tr></thead>
              <tbody id="courses-tbody"><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ LECTURES ════ -->
      <section class="dash-section" id="section-lectures">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">Lectures &amp; Announcements</div><div class="dash-page-title-sub">Create lectures and end them to record attendance</div></div>
          <button class="btn btn-primary btn-md" id="create-lecture-btn">+ Create Lecture</button>
        </div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <span style="font-size:.78rem;color:var(--t-muted)" id="lectures-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Lecture</th><th>Course</th><th>Scheduled</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="lectures-tbody"><tr><td colspan="5" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ ATTENDANCE ════ -->
      <section class="dash-section" id="section-attendance">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">Attendance Management</div><div class="dash-page-title-sub">Review attendance records for a lecture</div></div>
        </div>
        <div class="widget" style="margin-bottom:var(--s5)">
          <div class="widget-head"><div class="widget-head-left"><div class="widget-head-icon">🗓</div><span class="widget-title">Select a Lecture</span></div></div>
          <div class="widget-body">
            <select id="attendance-lecture-select" class="input">
              <option value="">-- Select a lecture --</option>
            </select>
          </div>
        </div>
        <div class="admin-table-wrap">
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Student</th><th>Matric</th><th>Status</th><th>Notes</th></tr></thead>
              <tbody id="attendance-tbody"><tr><td colspan="4" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Select a lecture to view attendance…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

    </div><!-- /dash-content -->
  </div><!-- /dash-main -->
</div><!-- /dash-root -->

<!-- CREATE LECTURE MODAL -->
<div class="student-modal" id="lecture-modal" style="display:none">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-name">Create New Lecture</div>
      <button class="modal-close" id="close-lecture-modal">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Course</label>
        <select id="lecture-course" class="input" required>
          <option value="">-- Select course --</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Title</label>
        <input type="text" id="lecture-title" class="input" placeholder="Lecture title" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Content</label>
        <textarea id="lecture-content" class="input" rows="5" placeholder="Lecture content or announcement" required></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Scheduled Date</label>
        <input type="datetime-local" id="lecture-date" class="input" required/>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-secondary" id="cancel-lecture">Cancel</button>
      <button class="btn btn-primary" id="save-lecture">Create Lecture</button>
    </div>
  </div>
</div>

<script src="{{ asset('assets/js/api.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
<script>
/* ── Lecturer Dashboard ── */
if (document.getElementById('lecturer-dashboard')) {
  SessionStore.requireLecturer('/lecturer/login');

  const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || '—'; };
  function escHtml(str) { return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function statusBadge(s) { return { Pending: 'badge-gold', Approved: 'badge-green', Rejected: 'badge-red' }[s] || 'badge-gray'; }

  let allStudents = [];
  let allCourses  = [];
  let allLectures = [];
  let courseMap   = {};

  function courseLabel(id) { return courseMap[id] ? `${courseMap[id].code} — ${courseMap[id].name}` : '—'; }

  /* ── Dashboard data ── */
  async function loadDashboard() {
    const res = await API.lecturer.dashboard();
    if (!res.ok) {
      if (res.status === 401 || res.status === 403) {
        SessionStore.clear();
        window.location.href = '/lecturer/login';
        return;
      }
      showToast('error', 'Load Error', res.data.message || 'Could not load dashboard data.');
      return;
    }
    const data = res.data;
    const first = (data.lecturer.name || 'Lecturer').split(' ')[0];
    const init  = first[0]?.toUpperCase() || 'L';
    document.querySelectorAll('[data-avatar]').forEach(el => el.textContent = init);
    document.querySelectorAll('[data-user-name]').forEach(el => el.textContent = data.lecturer.name || 'Lecturer');

    setText('stat-courses', data.courses_count);
    setText('stat-students', data.students_count);
    setText('stat-lectures', data.recent_lectures?.length || 0);
    setText('mc-courses', data.courses_count);
    setText('mc-students', data.students_count);
    setText('mc-lectures', data.recent_lectures?.length || 0);
    setText('mc-active', (data.recent_lectures || []).filter(l => l.is_active).length);

    renderRecentLectures(data.recent_lectures || []);
  }

  function renderRecentLectures(lectures) {
    const tbody = document.getElementById('recent-lectures-tbody');
    if (!tbody) return;
    if (!lectures.length) {
      tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:24px;color:var(--t-muted);font-style:italic">No lectures yet.</td></tr>';
      return;
    }
    tbody.innerHTML = lectures.map(l => `
      <tr>
        <td><div style="font-weight:600;font-size:.85rem">${escHtml(l.title)}</div></td>
        <td style="font-size:.8rem">${new Date(l.scheduled_date).toLocaleString()}</td>
        <td><span class="badge ${l.is_active ? 'badge-green' : 'badge-gray'}">${l.is_active ? 'Active' : 'Ended'}</span></td>
      </tr>`).join('');
  }

  function renderOverviewCourses(courses) {
    const tbody = document.getElementById('overview-courses-tbody');
    if (!tbody) return;
    if (!courses.length) {
      tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:24px;color:var(--t-muted);font-style:italic">No courses assigned.</td></tr>';
      return;
    }
    tbody.innerHTML = courses.slice(0, 5).map(c => `
      <tr>
        <td><strong style="font-size:.82rem">${escHtml(c.code)}</strong></td>
        <td style="font-size:.83rem">${escHtml(c.name)}</td>
        <td style="font-size:.82rem">${escHtml(c.level || '—')}</td>
      </tr>`).join('');
  }

  /* ── Students ── */
  async function loadStudents() {
    const res = await API.lecturer.students();
    if (!res.ok) {
      if (res.status === 401 || res.status === 403) { SessionStore.clear(); window.location.href = '/lecturer/login'; return; }
      showToast('error', 'Load Error', 'Could not load students.');
      return;
    }
    allStudents = res.data.students || [];
    renderStudents();
  }

  function renderStudents() {
    const tbody = document.getElementById('students-tbody');
    if (!tbody) return;
    const q = (document.getElementById('student-search')?.value || '').toLowerCase();
    const filtered = q
      ? allStudents.filter(s => (s.name + ' ' + s.matric + ' ' + (s.email || '')).toLowerCase().includes(q))
      : allStudents;
    const count = document.getElementById('students-count');
    if (count) count.textContent = `${filtered.length} student${filtered.length !== 1 ? 's' : ''}`;
    if (!filtered.length) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">${q ? `No results for "${escHtml(q)}".` : 'No students found.'}</td></tr>`;
      return;
    }
    tbody.innerHTML = filtered.map(s => `
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--brand);color:white;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;flex-shrink:0">${escHtml((s.name || 'S')[0])}</div>
            <div>
              <div style="font-weight:700;font-size:.875rem;color:var(--t-primary)">${escHtml(s.name)}</div>
              <div style="font-size:.72rem;color:var(--t-muted)">${escHtml(s.email || '')}</div>
            </div>
          </div>
        </td>
        <td style="font-size:.83rem;font-weight:600">${escHtml(s.matric)}</td>
        <td style="font-size:.83rem">${escHtml(s.dept || '—')}</td>
        <td style="font-size:.83rem">${escHtml(s.level || '—')}</td>
        <td><span class="badge ${s.verified ? 'badge-green' : 'badge-gold'}">${s.verified ? '✓ Verified' : '⏳ No'}</span></td>
        <td><span class="badge ${statusBadge(s.status)}">${escHtml(s.status)}</span></td>
      </tr>`).join('');
  }

  document.getElementById('student-search')?.addEventListener('input', renderStudents);

  /* ── Courses ── */
  async function loadCourses() {
    const res = await API.lecturer.courses();
    if (!res.ok) {
      if (res.status === 401 || res.status === 403) { SessionStore.clear(); window.location.href = '/lecturer/login'; return; }
      showToast('error', 'Load Error', 'Could not load courses.');
      return;
    }
    allCourses = res.data.courses || [];
    allCourses.forEach(c => { courseMap[c.id] = c; });
    renderCourses();
    populateCourseSelect();
    renderOverviewCourses(allCourses);
  }

  function renderCourses() {
    const tbody = document.getElementById('courses-tbody');
    if (!tbody) return;
    const count = document.getElementById('courses-count');
    if (count) count.textContent = `${allCourses.length} course${allCourses.length !== 1 ? 's' : ''}`;
    if (!allCourses.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">No courses assigned.</td></tr>';
      return;
    }
    tbody.innerHTML = allCourses.map(c => `
      <tr>
        <td><strong style="font-size:.82rem">${escHtml(c.code)}</strong></td>
        <td style="font-size:.85rem">${escHtml(c.name)}</td>
        <td style="font-size:.83rem">${escHtml(c.department || '—')}</td>
        <td style="font-size:.83rem">${escHtml(c.level || '—')}</td>
        <td style="font-size:.83rem">${c.credit_units}</td>
        <td><span class="badge ${c.is_active ? 'badge-green' : 'badge-gray'}">${c.is_active ? 'Active' : 'Inactive'}</span></td>
      </tr>`).join('');
  }

  function populateCourseSelect() {
    const select = document.getElementById('lecture-course');
    if (!select) return;
    select.innerHTML = '<option value="">-- Select course --</option>' +
      allCourses.map(c => `<option value="${c.id}">${escHtml(c.code)} - ${escHtml(c.name)}</option>`).join('');
  }

  /* ── Lectures ── */
  async function loadLectures() {
    const res = await API.lecturer.lectures();
    if (!res.ok) {
      if (res.status === 401 || res.status === 403) { SessionStore.clear(); window.location.href = '/lecturer/login'; return; }
      showToast('error', 'Load Error', 'Could not load lectures.');
      return;
    }
    allLectures = res.data.lectures || [];
    renderLectures();
    populateLectureSelect();
    updateDashboardCounts();
  }

  function renderLectures() {
    const tbody = document.getElementById('lectures-tbody');
    if (!tbody) return;
    const count = document.getElementById('lectures-count');
    if (count) count.textContent = `${allLectures.length} lecture${allLectures.length !== 1 ? 's' : ''}`;
    if (!allLectures.length) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">No lectures yet.</td></tr>';
      return;
    }
    tbody.innerHTML = allLectures.map(l => `
      <tr>
        <td><div style="font-weight:600;font-size:.85rem">${escHtml(l.title)}</div><div style="font-size:.72rem;color:var(--t-muted)">${escHtml(String(l.content || '').slice(0, 60))}${(l.content || '').length > 60 ? '…' : ''}</div></td>
        <td style="font-size:.83rem">${escHtml(courseLabel(l.course_id))}</td>
        <td style="font-size:.8rem">${new Date(l.scheduled_date).toLocaleString()}</td>
        <td><span class="badge ${l.is_active ? 'badge-green' : 'badge-gray'}">${l.is_active ? 'Active' : 'Ended'}</span></td>
        <td>
          ${l.is_active
            ? `<button class="btn btn-sm btn-secondary" data-end-lecture="${l.id}">⏹ End Lecture</button>`
            : '<span style="font-size:.75rem;color:var(--t-light)">—</span>'}
        </td>
      </tr>`).join('');

    tbody.querySelectorAll('[data-end-lecture]').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('End this lecture? Attendance will be finalised and absent students notified.')) return;
        const r = await API.lecturer.endLecture(btn.dataset.endLecture);
        if (r.ok) {
          showToast('success', 'Lecture Ended', r.data.message || 'Lecture ended successfully.');
          loadLectures(); loadDashboard();
        } else {
          showToast('error', 'Error', r.data.message || 'Could not end lecture.');
        }
      });
    });
  }

  function populateLectureSelect() {
    const select = document.getElementById('attendance-lecture-select');
    if (!select) return;
    select.innerHTML = '<option value="">-- Select a lecture --</option>' +
      allLectures.map(l => `<option value="${l.id}">${escHtml(l.title)} - ${new Date(l.scheduled_date).toLocaleDateString()}</option>`).join('');
  }

  function updateDashboardCounts() {
    setText('mc-lectures', allLectures.length);
    setText('mc-active', allLectures.filter(l => l.is_active).length);
  }

  /* ── Attendance ── */
  document.getElementById('attendance-lecture-select')?.addEventListener('change', async function () {
    const tbody = document.getElementById('attendance-tbody');
    if (!this.value) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Select a lecture to view attendance…</td></tr>';
      return;
    }
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading attendance…</td></tr>';
    const res = await API.lecturer.getAttendance(this.value);
    if (!res.ok) {
      if (res.status === 401 || res.status === 403) { SessionStore.clear(); window.location.href = '/lecturer/login'; return; }
      showToast('error', 'Load Error', 'Could not load attendance.');
      return;
    }
    renderAttendance(res.data.attendances || []);
  });

  function renderAttendance(attendances) {
    const tbody = document.getElementById('attendance-tbody');
    if (!tbody) return;
    if (!attendances.length) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">No attendance records yet for this lecture.</td></tr>';
      return;
    }
    tbody.innerHTML = attendances.map(a => `
      <tr>
        <td style="font-weight:600;font-size:.85rem">${escHtml(a.student_name)}</td>
        <td style="font-size:.83rem">${escHtml(a.student_matric)}</td>
        <td><span class="badge ${a.status === 'present' ? 'badge-green' : a.status === 'late' ? 'badge-gold' : 'badge-red'}">${escHtml(a.status)}</span></td>
        <td style="font-size:.8rem;color:var(--t-muted)">${escHtml(a.notes || '—')}</td>
      </tr>`).join('');
  }

  /* ── Section navigation ── */
  window.showSection = function (sec) {
    document.querySelectorAll('.dash-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.dash-nav-link').forEach(l => l.classList.remove('active'));
    document.getElementById('section-' + sec)?.classList.add('active');
    document.querySelector(`.dash-nav-link[data-section="${sec}"]`)?.classList.add('active');
    setText('dash-page-title-text', {
      overview: 'Overview', students: 'Students', courses: 'My Courses',
      lectures: 'Lectures', attendance: 'Attendance',
    }[sec] || sec);

    if (sec === 'students') loadStudents();
    if (sec === 'courses') loadCourses();
    if (sec === 'lectures') loadLectures();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  document.querySelectorAll('.dash-nav-link[data-section]').forEach(l => {
    l.addEventListener('click', e => { e.preventDefault(); showSection(l.dataset.section); });
  });

  /* ── Create lecture modal ── */
  const lectureModal = document.getElementById('lecture-modal');
  function openLectureModal() { if (lectureModal) { lectureModal.style.display = 'flex'; void lectureModal.offsetWidth; lectureModal.classList.add('open'); } }
  function closeLectureModal() { if (lectureModal) { lectureModal.classList.remove('open'); setTimeout(() => { lectureModal.style.display = 'none'; }, 300); } }

  document.getElementById('create-lecture-btn')?.addEventListener('click', () => {
    if (!allCourses.length) { showToast('warning', 'No Courses', 'No courses are assigned to you yet.'); return; }
    populateCourseSelect();
    openLectureModal();
  });
  document.getElementById('close-lecture-modal')?.addEventListener('click', closeLectureModal);
  document.getElementById('cancel-lecture')?.addEventListener('click', closeLectureModal);
  lectureModal?.addEventListener('click', e => { if (e.target === lectureModal) closeLectureModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLectureModal(); });

  document.getElementById('save-lecture')?.addEventListener('click', async () => {
    const courseId = document.getElementById('lecture-course')?.value;
    const title    = document.getElementById('lecture-title')?.value.trim();
    const content  = document.getElementById('lecture-content')?.value.trim();
    const date     = document.getElementById('lecture-date')?.value;

    if (!courseId || !title || !content || !date) {
      showToast('error', 'Required Fields', 'Please fill in all fields.');
      return;
    }

    const btn = document.getElementById('save-lecture');
    btn.disabled = true; btn.textContent = 'Creating…';
    const res = await API.lecturer.createLecture({
      course_id: parseInt(courseId), title, content, scheduled_date: date,
    });
    btn.disabled = false; btn.textContent = 'Create Lecture';

    if (res.ok) {
      showToast('success', 'Lecture Created', 'Your lecture has been created.');
      closeLectureModal();
      document.getElementById('lecture-title').value = '';
      document.getElementById('lecture-content').value = '';
      document.getElementById('lecture-date').value = '';
      loadLectures();
      loadDashboard();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not create lecture.');
    }
  });

  /* ── Dropdown + logout ── */
  setupDropdown('user-menu-btn', 'user-menu-dd');
  document.querySelectorAll('[data-logout]').forEach(el => el.addEventListener('click', async () => {
    await API.auth.lecturerLogout();
    SessionStore.clear();
    setTimeout(() => window.location.href = '/lecturer/login', 500);
  }));

  /* ── Init — verify the session on reload, then load the dashboard ── */
  (async function init() {
    if (!(await SessionStore.verify('lecturer', '/lecturer/login'))) return;
    await loadDashboard();
    await loadCourses();
    await loadLectures();
    await loadStudents();
    const name = SessionStore.getUser()?.name?.split(' ')[0] || 'Lecturer';
    setTimeout(() => showToast('success', `Welcome back, ${name}!`, ''), 500);
  })();
}
</script>
</body>
</html>
