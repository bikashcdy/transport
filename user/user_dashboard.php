<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';

$waysData = [];

$sql = "
    SELECT w.id AS way_id, w.origin, w.destination, w.departure_time, w.arrival_time, w.price,
           wt.transit_point, wt.transit_duration, wt.transit_time
    FROM ways w
    LEFT JOIN way_transits wt ON w.id = wt.way_id
    ORDER BY w.id, wt.transit_time
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $wayId = $row['way_id'];
        if (!isset($waysData[$wayId])) {
            $waysData[$wayId] = [
                'origin' => $row['origin'],
                'destination' => $row['destination'],
                'departure_time' => $row['departure_time'],
                'arrival_time' => $row['arrival_time'],
                'price' => $row['price'],
                'transits' => []
            ];
        }
        if (!empty($row['transit_point'])) {
            $waysData[$wayId]['transits'][] = [
                'point' => $row['transit_point'],
                'duration' => $row['transit_duration'],
                'time' => $row['transit_time']
            ];
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="favi.png" type="image/x-icon">
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
        cursor: pointer;
    }

    .nav-menu a:hover,
    .nav-menu a.active {
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

    /* My Booking Section Styles */
    .booking-dashboard {
        background: #f8fafc;
        padding: 8rem 2rem 4rem;
        min-height: 100vh;
    }

    .booking-dashboard .section-title {
        text-align: left;
        margin-bottom: 1rem;
    }

    .booking-dashboard .section-subtitle {
        color: #666;
        margin-bottom: 3rem;
        font-size: 1.1rem;
    }

    .booking-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .booking-header {
        background: #2d3748;
        color: white;
        padding: 1.5rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .booking-header h3 {
        font-size: 1.2rem;
        font-weight: 600;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .status-confirmed {
        background: #d4edda;
        color: #155724;
    }

    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }

    .booking-content {
        padding: 2rem;
    }

    .today-tag {
        background: #2d3748;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        display: inline-block;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
    }

    .booking-id {
        margin-bottom: 1.5rem;
    }

    .booking-id .label {
        color: #666;
        font-size: 0.9rem;
        margin-right: 0.5rem;
    }

    .booking-id .value {
        font-family: 'Courier New', monospace;
        font-size: 1.1rem;
        font-weight: bold;
    }

    .booking-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .detail-group {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }

    .detail-group:hover {
        background: #f1f3f4;
        border-color: #dee2e6;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .detail-icon {
        width: 20px;
        height: 20px;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .detail-content {
        flex: 1;
    }

    .detail-content .label {
        color: #666;
        font-size: 0.9rem;
        font-weight: 500;
        margin-right: 0.5rem;
    }

    .detail-content .value {
        color: #333;
        font-weight: 600;
    }

    .detail-inline {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
    }

    .cancel-btn {
        background: #dc3545;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s;
        float: right;
        margin-top: 1rem;
    }

    .cancel-btn:hover {
        background: #c82333;
    }

    .cancellation-reason {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
    }

    .cancellation-reason .reason-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: white;
        margin: 10% auto;
        padding: 2rem;
        border-radius: 15px;
        width: 90%;
        max-width: 500px;
    }

    .modal-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }

    .modal-header .icon {
        color: #dc3545;
        margin-right: 1rem;
        font-size: 1.5rem;
    }

    .modal-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .modal-btn {
        flex: 1;
        padding: 0.75rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
    }

    .modal-btn-secondary {
        background: #e9ecef;
        color: #495057;
    }

    .modal-btn-secondary:hover {
        background: #dee2e6;
    }

    .modal-btn-danger {
        background: #dc3545;
        color: white;
    }

    .modal-btn-danger:hover {
        background: #c82333;
    }

    .modal-btn-danger:disabled {
        background: #f8b2b2;
        cursor: not-allowed;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #666;
    }

    .empty-state .icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }


    .routes-section {
        padding: 4rem 2rem;
        background: #f8fafc;
    }

    .route-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 2rem;
    }

    .route-card {
        background: #ffffff;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s;
        position: relative;
    }

    .route-card:hover {
        transform: translateY(-5px);
    }

    .route-header {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1a1b5e;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .route-header i {
        color: #6f42c1;
    }

    .route-info {
        font-size: 0.95rem;
        line-height: 1.6;
        color: #333;
    }

    .route-info strong {
        color: #6f42c1;
    }

    .transit-list {
        margin-top: 1rem;
    }

    .transit-list ul {
        padding-left: 1rem;
        margin-top: 0.5rem;
    }

    .transit-badge {
        display: inline-block;
        background: #e0e7ff;
        color: #3730a3;
        padding: 0.25rem 0.75rem;
        border-radius: 25px;
        font-size: 0.85rem;
        font-weight: 500;
        margin: 0.25rem 0;
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

        .booking-details {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .booking-content {
            padding: 1rem;
        }
    }
    </style>
</head>

<body>

    <header class="header">
        <nav class="nav-container">
            <div class="logo">TMS</div>
            <ul class="nav-menu">
                <li><a href="#" onclick="showSection('home')" id="nav-home">Home</a></li>
                <li><a href="#" onclick="showSection('overview')" id="nav-overview">Overview</a></li>
                <li><a href="#" onclick="showSection('services')" id="nav-services">Services</a></li>
                <li><a href="#" onclick="showSection('contact')" id="nav-contact">Contact</a></li>
                <li><a href="#" onclick="showSection('myBooking')" id="nav-myBooking">My Booking</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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

    <section class="routes-section" id="availableWays">
        <h2 class="section-title">Available Routes</h2>

        <?php if (!empty($waysData)): ?>
        <div class="route-grid">
            <?php foreach ($waysData as $id => $way): ?>
            <div class="route-card">
                <div class="route-header">
                    <i>🛣️</i> <?= htmlspecialchars($way['origin']) ?> ➝ <?= htmlspecialchars($way['destination']) ?>
                </div>
                <div class="route-info">
                    <p><strong>Departure:</strong> <?= date("g:i A", strtotime($way['departure_time'])) ?></p>
                    <p><strong>Arrival:</strong> <?= date("g:i A", strtotime($way['arrival_time'])) ?></p>
                    <p><strong>Price:</strong> ₹<?= number_format($way['price'], 2) ?></p>
                </div>
                <?php if (!empty($way['transits'])): ?>
                <div class="transit-list">
                    <strong>Transit Stops:</strong>
                    <ul>
                        <?php foreach ($way['transits'] as $transit): ?>
                        <li class="transit-badge">
                            <?= htmlspecialchars($transit['point']) ?>
                            (<?= date("g:i A", strtotime($transit['time'])) ?>,
                            <?= (int) $transit['duration'] ?> min)
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <form action="user_booking.php" method="POST">
                    <input type="hidden" name="way_id" value="<?= $id ?>">
                    <button type="submit" class="form-btn" style="margin-top: 1rem;">Book Now</button>
                </form>

            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon">🚫</div>
            <h3>No Routes Found</h3>
            <p>There are currently no available transport routes.</p>
        </div>
        <?php endif; ?>
    </section>



    <section class="services" id="services">
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
                    <div class="form-group"><label>Email:</label><input type="email" id="customerEmail"></div>
                    <div class="form-group"><label>Address:</label><input type="text" id="customerAddress"></div>
                    <div class="form-group"><label>From:</label><input type="text" id="bookingFrom"></div>
                    <div class="form-group"><label>To:</label><input type="text" id="bookingTo"></div>
                    <div class="form-group"><label>Pickup Date:</label><input type="date" id="pickupDate"></div>
                    <div class="form-group"><label>Pickup Time:</label><input type="time" id="pickupTime"></div>
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

    <!-- My Booking Section -->
    <section class="booking-dashboard" id="myBooking">
        <h2 class="section-title">My Bookings</h2>
        <p class="section-subtitle">Manage and view your travel bookings</p>

        <div id="bookingsList">
            <!-- Bookings will be populated here -->
        </div>

        <div id="emptyBookings" class="empty-state">
            <div class="icon">📅</div>
            <h3>No bookings found</h3>
            <p>You haven't made any bookings yet.</p>
        </div>
    </section>

    <!-- Cancel Booking Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="icon">⚠️</span>
                <h3>Cancel Booking</h3>
            </div>
            <p>Are you sure you want to cancel booking <span id="cancelBookingId"></span>?</p>
            <div class="form-group">
                <label>Please select a reason for cancellation:</label>
                <select id="cancelReason">
                    <option value="">Select a reason...</option>
                    <option value="Change in travel plans">Change in travel plans</option>
                    <option value="Medical emergency">Medical emergency</option>
                    <option value="Weather concerns">Weather concerns</option>
                    <option value="Found better alternative">Found better alternative</option>
                    <option value="Personal reasons">Personal reasons</option>
                    <option value="Schedule conflict">Schedule conflict</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-secondary" onclick="closeCancelModal()">Keep Booking</button>
                <button class="modal-btn modal-btn-danger" id="confirmCancelBtn" onclick="confirmCancellation()"
                    disabled>Cancel Booking</button>
            </div>
        </div>
    </div>

    <footer class="footer" id="contact">
        <p>&copy; 2025 TMS - All Rights Reserved</p>
    </footer>

    <script>
    // Sample booking data (in a real app, this would come from a database)
    let bookings = [{
            id: '876543',
            from: 'Kathmandu, Nepal',
            to: 'Pokhara, Nepal',
            passengerName: 'John Smith',
            contact: '+977-1234567890',
            email: 'john.smith@email.com',
            address: '123 Thamel, Kathmandu, Nepal',
            time: '6:00 AM',
            totalDuration: '6hrs',
            transit: 'Mugling (45mins)',
            bookingDate: '2024/09/25',
            status: 'confirmed',
            serviceType: 'express'
        },
        {
            id: '654321',
            from: 'Chitwan, Nepal',
            to: 'Lumbini, Nepal',
            passengerName: 'Jane Doe',
            contact: '+977-9876543210',
            email: 'jane.doe@email.com',
            address: '456 Sauraha, Chitwan, Nepal',
            time: '2:30 PM',
            totalDuration: '4hrs 30mins',
            transit: 'Butwal (30mins)',
            bookingDate: '2024/09/28',
            status: 'confirmed',
            serviceType: 'standard'
        },
        {
            id: '123456',
            from: 'Bhaktapur, Nepal',
            to: 'Janakpur, Nepal',
            passengerName: 'Mike Johnson',
            contact: '+977-1122334455',
            email: 'mike.j@email.com',
            address: '789 Durbar Square, Bhaktapur, Nepal',
            time: '10:15 AM',
            totalDuration: '5hrs',
            transit: 'Bardibas (1hr)',
            bookingDate: '2024/09/29',
            status: 'confirmed',
            serviceType: 'bulk'
        }
    ];

    let selectedBookingForCancel = null;

    function showSection(sectionId) {
        // Hide all sections
        const sections = ['home', 'overview', 'services', 'myBooking'];
        sections.forEach(section => {
            const element = document.getElementById(section);
            if (element) element.style.display = 'none';
        });

        // Update navigation active state
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.classList.remove('active');
        });

        if (sectionId === 'home') {
            document.getElementById('home').style.display = 'block';
            document.getElementById('overview').style.display = 'block';
            document.getElementById('nav-home').classList.add('active');
        } else {
            document.getElementById(sectionId).style.display = 'block';
            document.getElementById('nav-' + sectionId).classList.add('active');
        }

        if (sectionId === 'myBooking') {
            renderBookings();
        }
    }

    function renderBookings() {
        const bookingsList = document.getElementById('bookingsList');
        const emptyBookings = document.getElementById('emptyBookings');

        if (bookings.length === 0) {
            bookingsList.innerHTML = '';
            emptyBookings.style.display = 'block';
            return;
        }

        emptyBookings.style.display = 'none';
        bookingsList.innerHTML = '';

        bookings.forEach(booking => {
            const bookingCard = createBookingCard(booking);
            bookingsList.appendChild(bookingCard);
        });
    }

    function createBookingCard(booking) {
        const card = document.createElement('div');
        card.className = 'booking-card';

        const statusClass = booking.status === 'confirmed' ? 'status-confirmed' : 'status-cancelled';
        const statusText = booking.status === 'confirmed' ? 'Confirmed' : 'Cancelled';

        card.innerHTML = `
            <div class="booking-header">
                <h3>My Booking Details</h3>
                <span class="status-badge ${statusClass}">${statusText}</span>
            </div>
            <div class="booking-content">
                <div class="today-tag">Today</div>
                
                <div class="booking-id">
                    <span class="label">Booking ID:</span>
                    <span class="value">#${booking.id}</span>
                </div>

                <div class="booking-details">
                    <div class="details-left">
                        <div class="detail-group">
                            <svg class="detail-icon" fill="#3b82f6" viewBox="0 0 24 24">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            <div class="detail-content">
                                <div class="detail-inline">
                                    <span class="label">From:</span>
                                    <span class="value">${booking.from}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-group">
                            <svg class="detail-icon" fill="#10b981" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                            <div class="detail-content">
                                <div class="detail-inline">
                                    <span class="label">Passenger Name:</span>
                                    <span class="value">${booking.passengerName}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-group">
                            <svg class="detail-icon" fill="#8b5cf6" viewBox="0 0 24 24">
                                <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                            </svg>
                            <div class="detail-content">
                                <div class="detail-inline">
                                    <span class="label">Contact:</span>
                                    <span class="value">${booking.contact}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-group">
                            <svg class="detail-icon" fill="#f59e0b" viewBox="0 0 24 24">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            <div class="detail-content">
                                <div class="detail-inline">
                                    <span class="label">Email:</span>
                                    <span class="value">${booking.email}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-group">
                            <svg class="detail-icon" fill="#ef4444" viewBox="0 0 24 24">
                                <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                            </svg>
                            <div class="detail-content">
                                <div class="detail-inline">
                                    <span class="label">Address:</span>
                                    <span class="value">${booking.address}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-group">
                            <svg class="detail-icon" fill="#6366f1" viewBox="0 0 24 24">
                                <path d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2.5-9H18V1h-2v1H8V1H6v1H4.5C3.11 2 2.01 3.09 2.01 4.5L2 20.5C2 21.91 3.11 23 4.5 23h15c1.39 0 2.5-1.09 2.5-2.5V4.5C22 3.09 20.89 2 19.5 2zm0 18.5h-15V8h15v12.5zm0-14h-15V4.5h15V6.5z"/>
                            </svg>
                            <div class="detail-content">
                                <div class="detail-inline">
                                    <span class="label">Booking Date:</span>
                                    <span class="value">${formatDate(booking.bookingDate)}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="details-right">
                        <div class="detail-group">
                            <svg class="detail-icon" fill="#3b82f6" viewBox="0 0 24 24">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            <div class="detail-content">
                                <div class="detail-inline">
                                    <span class="label">To:</span>
                                    <span class="value">${booking.to}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-group">
                            <svg class="detail-icon" fill="#eab308" viewBox="0 0 24 24">
                                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
                                <path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                            </svg>
                            <div class="detail-content">
                                <div class="detail-inline">
                                    <span class="label">Time:</span>
                                    <span class="value">${booking.time}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-group">
                            <svg class="detail-icon" fill="#059669" viewBox="0 0 24 24">
                                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
                                <path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                            </svg>
                            <div class="detail-content">
                                <div class="detail-inline">
                                    <span class="label">Total Duration:</span>
                                    <span class="value">${booking.totalDuration}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-group">
                            <svg class="detail-icon" fill="#dc2626" viewBox="0 0 24 24">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            <div class="detail-content">
                                <div class="detail-inline">
                                    <span class="label">Transit:</span>
                                    <span class="value">${booking.transit}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-group">
                            <svg class="detail-icon" fill="#7c3aed" viewBox="0 0 24 24">
                                <path d="M19 7h-3V6a4 4 0 0 0-8 0v1H5a1 1 0 0 0-1 1v11a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V8a1 1 0 0 0-1-1zM10 6a2 2 0 0 1 4 0v1h-4V6zm8 13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V9h2v1a1 1 0 0 0 2 0V9h4v1a1 1 0 0 0 2 0V9h2v10z"/>
                            </svg>
                            <div class="detail-content">
                                <div class="detail-inline">
                                    <span class="label">Service Type:</span>
                                    <span class="value">${booking.serviceType.charAt(0).toUpperCase() + booking.serviceType.slice(1)}</span>
                                </div>
                            </div>
                        </div>

                        ${booking.status === 'cancelled' && booking.cancelReason ? `
                            <div class="cancellation-reason">
                                <div class="reason-label">Cancellation Reason:</div>
                                <div>${booking.cancelReason}</div>
                            </div>
                        ` : ''}
                    </div>
                </div>

                ${booking.status === 'confirmed' ? `
                    <button class="cancel-btn" onclick="openCancelModal('${booking.id}')">
                        ❌ Cancel Booking
                    </button>
                    <div style="clear: both;"></div>
                ` : ''}
            </div>
        `;

        return card;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function openCancelModal(bookingId) {
        selectedBookingForCancel = bookingId;
        document.getElementById('cancelBookingId').textContent = '#' + bookingId;
        document.getElementById('cancelModal').style.display = 'block';
        document.getElementById('cancelReason').value = '';
        document.getElementById('confirmCancelBtn').disabled = true;
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
        selectedBookingForCancel = null;
    }

    function confirmCancellation() {
        const reason = document.getElementById('cancelReason').value;
        if (!reason || !selectedBookingForCancel) return;

        // Find and update the booking
        const bookingIndex = bookings.findIndex(b => b.id === selectedBookingForCancel);
        if (bookingIndex !== -1) {
            bookings[bookingIndex].status = 'cancelled';
            bookings[bookingIndex].cancelReason = reason;
        }

        // Close modal and re-render
        closeCancelModal();
        renderBookings();
    }

    // Enable/disable cancel button based on reason selection
    document.addEventListener('change', function(e) {
        if (e.target.id === 'cancelReason') {
            const confirmBtn = document.getElementById('confirmCancelBtn');
            confirmBtn.disabled = !e.target.value;
        }
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('cancelModal');
        if (e.target === modal) {
            closeCancelModal();
        }
    });

    function calculateFare() {
        let distance = parseFloat(document.getElementById('distance').value);
        let vehicleType = document.getElementById('vehicleType').value;
        let farePerKm = vehicleType === 'taxi' ? 25 : vehicleType === 'bus' ? 10 : 15;
        if (distance > 0) {
            document.getElementById('fareResult').innerHTML =
                `Estimated Fare for ${distance} km by ${vehicleType.toUpperCase()}: Rs. ${farePerKm * distance}`;
            document.getElementById('fareResult').style.display = 'block';
        } else alert('Enter valid distance');
    }

    function bookTransport() {
        let name = document.getElementById('customerName').value;
        let phone = document.getElementById('customerPhone').value;
        let email = document.getElementById('customerEmail').value;
        let address = document.getElementById('customerAddress').value;
        let from = document.getElementById('bookingFrom').value;
        let to = document.getElementById('bookingTo').value;
        let date = document.getElementById('pickupDate').value;
        let time = document.getElementById('pickupTime').value;
        let service = document.getElementById('serviceType').value;

        if (!name || !phone || !email || !address || !from || !to || !date || !time) {
            alert('Please fill all required fields');
            return;
        }

        // Generate 6-digit booking ID
        let bookingId = Math.floor(100000 + Math.random() * 900000).toString();

        // Add new booking to the array
        const newBooking = {
            id: bookingId,
            from: from,
            to: to,
            passengerName: name,
            contact: phone,
            email: email,
            address: address,
            time: time,
            totalDuration: '5hrs', // This could be calculated based on distance
            transit: 'Via Highway (30mins)', // This could be dynamic
            bookingDate: new Date().toISOString().split('T')[0].replace(/-/g, '/'),
            status: 'confirmed',
            serviceType: service
        };

        bookings.unshift(newBooking); // Add to beginning of array

        document.getElementById('bookingResult').innerHTML =
            `<h5>🎉 Booking Confirmed!</h5>
             <p><strong>Booking ID:</strong> #${bookingId}</p>
             <p><strong>Customer:</strong> ${name}</p>
             <p><strong>Phone:</strong> ${phone}</p>
             <p><strong>From:</strong> ${from}</p>
             <p><strong>To:</strong> ${to}</p>
             <p><strong>Date:</strong> ${date}</p>
             <p><strong>Time:</strong> ${time}</p>
             <p><strong>Service:</strong> ${service}</p>
             <p style="margin-top: 1rem; padding: 0.5rem; background: rgba(255,255,255,0.2); border-radius: 5px;">
                ✅ Your booking has been confirmed! You can view and manage it in the "My Booking" section.
             </p>`;
        document.getElementById('bookingResult').style.display = 'block';

        // Clear form
        document.getElementById('customerName').value = '';
        document.getElementById('customerPhone').value = '';
        document.getElementById('customerEmail').value = '';
        document.getElementById('customerAddress').value = '';
        document.getElementById('bookingFrom').value = '';
        document.getElementById('bookingTo').value = '';
        document.getElementById('pickupDate').value = '';
        document.getElementById('pickupTime').value = '';
    }

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        showSection('home');
    });
    </script>
</body>

</html>