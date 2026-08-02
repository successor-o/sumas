/* ═══════════════════════════════════════════════════════════════
   SUMAS SmartAttend — Main Application JS v5
   All page logic — uses API.js to talk to Laravel backend
═══════════════════════════════════════════════════════════════ */
'use strict';

/* ── Page Loader ── */
window.addEventListener('load', () => {
  setTimeout(() => document.getElementById('page-loader')?.classList.add('exit'), 1600);
});

/* ── Scroll Reveal ── */
const revObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (!e.isIntersecting) return;
    setTimeout(() => e.target.classList.add('visible'), parseInt(e.target.dataset.delay || 0));
    revObs.unobserve(e.target);
  });
}, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });
document.querySelectorAll('.fade-up,.fade-in,.scale-in').forEach(el => revObs.observe(el));

/* ── Navbar scroll ── */
const navbar = document.querySelector('.navbar');
window.addEventListener('scroll', () => {
  navbar?.classList.toggle('scrolled', window.scrollY > 20);
}, { passive: true });

/* ── Hamburger ── */
const hamburger = document.getElementById('hamburger');
const mobileNav = document.getElementById('mobile-nav');
hamburger?.addEventListener('click', () => {
  const open = mobileNav?.classList.toggle('open');
  hamburger.classList.toggle('open', open);
  hamburger.setAttribute('aria-expanded', String(!!open));
  document.body.style.overflow = open ? 'hidden' : '';
});
document.querySelectorAll('.mobile-link').forEach(l => l.addEventListener('click', () => {
  mobileNav?.classList.remove('open');
  hamburger?.classList.remove('open');
  document.body.style.overflow = '';
}));

/* ── Theme toggle ── */
function initTheme() {
  const t = localStorage.getItem('sumas-theme') || 'light';
  document.documentElement.setAttribute('data-theme', t);
  document.querySelectorAll('.theme-toggle, #theme-toggle').forEach(b => {
    b.textContent = t === 'dark' ? '☀️' : '🌙';
  });
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

/* ── Toast Notifications ── */
function showToast(type, title, message = '', duration = 4500) {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `
    <span class="toast-icon">${icons[type] || '💬'}</span>
    <div style="flex:1">
      <div class="toast-title">${title}</div>
      ${message ? `<div class="toast-body">${message}</div>` : ''}
    </div>
    <button class="toast-x" onclick="this.closest('.toast').remove()">✕</button>`;
  container.appendChild(t);
  setTimeout(() => {
    t.style.transition = 'all .4s ease';
    t.style.opacity = '0';
    t.style.transform = 'translateX(110%)';
    setTimeout(() => t.remove(), 400);
  }, duration);
}
window.showToast = showToast;

/* ── Format date ── */
function fmtDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' });
}

/* ── Dropdown helper ── */
function setupDropdown(btnId, ddId) {
  const btn = document.getElementById(btnId);
  const dd  = document.getElementById(ddId);
  if (!btn || !dd) return;
  btn.addEventListener('click', e => { e.stopPropagation(); dd.classList.toggle('open'); });
  document.addEventListener('click', () => dd.classList.remove('open'));
}

/* ── Hero Slideshow ── */
(function initSlideshow() {
  const panels = [
    { slides: document.querySelectorAll('.hero-photo-panel .hero-slide'), dots: document.querySelectorAll('.hero-photo-panel .hero-dot') },
    { slides: document.querySelectorAll('.hero-mobile-slides .hero-slide'), dots: document.querySelectorAll('.hero-mobile-slides .hero-mobile-dot') },
  ].filter(p => p.slides.length);
  if (!panels.length) return;
  const total = panels[0].slides.length;
  let cur = 0, timer;
  function goTo(n) {
    const prev = cur; cur = ((n % total) + total) % total;
    panels.forEach(p => {
      p.slides[prev]?.classList.remove('active'); p.slides[cur]?.classList.add('active');
      p.dots[prev]?.classList.remove('active');   p.dots[cur]?.classList.add('active');
    });
  }
  panels.forEach(p => p.dots.forEach((d, i) => d.addEventListener('click', () => { goTo(i); reset(); })));
  document.getElementById('mob-prev')?.addEventListener('click', () => { goTo(cur - 1); reset(); });
  document.getElementById('mob-next')?.addEventListener('click', () => { goTo(cur + 1); reset(); });
  function reset() { clearInterval(timer); timer = setInterval(() => goTo(cur + 1), 4800); }
  reset();
  let sx = 0;
  document.querySelector('.hero')?.addEventListener('touchstart', e => { sx = e.changedTouches[0].clientX; }, { passive: true });
  document.querySelector('.hero')?.addEventListener('touchend', e => {
    const d = sx - e.changedTouches[0].clientX;
    if (Math.abs(d) > 40) { d > 0 ? goTo(cur + 1) : goTo(cur - 1); reset(); }
  }, { passive: true });
})();


