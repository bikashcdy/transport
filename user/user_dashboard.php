<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';

// Get search parameters
$date = $_GET['date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$type = $_GET['type'] ?? 'bus';

// Validate vehicle type
$allowedTypes = ['bus', 'taxi', 'micro'];
if (!in_array($type, $allowedTypes)) {
    $type = 'bus';
}

$vehiclesData = [];
$bookedVehicleIds = [];

// Only proceed if dates are provided
if ($date !== '' && $end_date !== '') {
    // Get all booked vehicle IDs for the date range (FIXED)
    $bookedSql = "
        SELECT DISTINCT vehicle_id
        FROM bookings
        WHERE status IN ('confirmed', 'pending')
        AND (
            (DATE(trip_start) BETWEEN ? AND ?)
            OR (DATE(trip_end) BETWEEN ? AND ?)
            OR (DATE(trip_start) <= ? AND DATE(trip_end) >= ?)
        )
    ";
    
    $bookedStmt = $conn->prepare($bookedSql);
    if ($bookedStmt) {
        $bookedStmt->bind_param("ssssss", 
            $date, $end_date,
            $date, $end_date,
            $date, $end_date
        );
        $bookedStmt->execute();
        $bookedResult = $bookedStmt->get_result();
        
        while ($bookedRow = $bookedResult->fetch_assoc()) {
            $bookedVehicleIds[] = $bookedRow['vehicle_id'];
        }
        $bookedStmt->close();

    }
    
    // Query to get ALL vehicles by type with their availability status
    $sql = "
        SELECT 
            v.id,
            v.vehicle_number,
            v.vehicle_name,
            v.capacity,
            v.facilities,
            v.price,
            v.status,
            vt.type_name AS vehicle_type
        FROM vehicles v
        JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
        WHERE vt.type_name = ?
        AND v.status = 'available'
        ORDER BY v.vehicle_name ASC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Mark if vehicle is booked for selected dates
            $row['is_booked'] = in_array($row['id'], $bookedVehicleIds);
            $vehiclesData[] = $row;
        }
        $stmt->close();
    }
}

