<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';

// Get search parameters
$origin = $_GET['origin'] ?? '';
$destination = $_GET['destination'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'bus';

// Validate vehicle type
$allowedTypes = ['bus', 'taxi', 'micro'];
if (!in_array($type, $allowedTypes)) {
    $type = 'bus';
}

// Only proceed if origin, destination, and date are provided
if ($origin !== '' && $destination !== '' && $date !== '') {
    // Enhanced query with more vehicle details
    $sql = "
        SELECT 
            w.id AS way_id, 
            w.origin, 
            w.destination, 
            w.departure_time, 
            w.arrival_time, 
            w.price,
            vt.type_name AS vehicle_type,
            v.vehicle_name,
            v.vehicle_number,
            v.capacity,
            v.facilities,
            wt.transit_point, 
            wt.transit_duration, 
            wt.transit_time
        FROM ways w
        JOIN vehicles v ON w.vehicle_id = v.id
        JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
        LEFT JOIN way_transits wt ON w.id = wt.way_id
        WHERE vt.type_name = ?
    ";

    $params = [$type];
    $types = "s";

    if ($origin !== '') {
        $sql .= " AND w.origin LIKE ?";
        $params[] = "%$origin%";
        $types .= "s";
    }

    if ($destination !== '') {
        $sql .= " AND w.destination LIKE ?";
        $params[] = "%$destination%";
        $types .= "s";
    }

    if ($date !== '') {
        $sql .= " AND DATE(w.departure_time) = ?";
        $params[] = $date;
        $types .= "s";
    }

    $sql .= " ORDER BY w.departure_time ASC";

    // Execute prepared statement
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $waysData = [];
        while ($row = $result->fetch_assoc()) {
            $id = $row['way_id'];
            if (!isset($waysData[$id])) {
                $waysData[$id] = [
                    'origin' => $row['origin'],
                    'destination' => $row['destination'],
                    'departure_time' => $row['departure_time'],
                    'arrival_time' => $row['arrival_time'],
                    'price' => $row['price'],
                    'vehicle_type' => $row['vehicle_type'],
                    'vehicle_name' => $row['vehicle_name'],
                    'vehicle_number' => $row['vehicle_number'],
                    'capacity' => $row['capacity'],
                    'facilities' => $row['facilities'],
                    'transits' => []
                ];
            }
            if (!empty($row['transit_point'])) {
                $waysData[$id]['transits'][] = [
                    'point' => $row['transit_point'],
                    'duration' => $row['transit_duration'],
                    'time' => $row['transit_time']
                ];
            }
        }
        $stmt->close();
    } else {
        $waysData = [];
    }
} else {
    $waysData = [];
}

// Get username for display
$username = $_SESSION['username'] ?? 'User';