/* ════════════════════════════════════════════════
   LOGIN PAGE
════════════════════════════════════════════════ */
if (document.getElementById('login-page')) {
  SessionStore.redirectIfStudent('/dashboard');

  /* Toggle password visibility */
  document.getElementById('toggle-pw')?.addEventListener('click', function () {
    const pw = document.getElementById('login-password');
    pw.type = pw.type === 'password' ? 'text' : 'password';
    this.textContent = pw.type === 'text' ? '🙈' : '👁️';
  });

  /* Submit */
  const loginBtn = document.getElementById('login-btn');
  function doLogin() {
    const matric    = document.getElementById('login-matric')?.value.trim();
    const password  = document.getElementById('login-password')?.value;
    const matricErr = document.getElementById('matric-err');
    const passErr   = document.getElementById('pass-err');

    // Reset errors
    [matricErr, passErr].forEach(el => el && (el.style.display = 'none'));
    document.getElementById('login-matric')?.classList.remove('error');
    document.getElementById('login-password')?.classList.remove('error');

    let valid = true;
    if (!matric) {
      if (matricErr) matricErr.style.display = 'block';
      document.getElementById('login-matric')?.classList.add('error');
      valid = false;
    }
    if (!password) {
      if (passErr) passErr.style.display = 'block';
      document.getElementById('login-password')?.classList.add('error');
      valid = false;
    }
    if (!valid) return;

    loginBtn.disabled = true;
    loginBtn.textContent = '⏳  Verifying…';

    API.auth.login(matric, password).then(res => {
      if (res.ok && res.data.token) {
        SessionStore.save(res.data.token, res.data.user, 'student');
        showToast('success', `Welcome back, ${res.data.user.name?.split(' ')[0]}!`, 'Loading your dashboard…');
        setTimeout(() => window.location.href = '/dashboard', 800);
      } else {
        const msg = res.data.message || 'Invalid credentials. Please try again.';
        showToast('error', 'Login Failed', msg);
        loginBtn.disabled = false;
        loginBtn.textContent = 'Sign In →';
        document.getElementById('login-matric')?.classList.add('error');
        document.getElementById('login-password')?.classList.add('error');
      }
    });
  }

  loginBtn?.addEventListener('click', doLogin);
  document.getElementById('login-matric')?.addEventListener('keydown', e => e.key === 'Enter' && doLogin());
  document.getElementById('login-password')?.addEventListener('keydown', e => e.key === 'Enter' && doLogin());
}


