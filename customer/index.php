<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vehicle Management System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* --- Base Styles --- */
    * { 
      margin: 0; 
      padding: 0; 
      box-sizing: border-box; 
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    }
    
    body { 
      background: #f8fafc; 
      color: #334155; 
      line-height: 1.6; 
      padding-top: 70px; 
    }
    
    .container { 
      max-width: 1200px; 
      margin: 0 auto; 
      padding: 0 20px; 
    }

    /* --- Navbar --- */
    .navbar { 
      position: fixed; 
      top: 0; 
      left: 0; 
      right: 0; 
      background: white; 
      box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
      z-index: 1000; 
    }
    
    .nav-container { 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      max-width: 1200px; 
      margin: 0 auto; 
      height: 70px; 
      padding: 0 20px; 
    }
    
    .logo { 
      display: flex; 
      align-items: center; 
      font-weight: 700; 
      font-size: 1.4rem; 
      color: #2563eb; 
    }
    
    .logo i { 
      margin-right: 8px; 
    }
    
    .nav-links { 
      display: flex; 
      list-style: none; 
    }
    
    .nav-links li { 
      margin-left: 30px; 
    }
    
    .nav-links a { 
      text-decoration: none; 
      color: #64748b; 
      font-weight: 500; 
      transition: 0.3s; 
      font-size: 0.95rem; 
      position: relative;
    }
    
    .nav-links a:hover, 
    .nav-links a.active { 
      color: #2563eb; 
    }
    
    .nav-links a.active::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 100%;
      height: 3px;
      background: #2563eb;
      border-radius: 2px;
    }

    /* --- Hero Section --- */
     .hero {
      padding: 80px 0;
      margin-bottom: 40px;
      position: relative;
      overflow: hidden;
    }
    
    .hero-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: relative;
      z-index: 3;
    }
    
    .hero-content {
      flex: 1;
      max-width: 600px;
      padding-right: 40px;
      animation: fadeInUp 1s ease-out;
    }
    
    .hero-visual {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
      height: 400px;
    }
    
    .welcome-text {
      font-size: 3rem;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 20px;
      line-height: 1.2;
    }
    
    .welcome-subtext {
      font-size: 1.2rem;
      color: #475569;
      margin-bottom: 35px;
      max-width: 500px;
    }
    
    .hero-cta {
      display: flex;
      gap: 15px;
    }
    
    .btn {
      padding: 14px 28px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-block;
      border: none;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .btn-primary {
      background: #2563eb;
      color: white;
    }
    
    .btn-primary:hover {
      background: #1d4ed8;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
    }
    
    .btn-secondary {
      background: white;
      color: #2563eb;
      border: 2px solid #2563eb;
    }
    
    .btn-secondary:hover {
      background: #f0f4ff;
      transform: translateY(-2px);
    }
    
    /* Animated car visualization */
    .car-animation {
      width: 300px;
      height: 200px;
      position: relative;
    }
    
    .car-body {
      width: 250px;
      height: 70px;
      background: #2563eb;
      border-radius: 15px 15px 5px 5px;
      position: absolute;
      bottom: 60px;
      left: 25px;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
      animation: carBounce 4s infinite ease-in-out;
    }
    
    .car-top {
      width: 180px;
      height: 50px;
      background: #3b82f6;
      border-radius: 15px 15px 0 0;
      position: absolute;
      bottom: 130px;
      left: 60px;
    }
    
    .wheel {
      width: 50px;
      height: 50px;
      background: #1e293b;
      border-radius: 50%;
      position: absolute;
      bottom: 40px;
      border: 5px solid #64748b;
      animation: wheelSpin 3s infinite linear;
    }
    
    .wheel-front {
      left: 50px;
    }
    
    .wheel-back {
      right: 50px;
    }
    
    .road {
      width: 100%;
      height: 40px;
      background: #475569;
      position: absolute;
      bottom: 0;
      border-radius: 5px;
      overflow: hidden;
    }
    
    .road-line {
      position: absolute;
      height: 5px;
      width: 40px;
      background: #e2e8f0;
      top: 50%;
      transform: translateY(-50%);
      left: -40px;
      animation: roadMove 2s infinite linear;
    }
    
    /* Stats section */
    .hero-stats {
      display: flex;
      gap: 30px;
      margin-top: 40px;
      animation: fadeIn 1.5s ease-out;
    }
    
    .stat-item {
      display: flex;
      flex-direction: column;
    }
    
    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: #2563eb;
    }
    
    .stat-label {
      font-size: 0.9rem;
      color: #64748b;
    }
    
    /* Background elements */
    .hero-bg-circle {
      position: absolute;
      border-radius: 50%;
      background: rgba(37, 99, 235, 0.05);
      z-index: 1;
    }
    
    .circle-1 {
      width: 300px;
      height: 300px;
      top: -150px;
      right: -150px;
      background: radial-gradient(circle, #4f83d6ff, #2b3a62ff);
    }
    
    .circle-2 {
      width: 200px;
      height: 200px;
      bottom: -100px;
      left: -100px;
      background: radial-gradient(circle, #3b82f6, #1e3a8a);
    }
    
    /* Animations */
    @keyframes carBounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
    
    @keyframes wheelSpin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    @keyframes roadMove {
      0% { left: -40px; }
      100% { left: 100%; }
    }
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(40px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    /* Responsive design */
    @media (max-width: 968px) {
      .hero-container {
        flex-direction: column;
        text-align: center;
      }
      
      .hero-content {
        padding-right: 0;
        margin-bottom: 40px;
      }
      
      .hero-cta {
        justify-content: center;
      }
      
      .hero-stats {
        justify-content: center;
      }
      
      .welcome-text {
        font-size: 2.5rem;
      }
    }
    
    @media (max-width: 768px) {
      .welcome-text {
        font-size: 2rem;
      }
      
      .hero-visual {
        height: 300px;
      }
      
      .car-animation {
        transform: scale(0.8);
      }
    }

    /* --- Section Titles --- */
    .section-title { 
      font-size: 1.8rem; 
      font-weight: 700; 
      margin-bottom: 25px; 
      color: #0f172a; 
      position: relative;
      padding-bottom: 10px;
    }
    
    .section-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 60px;
      height: 4px;
      background: #2563eb;
      border-radius: 2px;
    }

    /* --- Cards (Offers, News, Features) --- */
    .offers-grid, 
    .news-grid { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
      gap: 25px; 
      margin-bottom: 40px; 
    }
    
    .card { 
      background: #fff; 
      border-radius: 16px; 
      padding: 25px; 
      box-shadow: 0 6px 15px rgba(0,0,0,0.05);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      height: 100%;
      border: 1px solid rgba(0,0,0,0.03);
    }
    
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 20px rgba(0,0,0,0.1);
    }
    
    .card-header { 
      display: flex; 
      align-items: center; 
      margin-bottom: 15px; 
    }
    
    .card-icon { 
      width: 50px; 
      height: 50px; 
      background: rgba(37,99,235,0.1); 
      color: #2563eb; 
      border-radius: 12px; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      margin-right: 15px; 
      font-size: 1.2rem;
    }
    
    .card-title { 
      font-size: 1.2rem; 
      font-weight: 600; 
      color: #0f172a; 
    }
    
    .offer-tag { 
      display: inline-block; 
      background: #2563eb; 
      color: white; 
      font-size: 0.75rem; 
      padding: 4px 12px; 
      border-radius: 20px; 
      margin-bottom: 12px; 
      font-weight: 500;
    }
    
    .news-date { 
      font-size: 0.85rem; 
      color: #64748b; 
      margin-bottom: 10px; 
      display: flex;
      align-items: center;
    }
    
    .news-date i {
      margin-right: 8px;
      font-size: 0.9rem;
    }

    /* --- Feature Card --- */
    .feature-card { 
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); 
      color: white; 
      padding: 30px;
      position: relative;
      overflow: hidden;
    }
    
    .feature-card::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 100%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
    }
    
    .feature-card .card-icon {
      background: rgba(255, 255, 255, 0.2);
      color: white;
    }
    
    .feature-card .card-title,
    .feature-card p {
      color: white;
    }
    
    .feature-card p {
      opacity: 0.9;
      margin-bottom: 20px;
    }

    /* --- CTA --- */
    .cta-section { 
      text-align: center; 
      margin: 50px 0; 
    }
    
    .btn { 
      background: #2563eb; 
      color: white; 
      padding: 14px 28px; 
      border-radius: 10px; 
      text-decoration: none; 
      font-weight: 600; 
      transition: 0.3s;
      display: inline-block;
      border: none;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }
    
    .btn:hover { 
      background: #1d4ed8; 
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
    }
    
    .feature-card .btn {
      background: white;
      color: #2563eb;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .feature-card .btn:hover {
      background: #f0f4ff;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }

    /* --- Footer --- */
    footer {
      width: 100%;
      margin-top: 60px;
      padding: 30px 0;
      background: #1e293b;
      color: #cbd5e1;
      text-align: center;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    footer a {
      color: #93c5fd;
      text-decoration: none;
      transition: color 0.3s;
    }
    
    footer a:hover {
      color: #60a5fa;
      text-decoration: underline;
    }

    /* --- Responsive --- */
    @media (max-width: 968px) {
      .hero-container { 
        flex-direction: column; 
        text-align: center; 
        padding: 30px; 
      }
      
      .hero-content { 
        margin-bottom: 40px; 
        max-width: 100%; 
      }
      
      .welcome-text {
        font-size: 2.2rem;
      }
      
      .nav-links li {
        margin-left: 20px;
      }
    }
    
    @media (max-width: 768px) {
      .nav-links { 
        display: none; 
      }
      
      .welcome-text {
        font-size: 2rem;
      }
      
      .section-title {
        font-size: 1.6rem;
      }
      
      .offers-grid,
      .news-grid {
        grid-template-columns: 1fr;
      }
      
      .card {
        padding: 20px;
      }
    }

    /* Mobile menu button */
    .mobile-menu-btn {
      display: none;
      background: none;
      border: none;
      font-size: 1.5rem;
      color: #64748b;
      cursor: pointer;
    }
    
    @media (max-width: 768px) {
      .mobile-menu-btn {
        display: block;
      }
    }

  </style>
</head>
<body>

 
<nav class="fixed top-0 left-0 w-full z-50 bg-white shadow">
  <!-- navbar content -->
    <?php
$current_page = basename($_SERVER['PHP_SELF']);
include __DIR__ . '/../navbar.php';
?>
</nav>

  <div class="container">
   
   <section class="hero">
    <div class="hero-bg-circle circle-1"></div>
    <div class="hero-bg-circle circle-2"></div>
    
    <div class="hero-container">
      <div class="hero-content">
        <h1 class="welcome-text">Welcome back, Michael!</h1>
        <p class="welcome-subtext">Manage your vehicles, track services, and explore rental options all in one place with AutoManager.</p>
        
        <div class="hero-cta">
          <a href="#" class="btn btn-primary">Explore Features</a>
          <a href="#" class="btn btn-secondary">View Dashboard</a>
        </div>
        
        <div class="hero-stats">
          <div class="stat-item">
            <span class="stat-value">24/7</span>
            <span class="stat-label">Support</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">100+</span>
            <span class="stat-label">Vehicles</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">5★</span>
            <span class="stat-label">Rating</span>
          </div>
        </div>
      </div>
      
      <div class="hero-visual">
        <div class="car-animation">
          <div class="car-body"></div>
          <div class="car-top"></div>
          <div class="wheel wheel-front"></div>
          <div class="wheel wheel-back"></div>
          <div class="road">
            <div class="road-line"></div>
          </div>
        </div>
      </div>
    </div>
  </section>

    <!-- Offers -->
    <section class="offers-section">
      <h2 class="section-title">Running Offers</h2>
      <div class="offers-grid">
        <div class="card">
          <div class="card-header">
            <div class="card-icon"><i class="fas fa-tools"></i></div>
            <h3 class="card-title">Service Discount</h3>
          </div>
          <span class="offer-tag">20% OFF</span>
          <p>Get 20% off on complete vehicle servicing until the end of this month. Limited time offer!</p>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-icon"><i class="fas fa-snowflake"></i></div>
            <h3 class="card-title">Winter Check</h3>
          </div>
          <span class="offer-tag">SEASONAL DEAL</span>
          <p>Prepare your vehicle for winter with our comprehensive winter check package at a special price.</p>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-icon"><i class="fas fa-tire"></i></div>
            <h3 class="card-title">Tire Replacement</h3>
          </div>
          <span class="offer-tag">FREE INSTALLATION</span>
          <p>Buy a set of 4 premium tires and get free installation plus wheel alignment included.</p>
        </div>
      </div>
    </section>

    <!-- Feature -->
    <section class="feature-section">
      <h2 class="section-title">New Feature</h2>
      <div class="card feature-card">
        <div class="card-header">
          <div class="card-icon">
            <i class="fas fa-calendar-alt"></i>
          </div>
          <h3 class="card-title">Introducing Our Rental Car System</h3>
        </div>
        <p>We're excited to announce our new car rental service! Now you can easily rent vehicles for short-term needs directly through your dashboard.</p>
        <a href="#" class="btn">Learn More</a>
      </div>
    </section>

    <!-- News -->
    <section class="news-section">
      <h2 class="section-title">News & Updates</h2>
      <div class="news-grid">
        <div class="card">
          <div class="card-header">
            <div class="card-icon"><i class="fas fa-bolt"></i></div>
            <h3 class="card-title">Faster Booking System</h3>
          </div>
          <div class="news-date"><i class="far fa-calendar-alt"></i> October 15, 2023</div>
          <p>We've improved our service booking system with a 40% reduction in steps needed to schedule an appointment.</p>
        </div>
        
        <div class="card">
          <div class="card-header">
            <div class="card-icon"><i class="fas fa-credit-card"></i></div>
            <h3 class="card-title">New Payment Options</h3>
          </div>
          <div class="news-date"><i class="far fa-calendar-alt"></i> October 5, 2023</div>
          <p>We now support Apple Pay, Google Pay, and cryptocurrency in addition to traditional payment methods.</p>
        </div>
        
        <div class="card">
          <div class="card-header">
            <div class="card-icon"><i class="fas fa-user-cog"></i></div>
            <h3 class="card-title">Upgraded Profile Settings</h3>
          </div>
          <div class="news-date"><i class="far fa-calendar-alt"></i> September 28, 2023</div>
          <p>Enhanced profile management with new customization options and improved privacy controls.</p>
        </div>
      </div>
    </section>
  </div>

  <footer>
    <div class="container">
      <div class="mb-2">
        &copy; 2023 Vehicle Management System (VMS). All rights reserved.
      </div>
      <div>
        <a href="about.php">About</a>
        &middot;
        <a href="contact.php">Contact</a>
        &middot;
        <a href="privacy.php">Privacy Policy</a>
      </div>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      console.log('Dashboard loaded successfully');
      
      // Mobile menu toggle
      const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
      const navLinks = document.querySelector('.nav-links');
      
      if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
          navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
        });
      }
    });



    
    // Additional interactive animations
    document.addEventListener('DOMContentLoaded', function() {
      const car = document.querySelector('.car-body');
      
      // Make car interactive on hover
      car.addEventListener('mouseover', () => {
        car.style.animation = 'carBounce 0.8s infinite ease-in-out';
      });
      
      car.addEventListener('mouseout', () => {
        car.style.animation = 'carBounce 4s infinite ease-in-out';
      });
      
      // Add more road lines for better effect
      const road = document.querySelector('.road');
      for (let i = 0; i < 5; i++) {
        const line = document.createElement('div');
        line.className = 'road-line';
        line.style.animationDelay = `${i * 0.4}s`;
        road.appendChild(line);
      }
    });
  
  </script>
</body>
</html>