// Function to calculate journey duration
function calculateDuration($departure, $arrival) {
    $dept = new DateTime($departure);
    $arr = new DateTime($arrival);
    $diff = $dept->diff($arr);
    
    if ($diff->h > 0) {
        return $diff->h . 'h ' . $diff->i . 'm';
    } else {
        return $diff->i . 'm';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Dashboard | BookingNepal</title>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    />
    <link rel="stylesheet" href="user_dashboard.css" />
    <link rel="shortcut icon" href="favi.png" type="image/x-icon" />
    <link rel="stylesheet" href="route_card.css">
    <style>
        /* Enhanced Route Card Styles */
        .route-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .route-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .route-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .vehicle-icon {
            background: rgba(255, 255, 255, 0.2);
            padding: 12px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .vehicle-logo {
            width: 40px;
            height: 40px;
            filter: brightness(0) invert(1);
        }

        .route-main-info {
            flex: 1;
        }

        .route-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0 0 5px 0;
        }

        .vehicle-number-badge {
            background: rgba(255, 255, 255, 0.3);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .route-details {
            padding: 20px;
        }

        .route-journey {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .journey-point {
            text-align: center;
            flex: 1;
        }

        .journey-time {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .journey-location {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }

        .journey-arrow {
            flex: 0.5;
            text-align: center;
            color: #667eea;
        }

        .journey-duration {
            font-size: 0.85rem;
            color: #64748b;
            display: block;
            margin-top: 5px;
        }

        .route-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: #e9ecef;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1rem;
            color: #2d3748;
            font-weight: 600;
            margin-top: 2px;
        }

        .facilities-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #e9ecef;
        }

        .facilities-title {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .facilities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .facility-tag {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .transit-section {
            margin-top: 15px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }

        .transit-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .transit-item {
            font-size: 0.85rem;
            color: #856404;
            padding: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .price-section {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin: 0 20px 20px 20px;
            border-radius: 12px;
        }

        .price-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .price-amount {
            font-size: 2rem;
            font-weight: 700;
        }

        .route-footer {
            padding: 0 20px 20px 20px;
        }

        .btn-book-now {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-book-now:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        /* Footer Styles */
        .site-footer {
            background-color: #1a1a1a;
            color: #ddd;
            padding: 40px 20px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin-top: 50px;
        }

        .footer-container {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 30px;
        }

        .footer-contact h3,
        .footer-socials h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: #fff;
        }

        .footer-contact p,
        .footer-contact a {
            font-size: 1rem;
            margin: 5px 0;
            color: #aaa;
            text-decoration: none;
        }

        .footer-contact a:hover {
            color: #0073e6;
        }

        .footer-socials {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .social-icon {
            color: #aaa;
            font-size: 1.5rem;
            margin-right: 15px;
            transition: color 0.3s ease;
            text-decoration: none;
        }

        .social-icon:hover {
            color: #0073e6;
        }

        .footer-bottom {
            text-align: center;
            padding: 15px 0 0;
            color: #666;
            font-size: 0.9rem;
            border-top: 1px solid #333;
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            .route-info-grid {
                grid-template-columns: 1fr;
            }

            .route-journey {
                flex-direction: column;
                gap: 15px;
            }

            .journey-arrow {
                transform: rotate(90deg);
            }
        }
    </style>
    <script>
        function setDate(daysFromNow) {
            const today = new Date();
            today.setDate(today.getDate() + daysFromNow);
            const formattedDate = today.toISOString().split('T')[0];
            document.getElementById('travelDate').value = formattedDate;
            document.getElementById('searchForm').submit();
        }
    </script>
</head>
<body>
    <header class="top-header">
        <div class="nav-container">
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-bus-alt"></i>
                </div>
                <h2>BookingNepal</h2>
            </div>
            <nav class="nav-tabs">
                <a
                    href="?type=bus&origin=<?= urlencode($origin) ?>&destination=<?= urlencode($destination) ?>&date=<?= urlencode($date) ?>"
                    class="nav-link <?= $type == 'bus' ? 'active' : '' ?>"
                >
                    <i class="fa-solid fa-bus"></i>
                    <span>Bus</span>
                </a>
                <a
                    href="?type=taxi&origin=<?= urlencode($origin) ?>&destination=<?= urlencode($destination) ?>&date=<?= urlencode($date) ?>"
                    class="nav-link <?= $type == 'taxi' ? 'active' : '' ?>"
                >
                    <i class="fa-solid fa-taxi"></i>
                    <span>Taxi</span>
                </a>
                <a
                    href="?type=micro&origin=<?= urlencode($origin) ?>&destination=<?= urlencode($destination) ?>&date=<?= urlencode($date) ?>"
                    class="nav-link <?= $type == 'micro' ? 'active' : '' ?>"
                >
                    <i class="fa-solid fa-van-shuttle"></i>
                    <span>Micro</span>
                </a>

                <a href="cancel_booking.php" class="nav-link">
                   <i class="fa-solid fa-ticket-alt"></i>
                   <span>My Bookings</span>
                </a>
           </nav>
            
            <div class="user-section">
                <span class="user-welcome">
                    <i class="fas fa-user-circle"></i>
                    <?= htmlspecialchars($username) ?>
                </span>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <section class="hero-section">
        <div class="hero-content">
            <h1>"Click. Book. Go!"</h1>
            <p class="hero-subtitle">Your Complete Transport Booking Solution</p>

            <form class="search-box" method="GET" id="searchForm">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>" />

                <div class="input-group">
                    <i class="fa-solid fa-location-dot"></i>
                    <input
                        type="text"
                        name="origin"
                        placeholder="Departure City"
                        value="<?= htmlspecialchars($origin) ?>"
                        autocomplete="off"
                    />
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-location-arrow"></i>
                    <input
                        type="text"
                        name="destination"
                        placeholder="Destination City"
                        value="<?= htmlspecialchars($destination) ?>"
                        autocomplete="off"
                    />
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-calendar-days"></i>
                    <input
                        type="date"
                        name="date"
                        id="travelDate"
                        value="<?= htmlspecialchars($date) ?>"
                        min="<?= date('Y-m-d') ?>"
                    />
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-today" onclick="setDate(0)">
                        <i class="fas fa-calendar-day"></i> Today
                    </button>
                    <button type="button" class="btn btn-tomorrow" onclick="setDate(1)">
                        <i class="fas fa-calendar-plus"></i> Tomorrow
                    </button>
                    <button type="submit" class="btn btn-search">
                        <i class="fas fa-search"></i> Search Routes
                    </button>
                </div>
            </form>
        </div>
    </section>
    
    <section class="routes-section" id="availableWays">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-route"></i> Available <?= ucfirst(htmlspecialchars($type)) ?> Routes
                <?php if ($origin || $destination): ?>
                <span class="route-filter">
                    <?php if ($origin): ?>from <strong><?= htmlspecialchars($origin) ?></strong><?php endif; ?>
                    <?php if ($destination): ?>to <strong><?= htmlspecialchars($destination) ?></strong><?php endif; ?>
                </span>
                <?php endif; ?>
            </h2>

            <?php if (!empty($waysData)): ?>
            <p class="results-count">
                <i class="fas fa-info-circle"></i>
                Found <?= count($waysData) ?> route<?= count($waysData) != 1 ? 's' : '' ?> for
                <?= date('M d, Y', strtotime($date)) ?>
            </p>
            <?php elseif ($origin && $destination && $date): ?>
            <p class="results-count">
                <i class="fas fa-info-circle"></i> No routes found for your selected criteria.
            </p>
            <?php endif; ?>
        </div>

        <?php if (!empty($waysData)): ?>
        <div class="route-grid">
            <?php foreach ($waysData as $id => $way): ?>
            <div class="route-card">
                <!-- Route Header -->
                <div class="route-header">
                    <div class="vehicle-icon">
                        <?php if ($way['vehicle_type'] == 'bus'): ?>
                        <i class="fas fa-bus fa-2x"></i>
                        <?php elseif ($way['vehicle_type'] == 'taxi'): ?>
                        <i class="fas fa-taxi fa-2x"></i>
                        <?php elseif ($way['vehicle_type'] == 'micro'): ?>
                        <i class="fas fa-van-shuttle fa-2x"></i>
                        <?php endif; ?>
                    </div>
                    <div class="route-main-info">
                        <h3 class="route-name"><?= htmlspecialchars($way['vehicle_name']) ?></h3>
                        <span class="vehicle-number-badge">
                            <i class="fas fa-hashtag"></i> <?= htmlspecialchars($way['vehicle_number']) ?>
                        </span>
                    </div>
                </div>

                <!-- Route Details -->
                <div class="route-details">
                    <!-- Journey Timeline -->
                    <div class="route-journey">
                        <div class="journey-point">
                            <div class="journey-time"><?= date('H:i', strtotime($way['departure_time'])) ?></div>
                            <div class="journey-location"><?= htmlspecialchars($way['origin']) ?></div>
                        </div>
                        <div class="journey-arrow">
                            <i class="fas fa-arrow-right fa-2x"></i>
                            <span class="journey-duration">
                                <?= calculateDuration($way['departure_time'], $way['arrival_time']) ?>
                            </span>
                        </div>
                        <div class="journey-point">
                            <div class="journey-time"><?= date('H:i', strtotime($way['arrival_time'])) ?></div>
                            <div class="journey-location"><?= htmlspecialchars($way['destination']) ?></div>
                        </div>
                    </div>

                    <!-- Info Grid -->
                    <div class="route-info-grid">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Capacity</div>
                                <div class="info-value"><?= htmlspecialchars($way['capacity']) ?> Seats</div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-car"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Vehicle Type</div>
                                <div class="info-value"><?= ucfirst(htmlspecialchars($way['vehicle_type'])) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Facilities -->
                    <?php if (!empty($way['facilities'])): ?>
                    <div class="facilities-section">
                        <div class="facilities-title">
                            <i class="fas fa-star"></i> Facilities & Amenities
                        </div>
                        <div class="facilities-list">
                            <?php 
                            $facilities = explode(',', $way['facilities']);
                            foreach ($facilities as $facility): 
                                $facility = trim($facility);
                                $icon = 'fa-check-circle';
                                if (stripos($facility, 'ac') !== false || stripos($facility, 'air') !== false) {
                                    $icon = 'fa-snowflake';
                                } elseif (stripos($facility, 'wifi') !== false) {
                                    $icon = 'fa-wifi';
                                } elseif (stripos($facility, 'charging') !== false || stripos($facility, 'usb') !== false) {
                                    $icon = 'fa-plug';
                                } elseif (stripos($facility, 'water') !== false) {
                                    $icon = 'fa-bottle-water';
                                } elseif (stripos($facility, 'tv') !== false || stripos($facility, 'screen') !== false) {
                                    $icon = 'fa-tv';
                                }
                            ?>
                            <span class="facility-tag">
                                <i class="fas <?= $icon ?>"></i>
                                <?= htmlspecialchars($facility) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Transit Points -->
                    <?php if (!empty($way['transits'])): ?>
                    <div class="transit-section">
                        <div class="transit-title">
                            <i class="fas fa-route"></i> Transit Points
                        </div>
                        <?php foreach ($way['transits'] as $transit): ?>
                        <div class="transit-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <strong><?= htmlspecialchars($transit['point']) ?></strong>
                            <?php if ($transit['time']): ?>
                                - <?= date('H:i', strtotime($transit['time'])) ?>
                            <?php endif; ?>
                            <?php if ($transit['duration']): ?>
                                (<?= htmlspecialchars($transit['duration']) ?> min stop)
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Price Section -->
                <div class="price-section">
                    <div class="price-label">Total Fare</div>
                    <div class="price-amount">NPR <?= number_format($way['price'], 2) ?></div>
                </div>

                <!-- Booking Button -->
                <div class="route-footer">
                    <form action="user_booking.php" method="POST">
                        <input type="hidden" name="way_id" value="<?= $id ?>" />
                        <input type="hidden" name="travel_date" value="<?= htmlspecialchars($date) ?>" />
                        <button type="submit" class="btn-book-now">
                            <i class="fas fa-ticket-alt"></i> Book Now
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3>No Routes Found</h3>
            <p>
                We couldn't find any <?= htmlspecialchars($type) ?> routes matching your search criteria.
            </p>
            <p class="empty-suggestion">
                Try adjusting your search filters or selecting a different date.
            </p>
        </div>
        <?php endif; ?>
    </section>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-contact">
                <h3>Contact Us</h3>
                <p>BookingNepal Transport Solutions</p>
                <p>Email: <a href="mailto:bikashtransportt@gmail.com">bikashtransportt@gmail.com</a></p>
                <p>Phone: <a href="tel:9748777251">9748777251</a></p>
                <p>Address: Butwal-10, Rupandehi, Nepal</p>
            </div>
            <div class="footer-socials">
                <h3>Follow Us</h3>
                <a href="https://www.facebook.com/login" aria-label="Facebook" class="social-icon" target="_blank">
                    <i class="fab fa-facebook-f"></i> Facebook
                </a>
                <a href="https://x.com/i/flow/login" aria-label="Twitter" class="social-icon" target="_blank">
                    <i class="fab fa-twitter"></i> X (Twitter)
                </a>
                <a href="https://www.instagram.com/accounts/login/" aria-label="Instagram" class="social-icon" target="_blank">
                    <i class="fab fa-instagram"></i> Instagram
                </a>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?= date('Y') ?> BookingNepal. All Rights Reserved.
        </div>
    </footer>
</body>
</html>