/* ════════════════════════════════════════════════
   REGISTRATION PAGE
════════════════════════════════════════════════ */
if (document.getElementById('register-page')) {
  const state = { dept: null, level: null, verified: false, uploads: {}, faculty: null };

  /* Load faculties from API */
  async function loadFaculties() {
    const res = await fetch('/api/faculties');
    const data = await res.json();
    if (data.faculties) {
      const select = document.getElementById('faculty-select');
      select.innerHTML = '<option value="">— Select your faculty —</option>';
      data.faculties.forEach(faculty => {
        const option = document.createElement('option');
        option.value = faculty.id;
        option.textContent = faculty.name;
        select.appendChild(option);
      });
    }
  }
  loadFaculties();

  /* Load departments from API */
  async function loadDepartments(facultyId = null) {
    const res = await fetch('/api/departments');
    const data = await res.json();
    if (data.departments) {
      const select = document.getElementById('dept-select');
      select.innerHTML = '<option value="">— Select your department —</option>';
      
      const filteredDepts = facultyId 
        ? data.departments.filter(dept => dept.faculty_id === parseInt(facultyId))
        : data.departments;
      
      filteredDepts.forEach(dept => {
        const option = document.createElement('option');
        option.value = dept.id;
        option.textContent = dept.name;
        select.appendChild(option);
      });
      
      select.disabled = !facultyId;
    }
  }

  /* Faculty select change */
  document.getElementById('faculty-select')?.addEventListener('change', function () {
    state.faculty = this.value || null;
    document.getElementById('faculty-err').style.display = 'none';
    this.classList.toggle('error', !state.faculty);
    
    // Reset department and reload filtered departments
    state.dept = null;
    document.getElementById('dept-select').value = '';
    loadDepartments(state.faculty);
  });

  /* Department select */
  document.getElementById('dept-select')?.addEventListener('change', function () {
    state.dept = this.value || null;
    document.getElementById('dept-err').style.display = 'none';
    this.classList.toggle('error', !state.dept);
  });

  /* Level chips */
  document.querySelectorAll('.level-chip input').forEach(inp => {
    inp.addEventListener('change', function () {
      state.level = this.value;
      document.getElementById('level-err').style.display = 'none';
    });
  });

  /* Password toggles */
  ['toggle-pw-reg', 'toggle-pw-reg2'].forEach((id, i) => {
    document.getElementById(id)?.addEventListener('click', function () {
      const pwId = i === 0 ? 'field-password' : 'field-password2';
      const pw = document.getElementById(pwId);
      pw.type = pw.type === 'password' ? 'text' : 'password';
      this.textContent = pw.type === 'text' ? '🙈' : '👁️';
    });
  });

  /* File uploads — real files stored for upload after registration */
  document.querySelectorAll('.upload-zone input[type="file"], .photo-box input[type="file"]').forEach(inp => {
    inp.addEventListener('change', function () {
      const file = this.files[0]; if (!file) return;
      const zone  = this.closest('.upload-zone') || this.closest('.photo-box');
      const docId = this.dataset.doc;
      state.uploads[docId] = file; // keep the File — uploaded after registration
      zone.classList.add('uploaded');
      const icon = zone.querySelector('.uz-icon');
      const hint = zone.querySelector('.uz-hint');
      if (icon) icon.textContent = '✅';
      if (hint) hint.textContent = file.name.length > 30 ? file.name.slice(0, 30) + '…' : file.name;

      // Photo boxes get a live preview
      if (zone.classList.contains('photo-box')) {
        zone.classList.add('done');
        const reader = new FileReader();
        reader.onload = ev => {
          let img = zone.querySelector('img.preview');
          if (!img) { img = document.createElement('img'); img.className = 'preview'; zone.appendChild(img); }
          img.src = ev.target.result;
          const inner = zone.querySelector('.photo-inner');
          if (inner) inner.style.opacity = '0';
        };
        reader.readAsDataURL(file);
        showToast('success', 'Photo Selected', `Passport photo ${docId.split('-')[1]} ready.`);
      } else {
        showToast('success', 'Document Ready', file.name);
      }
    });
  });

  /* ── Validation helper ── */
  function validateField(id, errId, check, msg) {
    const el  = document.getElementById(id);
    const err = document.getElementById(errId);
    const val = el?.value.trim() || '';
    const ok  = check(val);
    el?.classList.toggle('error', !ok);
    if (err) err.style.display = ok ? 'none' : 'block';
    return ok;
  }

  /* ── Final Submit ── */
  document.getElementById('final-submit-btn')?.addEventListener('click', async () => {
    const g = id => document.getElementById(id)?.value.trim() || '';

    // Validate all fields
    let valid = true;

    if (!state.faculty) {
      document.getElementById('faculty-err').style.display = 'block';
      document.getElementById('faculty-select')?.classList.add('error');
      valid = false;
    }
    if (!state.dept) {
      document.getElementById('dept-err').style.display = 'block';
      document.getElementById('dept-select')?.classList.add('error');
      valid = false;
    }
    if (!state.level) {
      document.getElementById('level-err').style.display = 'block';
      valid = false;
    }
    if (!validateField('field-fullname', 'err-fullname', v => v.length >= 2, '')) valid = false;
    if (!validateField('field-matric', 'err-matric', v => v.length >= 4, '')) valid = false;
    if (!validateField('field-email', 'err-email', v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v), '')) valid = false;
    if (!validateField('field-phone', 'err-phone', v => v.length >= 8, '')) valid = false;

    const pw  = g('field-password');
    const pw2 = g('field-password2');
    if (pw.length < 6) {
      document.getElementById('field-password')?.classList.add('error');
      document.getElementById('err-password').style.display = 'block';
      valid = false;
    }
    if (pw !== pw2) {
      document.getElementById('field-password2')?.classList.add('error');
      document.getElementById('err-password2').style.display = 'block';
      valid = false;
    }

    const missDocs = ['school-id', 'admission', 'clearance'].filter(d => !state.uploads[d]);
    if (missDocs.length) {
      showToast('error', 'Documents Missing', `${missDocs.length} required document(s) not uploaded.`);
      valid = false;
    }
    const missPhotos = ['pp-1', 'pp-2', 'pp-3'].filter(d => !state.uploads[d]);
    if (missPhotos.length) {
      showToast('error', 'Photos Missing', 'Please upload all 3 passport photographs.');
      valid = false;
    }
    if (!valid) { showToast('error', 'Form Incomplete', 'Please fix all errors above.'); return; }

    const btn = document.getElementById('final-submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<span style="animation:spin .8s linear infinite;display:inline-block">⏳</span>&nbsp; Submitting…';

    // Documents travel with the registration itself (multipart FormData) — no
    // session/token is issued, so the admin reviews them alongside the pending
    // application. The student signs in only after approval.
    const fd = new FormData();
    fd.append('name',            g('field-fullname'));
    fd.append('matric',          g('field-matric'));
    fd.append('email',           g('field-email'));
    fd.append('phone',           g('field-phone'));
    fd.append('password',        pw);
    fd.append('department_id',   state.dept);
    fd.append('level',           state.level);
    fd.append('gender',          g('field-gender'));
    fd.append('dob',             g('field-dob'));
    fd.append('state_of_origin', g('field-state'));
    for (const [docType, file] of Object.entries(state.uploads)) {
      fd.append(`documents[${docType}]`, file);
    }

    const res = await API.auth.register(fd);

    if (res.ok && res.data.user) {
      document.getElementById('reg-form-body').style.display = 'none';
      document.getElementById('reg-success-view').style.display = 'block';

      const docs = res.data.user.docs_count || 0;
      const docsLine = document.getElementById('reg-docs-count');
      if (docsLine) {
        docsLine.textContent = docs > 0
          ? `📄 ${docs} document(s) received — the administration can review them now.`
          : '📄 No documents uploaded. You can add them after your registration is approved.';
      }
      showToast('success', 'Registration Submitted!', 'Your registration is under review. Check your status below.');
    } else {
      const msg = res.data.message || res.data.errors
        ? Object.values(res.data.errors || {}).flat().join('. ')
        : 'Registration failed. Please try again.';
      showToast('error', 'Registration Failed', msg);
      btn.disabled = false;
      btn.textContent = '🚀 Submit Registration';
    }
  });

  /* Status check */
  document.getElementById('status-check-btn')?.addEventListener('click', async () => {
    const m = document.getElementById('status-matric-input')?.value.trim();
    if (!m) { showToast('error', 'Required', 'Enter your matric number.'); return; }
    const btn = document.getElementById('status-check-btn');
    btn.disabled = true; btn.textContent = '🔍 Searching…';

    const res = await API.student.checkStatus(m);
    const resultEl = document.getElementById('status-result');
    if (!resultEl) { btn.disabled = false; btn.textContent = 'Check Status'; return; }
    resultEl.style.display = 'block';

    if (res.ok && res.data) {
      const u = res.data;
      const badgeClass = { Pending: 'badge-gold', Approved: 'badge-green', Rejected: 'badge-red' }[u.status] || 'badge-gray';
      resultEl.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
          <div>
            <div style="font-size:1rem;font-weight:700;color:var(--t-primary)">${u.name}</div>
            <div style="font-size:.75rem;color:var(--t-muted)">${u.matric} · ${u.dept}</div>
          </div>
          <span class="badge ${badgeClass}">${u.status === 'Pending' ? '⏳' : u.status === 'Approved' ? '✅' : '❌'} ${u.status}</span>
        </div>
        <p style="font-size:.875rem;color:var(--t-secondary);line-height:1.7">
          Submitted: ${fmtDate(u.created_at)} · AI Verification: ${u.verified ? '<strong style="color:var(--success)">✓ Verified</strong>' : '<span style="color:var(--warning)">⏳ Pending</span>'}
          <br/><br/>
          ${u.status === 'Approved' ? '✅ Approved! <a href="/login" style="color:var(--brand);font-weight:700">Sign in →</a>' : u.status === 'Rejected' ? '❌ Not approved. Contact registrar.' : 'Under review. Expected: 24–48 hours.'}
        </p>`;
      showToast('success', 'Status Found', '');
    } else {
      resultEl.innerHTML = `<p style="font-size:.875rem;color:var(--error)">❌ No registration found for <strong>${m}</strong>. Please <a href="/register" style="color:var(--brand);font-weight:700">register first →</a></p>`;
      showToast('warning', 'Not Found', '');
    }
    btn.disabled = false; btn.textContent = 'Check Status';
  });
}


/* ════════════════════════════════════════════════
   STUDENT DASHBOARD
════════════════════════════════════════════════ */
if (document.getElementById('student-dashboard')) {
  SessionStore.requireStudent('/login');

  const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || '—'; };
  const setVal  = (id, v) => { const el = document.getElementById(id); if (el) el.value  = v || ''; };

  async function loadDashboard() {
    const res = await API.student.dashboard();
    if (!res.ok) {
      if (res.status === 401) { SessionStore.clear(); window.location.href = '/login'; return; }
      // 403 — registration is no longer approved (e.g. after a profile edit the
      // account is set back to pending verification). Sign the student out so
      // they are not left on a dashboard they can no longer access.
      if (res.status === 403) {
        const msg = res.data.message || 'Your registration is under review.';
        showToast('warning', 'Access Suspended', msg);
        SessionStore.clear();
        setTimeout(() => window.location.href = '/login', 1500);
        return;
      }
      showToast('error', 'Load Error', 'Could not load dashboard data.');
      return;
    }
    const user = res.data.user;
    SessionStore.save(SessionStore.getToken(), user, 'student');
    populate(user);
  }

  function populate(user) {
    const first = (user.name || 'Student').split(' ')[0];
    const init  = first[0]?.toUpperCase() || 'S';

    document.querySelectorAll('[data-avatar]').forEach(el => el.textContent = init);
    document.querySelectorAll('[data-user-name]').forEach(el => el.textContent = user.name || 'Student');

    setText('wb-name',       first);
    setText('wb-status-val', { Pending: '⏳ Under Review', Approved: '✅ Approved', Rejected: '❌ Rejected' }[user.status] || user.status);
    setText('wb-submitted',  fmtDate(user.created_at));
    setText('wb-docs',       (user.docs_count || 0) + ' Files');

    setText('mc-reg-val',    'Submitted');
    setText('mc-docs-val',   (user.docs_count || 0) + ' Files');
    setText('mc-verify-val', user.verified ? '✅ Verified' : '⏳ Pending');
    setText('mc-date-val',   fmtDate(user.created_at));

    setText('inf-name',   user.name);
    setText('inf-matric', user.matric);
    setText('inf-dept',   user.dept);
    setText('inf-level',  user.level);
    setText('inf-email',  user.email);
    setText('inf-status', user.status);

    // ── Face verification / enrollment status (real data) ──
    const enrolled = !!(user.face_enrolled_at || user.face_photo);
    if (enrolled) {
      setText('vsd-icon', '🧑‍🏫');
      setText('vsd-title', '✅ Face Enrolled');
      setText('vsd-sub',   user.face_enrolled_at ? 'Webcam face enrollment recorded on ' + fmtDate(user.face_enrolled_at) + '.' : 'Webcam face enrollment recorded.');
      setText('vmc-status', '✓ Enrolled'); setText('vmc-verified', '✓ Verified');
      setText('vmc-date',   user.face_enrolled_at ? fmtDate(user.face_enrolled_at) : '—');
      setText('vmc-student', user.status);
      const bio = document.getElementById('sc-bio-stat');
      if (bio) { bio.textContent = 'Enrolled ✓'; bio.className = 'sc-stat sc-ok'; }
      const stv = document.getElementById('st-verify-item');
      if (stv) { stv.classList.add('done'); const d = stv.querySelector('.st-dot'); if (d) { d.textContent = '✓'; d.className = 'st-dot'; } }
      setText('st-verify-time', 'Enrolled ✓');
      const td3 = document.getElementById('tl-dot-3'); if (td3) td3.className = 'tl-dot gold';
      setText('tl-3-date', user.face_enrolled_at ? fmtDate(user.face_enrolled_at) : fmtDate(user.created_at));
      setText('tl-3-sub', 'Face enrolled via supervised webcam');
      const psb = document.getElementById('profile-status-badge');
      if (psb) { psb.textContent = '✅ Face Enrolled'; psb.className = 'badge badge-green badge-lg'; }
    } else {
      setText('vsd-title', '⏳ Pending Enrollment');
      setText('vsd-sub',   'Your face will be enrolled by the administration after approval.');
      setText('vmc-status', '—'); setText('vmc-verified', '⏳ Pending');
      setText('vmc-date', '—'); setText('vmc-student', user.status);
      const bio = document.getElementById('sc-bio-stat');
      if (bio) { bio.textContent = 'Pending'; bio.className = 'sc-stat sc-pend'; }
    }

    // ── Admin approval status updates ──
    if (user.status === 'Approved') {
      // Update Document Authenticity and University Record Match
      const docStat = document.getElementById('sc-doc-stat');
      if (docStat) { docStat.textContent = 'Verified ✓'; docStat.className = 'sc-stat sc-ok'; }
      const regStat = document.getElementById('sc-reg-stat');
      if (regStat) { regStat.textContent = 'Verified ✓'; regStat.className = 'sc-stat sc-ok'; }

      // Update Admin Review timeline item
      const tlItems = document.querySelectorAll('.tl-item');
      if (tlItems.length >= 4) {
        const adminReviewItem = tlItems[3];
        const adminReviewDot = adminReviewItem.querySelector('.tl-dot');
        if (adminReviewDot) { adminReviewDot.className = 'tl-dot gold'; }
        const adminReviewDate = adminReviewItem.querySelector('.tl-date');
        if (adminReviewDate) adminReviewDate.textContent = fmtDate(user.updated_at);
      }

      // Update Admin Approval and SmartAttend Active steps
      const stItems = document.querySelectorAll('.st-item');
      if (stItems.length >= 5) {
        // Admin Approval (step 4)
        const adminApprovalItem = stItems[3];
        const adminApprovalDot = adminApprovalItem.querySelector('.st-dot');
        if (adminApprovalDot) { adminApprovalDot.textContent = '✓'; adminApprovalDot.className = 'st-dot'; }
        adminApprovalItem.classList.add('done');
        const adminApprovalTime = adminApprovalItem.querySelector('.st-time');
        if (adminApprovalTime) adminApprovalTime.textContent = 'Approved ✓';

        // SmartAttend Active (step 5)
        const smartAttendItem = stItems[4];
        const smartAttendDot = smartAttendItem.querySelector('.st-dot');
        if (smartAttendDot) { smartAttendDot.textContent = '✓'; smartAttendDot.className = 'st-dot'; }
        smartAttendItem.classList.add('done');
        const smartAttendTime = smartAttendItem.querySelector('.st-time');
        if (smartAttendTime) smartAttendTime.textContent = 'Active ✓';
      }
    }

    const d = fmtDate(user.created_at);
    setText('tl-1-date', d); setText('tl-2-date', d);

    setText('profile-name', user.name); setText('profile-id', user.matric);
    setVal('pf-name',   user.name); setVal('pf-matric', user.matric);
    setVal('pf-dept',   user.dept); setVal('pf-level',  user.level);
    setVal('pf-email',  user.email); setVal('pf-phone',  user.phone || '');
    setVal('pf-gender', user.gender || ''); setVal('pf-state', user.state_of_origin || '');
    setVal('pf-dob',    user.dob || ''); setVal('pf-faculty', user.faculty || '');

    // Documents nav badge (real count)
    const nb = document.getElementById('nav-docs-badge');
    if (nb) nb.textContent = user.docs_count || 0;

    buildRegSummary(user);
    buildAlerts(user);
    buildNotifications(user);
    buildActivity(user);
    loadDocuments();

    // Welcome banner colour based on status
    const wb = document.querySelector('.wb');
    if (wb && user.status === 'Approved') wb.style.background = 'linear-gradient(135deg,#0A5C2E,#0A6E3A)';
    if (wb && user.status === 'Rejected') wb.style.background = 'linear-gradient(135deg,#8B1A14,#C0241A)';
  }

  function buildRegSummary(user) {
    const tbody = document.getElementById('rs-tbody'); if (!tbody) return;
    const rows = [
      ['Full Name', user.name], ['Matric Number', user.matric],
      ['Department', user.dept, 'ok'], ['Level', user.level, 'ok'],
      ['Email', user.email], ['Phone', user.phone || '—'],
      ['Docs Uploaded', (user.docs_count || 0) + ' files', user.docs_count >= 4 ? 'ok' : 'warn'],
      ['AI Verification', user.verified ? '✅ Verified (97%)' : '⏳ Pending', user.verified ? 'ok' : 'warn'],
      ['Status', user.status, user.status === 'Approved' ? 'ok' : user.status === 'Rejected' ? 'warn' : ''],
      ['Submitted', fmtDate(user.created_at)],
    ];
    tbody.innerHTML = rows.map(([k, v, c = '']) => `<div class="rs-row"><span class="rs-key">${k}</span><span class="rs-val ${c}">${v || '—'}</span></div>`).join('');
  }

  function buildAlerts(user) {
    const wrap = document.getElementById('dash-alerts-wrap'); if (!wrap) return;
    const alerts = [];
    if (user.status === 'Approved') {
      alerts.push({ type: 'success', msg: '✅ <strong>Registration Approved!</strong> You are now enrolled in the SmartAttend system.' });
      if (!user.face_enrolled_at) alerts.push({ type: 'info', msg: '🧑‍🏫 <strong>Face enrollment pending.</strong> Visit the ICT office so an administrator can enroll your face via webcam.' });
    } else if (user.status === 'Rejected') {
      alerts.push({ type: 'error', msg: '❌ <strong>Registration Rejected.</strong> Please contact the SUMAS Registrar.' });
    } else {
      alerts.push({ type: 'info', msg: 'ℹ️ Registration submitted and <strong>under admin review</strong>. Expected: 24–48 hours.' });
      if (user.docs_count > 0) alerts.push({ type: 'info', msg: '📄 ' + user.docs_count + ' document(s) received. Face enrollment is performed by the administration.' });
    }
    wrap.innerHTML = alerts.map(a => `<div class="alert alert-${a.type}">${a.msg}</div>`).join('');
  }

  function buildNotifications(user) {
    const wrap = document.getElementById('notif-items'); if (!wrap) return;
    const items = [
      { icon: '📝', title: 'Registration submitted', time: fmtDate(user.created_at), unread: false },
    ];
    if (user.docs_count > 0) items.unshift({ icon: '📄', title: `${user.docs_count} document(s) uploaded`, time: fmtDate(user.updated_at), unread: true });
    if (user.face_enrolled_at) items.unshift({ icon: '🧑‍🏫', title: 'Face enrolled by admin', time: fmtDate(user.face_enrolled_at), unread: true });
    if (user.status === 'Approved') items.unshift({ icon: '✅', title: 'Registration approved', time: fmtDate(user.updated_at), unread: true });
    if (user.status === 'Rejected') items.unshift({ icon: '❌', title: 'Registration rejected', time: fmtDate(user.updated_at), unread: true });
    wrap.innerHTML = items.map(i => `
      <div class="notif-item ${i.unread ? 'unread' : ''}">
        <div class="notif-icon">${i.icon}</div>
        <div><div class="notif-title">${escHtml(i.title)}</div><div class="notif-time">${i.time}</div></div>
      </div>`).join('') || '<div style="padding:16px;color:var(--t-muted);font-size:.8rem;font-style:italic">No notifications.</div>';
  }

  function buildActivity(user) {
    const wrap = document.getElementById('activity-feed'); if (!wrap) return;
    const act = [];
    if (user.face_enrolled_at) act.push({ icon: '🧑‍🏫', title: 'Face enrolled', sub: 'Webcam enrollment by administration', time: fmtDate(user.face_enrolled_at) });
    if (user.status === 'Approved') act.push({ icon: '✅', title: 'Registration approved', sub: 'Approved by SUMAS administration', time: fmtDate(user.updated_at) });
    if (user.docs_count > 0) act.push({ icon: '📄', title: 'Documents uploaded', sub: user.docs_count + ' file(s) received', time: fmtDate(user.updated_at) });
    act.push({ icon: '📝', title: 'Registration submitted', sub: user.dept + ' · ' + user.level, time: fmtDate(user.created_at) });
    wrap.innerHTML = act.map(a => `
      <div class="activity-item">
        <div class="activity-icon">${a.icon}</div>
        <div><div class="activity-title">${escHtml(a.title)}</div><div class="activity-sub">${escHtml(a.sub)}</div></div>
        <div class="activity-time">${a.time}</div>
      </div>`).join('');
  }

  function escHtml(str) { return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  /* Section nav */
  window.showSection = function (sec) {
    document.querySelectorAll('.dash-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.dash-nav-link').forEach(l => l.classList.remove('active'));
    document.getElementById('section-' + sec)?.classList.add('active');
    document.querySelector(`.dash-nav-link[data-section="${sec}"]`)?.classList.add('active');
    setText('dash-page-title-text', {
      overview: 'Overview', registration: 'My Registration',
      documents: 'My Documents', verification: 'AI Verification', profile: 'My Profile',
    }[sec] || sec);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };
  document.querySelectorAll('.dash-nav-link[data-section]').forEach(l => {
    l.addEventListener('click', e => { e.preventDefault(); showSection(l.dataset.section); });
  });

  /* Dropdowns */
  setupDropdown('notif-btn', 'notif-dd');
  setupDropdown('user-menu-btn', 'user-menu-dd');

  /* ── Documents: list / upload / delete ── */
  const DOC_ICONS = { 'school-id': '🪪', admission: '📜', clearance: '✅', 'nat-id': '🆔', 'pp-1': '📸', 'pp-2': '📸', 'pp-3': '📸' };

  async function loadDocuments() {
    const list = document.getElementById('doc-list'); if (!list) return;
    const res = await API.student.getDocuments();
    if (!res.ok) { list.innerHTML = '<div class="doc-card"><div class="doc-icon">⚠️</div><div class="doc-name">Could not load documents.</div></div>'; return; }
    const docs = res.data.documents || [];
    const nb = document.getElementById('nav-docs-badge');
    if (nb) nb.textContent = docs.length;
    if (!docs.length) {
      list.innerHTML = '<div class="doc-card" style="grid-column:1/-1;text-align:left;display:flex;align-items:center;gap:var(--s3);justify-content:center;padding:var(--s6)"><div class="doc-icon">📂</div><div class="doc-name" style="margin:0">No documents uploaded yet. Use the upload widget above.</div></div>';
      return;
    }
    list.innerHTML = docs.map(d => `
      <div class="doc-card">
        <div class="doc-icon">${DOC_ICONS[d.doc_type] || '📄'}</div>
        <div class="doc-name">${escHtml(d.label)}</div>
        <div class="doc-meta">${escHtml(d.original_name)}</div>
        <div class="doc-meta">${fmtDate(d.created_at)}</div>
        <div style="display:flex;gap:var(--s2);justify-content:center;margin-top:var(--s3)">
          <a class="doc-action" href="${d.url}" target="_blank" rel="noopener" title="View ${escHtml(d.original_name)}">👁 View</a>
          <button class="doc-action danger" data-del-doc="${d.id}" title="Delete document">🗑</button>
        </div>
      </div>`).join('');
    list.querySelectorAll('[data-del-doc]').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Delete this document?')) return;
        const r = await API.student.deleteDocument(btn.dataset.delDoc);
        if (r.ok) { showToast('success', 'Deleted', 'Document removed.'); loadDocuments(); loadDashboard(); }
        else showToast('error', 'Error', r.data.message || 'Could not delete.');
      });
    });
  }

  /* Upload file selection label */
  const uploadFile = document.getElementById('doc-upload-file');
  uploadFile?.addEventListener('change', function () {
    const name = this.files[0] ? this.files[0].name : 'No file selected';
    const el = document.getElementById('doc-file-name');
    if (el) el.textContent = name;
  });

  document.getElementById('doc-upload-btn')?.addEventListener('click', async () => {
    const type = document.getElementById('doc-upload-type')?.value;
    const file = document.getElementById('doc-upload-file')?.files[0];
    if (!file) { showToast('warning', 'No File', 'Choose a file to upload.'); return; }
    const btn = document.getElementById('doc-upload-btn');
    const bar = document.getElementById('doc-upload-progress');
    const fill = document.getElementById('doc-upload-fill');
    btn.disabled = true; btn.textContent = 'Uploading…';
    if (bar) bar.style.display = 'block'; if (fill) fill.style.width = '0%';
    const res = await API.student.uploadDocument(type, file, pct => { if (fill) fill.style.width = pct + '%'; });
    btn.disabled = false; btn.textContent = 'Upload →';
    if (res.ok) {
      showToast('success', 'Uploaded', res.data.document?.label || 'Document uploaded.');
      if (bar) setTimeout(() => { bar.style.display = 'none'; if (fill) fill.style.width = '0%'; }, 1200);
      uploadFile.value = '';
      const nameEl = document.getElementById('doc-file-name'); if (nameEl) nameEl.textContent = 'No file selected';
      loadDocuments(); loadDashboard();
    } else {
      showToast('error', 'Upload Failed', res.data.message || 'Please try a JPG, PNG or PDF under 5 MB.');
      if (bar) setTimeout(() => { bar.style.display = 'none'; if (fill) fill.style.width = '0%'; }, 1200);
    }
  });

  /* ── Profile save ── */
  document.getElementById('profile-save-btn')?.addEventListener('click', async () => {
    if (!confirm('⚠️ Warning: Editing your profile will require re-verification by the administration. Your status will be set to "Pending Verification" until reviewed. Continue?')) {
      return;
    }
    const payload = {
      phone:           document.getElementById('pf-phone')?.value.trim(),
      gender:          document.getElementById('pf-gender')?.value,
      dob:             document.getElementById('pf-dob')?.value || null,
      state_of_origin: document.getElementById('pf-state')?.value.trim(),
      faculty:         document.getElementById('pf-faculty')?.value,
    };
    const btn = document.getElementById('profile-save-btn');
    btn.disabled = true; btn.textContent = 'Saving…';
    const res = await API.student.updateProfile(payload);
    btn.disabled = false; btn.textContent = '💾 Save Profile';
    if (res.ok) {
      SessionStore.save(SessionStore.getToken(), res.data.user, 'student');
      showToast('success', 'Profile Saved', 'Your information has been updated. Status is now pending verification.');
      loadDashboard();
    } else {
      const msg = res.data.errors ? Object.values(res.data.errors).flat().join('. ') : (res.data.message || 'Could not save profile.');
      showToast('error', 'Save Failed', msg);
    }
  });

  /* Logout */
  document.querySelectorAll('[data-logout]').forEach(el => el.addEventListener('click', async () => {
    await API.auth.logout();
    SessionStore.clear();
    showToast('info', 'Signed out', '');
    setTimeout(() => window.location.href = '/login', 600);
  }));

  /* ── Courses ──
     Courses are automatically loaded from the student's department based on
     their registration. No manual filtering is needed. */
  async function loadCourses() {
    const res = await API.student.courses();
    if (res.ok) {
      renderCourses(res.data.courses);
    }
  }

  function renderCourses(courses) {
    const container = document.getElementById('courses-list');
    if (!courses.length) {
      container.innerHTML = '<p style="color:var(--t-muted)">No courses available in your department yet.</p>';
      return;
    }
    container.innerHTML = '<div class="course-grid">' + courses.map(c => `
      <div class="course-card">
        <span class="course-card-code">${escHtml(c.code)}</span>
        <div class="course-card-name">${escHtml(c.name)}</div>
        <div class="course-card-meta">${escHtml(c.department || '—')} · ${escHtml(c.level || '—')} Level<br/>${c.credit_units} Credit Unit(s)</div>
        <span class="badge ${c.is_active ? 'badge-green' : 'badge-gray'} course-card-badge">${c.is_active ? 'Active' : 'Inactive'}</span>
      </div>
    `).join('') + '</div>';
  }

  /* ── Attendance ── */
  async function loadAttendance() {
    const res = await API.student.attendance();
    if (res.ok) {
      renderAttendance(res.data.attendances);
    }
  }

  function renderAttendance(attendances) {
    const container = document.getElementById('attendance-list');
    if (!attendances.length) {
      container.innerHTML = '<p style="color:var(--t-muted)">No attendance records yet.</p>';
      return;
    }
    container.innerHTML = attendances.map(a => `
      <div class="dash-list-item">
        <div>
          <div style="font-weight:600">${a.course_code} - ${a.course_name}</div>
          <div style="font-size:.8rem;color:var(--t-muted)">${a.lecturer_name} · ${new Date(a.lecture_date).toLocaleDateString()}</div>
        </div>
        <span class="badge ${a.status === 'present' ? 'badge-green' : a.status === 'late' ? 'badge-gold' : 'badge-red'}">${a.status}</span>
      </div>
    `).join('');
  }

  /* ── Lectures ── */
  async function loadLectures() {
    const res = await API.student.lectures();
    if (res.ok) {
      renderLectures(res.data.lectures);
    }
  }

  function renderLectures(lectures) {
    const container = document.getElementById('lectures-list');
    if (!lectures.length) {
      container.innerHTML = '<p style="color:var(--t-muted)">No lectures yet.</p>';
      return;
    }
    container.innerHTML = lectures.map(l => `
      <div class="dash-list-item" style="display:block">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--s2)">
          <div>
            <div style="font-weight:600">${l.title}</div>
            <div style="font-size:.8rem;color:var(--t-muted)">${l.course_code} - ${l.course_name}</div>
            <div style="font-size:.8rem;color:var(--t-muted)">${l.lecturer_name} · ${new Date(l.scheduled_date).toLocaleString()}</div>
          </div>
          <span class="badge ${l.is_active ? 'badge-green' : 'badge-gray'}">${l.is_active ? 'Active' : 'Ended'}</span>
        </div>
        <div style="font-size:.85rem;color:var(--t-secondary);line-height:1.5">${l.content}</div>
      </div>
    `).join('');
  }

  /* ── Notifications ── */
  async function loadNotifications() {
    const res = await API.student.notifications();
    if (res.ok) {
      renderNotifications(res.data.notifications);
      updateNotificationBadge(res.data.notifications);
    }
  }

  function renderNotifications(notifications) {
    const container = document.getElementById('notif-items');
    if (!notifications.length) {
      container.innerHTML = '<div style="padding:var(--s3);color:var(--t-muted);font-size:.85rem">No notifications yet.</div>';
      return;
    }
    container.innerHTML = notifications.map(n => `
      <div class="notif-item ${n.read ? 'read' : ''}" data-notif-id="${n.id}">
        <div style="display:flex;align-items:flex-start;gap:var(--s2)">
          <span class="notif-icon">${n.type === 'warning' ? '⚠️' : n.type === 'success' ? '✅' : n.type === 'error' ? '❌' : 'ℹ️'}</span>
          <div style="flex:1">
            <div style="font-weight:600;font-size:.85rem">${n.title}</div>
            <div style="font-size:.8rem;color:var(--t-secondary);margin-top:2px">${n.message}</div>
            <div style="font-size:.7rem;color:var(--t-muted);margin-top:4px">${new Date(n.created_at).toLocaleString()}</div>
          </div>
        </div>
        ${!n.read ? '<button class="notif-mark-read" data-id="${n.id}">✓</button>' : ''}
      </div>
    `).join('');

    container.querySelectorAll('.notif-mark-read').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const res = await API.student.markNotificationRead(id);
        if (res.ok) {
          loadNotifications();
        }
      });
    });
  }

  function updateNotificationBadge(notifications) {
    const unreadCount = notifications.filter(n => !n.read).length;
    const badge = document.querySelector('.hdr-badge');
    if (badge) {
      badge.textContent = unreadCount;
      badge.style.display = unreadCount > 0 ? 'inline-block' : 'none';
    }
  }

  /* ── Section navigation update ── */
  const originalShowSection = window.showSection;
  window.showSection = function(sec) {
    document.querySelectorAll('.dash-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.dash-nav-link').forEach(l => l.classList.remove('active'));
    document.getElementById('section-'+sec)?.classList.add('active');
    document.querySelector(`.dash-nav-link[data-section="${sec}"]`)?.classList.add('active');
    document.getElementById('dash-page-title-text').textContent = {
      overview: 'Overview', courses: 'My Courses', attendance: 'Attendance',
      lectures: 'Lectures', registration: 'My Registration', documents: 'Documents',
      verification: 'AI Verification', profile: 'My Profile'
    }[sec] || sec;

    if (sec === 'courses') loadCourses();
    if (sec === 'attendance') loadAttendance();
    if (sec === 'lectures') loadLectures();
  };

  /* Init — verify the session on reload first, then load the dashboard */
  (async function init() {
    if (!(await SessionStore.verify('student', '/login'))) return;
    loadDashboard().then(() => {
      const name = SessionStore.getUser()?.name?.split(' ')[0] || 'Student';
      setTimeout(() => showToast('success', `Welcome back, ${name}!`, ''), 500);
    });
  })();
}

/* CSS spin animation for submit button */
const spinStyle = document.createElement('style');
spinStyle.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
document.head.appendChild(spinStyle);
