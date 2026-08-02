<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"/>
  <meta name="theme-color" content="#6B3318"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="description" content="SUMAS SmartAttend — AI-Powered Student Identity Verification & Smart Attendance for State University of Medical and Applied Sciences, Nigeria."/>
  <title>SUMAS SmartAttend — AI Academic Platform</title>
  <link rel="stylesheet" href="{{ asset('assets/css/design-system.css') }}"/>
  <link rel="stylesheet" href="{{ asset('assets/css/home.css') }}"/>
</head>
<body>

<!-- LOADER -->
<div id="page-loader">
  <div class="ldr-wrap">
    <div class="ldr-logo-row">
      <div class="ldr-crest"><img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS"/></div>
      <div class="ldr-text-col">
        <div class="ldr-uni">State University of Medical &amp; Applied Sciences</div>
        <div class="ldr-name">SUMAS <em>Smart</em>Attend</div>
        <div class="ldr-tag">Igbo Eno · Enugu State · Nigeria</div>
      </div>
    </div>
    <div class="ldr-divider"></div>
    <div class="ldr-bar-wrap"><div class="ldr-bar-track"><div class="ldr-bar-fill"></div></div></div>
    <div class="ldr-dots"><div class="ldr-dot"></div><div class="ldr-dot"></div><div class="ldr-dot"></div></div>
  </div>
</div>

<div id="toast-container"></div>

<!-- TOPBAR -->
<div class="topbar">
  <div class="container">
    <div class="topbar-inner">
      <div class="topbar-left">
        <div class="topbar-item">📍 Igbo Eno, Enugu State, Nigeria</div>
        <div class="topbar-item">✉️ info@sumas.edu.ng</div>
        <div class="topbar-item">📞 +234 800 SUMAS 00</div>
      </div>
      <div class="topbar-right">
        <a href="{{ route('login') }}" class="topbar-link">🔐 Student Portal</a>
        <span class="topbar-sep">·</span>
        <a href="{{ route('register') }}" class="topbar-link">📝 Register</a>
      </div>
    </div>
  </div>
</div>

<!-- NAVBAR -->
<nav class="navbar" role="navigation" aria-label="Main navigation">
  <div class="container">
    <div class="navbar-inner">
      <a href="{{ route('home') }}" class="nav-brand">
        <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS Crest" class="nav-logo"/>
        <div class="nav-brand-text">
          <span class="nav-brand-name">SUMAS SmartAttend</span>
          <span class="nav-brand-sub">AI Identity Verification</span>
        </div>
      </a>
      <ul class="nav-menu" role="list">
        <li><a href="{{ route('home') }}" class="nav-link active">Home</a></li>
        <li><a href="#about" class="nav-link">About</a></li>
        <li><a href="#how-it-works" class="nav-link">How It Works</a></li>
        <li><a href="#testimonials" class="nav-link">Testimonials</a></li>
        <li><a href="{{ route('register') }}#status-check" class="nav-link">Check Status</a></li>
      </ul>
      <div class="nav-actions">
        <button class="icon-btn theme-toggle" id="theme-toggle" aria-label="Toggle theme">🌙</button>
        <a href="{{ route('login') }}" class="btn btn-secondary btn-md">Sign In</a>
        <a href="{{ route('register') }}" class="btn btn-primary btn-md">Register Now</a>
        <button class="hamburger" id="hamburger" aria-label="Menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </div>
  <div class="mobile-nav" id="mobile-nav" role="menu">
    <a href="{{ route('home') }}" class="mobile-link active" role="menuitem">🏠 Home</a>
    <a href="#about" class="mobile-link" role="menuitem">🎓 About</a>
    <a href="#how-it-works" class="mobile-link" role="menuitem">📋 How It Works</a>
    <a href="#testimonials" class="mobile-link" role="menuitem">💬 Testimonials</a>
    <div class="mobile-sep"></div>
    <a href="{{ route('register') }}" class="mobile-link" role="menuitem" style="color:var(--brand);font-weight:700">📝 Register Now</a>
    <a href="{{ route('login') }}" class="mobile-link" role="menuitem">🔐 Student Portal</a>
  </div>
</nav>

<!-- ══════════════════════════════════════════
     HERO — WHITE BACKGROUND, CHOCOLATE BROWN TEXT