// Get username for display
$username = $_SESSION['username'] ?? 'User';
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
        /* Enhanced Vehicle Card Styles */
        .vehicles-section {
            padding: 40px 20px;
            background: #f8f9fa;
            min-height: 400px;
        }

        .vehicles-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .section-title {
            font-size: 2rem;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .results-count {
            color: #64748b;
            font-size: 1rem;
        }

        .vehicle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .vehicle-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .vehicle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .vehicle-header {
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
            font-size: 2rem;
        }

        .vehicle-main-info {
            flex: 1;
        }

        .vehicle-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .vehicle-number-badge {
            background: rgba(255, 255, 255, 0.3);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
        }

        .vehicle-details {
            padding: 25px;
        }

        .date-range-display {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }

        .date-range-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .date-range-dates {
            font-size: 1.1rem;
            color: #667eea;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .status-banner {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .status-available {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }

        .status-booked {
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
            color: white;
        }

        .vehicle-card.booked {
            opacity: 0.7;
        }

        .vehicle-card.booked .vehicle-header {
            background: linear-gradient(135deg, #a0aec0 0%, #718096 100%);
        }

        .vehicle-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .info-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 1.1rem;
            color: #2d3748;
            font-weight: 700;
        }

        .facilities-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #e9ecef;
        }

        .facilities-title {
            font-size: 1rem;
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .facilities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .facility-tag {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .price-section {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 25px;
            margin: 0 25px 25px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
            text-align: center;
        }

        .price-label {
            font-size: 1rem;
            opacity: 0.95;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .price-amount {
            font-size: 2.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .vehicle-footer {
            padding: 0 25px 25px 25px;
        }

        .btn-book-now {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 12px;
            font-size: 1.15rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-book-now:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
        }

        .btn-book-now:active {
            transform: scale(0.98);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .empty-icon {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 10px;
        }

        .empty-suggestion {
            font-weight: 600;
            color: #667eea;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .vehicle-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .vehicles-container {
                padding: 0 10px;
            }

            .vehicle-info-grid {
                grid-template-columns: 1fr;
            }

            .vehicle-header {
                padding: 15px;
            }

            .vehicle-details {
                padding: 15px;
            }

            .price-section {
                padding: 20px;
                margin: 0 15px 15px 15px;
            }

            .vehicle-footer {
                padding: 0 15px 15px 15px;
            }
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

        .date-group {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .date-input-wrapper {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .date-label {
            font-size: 0.85rem;
            color: #4a5568;
            font-weight: 600;
            padding-left: 5px;
        }

        .date-input-wrapper .input-group {
            margin: 0;
        }

        @media (max-width: 768px) {
            .date-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                    href="?type=bus&date=<?= urlencode($date) ?>&end_date=<?= urlencode($end_date) ?>"
                    class="nav-link <?= $type == 'bus' ? 'active' : '' ?>"
                >
                    <i class="fa-solid fa-bus"></i>
                    <span>Bus</span>
                </a>
                <a
                    href="?type=taxi&date=<?= urlencode($date) ?>&end_date=<?= urlencode($end_date) ?>"
                    class="nav-link <?= $type == 'taxi' ? 'active' : '' ?>"
                >
                    <i class="fa-solid fa-taxi"></i>
                    <span>Taxi</span>
                </a>
                <a
                    href="?type=micro&date=<?= urlencode($date) ?>&end_date=<?= urlencode($end_date) ?>"
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

                <div class="date-group">
                    <div class="date-input-wrapper">
                        <label class="date-label">Trip Starts</label>
                        <div class="input-group">
                            <i class="fa-solid fa-calendar-days"></i>
                            <input
                                type="date"
                                name="date"
                                id="travelDate"
                                value="<?= htmlspecialchars($date) ?>"
                                min="<?= date('Y-m-d') ?>"
                                required
                            />
                        </div>
                    </div>
                    
                    <div class="date-input-wrapper">
                        <label class="date-label">Trip Ends</label>
                        <div class="input-group"> 
                            <i class="fa-solid fa-calendar-days"></i>
                            <input
                                type="date"
                                name="end_date"
                                id="endDate"
                                value="<?= htmlspecialchars($end_date) ?>"
                                min="<?= htmlspecialchars($date ?: date('Y-m-d')) ?>"
                                required
                            />
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-search">
                        <i class="fas fa-search"></i> Search Available Vehicles
                    </button>
                </div>
            </form>
        </div>
    </section>
    
    <section class="vehicles-section" id="availableVehicles">
        <div class="vehicles-container">
            <?php if ($date && $end_date): ?>
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-car"></i> Available <?= ucfirst(htmlspecialchars($type)) ?> Vehicles
                </h2>

                <?php if (!empty($vehiclesData)): ?>
                <p class="results-count">
                    <i class="fas fa-info-circle"></i>
                    Showing <?= count($vehiclesData) ?> vehicle<?= count($vehiclesData) != 1 ? 's' : '' ?> 
                    (<?= count(array_filter($vehiclesData, function($v) { return !$v['is_booked']; })) ?> available, 
                    <?= count(array_filter($vehiclesData, function($v) { return $v['is_booked']; })) ?> booked)
                </p>
                <?php else: ?>
                <p class="results-count">
                    <i class="fas fa-exclamation-circle"></i> No <?= htmlspecialchars($type) ?> vehicles found.
                </p>
                <?php endif; ?>
            </div>

            <?php if (!empty($vehiclesData)): ?>
            <div class="vehicle-grid">
                <?php foreach ($vehiclesData as $vehicle): ?>
                <div class="vehicle-card <?= $vehicle['is_booked'] ? 'booked' : '' ?>">
                    <!-- Vehicle Header -->
                    <div class="vehicle-header">
                        <div class="vehicle-icon">
                            <?php if ($vehicle['vehicle_type'] == 'bus'): ?>
                            <i class="fas fa-bus"></i>
                            <?php elseif ($vehicle['vehicle_type'] == 'taxi'): ?>
                            <i class="fas fa-taxi"></i>
                            <?php elseif ($vehicle['vehicle_type'] == 'micro'): ?>
                            <i class="fas fa-van-shuttle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="vehicle-main-info">
                            <h3 class="vehicle-name"><?= htmlspecialchars($vehicle['vehicle_name']) ?></h3>
                            <span class="vehicle-number-badge">
                                <i class="fas fa-hashtag"></i> <?= htmlspecialchars($vehicle['vehicle_number']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Vehicle Details -->
                    <div class="vehicle-details">
                        <!-- Date Range Display -->
                        <div class="date-range-display">
                            <div class="date-range-label">Your Booking Period</div>
                            <div class="date-range-dates">
                                <span><?= date('M d, Y', strtotime($date)) ?></span>
                                <i class="fas fa-arrow-right"></i>
                                <span><?= date('M d, Y', strtotime($end_date)) ?></span>
                            </div>
                        </div>

                        <!-- Status Banner -->
                        <?php if ($vehicle['is_booked']): ?>
                        <div class="status-banner status-booked">
                            <i class="fas fa-times-circle"></i>
                            <span>Already Booked for These Dates</span>
                        </div>
                        <?php else: ?>
                        <div class="status-banner status-available">
                            <i class="fas fa-check-circle"></i>
                            <span>Available for These Dates</span>
                        </div>
                        <?php endif; ?>

                        <!-- Info Grid -->
                        <div class="vehicle-info-grid">
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Capacity</div>
                                    <div class="info-value"><?= htmlspecialchars($vehicle['capacity']) ?> Seats</div>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-car"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Type</div>
                                    <div class="info-value"><?= ucfirst(htmlspecialchars($vehicle['vehicle_type'])) ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Facilities -->
                        <?php if (!empty($vehicle['facilities'])): ?>
                        <div class="facilities-section">
                            <div class="facilities-title">
                                <i class="fas fa-star"></i> Facilities & Amenities
                            </div>
                            <div class="facilities-list">
                                <?php 
                                $facilities = explode(',', $vehicle['facilities']);
                                foreach ($facilities as $facility): 
                                    $facility = trim($facility);
                                    if (empty($facility)) continue;
                                    
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
                    </div>
<!-- Price Section -->
<div class="price-section">
    <?php 
    // Calculate number of days
    $start = new DateTime($date);
    $end = new DateTime($end_date);
    $days = $start->diff($end)->days;
    if ($days == 0) $days = 1; // Minimum 1 day
    
    $daily_rate = $vehicle['price'];
    $total_price = $daily_rate * $days;
    ?>
    <div class="price-label">Total Price</div>
    <div class="price-amount">
        Rs. <?= number_format($total_price, 2) ?>
    </div>
</div>
                    <!-- Booking Button -->
                    <div class="vehicle-footer">
                        <?php if ($vehicle['is_booked']): ?>
                        <button class="btn-book-now" style="background: linear-gradient(135deg, #a0aec0 0%, #718096 100%); cursor: not-allowed;" disabled>
                            <i class="fas fa-ban"></i> Not Available
                        </button>
                        <?php else: ?>
                       <!-- NEW -->
                        <form action="booking_process.php" method="POST">
                            <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>" />
                            <input type="hidden" name="start_date" value="<?= htmlspecialchars($date) ?>" />
                            <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>" />
                            <button type="submit" class="btn-book-now">
                                <i class="fas fa-ticket-alt"></i> Book This Vehicle
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3>No Vehicles Available</h3>
                <p>
                    Sorry, all <?= htmlspecialchars($type) ?> vehicles are booked for the selected dates.
                </p>
                <p class="empty-suggestion">
                    Try selecting different dates or check other vehicle types.
                </p>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Search for Available Vehicles</h3>
                <p>
                    Please select your trip start and end dates above to view available vehicles.
                </p>
                <p class="empty-suggestion">
                    <i class="fas fa-arrow-up"></i> Use the search form to get started
                </p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-contact">
                <h3>Contact Us</h3>
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

    <script>
       
        // Update end date minimum when start date changes
        document.getElementById('travelDate').addEventListener('change', function() {
            document.getElementById('endDate').min = this.value;
            if (document.getElementById('endDate').value && document.getElementById('endDate').value < this.value) {
                document.getElementById('endDate').value = this.value;
            }
        });

        // Make calendar icons clickable to open date picker
        document.querySelectorAll('.input-group i.fa-calendar-days').forEach(function(icon) {
            icon.style.cursor = 'pointer';
            icon.addEventListener('click', function() {
                // Find the date input in the same input-group
                const dateInput = this.parentElement.querySelector('input[type="date"]');
                if (dateInput) {
                    try {
                        dateInput.showPicker(); // Modern browsers support this method
                    } catch (e) {
                        // Fallback for older browsers
                        dateInput.focus();
                        dateInput.click();
                    }
                }
            });
        });

        // Update end date minimum when start date changes
        document.getElementById('travelDate').addEventListener('change', function() {
            document.getElementById('endDate').min = this.value;
            if (document.getElementById('endDate').value && document.getElementById('endDate').value < this.value) {
                document.getElementById('endDate').value = this.value;
            }
        });
    </script>
</body>
</html>