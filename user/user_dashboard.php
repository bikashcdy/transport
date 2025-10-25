<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';

// Fetch available routes
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

// Fetch confirmed bookings for the logged-in user (initial view)
$user_id = $_SESSION['user_id'];
$confirmed_bookings_sql = "
    SELECT b.booking_id AS id, b.origin AS `from`, b.destination AS `to`, u.name AS passengerName, u.email,
           b.departure_time AS `time`, b.arrival_time, b.status, b.created_at AS bookingDate,
           vt.type_name AS vehicleName
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    WHERE b.user_id = ? AND b.status = 'confirmed'
    ORDER BY b.created_at DESC
";
$stmt = $conn->prepare($confirmed_bookings_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$confirmed_bookings_result = $stmt->get_result();
$confirmed_bookings = [];
while ($row = $confirmed_bookings_result->fetch_assoc()) {
    $departure_time = new DateTime($row['time']);
    $arrival_time = new DateTime($row['arrival_time']);
    $duration = $departure_time->diff($arrival_time);
    $row['totalDuration'] = sprintf('%dh %dm', $duration->h, $duration->i);
    $row['time'] = $departure_time->format('g:i A');
    $row['bookingDate'] = date('Y/m/d', strtotime($row['bookingDate']));
    $confirmed_bookings[] = $row;
}
$stmt->close();

// Fetch all bookings for the logged-in user (for View All section)
$all_bookings_sql = "
    SELECT b.booking_id AS id, b.origin AS `from`, b.destination AS `to`, u.name AS passengerName, u.email,
           b.departure_time AS `time`, b.arrival_time, b.status, b.created_at AS bookingDate,
           vt.type_name AS vehicleName
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
";
$stmt = $conn->prepare($all_bookings_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$all_bookings_result = $stmt->get_result();
$all_bookings = [];
while ($row = $all_bookings_result->fetch_assoc()) {
    $departure_time = new DateTime($row['time']);
    $arrival_time = new DateTime($row['arrival_time']);
    $duration = $departure_time->diff($arrival_time);
    $row['totalDuration'] = sprintf('%dh %dm', $duration->h, $duration->i);
    $row['time'] = $departure_time->format('g:i A');
    $row['bookingDate'] = date('Y/m/d', strtotime($row['bookingDate']));
    $all_bookings[] = $row;
}
$stmt->close();
$conn->close();
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

        .fare-form {
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

        .tab-btn {
            background: #e9ecef;
            color: #495057;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab-btn.active,
        .tab-btn:hover {
            background: #6f42c1;
            color: white;
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

            .tabs {
                flex-direction: column;
                align-items: center;
            }

            .tab-btn {
                width: 100%;
                text-align: center;
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
        <p>Powerful and flexible tools for your Transport. TMS is used by transporters worldwide for efficient operations.</p>
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
                    <div class="form-group"><label>From:</label><input type="text" id="fromLocation" placeholder="Enter pickup location"></div>
                    <div class="form-group"><label>To:</label><input type="text" id="toLocation" placeholder="Enter destination"></div>
                    <div class="form-group"><label>Distance (km):</label><input type="number" id="distance" placeholder="Enter distance"></div>
                    <div class="form-group"><label>Transport Type:</label><select id="vehicleType">
                            <option value="taxi">Taxi</option>
                            <option value="bus">Bus</option>
                            <option value="micro">Micro</option>
                        </select></div>
                    <button class="form-btn" onclick="calculateFare()">Calculate Fare</button>
                    <div class="result-display" id="fareResult"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="booking-dashboard" id="myBooking">
        <h2 class="section-title">My Bookings</h2>
        <p class="section-subtitle">Manage and view your travel bookings</p>
        <?php if (count($confirmed_bookings) > 1): ?>
            <div style="margin-bottom: 1.5rem;">
                <a href="#" onclick="showAllBookings()" class="btn-primary">View All Bookings</a>
            </div>
        <?php endif; ?>
        <div id="bookingsList"></div>
        <div id="allBookingsSection" style="display: none;">
            <div class="tabs" style="margin-bottom: 2rem; display: flex; gap: 1rem;">
                <button class="tab-btn active" onclick="filterBookings('all')">All Bookings</button>
                <button class="tab-btn" onclick="filterBookings('confirmed')">Confirmed Bookings</button>
                <button class="tab-btn" onclick="filterBookings('cancelled')">Cancelled Bookings</button>
            </div>
            <div id="allBookingsList"></div>
        </div>
        <div id="emptyBookings" class="empty-state">
            <div class="icon">📅</div>
            <h3>No bookings found</h3>
            <p>You haven't made any bookings yet.</p>
        </div>
    </section>

    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="icon">⚠️</span>
                <h3>Cancel Booking</h3>
            </div>
            <p>Are you sure you want to cancel booking <span id="cancelBookingId"></span>?</p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-secondary" onclick="closeCancelModal()">Keep Booking</button>
                <button class="modal-btn modal-btn-danger" id="confirmCancelBtn" onclick="confirmCancellation()">Cancel Booking</button>
            </div>
        </div>
    </div>

    <footer class="footer" id="contact">
        <p>&copy; 2025 TMS - All Rights Reserved</p>
    </footer>

    <script>
        let confirmedBookings = <?php echo json_encode($confirmed_bookings); ?>;
        let allBookings = <?php echo json_encode($all_bookings); ?>;
        let selectedBookingForCancel = null;

        function showSection(sectionId) {
            const sections = ['home', 'overview', 'services', 'myBooking'];
            sections.forEach(section => {
                const element = document.getElementById(section);
                if (element) element.style.display = 'none';
            });

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
            const allBookingsSection = document.getElementById('allBookingsSection');
            const emptyBookings = document.getElementById('emptyBookings');

            bookingsList.innerHTML = '';
            allBookingsSection.style.display = 'none';

            if (confirmedBookings.length === 0) {
                bookingsList.innerHTML = '';
                emptyBookings.style.display = 'block';
                return;
            }

            emptyBookings.style.display = 'none';
            confirmedBookings.forEach(booking => {
                const bookingCard = createBookingCard(booking);
                bookingsList.appendChild(bookingCard);
            });
        }

        function showAllBookings() {
            const bookingsList = document.getElementById('bookingsList');
            const allBookingsSection = document.getElementById('allBookingsSection');
            const emptyBookings = document.getElementById('emptyBookings');

            bookingsList.innerHTML = '';
            allBookingsSection.style.display = 'block';
            emptyBookings.style.display = 'none';

            filterBookings('all');
        }

        function filterBookings(filter) {
            const allBookingsList = document.getElementById('allBookingsList');
            const emptyBookings = document.getElementById('emptyBookings');
            allBookingsList.innerHTML = '';

            // Update active tab
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`.tab-btn[onclick="filterBookings('${filter}')"]`).classList.add('active');

            let filteredBookings = allBookings;
            if (filter === 'confirmed') {
                filteredBookings = allBookings.filter(booking => booking.status === 'confirmed');
            } else if (filter === 'cancelled') {
                filteredBookings = allBookings.filter(booking => booking.status === 'cancelled');
            }

            if (filteredBookings.length === 0) {
                emptyBookings.style.display = 'block';
                return;
            }

            emptyBookings.style.display = 'none';
            filteredBookings.forEach(booking => {
                const bookingCard = createBookingCard(booking);
                allBookingsList.appendChild(bookingCard);
            });
        }

        function createBookingCard(booking) {
            const card = document.createElement('div');
            card.className = 'booking-card';

            const statusClass = booking.status === 'confirmed' ? 'status-confirmed' : 'status-cancelled';
            const statusText = booking.status.charAt(0).toUpperCase() + booking.status.slice(1);

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
                                <svg class="detail-icon" fill="#7c3aed" viewBox="0 0 24 24">
                                    <path d="M19 7h-3V6a4 4 0 0 0-8 0v1H5a1 1 0 0 0-1 1v11a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V8a1 1 0 0 0-1-1zM10 6a2 2 0 0 1 4 0v1h-4V6zm8 13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V9h2v1a1 1 0 0 0 2 0V9h4v1a1 1 0 0 0 2 0V9h2v10z"/>
                                </svg>
                                <div class="detail-content">
                                    <div class="detail-inline">
                                        <span class="label">Vehicle:</span>
                                        <span class="value">${booking.vehicleName}</span>
                                    </div>
                                </div>
                            </div>
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
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
            selectedBookingForCancel = null;
        }

        function confirmCancellation() {
            if (!selectedBookingForCancel) return;

            fetch('cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `booking_id=${encodeURIComponent(selectedBookingForCancel)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update both arrays
                        const confirmedIndex = confirmedBookings.findIndex(b => b.id === selectedBookingForCancel);
                        if (confirmedIndex !== -1) {
                            confirmedBookings.splice(confirmedIndex, 1); // Remove from confirmed bookings
                        }
                        const allIndex = allBookings.findIndex(b => b.id === selectedBookingForCancel);
                        if (allIndex !== -1) {
                            allBookings[allIndex].status = 'cancelled'; // Update status in all bookings
                        }
                        // Re-render based on current view
                        if (document.getElementById('allBookingsSection').style.display === 'block') {
                            filterBookings(document.querySelector('.tab-btn.active').getAttribute('onclick').match(/'([^']+)'/)[1]);
                        } else {
                            renderBookings();
                        }
                        closeCancelModal();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the booking');
                    closeCancelModal();
                });
        }

        function calculateFare() {
            let distance = parseFloat(document.getElementById('distance').value);
            let vehicleType = document.getElementById('vehicleType').value;
            let farePerKm = vehicleType === 'taxi' ? 25 : vehicleType === 'bus' ? 10 : 15;
            if (distance > 0) {
                document.getElementById('fareResult').innerHTML =
                    `Estimated Fare for ${distance} km by ${vehicleType.toUpperCase()}: Rs. ${farePerKm * distance}`;
                document.getElementById('fareResult').style.display = 'block';
            } else {
                alert('Enter valid distance');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            showSection('home');
        });

        window.addEventListener('click', function(e) {
            const modal = document.getElementById('cancelModal');
            if (e.target === modal) {
                closeCancelModal();
            }
        });
    </script>
</body>

</html>