══════════════════════════════════════════ -->
<section class="hero" id="home" aria-label="Hero section">

  <!-- Right side photo carousel -->
  <div class="hero-photo-panel" aria-hidden="true">
    <div class="hero-slide active">
      <img src="{{ asset('assets/images/campus-gate.png') }}" alt="SUMAS Campus Gate"/>
      <div class="hero-slide-overlay"></div>
    </div>
    <div class="hero-slide">
      <img src="https://images.unsplash.com/photo-1529333166437-7750a6dd5a70?w=900&q=80" alt="Students in lecture"/>
      <div class="hero-slide-overlay"></div>
    </div>
    <div class="hero-slide">
      <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=900&q=80" alt="Student with laptop"/>
      <div class="hero-slide-overlay"></div>
    </div>
    <div class="hero-slide">
      <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80" alt="Medical students"/>
      <div class="hero-slide-overlay"></div>
    </div>
    <div class="hero-slide">
      <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=900&q=80" alt="University building"/>
      <div class="hero-slide-overlay"></div>
    </div>
    <div class="hero-dots" aria-label="Slide navigation">
      <button class="hero-dot active" aria-label="Slide 1"></button>
      <button class="hero-dot" aria-label="Slide 2"></button>
      <button class="hero-dot" aria-label="Slide 3"></button>
      <button class="hero-dot" aria-label="Slide 4"></button>
      <button class="hero-dot" aria-label="Slide 5"></button>
    </div>
  </div>

  <!-- Left text — chocolate brown on white -->
  <div class="container" style="position:relative;z-index:5;width:100%">
    <div class="hero-content fade-up">

      <div class="hero-badge">
        <div class="hero-badge-dot"></div>
        AI-Powered Smart Attendance · Igbo Eno, Enugu State
      </div>

      <h1 class="hero-title">
        <span class="hero-title-sub">State University of Medical &amp; Applied Sciences</span>
        Smart<span class="hero-title-accent">Attend</span>
      </h1>

      <div class="hero-rule"></div>

      <p class="hero-desc">
        The most advanced <strong>AI student identity verification</strong> and smart attendance system. Eliminating proxy signing, impersonation, and attendance fraud — permanently.
      </p>

      <div class="hero-cta">
        <a href="{{ route('register') }}" class="btn btn-primary btn-xl">📝 &nbsp;Begin Registration</a>
        <a href="{{ route('login') }}" class="btn btn-outline btn-xl">🔐 &nbsp;Student Portal</a>
      </div>

      <div class="hero-stats">
        <div>
          <div class="hero-stat-num">14+</div>
          <div class="hero-stat-lbl">Departments</div>
        </div>
        <div>
          <div class="hero-stat-num">99%</div>
          <div class="hero-stat-lbl">AI Accuracy</div>
        </div>
        <div>
          <div class="hero-stat-num">5,000+</div>
          <div class="hero-stat-lbl">Students</div>
        </div>
        <div>
          <div class="hero-stat-num">Zero</div>
          <div class="hero-stat-lbl">Fraud Tolerance</div>
        </div>
      </div>

      <!-- Mobile slideshow — shows below text on phones/tablets -->
      <div class="hero-mobile-slides" id="hero-mobile-slides">
        <div class="hero-slide active">
          <img src="{{ asset('assets/images/campus-gate.png') }}" alt="SUMAS Campus Gate"/>
          <div class="hero-slide-overlay"></div>
          <div class="hero-mobile-caption"><span class="hmc-tag">Our Campus</span><span class="hmc-text">State University of Medical &amp; Applied Sciences — Igbo Eno</span></div>
        </div>
        <div class="hero-slide">
          <img src="https://images.unsplash.com/photo-1529333166437-7750a6dd5a70?w=800&q=80" alt="Students in lecture"/>
          <div class="hero-slide-overlay"></div>
          <div class="hero-mobile-caption"><span class="hmc-tag">Smart Learning</span><span class="hmc-text">AI-verified attendance for every lecture and session</span></div>
        </div>
        <div class="hero-slide">
          <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=800&q=80" alt="Student with laptop"/>
          <div class="hero-slide-overlay"></div>
          <div class="hero-mobile-caption"><span class="hmc-tag">Digital Innovation</span><span class="hmc-text">Modern technology for world-class academic integrity</span></div>
        </div>
        <div class="hero-slide">
          <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&q=80" alt="Medical students"/>
          <div class="hero-slide-overlay"></div>
          <div class="hero-mobile-caption"><span class="hmc-tag">Medical Excellence</span><span class="hmc-text">Training Nigeria's next generation of medical professionals</span></div>
        </div>
        <div class="hero-slide">
          <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=800&q=80" alt="Campus"/>
          <div class="hero-slide-overlay"></div>
          <div class="hero-mobile-caption"><span class="hmc-tag">World-Class Facilities</span><span class="hmc-text">State-of-the-art infrastructure for cutting-edge research</span></div>
        </div>
        <div class="hero-mobile-arrows">
          <button class="hero-m-arrow" id="mob-prev" aria-label="Previous">&#8249;</button>
          <button class="hero-m-arrow" id="mob-next" aria-label="Next">&#8250;</button>
        </div>
        <div class="hero-mobile-dots" id="mob-dots">
          <button class="hero-mobile-dot active" aria-label="Slide 1"></button>
          <button class="hero-mobile-dot" aria-label="Slide 2"></button>
          <button class="hero-mobile-dot" aria-label="Slide 3"></button>
          <button class="hero-mobile-dot" aria-label="Slide 4"></button>
          <button class="hero-mobile-dot" aria-label="Slide 5"></button>
        </div>
      </div>
    </div>
  </div>

  <!-- Floating AI live card -->
  <div class="hero-float-ai" aria-live="polite">
    <div class="fai-header"><div class="fai-dot"></div> AI Verification Live</div>
    <div class="fai-row"><span>Face Detected</span><span class="ok">✓ Active</span></div>
    <div class="fai-row"><span>Liveness Check</span><span class="ok">✓ Pass</span></div>
    <div class="fai-row"><span>Identity Match</span><span class="pct">97.4%</span></div>
  </div>

  <!-- Floating VC card -->
  <div class="hero-float-vc">
    <img src="{{ asset('assets/images/vice-chancellor.png') }}" alt="Vice Chancellor" class="fvc-img"/>
    <div>
      <div class="fvc-name">Prof. James Chukwuma Ogbonna</div>
      <div class="fvc-title">Vice Chancellor, SUMAS</div>
    </div>
  </div>

  <div class="hero-scroll" role="button" tabindex="0"
       onclick="document.getElementById('trust-strip').scrollIntoView({behavior:'smooth'})">
    <div class="hero-scroll-lbl">Scroll</div>
    <div class="hero-scroll-line"></div>
  </div>
