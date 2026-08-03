<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <meta name="theme-color" content="#6B3318"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Mark Attendance — SUMAS SmartAttend</title>
  <link rel="stylesheet" href="{{ asset('assets/css/design-system.css') }}"/>
  <link rel="stylesheet" href="{{ asset('assets/css/pages.css') }}"/>
  <style>
    body#attend-page{
      min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
      background:
        radial-gradient(1200px 600px at 15% -10%, rgba(107,51,24,.14), transparent 60%),
        radial-gradient(1000px 500px at 110% 110%, rgba(22,110,60,.14), transparent 55%),
        var(--surf-1);
    }
    .attend-card{width:100%;max-width:440px;background:var(--white);border:1px solid var(--bdr-light);
      border-radius:var(--r-2xl);box-shadow:var(--sh-2xl);overflow:hidden}
    .attend-brand{display:flex;align-items:center;gap:12px;padding:20px 24px;background:linear-gradient(135deg,var(--brand),var(--b700));color:#fff}
    .attend-brand img{width:42px;height:42px;object-fit:contain;background:#fff;border-radius:10px;padding:4px}
    .attend-brand-name{font-family:var(--f-display);font-weight:800;font-size:1rem;line-height:1.1}
    .attend-brand-sub{font-size:.62rem;opacity:.85;text-transform:uppercase;letter-spacing:1px;margin-top:2px}
    .attend-body{padding:24px}
    .attend-lecture{background:var(--surf-2);border:1px solid var(--bdr-light);border-radius:var(--r-lg);padding:16px;margin-bottom:16px}
    .attend-lecture-course{font-family:var(--f-mono);font-size:.68rem;font-weight:700;letter-spacing:.6px;color:var(--brand);text-transform:uppercase}
    .attend-lecture-title{font-size:1.05rem;font-weight:800;color:var(--t-primary);margin:6px 0 2px}
    .attend-lecture-meta{font-size:.78rem;color:var(--t-muted);line-height:1.7}
    .attend-status{display:flex;align-items:flex-start;gap:10px;border-radius:var(--r-lg);padding:13px 15px;font-size:.84rem;font-weight:600;line-height:1.5}
    .attend-status.success{background:var(--success-bg);color:var(--success)}
    .attend-status.error{background:var(--error-bg);color:var(--error)}
    .attend-status.warn{background:var(--g100);color:var(--g700)}
    .attend-status.info{background:var(--b25);color:var(--brand)}
    .attend-login-title{font-size:.85rem;font-weight:700;color:var(--t-primary);margin-bottom:12px}
    .attend-big-btn{width:100%;height:50px;font-size:.92rem;font-weight:700;border-radius:12px}
    .attend-foot{text-align:center;font-size:.68rem;color:var(--t-light);margin-top:14px}
    .attend-code-input{font-family:var(--f-mono);font-size:1.6rem;font-weight:800;letter-spacing:.5em;text-align:center;height:60px}
  </style>
</head>
<body id="attend-page">
  <div class="attend-card">
    <div class="attend-brand">
      <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS"/>
      <div>
        <div class="attend-brand-name">SUMAS SmartAttend</div>
        <div class="attend-brand-sub">Attendance Check-In</div>
      </div>
    </div>
    <div class="attend-body" id="attend-body">
      <div class="attend-status info"><span>⏳</span><span>Loading…</span></div>
    </div>
  </div>

  <script src="{{ asset('assets/js/api.js') }}"></script>
  <script>
  (function () {
    'use strict';
    var TOKEN = @json($token);          // null on the manual-code page
    var MODE  = TOKEN ? 'qr' : 'code';
    var body  = document.getElementById('attend-body');
    var state = { info: null, student: null, code: null };

    function esc(str) {
      return String(str == null ? '' : str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function render(html) { body.innerHTML = html; }
    function signedIn() { return !!(SessionStore.getToken() && SessionStore.getRole() === 'student'); }

    function lectureCard() {
      if (!state.info) return '';
      var i = state.info;
      var extra = (i.gps_required ? '<div class="attend-lecture-meta">📍 Location required · enable location to check in</div>' : '')
        + (i.attendance_score != null ? '<div class="attend-lecture-meta">🎯 Attend and earn ' + esc(String(i.attendance_score)) + ' marks</div>' : '');
      return '<div class="attend-lecture">' +
        '<div class="attend-lecture-course">' + esc(i.course_code) + ' · ' + esc(i.course_name) + '</div>' +
        '<div class="attend-lecture-title">' + esc(i.title) + '</div>' +
        '<div class="attend-lecture-meta">👨‍🏫 ' + esc(i.lecturer_name) + '<br/>🕒 ' + new Date(i.scheduled_date).toLocaleString() + '</div>' +
        extra +
        '</div>';
    }

    function statusHtml(kind, msg) {
      var icon = { success: '✅', error: '❌', warn: '⚠️', info: 'ℹ️' }[kind] || 'ℹ️';
      return '<div class="attend-status ' + kind + '"><span>' + icon + '</span><span>' + msg + '</span></div>';
    }

    /* ── Shared: login form ── */
    function renderLogin(notice, afterLogin) {
      render(lectureCard() +
        (notice ? statusHtml('warn', esc(notice)) + '<div style="height:12px"></div>' : '') +
        '<div class="attend-status error" id="att-login-status" style="display:none;margin-bottom:14px"><span>❌</span><span id="att-login-status-msg"></span></div>' +
        '<div class="attend-login-title">Sign in to mark your attendance</div>' +
        '<div class="form-group"><label class="form-label">Matric Number</label>' +
          '<input class="input" id="att-matric" placeholder="SUMAS/CS/2023/001" autocomplete="username" required/></div>' +
        '<div class="form-group"><label class="form-label">Password</label>' +
          '<input class="input" id="att-pass" type="password" placeholder="••••••••" autocomplete="current-password" required/></div>' +
        '<button class="btn btn-primary attend-big-btn" id="att-login-btn">Sign in</button>' +
        '<div class="attend-foot">Use the same credentials as your student portal account.</div>');

      var doIt = async function () {
        var matric = document.getElementById('att-matric').value.trim();
        var password = document.getElementById('att-pass').value;
        if (!matric || !password) { loginError('Enter your matric number and password.'); return; }
        var btn = document.getElementById('att-login-btn');
        btn.disabled = true; btn.textContent = 'Signing in…';
        var res = await API.auth.login(matric, password);
        if (!res.ok || !res.data.token) {
          loginError(res.data.message || 'Invalid credentials. Please try again.');
          return;
        }
        SessionStore.save(res.data.token, res.data.user, 'student');
        state.student = res.data.user;
        afterLogin();
      };
      document.getElementById('att-login-btn').addEventListener('click', doIt);
      ['att-matric', 'att-pass'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); doIt(); } });
      });
    }

    function loginError(msg) {
      var st = document.getElementById('att-login-status');
      var msgEl = document.getElementById('att-login-status-msg');
      if (st && msgEl) { msgEl.textContent = msg || 'Sign in failed.'; st.style.display = 'flex'; }
      var btn = document.getElementById('att-login-btn');
      if (btn) { btn.disabled = false; btn.textContent = 'Sign in'; }
    }

    /* ── Check-in card (signed in): button, optional code entry, switch account ── */
    function renderCheckIn(label, onGo) {
      var codeInput = MODE === 'code'
        ? '<div class="form-group" style="margin-top:14px"><label class="form-label">6-digit code from your lecturer</label>' +
          '<input class="input attend-code-input" id="att-code" inputmode="numeric" maxlength="6" placeholder="••••••" value="' + esc(state.code || '') + '"/></div>' +
          '<div class="attend-status error" id="att-code-status" style="display:none;margin-bottom:14px"><span>❌</span><span id="att-code-status-msg"></span></div>'
        : '';

      render(lectureCard() +
        statusHtml('info', 'Signed in as <strong>' + esc((state.student || {}).name || 'Student') + '</strong> (' + esc((state.student || {}).matric || '') + ')') +
        codeInput +
        '<div style="margin-top:16px"><button class="btn btn-primary attend-big-btn" id="att-mark-btn">' + label + '</button></div>' +
        '<div class="attend-foot">Not you? <a href="#" id="att-switch" style="color:var(--brand);font-weight:700">Use a different account</a></div>');

      document.getElementById('att-mark-btn').addEventListener('click', onGo);
      document.getElementById('att-switch').addEventListener('click', function (e) {
        e.preventDefault(); SessionStore.clear(); renderLogin(null, goAfterLogin);
      });
      var codeInputEl = document.getElementById('att-code');
      if (codeInputEl) codeInputEl.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); onGo(); } });
    }

    /* ── Manual code entry (not signed in yet) ── */
    function renderCodeEntry(notice) {
      render(statusHtml('info', 'Enter the <strong>6-digit code</strong> shown on your lecturer’s screen to check in.') +
        (notice ? '<div style="height:12px"></div>' + statusHtml('warn', esc(notice)) : '') +
        '<div class="form-group" style="margin-top:14px"><label class="form-label">Code</label>' +
          '<input class="input attend-code-input" id="att-code" inputmode="numeric" maxlength="6" placeholder="••••••"/></div>' +
        '<div class="attend-status error" id="att-code-status" style="display:none;margin-bottom:14px"><span>❌</span><span id="att-code-status-msg"></span></div>' +
        '<button class="btn btn-primary attend-big-btn" id="att-code-btn">Continue</button>' +
        '<div class="attend-foot">You’ll sign in on the next step.</div>');

      var go = function () {
        var code = (document.getElementById('att-code').value || '').trim();
        if (!/^\d{6}$/.test(code)) {
          var st = document.getElementById('att-code-status');
          var msg = document.getElementById('att-code-status-msg');
          if (st && msg) { msg.textContent = 'Enter the 6-digit code exactly as shown.'; st.style.display = 'flex'; }
          return;
        }
        state.code = code;
        if (signedIn()) { state.student = SessionStore.getUser(); mark(); }
        else renderLogin(null, mark);
      };
      document.getElementById('att-code-btn').addEventListener('click', go);
      document.getElementById('att-code').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); go(); } });
    }

    function goAfterLogin() { renderCheckIn(MODE === 'code' ? '✅ Check In with Code' : '✅ Mark My Attendance', mark); }

    /* ── Submit ── */
    async function mark() {
      var btn = document.getElementById('att-mark-btn') || document.getElementById('att-code-btn') || document.getElementById('att-login-btn');
      if (btn) { btn.disabled = true; btn.textContent = 'Checking in…'; }
      var payload = { device_id: API.deviceId() };
      if (MODE === 'qr') payload.token = TOKEN;
      else payload.code = state.code;
      var pos = await API.getPosition(5000);
      if (pos) { payload.latitude = pos.lat; payload.longitude = pos.lng; }

      var res = await API.post('/student/attend', payload);

      if (res.ok) {
        var a = res.data.attendance || {};
        state.info = {
          course_code: a.course_code, course_name: a.course_name,
          lecturer_name: a.lecturer_name, scheduled_date: a.lecture_date, title: a.lecture_title,
          attendance_score: a.attendance_score,
        };
        var scoreLine = a.attendance_score != null
          ? '<br/><span style="font-weight:400">You earned <strong>+' + esc(String(a.attendance_score)) + ' marks</strong> for attending.</span>'
          : '';
        render(lectureCard() +
          statusHtml('success', '<div><strong>Attendance marked!</strong><br/><span style="font-weight:400">You are recorded as present for this lecture.</span>' + scoreLine + '</div>') +
          '<div style="margin-top:16px"><a href="/dashboard" class="btn btn-secondary attend-big-btn" style="text-decoration:none">Go to my portal →</a></div>');
        return;
      }

      var msg = res.data.message || 'Could not mark attendance.';

      if (res.status === 401) {
        SessionStore.clear();
        renderLogin('Your session expired. Please sign in again.', goAfterLogin);
        return;
      }
      if (res.status === 403) {
        render(lectureCard() +
          statusHtml('error', esc(msg)) +
          '<div class="attend-foot"><a href="#" id="att-switch2" style="color:var(--brand);font-weight:700">Use a different account</a></div>');
        document.getElementById('att-switch2').addEventListener('click', function (e) {
          e.preventDefault(); SessionStore.clear(); renderLogin(null, goAfterLogin);
        });
        return;
      }
      if (/Location access is required/i.test(msg)) {
        render(lectureCard() +
          statusHtml('error', esc(msg)) +
          '<div style="margin-top:16px"><button class="btn btn-primary attend-big-btn" id="att-loc-btn">📍 Allow location &amp; retry</button></div>');
        document.getElementById('att-loc-btn').addEventListener('click', async function () {
          var b = this; b.disabled = true; b.textContent = 'Getting location…';
          var pos = await API.getPosition(15000);
          if (pos) { mark(); return; }
          render(lectureCard() +
            statusHtml('error', 'Location is required for this lecture. Enable location (or GPS) in your browser settings and try again.') +
            '<div style="margin-top:16px"><button class="btn btn-primary attend-big-btn" onclick="location.reload()">Try again</button></div>');
        });
        return;
      }
      render(lectureCard() +
        statusHtml('error', esc(msg)) +
        '<div style="margin-top:16px"><button class="btn btn-secondary attend-big-btn" id="att-retry-btn">Try again</button></div>');
      document.getElementById('att-retry-btn').addEventListener('click', function () {
        if (MODE === 'qr') renderCheckIn('✅ Mark My Attendance', mark);
        else renderCodeEntry();
      });
    }

    /* ── Boot ── */
    async function boot() {
      if (MODE === 'qr') {
        var res = await API.get('/attend/' + encodeURIComponent(TOKEN), false);
        if (!res.ok || !res.data.lecture) {
          render(statusHtml('error', 'Invalid or expired attendance code.') +
            '<div class="attend-foot">Ask your lecturer to show the QR code again.</div>');
          return;
        }
        state.info = res.data.lecture;
        if (!state.info.is_active) { render(lectureCard() + statusHtml('warn', 'This lecture has ended. Attendance is closed.')); return; }
        if (state.info.attendance_enabled === false) { render(lectureCard() + statusHtml('warn', 'Smart attendance is not enabled for this lecture.')); return; }
        if (signedIn()) { state.student = SessionStore.getUser(); renderCheckIn('✅ Mark My Attendance', mark); }
        else renderLogin(null, goAfterLogin);
      } else {
        renderCodeEntry();
      }
    }

    boot();
  })();
  </script>
</body>
</html>
