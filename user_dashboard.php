<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Management System (TMS)</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Arial', sans-serif;
        line-height: 1.6;
        color: #333;
    }

    .header {
        background: #fff;
        padding: 1rem 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
    }

    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 2rem;
    }

    .logo {
        display: flex;
        align-items: center;
        font-size: 1.5rem;
        font-weight: bold;
        color: #6f42c1;
    }

    .logo::before {
        content: "🚛";
        margin-right: 0.5rem;
        font-size: 2rem;
    }

    .nav-menu {
        display: flex;
        list-style: none;
        gap: 2rem;
    }

    .nav-menu a {
        text-decoration: none;
        color: #333;
        font-weight: 500;
        transition: color 0.3s;
    }

    .nav-menu a:hover {
        color: #6f42c1;
    }

    .cta-btn {
        background: linear-gradient(135deg, #6f42c1, #8b5cf6);
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
        transition: transform 0.3s;
    }

    .cta-btn:hover {
        transform: translateY(-2px);
    }

    .hero {
        background: linear-gradient(135deg, #1a1b5e, #6f42c1);
        color: white;
        padding: 8rem 0 4rem;
        text-align: center;
    }

    .hero h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
        font-weight: 700;
    }

    .hero p {
        font-size: 1.2rem;
        margin-bottom: 2rem;
        max-width: 600px;
        margin: auto;
    }

    .hero-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
    }

    .btn-primary,
    .btn-secondary {
        padding: 1rem 2rem;
        border-radius: 25px;
        border: none;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-primary {
        background: #4f46e5;
        color: white;
    }

    .btn-secondary {
        background: #10b981;
        color: white;
    }

    .btn-primary:hover,
    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .overview,
    .services {
        padding: 4rem 2rem;
        background: #f8fafc;
    }

    .section-title {
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 3rem;
        color: #1a1b5e;
    }

    .features-grid,
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .feature-card,
    .service-card {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
        text-align: center;
    }

    .feature-card:hover,
    .service-card:hover {
        transform: translateY(-5px);
    }

    .service-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }

    .booking-form,
    .fare-form,
    .cancel-form {
        background: #6f42c1;
        color: white;
        padding: 1.5rem;
        border-radius: 10px;
        margin-top: 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.75rem;
        border: none;
        border-radius: 5px;
        font-size: 1rem;
    }

    .form-btn {
        background: #10b981;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s;
    }

    .form-btn:hover {
        background: #059669;
    }

    .result-display {
        background: rgba(255, 255, 255, 0.1);
        padding: 1rem;
        border-radius: 5px;
        margin-top: 1rem;
        display: none;
    }

    .footer {
        background: #1a1b5e;
        color: white;
        text-align: center;
        padding: 2rem 0;
    }

    @media (max-width: 768px) {
        .nav-menu {
            display: none;
        }

        .hero h1 {
            font-size: 2.5rem;
        }

        .hero-buttons {
            flex-direction: column;
            align-items: center;
        }

        .services-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>

<body>

    <header class="header">
        <nav class="nav-container">
            <div class="logo">TMS</div>
            <ul class="nav-menu">
                <li><a href="#home">Home</a></li>
                <li><a href="#overview">Overview</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <a href="#demo" class="cta-btn">Free Demo</a>
        </nav>
    </header>

    <section class="hero" id="home">
        <h1>Transport Management Software</h1>
        <p>Powerful and flexible tools for your Transport. TMS is used by transporters worldwide for efficient
            operations.</p>
        <div class="hero-buttons">
            <a href="#demo" class="btn-primary">Request Demo</a>
            <a href="https://www.whatsapp.com/" target="_blank" class="btn-secondary">WhatsApp Us</a>
        </div>
    </section>

    <section class="overview" id="overview">
        <h2 class="section-title">Why Choose TMS?</h2>
        <div class="features-grid">
            <div class="feature-card"><span class="service-icon">📦</span>
                <h3>Complete Transport Solution</h3>
                <p>End-to-end transport management with booking, user and vehicle management.</p>
            </div>
            <div class="feature-card"><span class="service-icon">📊</span>
                <h3>Real-time Analytics</h3>
                <p>Insights into your transport operations with detailed reports.</p>
            </div>
            <div class="feature-card"><span class="service-icon">🌍</span>
                <h3>Global Reach</h3>
                <p>Used worldwide for efficient logistics management.</p>
            </div>
        </div>
    </section>

    <section class="services" id="services" style="display:none;">
        <h2 class="section-title">Transport Management Services</h2>
        <div class="services-grid">
            <!-- Fare Calculation -->
            <div class="service-card">
                <span class="service-icon">💰</span>
                <h3>Fare Details & Calculation</h3>
                <div class="fare-form">
                    <div class="form-group"><label>From:</label><input type="text" id="fromLocation"
                            placeholder="Enter pickup location"></div>
                    <div class="form-group"><label>To:</label><input type="text" id="toLocation"
                            placeholder="Enter destination"></div>
                    <div class="form-group"><label>Distance (km):</label><input type="number" id="distance"
                            placeholder="Enter distance"></div>
                    <div class="form-group"><label>Transport Type:</label><select id="vehicleType">
                            <option value="taxi">Taxi</option>
                            <option value="bus">Bus</option>
                            <option value="micro">Micro</option>
                        </select></div>
                    <button class="form-btn" onclick="calculateFare()">Calculate Fare</button>
                    <div class="result-display" id="fareResult"></div>
                </div>
            </div>

            <!-- Booking Form -->
            <div class="service-card">
                <span class="service-icon">🎫</span>
                <h3>Booking & Ticketing</h3>
                <div class="booking-form">
                    <div class="form-group"><label>Name:</label><input type="text" id="customerName"></div>
                    <div class="form-group"><label>Phone:</label><input type="tel" id="customerPhone"></div>
                    <div class="form-group"><label>Pickup Date:</label><input type="date" id="pickupDate"></div>
                    <div class="form-group"><label>Service Type:</label><select id="serviceType">
                            <option value="express">Express</option>
                            <option value="standard">Standard</option>
                            <option value="bulk">Bulk</option>
                        </select></div>
                    <button class="form-btn" onclick="bookTransport()">Book Now</button>
                    <div class="result-display" id="bookingResult"></div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer" id="contact">
        <p>&copy; 2025 TMS - All Rights Reserved</p>
    </footer>

    <script>
    function showSection(sectionId) {
        document.getElementById('home').style.display = 'none';
        document.getElementById('overview').style.display = 'none';
        document.getElementById('services').style.display = 'none';
        if (sectionId == 'home') {
            document.getElementById('home').style.display = 'block';
            document.getElementById('overview').style.display = 'block';
        } else document.getElementById(sectionId).style.display = 'block';
    }

    function calculateFare() {
        let distance = parseFloat(document.getElementById('distance').value);
        let vehicleType = document.getElementById('vehicleType').value;
        let farePerKm = vehicleType === 'taxi' ? 25 : vehicleType === 'bus' ? 10 : 15;
        if (distance > 0) {
            document.getElementById('fareResult').innerHTML =
                `Estimated Fare for ${distance} km by ${vehicleType.toUpperCase()}: Rs. ${farePerKm*distance}`;
            document.getElementById('fareResult').style.display = 'block';
        } else alert('Enter valid distance');
    }

    function bookTransport() {
        let name = document.getElementById('customerName').value;
        let phone = document.getElementById('customerPhone').value;
        let date = document.getElementById('pickupDate').value;
        let service = document.getElementById('serviceType').value;
        if (!name || !phone || !date) {
            alert('Fill all fields');
            return;
        }
        let bookingId = 'TMS' + Date.now().toString().slice(-6);
        document.getElementById('bookingResult').innerHTML =
            `<h5>Booking Confirmed!</h5><p>Booking ID: ${bookingId}</p><p>Customer: ${name}</p><p>Phone: ${phone}</p><p>Pickup Date: ${date}</p><p>Service: ${service}</p>`;
        document.getElementById('bookingResult').style.display = 'block';
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                showSection(this.getAttribute('href').replace('#', ''));
            });
        });
        showSection('home');
    });
    </script>
</body>

</html>