</section>

<!-- MARQUEE -->
<div class="marquee-strip" aria-hidden="true">
  <div class="marquee-track">
    <div class="marquee-item"><span class="marquee-dot"></span> AI-Powered Attendance</div>
    <div class="marquee-item"><span class="marquee-dot"></span> Zero Fraud Tolerance</div>
    <div class="marquee-item"><span class="marquee-dot"></span> Biometric Verification</div>
    <div class="marquee-item"><span class="marquee-dot"></span> 14+ Departments</div>
    <div class="marquee-item"><span class="marquee-dot"></span> 99% Accuracy</div>
    <div class="marquee-item"><span class="marquee-dot"></span> FaceNet Technology</div>
    <div class="marquee-item"><span class="marquee-dot"></span> Liveness Detection</div>
    <div class="marquee-item"><span class="marquee-dot"></span> Anti-Spoofing Shield</div>
    <div class="marquee-item"><span class="marquee-dot"></span> Student Dashboard</div>
    <div class="marquee-item"><span class="marquee-dot"></span> Academic Excellence</div>
    <div class="marquee-item"><span class="marquee-dot"></span> AI-Powered Attendance</div>
    <div class="marquee-item"><span class="marquee-dot"></span> Zero Fraud Tolerance</div>
    <div class="marquee-item"><span class="marquee-dot"></span> Biometric Verification</div>
    <div class="marquee-item"><span class="marquee-dot"></span> 14+ Departments</div>
    <div class="marquee-item"><span class="marquee-dot"></span> 99% Accuracy</div>
    <div class="marquee-item"><span class="marquee-dot"></span> FaceNet Technology</div>
    <div class="marquee-item"><span class="marquee-dot"></span> Liveness Detection</div>
    <div class="marquee-item"><span class="marquee-dot"></span> Anti-Spoofing Shield</div>
    <div class="marquee-item"><span class="marquee-dot"></span> Student Dashboard</div>
    <div class="marquee-item"><span class="marquee-dot"></span> Academic Excellence</div>
  </div>
</div>

