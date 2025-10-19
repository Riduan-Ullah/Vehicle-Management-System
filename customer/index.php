<?php
// Start session and include database connection
session_start();
require_once __DIR__ . '/../config/db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Fetch statistics from database
$total_cars_sql = "SELECT COUNT(*) as total_cars FROM cars WHERE quantity > 0";
$total_cars_result = $conn->query($total_cars_sql);
$total_cars = $total_cars_result->fetch_assoc()['total_cars'];

$total_models_sql = "SELECT COUNT(DISTINCT brand) as total_models FROM cars WHERE quantity > 0";
$total_models_result = $conn->query($total_models_sql);
$total_models = $total_models_result->fetch_assoc()['total_models'];

$available_cars_sql = "SELECT SUM(quantity) as available_cars FROM cars WHERE quantity > 0";
$available_cars_result = $conn->query($available_cars_sql);
$available_cars = $available_cars_result->fetch_assoc()['available_cars'];

$total_services_sql = "SELECT COUNT(*) as total_services FROM services";
$total_services_result = $conn->query($total_services_sql);
$total_services = $total_services_result->fetch_assoc()['total_services'];

// Fetch data for charts
// 1. Car Distribution by Fuel Type
$fuel_type_sql = "SELECT fuel_type, COUNT(*) as total FROM cars WHERE quantity > 0 GROUP BY fuel_type";
$fuel_type_result = $conn->query($fuel_type_sql);
$fuel_types = [];
$fuel_totals = [];
while($row = $fuel_type_result->fetch_assoc()) {
    $fuel_types[] = $row['fuel_type'];
    $fuel_totals[] = $row['total'];
}

// 2. Parts Stock by Category
$parts_stock_sql = "SELECT category, SUM(stock_quantity) as total_stock FROM parts GROUP BY category";
$parts_stock_result = $conn->query($parts_stock_sql);
$part_categories = [];
$part_stocks = [];
while($row = $parts_stock_result->fetch_assoc()) {
    $part_categories[] = $row['category'];
    $part_stocks[] = $row['total_stock'];
}

// 3. Service Types Popularity
$service_types_sql = "SELECT service_type, COUNT(*) as total_services FROM services GROUP BY service_type";
$service_types_result = $conn->query($service_types_sql);
$service_types = [];
$service_totals = [];
while($row = $service_types_result->fetch_assoc()) {
    $service_types[] = $row['service_type'];
    $service_totals[] = $row['total_services'];
}

// Fetch latest cars from database (newest first, limit to 3)
$cars_sql = "SELECT * FROM cars WHERE quantity > 0 ORDER BY created_at DESC LIMIT 3";
$cars_result = $conn->query($cars_sql);