<!-- TRUST STRIP — static numbers, no animation -->
<section class="trust-strip" id="trust-strip">
  <div class="container">
    <div class="trust-grid">
      <div class="trust-item fade-up">
        <div class="trust-num">14+</div>
        <div class="trust-lbl">Departments</div>
      </div>
      <div class="trust-sep"></div>
      <div class="trust-item fade-up">
        <div class="trust-num">5,000+</div>
        <div class="trust-lbl">Students Enrolled</div>
      </div>
      <div class="trust-sep"></div>
      <div class="trust-item fade-up">
        <div class="trust-num">99%</div>
        <div class="trust-lbl">Verification Accuracy</div>
      </div>
      <div class="trust-sep"></div>
      <div class="trust-item fade-up">
        <div class="trust-num">6</div>
        <div class="trust-lbl">AI Security Layers</div>
      </div>
      <div class="trust-sep"></div>
      <div class="trust-item fade-up">
        <div class="trust-num">0%</div>
        <div class="trust-lbl">Fraud Tolerance</div>
      </div>
    </div>
  </div>
</section>

<!-- ABOUT -->
<section class="about-strip section" id="about">
  <div class="container">
    <div class="about-grid">
      <div class="about-img-wrap fade-in">
        <img src="{{ asset('assets/images/campus-gate.png') }}" alt="SUMAS Campus" class="about-main-img"/>
        <div class="about-badge">
          <div class="num">2021</div>
          <div class="lbl">Est. Igbo Eno</div>
        </div>
      </div>
      <div>
        <div class="section-header" style="text-align:left;margin:0 0 var(--s5)">
          <div class="section-eyebrow fade-up">About The Project</div>
          <h2 class="section-title fade-up">Smart Attendance Using <em>Facial Recognition</em></h2>
          <div class="section-rule fade-up" style="margin-left:0"></div>
        </div>
        <p class="fade-up" style="font-size:1rem;color:var(--t-secondary);margin-bottom:var(--s4);line-height:1.8">
          SUMAS SmartAttend is an AI-powered student identity verification and attendance management system built exclusively for the <strong style="color:var(--t-primary)">State University of Medical and Applied Sciences (SUMAS)</strong>, Igbo Eno, Enugu State, Nigeria.
        </p>
        <p class="fade-up" style="font-size:.95rem;color:var(--t-muted);margin-bottom:var(--s5);line-height:1.75">
          Designed to permanently eliminate proxy signing, impersonation, and attendance fraud — ensuring only verified, physically present students are recorded.
        </p>
        <ul class="about-bullets fade-up">
          <li class="about-bullet"><span class="ab-dot"></span><span>Prevents all forms of attendance fraud and impersonation</span></li>
          <li class="about-bullet"><span class="ab-dot"></span><span>Powered by FaceNet, DeepFace, and MediaPipe AI models</span></li>
          <li class="about-bullet"><span class="ab-dot"></span><span>Lecturer companion mobile app (coming soon)</span></li>
          <li class="about-bullet"><span class="ab-dot"></span><span>Automated real-time reports for faculty and administration</span></li>
          <li class="about-bullet"><span class="ab-dot"></span><span>End-to-end encrypted biometric data storage</span></li>
        </ul>
        <div style="display:flex;gap:var(--s4);margin-top:var(--s6);flex-wrap:wrap" class="fade-up">
          <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Begin Registration →</a>
          <a href="{{ route('login') }}" class="btn btn-secondary btn-lg">Student Portal</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════
     HOW IT WORKS — PREMIUM NUMBERED FLOW
══════════════════════════════════════════ -->
<section class="how-section section" id="how-it-works">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow fade-up">Registration Process</div>
      <h2 class="section-title fade-up">How <em>SmartAttend</em> Works</h2>
      <div class="section-rule fade-up"></div>
      <p class="section-subtitle fade-up">Complete your AI identity verification in 7 guided steps — secure, fast, and fraud-proof.</p>
    </div>

    <div class="how-steps">
      <div class="step-item fade-up" style="transition-delay:0ms">
        <div class="step-num-circle">
          <span class="step-number">1</span>
          <span class="step-icon">🏛</span>
        </div>
        <div class="step-title">Select Department</div>
        <div class="step-desc">Choose your faculty from 14+ academic programs</div>
      </div>
      <div class="step-item fade-up" style="transition-delay:80ms">
        <div class="step-num-circle">
          <span class="step-number">2</span>
          <span class="step-icon">📚</span>
        </div>
        <div class="step-title">Choose Level</div>
        <div class="step-desc">Select your academic year — 100 to 600 Level</div>
      </div>
      <div class="step-item fade-up" style="transition-delay:160ms">
        <div class="step-num-circle">
          <span class="step-number">3</span>
          <span class="step-icon">👤</span>
        </div>
        <div class="step-title">Personal Info</div>
        <div class="step-desc">Name, matric number, email and contact details</div>
      </div>
      <div class="step-item fade-up" style="transition-delay:240ms">
        <div class="step-num-circle">
          <span class="step-number">4</span>
          <span class="step-icon">📄</span>
        </div>
        <div class="step-title">Upload Documents</div>
        <div class="step-desc">School ID, admission letter, department clearance</div>
      </div>
      <div class="step-item fade-up" style="transition-delay:320ms">
        <div class="step-num-circle">
          <span class="step-number">5</span>
          <span class="step-icon">📸</span>
        </div>
        <div class="step-title">Passport Photos</div>
        <div class="step-desc">Upload 3 clear passport photographs for biometrics</div>
      </div>
      <div class="step-item fade-up" style="transition-delay:400ms">
        <div class="step-num-circle">
          <span class="step-number">6</span>
          <span class="step-icon">🤖</span>
        </div>
        <div class="step-title">AI Face Scan</div>
        <div class="step-desc">Live facial recognition with liveness detection</div>
      </div>
      <div class="step-item fade-up" style="transition-delay:480ms">
        <div class="step-num-circle">
          <span class="step-number">7</span>
          <span class="step-icon">✅</span>
        </div>
        <div class="step-title">Submit &amp; Track</div>
        <div class="step-desc">Review, submit and track status on your dashboard</div>
      </div>
    </div>

    <div style="text-align:center;margin-top:var(--s12)" class="fade-up">
      <a href="{{ route('register') }}" class="btn btn-primary btn-xl">Begin Registration →</a>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonials-section section" id="testimonials">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow fade-up">Student Voices</div>
      <h2 class="section-title fade-up">What <em>Students Say</em></h2>
      <div class="section-rule fade-up"></div>
    </div>
    <div class="testimonials-grid">
      <div class="testimonial-card fade-up">
        <div class="t-stars">⭐⭐⭐⭐⭐</div>
        <p class="t-text">"SmartAttend has completely transformed how attendance is taken. No more proxy signing — every student is verified in seconds. It feels like a system from a top-tier university."</p>
        <div class="t-author">
          <div class="t-avatar">👨🏿‍🎓</div>
          <div><div class="t-name">Chukwuemeka Obi</div><div class="t-info">Computer Science · 300 Level</div></div>
        </div>
      </div>
      <div class="testimonial-card fade-up" style="transition-delay:80ms">
        <div class="t-stars">⭐⭐⭐⭐⭐</div>
        <p class="t-text">"The AI face verification is incredibly accurate. I registered in under 15 minutes, and the dashboard shows everything in real-time. This is exactly what SUMAS needed."</p>
        <div class="t-author">
          <div class="t-avatar">👩🏿‍⚕️</div>
          <div><div class="t-name">Adaeze Nwosu</div><div class="t-info">Nursing Science · 200 Level</div></div>
        </div>
      </div>
      <div class="testimonial-card fade-up" style="transition-delay:160ms">
        <div class="t-stars">⭐⭐⭐⭐⭐</div>
        <p class="t-text">"As a medical student, attendance is critical for clinical practice. SmartAttend ensures my records are always accurate and tamper-proof. The design is world-class."</p>
        <div class="t-author">
          <div class="t-avatar">👨🏿‍⚕️</div>
          <div><div class="t-name">Dr. Emeka Eze</div><div class="t-info">Medicine &amp; Surgery · 500 Level</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- NEWS -->