// Fetch featured services
$services_sql = "SELECT * FROM services ORDER BY sid DESC LIMIT 3";
$services_result = $conn->query($services_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wheelsdoc | Premium Vehicle Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* --- CSS Variables --- */
    :root {
      --primary: #2563eb;
      --primary-dark: #1d4ed8;
      --primary-light: #3b82f6;
      --secondary: #f59e0b;
      --accent: #8b5cf6;
      --success: #10b981;
      --warning: #f59e0b;
      --error: #ef4444;
      --info: #06b6d4;
      --dark: #0f172a;
      --darker: #020617;
      --light: #f8fafc;
      --gray: #64748b;
      --gray-light: #cbd5e1;
      --gradient-primary: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      --gradient-secondary: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
      --gradient-accent: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
      --gradient-dark: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      --border-radius: 16px;
      --border-radius-lg: 24px;
    }

    /* --- Base Styles --- */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      background: var(--light);
      color: #334155;
      line-height: 1.6;
      overflow-x: hidden;
    }

    .container {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* --- Navbar --- */
    .navbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      box-shadow: var(--shadow-sm);
      z-index: 1000;
      transition: all 0.3s ease;
    }

    .nav-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1280px;
      margin: 0 auto;
      height: 80px;
      padding: 0 20px;
    }

    .logo {
      display: flex;
      align-items: center;
      font-weight: 800;
      font-size: 1.8rem;
      color: var(--primary);
      text-decoration: none;
    }

    .logo i {
      margin-right: 10px;
      font-size: 2rem;
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
      color: var(--gray);
      font-weight: 600;
      transition: 0.3s;
      font-size: 0.95rem;
      position: relative;
    }

    .nav-links a:hover,
    .nav-links a.active {
      color: var(--primary);
    }

    .nav-links a.active::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 100%;
      height: 3px;
      background: var(--primary);
      border-radius: 2px;
    }

    .nav-cta {
      display: flex;
      gap: 15px;
    }

    /* --- Hero Section --- */
    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      position: relative;
      background: var(--gradient-dark);
      color: white;
      overflow: hidden;
      padding-top: 80px;
    }

    .hero-bg {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('https://images.unsplash.com/photo-1494976388531-d1058494cdd8?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80') no-repeat center center/cover;
      opacity: 0.3;
      z-index: 1;
    }

    .hero-content {
      max-width: 650px;
      z-index: 2;
      position: relative;
      animation: fadeInUp 1s ease-out;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      background: rgba(37, 99, 235, 0.2);
      color: #93c5fd;
      padding: 8px 16px;
      border-radius: 100px;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 20px;
      border: 1px solid rgba(37, 99, 235, 0.3);
    }

    .hero-badge i {
      margin-right: 8px;
    }

    .hero-title {
      font-size: 3.8rem;
      font-weight: 800;
      margin-bottom: 20px;
      line-height: 1.1;
      background: linear-gradient(to right, #fff, #cbd5e1);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .hero-subtitle {
      font-size: 1.3rem;
      color: #cbd5e1;
      margin-bottom: 35px;
      max-width: 500px;
    }

    .hero-stats {
      display: flex;
      gap: 40px;
      margin-top: 40px;
    }

    .stat-item {
      text-align: center;
    }

    .stat-value {
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--secondary);
      display: block;
      line-height: 1;
    }

    .stat-label {
      font-size: 0.9rem;
      color: #94a3b8;
      margin-top: 5px;
    }

    .btn {
      padding: 16px 32px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: none;
      cursor: pointer;
      font-size: 1rem;
      gap: 8px;
    }

    .btn-primary {
      background: var(--gradient-primary);
      color: white;
      box-shadow: var(--shadow-md);
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-lg);
    }

    .btn-secondary {
      background: transparent;
      color: white;
      border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-3px);
    }

    .hero-visual {
      position: absolute;
      right: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 50%;
      height: 70%;
      z-index: 2;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .floating-card {
      position: absolute;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: var(--border-radius);
      padding: 20px;
      box-shadow: var(--shadow-lg);
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: float 6s ease-in-out infinite;
    }

    .floating-card:nth-child(1) {
      top: 20%;
      right: 10%;
      width: 200px;
      animation-delay: 0s;
    }

    .floating-card:nth-child(2) {
      top: 50%;
      right: 25%;
      width: 180px;
      animation-delay: 2s;
    }

    .floating-card:nth-child(3) {
      bottom: 20%;
      right: 15%;
      width: 220px;
      animation-delay: 4s;
    }

    .card-icon {
      width: 50px;
      height: 50px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 15px;
      color: white;
      font-size: 1.5rem;
    }

    .card-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 8px;
      color: white;
    }

    .card-text {
      font-size: 0.9rem;
      color: #cbd5e1;
    }

    /* --- Enhanced Stats Section --- */
    .stats-section {
      padding: 100px 0;
      background: linear-gradient(135deg, #f0f4ff 0%, #f8fafc 100%);
      position: relative;
      overflow: hidden;
    }

    .stats-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" opacity="0.03"><polygon fill="%232563eb" points="0,1000 1000,0 1000,1000"/></svg>');
      background-size: cover;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 30px;
      position: relative;
      z-index: 2;
    }

    .stat-card {
      background: white;
      border-radius: var(--border-radius);
      padding: 40px 30px;
      box-shadow: var(--shadow-lg);
      text-align: center;
      transition: all 0.4s ease;
      border: 2px solid transparent;
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: var(--gradient-primary);
      opacity: 0;
      transition: opacity 0.3s ease;
      z-index: 1;
    }

    .stat-card:hover::before {
      opacity: 0.03;
    }

    .stat-card:hover {
      transform: translateY(-10px);
      box-shadow: var(--shadow-xl);
      border-color: var(--primary-light);
    }

    .stat-card:nth-child(1) {
      border-color: rgba(37, 99, 235, 0.3);
    }

    .stat-card:nth-child(2) {
      border-color: rgba(245, 158, 11, 0.3);
    }

    .stat-card:nth-child(3) {
      border-color: rgba(139, 92, 246, 0.3);
    }

    .stat-card:nth-child(4) {
      border-color: rgba(16, 185, 129, 0.3);
    }

    .stat-icon {
      width: 80px;
      height: 80px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 25px;
      font-size: 2.2rem;
      transition: all 0.3s ease;
      position: relative;
      z-index: 2;
    }

    .stat-card:nth-child(1) .stat-icon {
      background: var(--gradient-primary);
      color: white;
      box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
    }

    .stat-card:nth-child(2) .stat-icon {
      background: var(--gradient-secondary);
      color: white;
      box-shadow: 0 10px 20px rgba(245, 158, 11, 0.3);
    }

    .stat-card:nth-child(3) .stat-icon {
      background: var(--gradient-accent);
      color: white;
      box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3);
    }

    .stat-card:nth-child(4) .stat-icon {
      background: var(--gradient-success);
      color: white;
      box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
    }

    .stat-card:hover .stat-icon {
      transform: scale(1.1) rotate(5deg);
    }

    .stat-card h3 {
      font-size: 2.8rem;
      font-weight: 800;
      color: var(--dark);
      margin-bottom: 10px;
      line-height: 1;
      position: relative;
      z-index: 2;
    }

    .stat-card p {
      color: var(--gray);
      font-size: 1.1rem;
      position: relative;
      z-index: 2;
    }

    /* --- Charts Section --- */
    .charts-section {
      padding: 100px 0;
      background: white;
    }

    .section-title {
      font-size: 2.8rem;
      font-weight: 800;
      margin-bottom: 60px;
      color: var(--dark);
      text-align: center;
      position: relative;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: -15px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 5px;
      background: var(--primary);
      border-radius: 5px;
    }

    .charts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
      gap: 30px;
    }

    .chart-container {
      background: white;
      border-radius: var(--border-radius);
      padding: 30px;
      box-shadow: var(--shadow-md);
      border: 1px solid #f1f5f9;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .chart-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
      background: var(--primary);
    }

    .chart-container:nth-child(2)::before {
      background: var(--secondary);
    }

    .chart-container:nth-child(3)::before {
      background: var(--accent);
    }

    .chart-container:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-lg);
    }

    .chart-header {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .chart-icon {
      width: 50px;
      height: 50px;
      background: rgba(37, 99, 235, 0.1);
      color: var(--primary);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      font-size: 1.5rem;
    }

    .chart-container:nth-child(2) .chart-icon {
      background: rgba(245, 158, 11, 0.1);
      color: var(--secondary);
    }

    .chart-container:nth-child(3) .chart-icon {
      background: rgba(139, 92, 246, 0.1);
      color: var(--accent);
    }

    .chart-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--dark);
    }

    .chart-subtitle {
      color: var(--gray);
      font-size: 0.9rem;
    }

    .chart-wrapper {
      height: 300px;
      position: relative;
    }

    /* --- Features Section --- */
    .features-section {
      padding: 100px 0;
      background: #f8fafc;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 40px;
    }

    .feature-card {
      background: white;
      border-radius: var(--border-radius);
      padding: 40px 30px;
      box-shadow: var(--shadow-md);
      transition: all 0.4s ease;
      border: 1px solid #f1f5f9;
      position: relative;
      overflow: hidden;
    }

    .feature-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: var(--gradient-primary);
      opacity: 0;
      transition: opacity 0.3s ease;
      z-index: 0;
    }

    .feature-card:hover::before {
      opacity: 0.03;
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: var(--shadow-xl);
    }

    .feature-icon {
      width: 80px;
      height: 80px;
      background: rgba(37, 99, 235, 0.1);
      color: var(--primary);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 25px;
      font-size: 2rem;
      transition: all 0.3s ease;
      position: relative;
      z-index: 1;
    }

    .feature-card:hover .feature-icon {
      transform: scale(1.1) rotate(5deg);
      background: var(--gradient-primary);
      color: white;
    }

    .feature-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 15px;
      color: var(--dark);
      position: relative;
      z-index: 1;
    }

    .feature-description {
      color: var(--gray);
      margin-bottom: 25px;
      position: relative;
      z-index: 1;
    }

    .feature-link {
      display: inline-flex;
      align-items: center;
      color: var(--primary);
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      position: relative;
      z-index: 1;
    }

    .feature-link i {
      margin-left: 8px;
      transition: transform 0.3s ease;
    }

    .feature-link:hover i {
      transform: translateX(5px);
    }

    /* --- Testimonials Section --- */
    .testimonials-section {
      padding: 100px 0;
      background: white;
    }

    .testimonials-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 30px;
    }

    .testimonial-card {
      background: white;
      border-radius: var(--border-radius);
      padding: 40px 30px;
      box-shadow: var(--shadow-md);
      border: 1px solid #f1f5f9;
      position: relative;
      transition: all 0.3s ease;
    }

    .testimonial-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-lg);
    }

    .testimonial-card::before {
      content: '"';
      position: absolute;
      top: 20px;
      right: 30px;
      font-size: 5rem;
      color: rgba(37, 99, 235, 0.1);
      font-family: Georgia, serif;
      line-height: 1;
    }

    .testimonial-content {
      margin-bottom: 25px;
      color: var(--gray);
      font-style: italic;
      font-size: 1.05rem;
      position: relative;
      z-index: 1;
    }

    .testimonial-author {
      display: flex;
      align-items: center;
    }

    .author-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 15px;
      border: 3px solid #f1f5f9;
    }

    .author-info h4 {
      font-weight: 700;
      color: var(--dark);
      margin-bottom: 5px;
    }

    .author-info p {
      color: var(--gray);
      font-size: 0.9rem;
    }

    .rating {
      color: var(--secondary);
      margin-top: 5px;
      font-size: 0.9rem;
    }

    /* --- News Section --- */
    .news-section {
      padding: 100px 0;
      background: #f8fafc;
    }

    .news-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 30px;
    }

    .news-card {
      background: white;
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: var(--shadow-md);
      transition: all 0.3s ease;
      border: 1px solid #f1f5f9;
    }

    .news-card:hover {
      transform: translateY(-10px);
      box-shadow: var(--shadow-xl);
    }

    .news-image {
      width: 100%;
      height: 220px;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .news-card:hover .news-image {
      transform: scale(1.05);
    }

    .news-content {
      padding: 25px;
    }

    .news-date {
      color: var(--gray);
      font-size: 0.9rem;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
    }

    .news-date i {
      margin-right: 8px;
      color: var(--primary);
    }

    .news-title {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 12px;
      color: var(--dark);
      line-height: 1.3;
    }

    .news-excerpt {
      color: var(--gray);
      margin-bottom: 20px;
      line-height: 1.6;
    }

    /* --- CTA Section --- */
    .cta-section {
      padding: 120px 0;
      background: var(--gradient-primary);
      color: white;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .cta-section::before {
      content: '';
      position: absolute;
      top: -100px;
      right: -100px;
      width: 400px;
      height: 400px;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
      border-radius: 50%;
    }

    .cta-section::after {
      content: '';
      position: absolute;
      bottom: -100px;
      left: -100px;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
      border-radius: 50%;
    }

    .cta-title {
      font-size: 3rem;
      font-weight: 800;
      margin-bottom: 20px;
      position: relative;
      z-index: 1;
    }

    .cta-subtitle {
      font-size: 1.3rem;
      margin-bottom: 40px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
      opacity: 0.9;
      position: relative;
      z-index: 1;
    }

    .cta-buttons {
      display: flex;
      gap: 20px;
      justify-content: center;
      position: relative;
      z-index: 1;
    }

    .cta-section .btn {
      padding: 18px 36px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 1.1rem;
    }

    .cta-section .btn-primary {
      background: white;
      color: var(--primary);
    }

    .cta-section .btn-primary:hover {
      background: #f0f4ff;
      transform: translateY(-3px);
      box-shadow: var(--shadow-lg);
    }

    .cta-section .btn-secondary {
      background: transparent;
      color: white;
      border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .cta-section .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-3px);
    }

    /* --- Footer --- */
    footer {
      background: var(--dark);
      color: #cbd5e1;
      padding: 80px 0 30px;
    }

    .footer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 50px;
      margin-bottom: 50px;
    }

    .footer-column h3 {
      color: white;
      margin-bottom: 25px;
      font-size: 1.3rem;
      font-weight: 700;
    }

    .footer-links {
      list-style: none;
    }

    .footer-links li {
      margin-bottom: 12px;
    }

    .footer-links a {
      color: #94a3b8;
      text-decoration: none;
      transition: color 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .footer-links a:hover {
      color: white;
    }

    .footer-links a i {
      font-size: 0.8rem;
    }

    .footer-bottom {
      text-align: center;
      padding-top: 40px;
      border-top: 1px solid #334155;
      color: #94a3b8;
      font-size: 0.9rem;
    }

    .social-links {
      display: flex;
      gap: 15px;
      margin-top: 25px;
    }

    .social-links a {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 44px;
      height: 44px;
      background: #334155;
      color: white;
      border-radius: 50%;
      transition: all 0.3s;
      text-decoration: none;
    }

    .social-links a:hover {
      background: var(--primary);
      transform: translateY(-3px);
    }

    /* --- Animations --- */
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

    @keyframes float {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-20px);
      }
    }

    /* --- Responsive --- */
    @media (max-width: 1200px) {
      .hero-visual {
        display: none;
      }
      
      .hero-content {
        max-width: 100%;
        text-align: center;
      }
      
      .hero-stats {
        justify-content: center;
      }
    }

    @media (max-width: 968px) {
      .hero-title {
        font-size: 3rem;
      }
      
      .charts-grid {
        grid-template-columns: 1fr;
      }
      
      .chart-wrapper {
        height: 250px;
      }
      
      .nav-links {
        display: none;
      }
      
      .cta-buttons {
        flex-direction: column;
        align-items: center;
      }
      
      .cta-title {
        font-size: 2.5rem;
      }
    }

    @media (max-width: 768px) {
      .hero-title {
        font-size: 2.5rem;
      }
      
      .hero-subtitle {
        font-size: 1.1rem;
      }
      
      .section-title {
        font-size: 2.2rem;
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .features-grid, .testimonials-grid, .news-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 480px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .hero-stats {
        flex-direction: column;
        gap: 25px;
      }
      
      .stat-value {
        font-size: 2rem;
      }
    }

    /* Mobile menu button */
    .mobile-menu-btn {
      display: none;
      background: none;
      border: none;
      font-size: 1.5rem;
      color: var(--gray);
      cursor: pointer;
      z-index: 1001;
    }

    @media (max-width: 968px) {
      .mobile-menu-btn {
        display: block;
      }
    }
  </style>
</head>

<body>

 <nav class="fixed top-0 left-0 w-full z-50 bg-white shadow">
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    include __DIR__ . '/../navbar.php';
    ?>
</nav>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-bg"></div>
    <div class="container">
      <div class="hero-content">
        <div class="hero-badge">
          <i class="fas fa-star"></i>
          Premium Vehicle Services
        </div>
        <h1 class="hero-title">Experience Premium Vehicle Management</h1>
        <p class="hero-subtitle">Your all-in-one platform for car rentals, maintenance services, parts, and driving courses. Experience the future of automotive services.</p>
        <div class="hero-cta">
          <a href="car-rental.php" class="btn btn-primary">
            <i class="fas fa-car"></i>
            Explore Our Fleet
          </a>
          <a href="services.php" class="btn btn-secondary">
            <i class="fas fa-calendar-alt"></i>
            Book a Service
          </a>
        </div>
        <div class="hero-stats">
          <div class="stat-item">
            <span class="stat-value"><?php echo $total_cars; ?>+</span>
            <span class="stat-label">Premium Vehicles</span>
          </div>
          <div class="stat-item">
            <span class="stat-value"><?php echo $total_models; ?>+</span>
            <span class="stat-label">Car Models</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">24/7</span>
            <span class="stat-label">Customer Support</span>
          </div>
        </div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="floating-card">
        <div class="card-icon">
          <i class="fas fa-car"></i>
        </div>
        <h3 class="card-title">Car Rental</h3>
        <p class="card-text">Wide selection of premium vehicles</p>
      </div>
      <div class="floating-card">
        <div class="card-icon">
          <i class="fas fa-tools"></i>
        </div>
        <h3 class="card-title">Maintenance</h3>
        <p class="card-text">Professional service & repairs</p>
      </div>
      <div class="floating-card">
        <div class="card-icon">
          <i class="fas fa-cog"></i>
        </div>
        <h3 class="card-title">Parts Store</h3>
        <p class="card-text">Genuine parts & accessories</p>
      </div>
    </div>
  </section>

  <!-- Enhanced Stats Section -->
  <section class="stats-section">
    <div class="container">
      <h2 class="section-title" style="color: var(--dark);">Key Metrics</h2>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-car"></i>
          </div>
          <h3><?php echo $total_cars; ?></h3>
          <p>Total Vehicles</p>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-layer-group"></i>
          </div>
          <h3><?php echo $total_models; ?></h3>
          <p>Different Models</p>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <h3><?php echo $available_cars; ?></h3>
          <p>Available Now</p>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-tools"></i>
          </div>
          <h3><?php echo $total_services; ?></h3>
          <p>Services Offered</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Charts Section -->
  <section class="charts-section">
    <div class="container">
      <h2 class="section-title">Fleet Analytics</h2>
      <div class="charts-grid">
        <!-- Chart 1: Car Distribution by Fuel Type -->
        <div class="chart-container">
          <div class="chart-header">
            <div class="chart-icon">
              <i class="fas fa-gas-pump"></i>
            </div>
            <div>
              <h3 class="chart-title">Car Distribution by Fuel Type</h3>
              <p class="chart-subtitle">Breakdown of vehicles by fuel type</p>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="fuelChart"></canvas>
          </div>
        </div>

        <!-- Chart 2: Parts Stock by Category -->
        <div class="chart-container">
          <div class="chart-header">
            <div class="chart-icon">
              <i class="fas fa-boxes"></i>
            </div>
            <div>
              <h3 class="chart-title">Parts Stock by Category</h3>
              <p class="chart-subtitle">Inventory levels across part categories</p>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="partsChart"></canvas>
          </div>
        </div>

        <!-- Chart 3: Service Types Popularity -->
        <div class="chart-container">
          <div class="chart-header">
            <div class="chart-icon">
              <i class="fas fa-concierge-bell"></i>
            </div>
            <div>
              <h3 class="chart-title">Service Types Popularity</h3>
              <p class="chart-subtitle">Most requested service categories</p>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="servicesChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features-section">
    <div class="container">
      <h2 class="section-title" style="color: var(--dark);">Our Premium Services</h2>
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-car"></i>
          </div>
          <h3 class="feature-title">Car Rental</h3>
          <p class="feature-description">Choose from our wide selection of premium vehicles for short-term or long-term rentals. All vehicles are regularly maintained and fully insured.</p>
          <a href="car-rental.php" class="feature-link">
            Explore Fleet
            <i class="fas fa-arrow-right"></i>
          </a>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-tools"></i>
          </div>
          <h3 class="feature-title">Maintenance Services</h3>
          <p class="feature-description">Professional maintenance and repair services for all vehicle types. Our certified technicians use state-of-the-art diagnostic equipment.</p>
          <a href="mainten.php" class="feature-link">
            Book Service
            <i class="fas fa-arrow-right"></i>
          </a>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-cog"></i>
          </div>
          <h3 class="feature-title">Parts & Accessories</h3>
          <p class="feature-description">Genuine parts and premium accessories for your vehicle. We offer competitive pricing and fast delivery for all your automotive needs.</p>
          <a href="parts.php" class="feature-link">
            Shop Now
            <i class="fas fa-arrow-right"></i>
          </a>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-graduation-cap"></i>
          </div>
          <h3 class="feature-title">Driving Courses</h3>
          <p class="feature-description">Comprehensive driving courses for all skill levels. Learn from certified instructors with years of experience in defensive driving techniques.</p>
          <a href="courses.php" class="feature-link">
            View Courses
            <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section class="testimonials-section">
    <div class="container">
      <h2 class="section-title" style="color: var(--dark);">What Our Customers Say</h2>
      <div class="testimonials-grid">
        <div class="testimonial-card">
          <div class="testimonial-content">
            "AutoVista made my car rental experience seamless. The vehicle was in perfect condition, and the pickup/dropoff process was incredibly efficient. Highly recommended!"
          </div>
          <div class="testimonial-author">
            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User" class="author-avatar">
            <div class="author-info">
              <h4>Michael Johnson</h4>
              <p>Business Traveler</p>
              <div class="rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="testimonial-card">
          <div class="testimonial-content">
            "The maintenance service was exceptional. They identified issues I didn't even know about and fixed them promptly. Will definitely use their services again!"
          </div>
          <div class="testimonial-author">
            <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User" class="author-avatar">
            <div class="author-info">
              <h4>Sarah Williams</h4>
              <p>Regular Customer</p>
              <div class="rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="testimonial-card">
          <div class="testimonial-content">
            "I've rented from many services, but AutoVista stands out with their premium vehicles and outstanding customer support. The entire process was hassle-free."
          </div>
          <div class="testimonial-author">
            <img src="https://randomuser.me/api/portraits/men/67.jpg" alt="User" class="author-avatar">
            <div class="author-info">
              <h4>Robert Chen</h4>
              <p>Frequent Renter</p>
              <div class="rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- News Section -->
  <section class="news-section">
    <div class="container">
      <h2 class="section-title" style="color: var(--dark);">Latest News & Updates</h2>
      <div class="news-grid">
        <div class="news-card">
          <img src="https://images.unsplash.com/photo-1563720223480-8bfe89da47f8?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" alt="News" class="news-image">
          <div class="news-content">
            <div class="news-date"><i class="far fa-calendar-alt"></i> October 18, 2025</div>
            <h3 class="news-title">Introducing Electric Vehicle Rentals</h3>
            <p class="news-excerpt">We're excited to announce our new fleet of electric vehicles available for rental starting next month.</p>
            <a href="#" class="btn btn-primary">Read More</a>
          </div>
        </div>
        <div class="news-card">
          <img src="https://images.unsplash.com/photo-1621905252507-b35492cc74b4?ixlib=rb-4.0.3&auto=format&fit=crop&w=2069&q=80" alt="News" class="news-image">
          <div class="news-content">
            <div class="news-date"><i class="far fa-calendar-alt"></i> October 10, 2025</div>
            <h3 class="news-title">Enhanced Maintenance Services</h3>
            <p class="news-excerpt">Our service centers now offer advanced diagnostics and maintenance for all vehicle types.</p>
            <a href="#" class="btn btn-primary">Read More</a>
          </div>
        </div>
        <div class="news-card">
          <img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" alt="News" class="news-image">
          <div class="news-content">
            <div class="news-date"><i class="far fa-calendar-alt"></i> October 5, 2023</div>
            <h3 class="news-title">New Driving Courses Available</h3>
            <p class="news-excerpt">Learn advanced driving techniques and electric vehicle maintenance in our new course offerings.</p>
            <a href="#" class="btn btn-primary">Read More</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
      <h2 class="cta-title">Ready to Get Started?</h2>
      <p class="cta-subtitle">Join thousands of satisfied customers who trust AutoVista for all their automotive needs.</p>
      <div class="cta-buttons">
        <a href="car-rental.php" class="btn btn-primary">
          <i class="fas fa-car"></i>
          Rent a Vehicle
        </a>
        <a href="services.php" class="btn btn-secondary">
          <i class="fas fa-tools"></i>
          Book a Service
        </a>
      </div>
    </div>
  </section>

  <?php
include __DIR__ . '/../footer.php';
// Close connection
$conn->close();
?>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      console.log('AutoVista homepage loaded successfully');

      // Navbar scroll effect
      const navbar = document.querySelector('.navbar');
      window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
          navbar.style.background = 'rgba(255, 255, 255, 0.98)';
          navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
        } else {
          navbar.style.background = 'rgba(255, 255, 255, 0.95)';
          navbar.style.boxShadow = 'var(--shadow-sm)';
        }
      });

      // Mobile menu toggle
      const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
      const navLinks = document.querySelector('.nav-links');

      if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
          navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
        });
      }

      // Chart 1: Car Distribution by Fuel Type (Pie Chart)
      const fuelCtx = document.getElementById('fuelChart').getContext('2d');
      const fuelChart = new Chart(fuelCtx, {
        type: 'doughnut',
        data: {
          labels: <?php echo json_encode($fuel_types); ?>,
          datasets: [{
            data: <?php echo json_encode($fuel_totals); ?>,
            backgroundColor: [
              '#2563eb',
              '#f59e0b',
              '#10b981',
              '#8b5cf6',
              '#ef4444',
              '#06b6d4'
            ],
            borderWidth: 0,
            hoverOffset: 15
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                color: '#334155',
                font: {
                  size: 13,
                  weight: '500'
                },
                padding: 20
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.raw || 0;
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = Math.round((value / total) * 100);
                  return `${label}: ${value} (${percentage}%)`;
                }
              }
            }
          },
          cutout: '65%'
        }
      });

      // Chart 2: Parts Stock by Category (Bar Chart)
      const partsCtx = document.getElementById('partsChart').getContext('2d');
      const partsChart = new Chart(partsCtx, {
        type: 'bar',
        data: {
          labels: <?php echo json_encode($part_categories); ?>,
          datasets: [{
            label: 'Stock Quantity',
            data: <?php echo json_encode($part_stocks); ?>,
            backgroundColor: [
              'rgba(37, 99, 235, 0.8)',
              'rgba(245, 158, 11, 0.8)',
              'rgba(16, 185, 129, 0.8)',
              'rgba(139, 92, 246, 0.8)',
              'rgba(239, 68, 68, 0.8)',
              'rgba(6, 182, 212, 0.8)'
            ],
            borderColor: [
              'rgb(37, 99, 235)',
              'rgb(245, 158, 11)',
              'rgb(16, 185, 129)',
              'rgb(139, 92, 246)',
              'rgb(239, 68, 68)',
              'rgb(6, 182, 212)'
            ],
            borderWidth: 0,
            borderRadius: 8,
            borderSkipped: false,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              },
              ticks: {
                color: '#64748b'
              }
            },
            x: {
              grid: {
                display: false
              },
              ticks: {
                color: '#64748b'
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });

      // Chart 3: Service Types Popularity (Column Chart)
      const servicesCtx = document.getElementById('servicesChart').getContext('2d');
      const servicesChart = new Chart(servicesCtx, {
        type: 'bar',
        data: {
          labels: <?php echo json_encode($service_types); ?>,
          datasets: [{
            label: 'Number of Services',
            data: <?php echo json_encode($service_totals); ?>,
            backgroundColor: [
              'rgba(37, 99, 235, 0.8)',
              'rgba(245, 158, 11, 0.8)',
              'rgba(16, 185, 129, 0.8)',
              'rgba(139, 92, 246, 0.8)',
              'rgba(239, 68, 68, 0.8)'
            ],
            borderColor: [
              'rgb(37, 99, 235)',
              'rgb(245, 158, 11)',
              'rgb(16, 185, 129)',
              'rgb(139, 92, 246)',
              'rgb(239, 68, 68)'
            ],
            borderWidth: 0,
            borderRadius: 8,
            borderSkipped: false,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          indexAxis: 'y', // This makes it a horizontal bar chart
          scales: {
            x: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              },
              ticks: {
                color: '#64748b'
              }
            },
            y: {
              grid: {
                display: false
              },
              ticks: {
                color: '#64748b'
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });

      // Add animation to elements on scroll
      const animatedElements = document.querySelectorAll('.stat-card, .feature-card, .testimonial-card, .news-card, .chart-container');
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.animation = 'fadeInUp 0.6s ease-out forwards';
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1 });

      animatedElements.forEach(element => {
        element.style.opacity = '0';
        observer.observe(element);
      });
    });
  </script>
</body>
</html>