<section class="news-section section">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow fade-up">News &amp; Updates</div>
      <h2 class="section-title fade-up">Latest from <em>SUMAS</em></h2>
      <div class="section-rule fade-up"></div>
    </div>
    <div class="news-grid">
      <div class="fade-up">
        <img src="{{ asset('assets/images/campus-gate.png') }}" alt="SUMAS News" class="news-feature-img"/>
        <div class="news-tag">Announcement</div>
        <h3 class="news-feature-title">SUMAS SmartAttend AI Registration Now Open for All Students</h3>
        <p class="news-feature-body">All students are required to complete AI identity verification registration before the commencement of the 2025/2026 academic session. The system is now fully operational for all 14+ departments.</p>
        <a href="{{ route('register') }}" class="btn btn-primary btn-md">Register Now →</a>
      </div>
      <div class="news-items fade-up" style="transition-delay:80ms">
        <div class="news-item">
          <div class="news-date"><div class="news-date-day">15</div><div class="news-date-mon">Jan</div></div>
          <div><div class="news-title">New AI Model Update: 99.4% Face Recognition Accuracy Achieved</div><div class="news-meta">AI Technology · 3 min read</div></div>
        </div>
        <div class="news-item">
          <div class="news-date"><div class="news-date-day">10</div><div class="news-date-mon">Jan</div></div>
          <div><div class="news-title">SmartAttend Lecturer Mobile App Development Announced</div><div class="news-meta">Product Update · 2 min read</div></div>
        </div>
        <div class="news-item">
          <div class="news-date"><div class="news-date-day">05</div><div class="news-date-mon">Jan</div></div>
          <div><div class="news-title">Department of Computer Science Achieves 100% Registration Rate</div><div class="news-meta">Academic News · 4 min read</div></div>
        </div>
        <div class="news-item">
          <div class="news-date"><div class="news-date-day">28</div><div class="news-date-mon">Dec</div></div>
          <div><div class="news-title">SUMAS Administration Endorses SmartAttend for All Faculties</div><div class="news-meta">Administration · 3 min read</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <div class="cta-bg"></div><div class="cta-glow"></div>
  <div class="container">
    <div class="cta-content fade-up">
      <div class="cta-eyebrow">Join SUMAS SmartAttend</div>
      <h2 class="cta-title">Ready to Register for <em>SmartAttend</em>?</h2>
      <p class="cta-sub">Complete your AI identity verification today. Join thousands of SUMAS students already enrolled in the most advanced attendance system in Nigeria.</p>
      <div class="cta-actions">
        <a href="{{ route('register') }}" class="btn btn-gold btn-xl">📝 Register Now →</a>
        <a href="{{ route('login') }}" class="btn btn-outline-white btn-xl">🔐 Student Portal</a>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-logo-row">
          <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS" class="footer-logo"/>
          <div><div class="footer-brand-name">SUMAS SmartAttend</div><div class="footer-brand-sub">AI Attendance System</div></div>
        </div>
        <p class="footer-desc">World-class AI-powered student identity verification and smart attendance system for the State University of Medical and Applied Sciences, Igbo Eno, Enugu State, Nigeria.</p>
        <div class="footer-socials">
          <a href="#" class="social-btn" aria-label="Facebook">📘</a>
          <a href="#" class="social-btn" aria-label="Twitter">𝕏</a>
          <a href="#" class="social-btn" aria-label="Instagram">📸</a>
          <a href="#" class="social-btn" aria-label="LinkedIn">💼</a>
        </div>
      </div>
      <div>
        <div class="footer-col-title">Quick Links</div>
        <div class="footer-links">
          <a href="{{ route('home') }}" class="footer-link">Home</a>
          <a href="{{ route('register') }}" class="footer-link">Student Registration</a>
          <a href="{{ route('login') }}" class="footer-link">Student Portal</a>
          <a href="#how-it-works" class="footer-link">How It Works</a>
          <a href="#about" class="footer-link">About SmartAttend</a>
        </div>
      </div>
      <div>
        <div class="footer-col-title">Faculties</div>
        <div class="footer-links">
          <a href="#" class="footer-link">Clinical Sciences</a>
          <a href="#" class="footer-link">Basic Medical Sciences</a>
          <a href="#" class="footer-link">Health Sciences &amp; Tech</a>
          <a href="#" class="footer-link">Applied Sciences</a>
          <a href="#" class="footer-link">Pharmaceutical Sciences</a>
        </div>
      </div>
      <div>
        <div class="footer-col-title">Contact</div>
        <div class="footer-contact-item"><span class="footer-contact-icon">📍</span><span>SUMAS Campus, Igbo Eno, Enugu State, Nigeria</span></div>
        <div class="footer-contact-item"><span class="footer-contact-icon">✉️</span><span>smartattend@sumas.edu.ng</span></div>
        <div class="footer-contact-item"><span class="footer-contact-icon">📞</span><span>+234 800 SUMAS 00</span></div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2025 State University of Medical and Applied Sciences (SUMAS). All rights reserved.</span>
      <span>SmartAttend Research Project &nbsp;·&nbsp; <a href="#">Privacy Policy</a></span>
    </div>
  </div>
</footer>

<script src="{{ asset('assets/js/api.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
