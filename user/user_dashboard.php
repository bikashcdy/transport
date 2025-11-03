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

// Prepare SQL query with proper escaping
$sql = "
    SELECT w.id AS way_id, w.origin, w.destination, w.departure_time, w.arrival_time, w.price,
           v.vehicle_type, v.vehicle_name,
           wt.transit_point, wt.transit_duration, wt.transit_time
    FROM ways w
    JOIN vehicles v ON w.vehicle_id = v.id
    LEFT JOIN way_transits wt ON w.id = wt.way_id
    WHERE v.vehicle_type = ?
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

// Get username for display
$username = $_SESSION['username'] ?? 'User';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | BookingNepal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="user_dashboard.css">
    <link rel="shortcut icon" href="favi.png" type="image/x-icon">
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
            <a href="?type=bus&origin=<?= urlencode($origin) ?>&destination=<?= urlencode($destination) ?>&date=<?= urlencode($date) ?>" 
               class="nav-link <?= $type == 'bus' ? 'active' : '' ?>">
                <i class="fa-solid fa-bus"></i>
                <span>Bus</span>
            </a>
            <a href="?type=taxi&origin=<?= urlencode($origin) ?>&destination=<?= urlencode($destination) ?>&date=<?= urlencode($date) ?>" 
               class="nav-link <?= $type == 'taxi' ? 'active' : '' ?>">
                <i class="fa-solid fa-taxi"></i>
                <span>Taxi</span>
            </a>
            <a href="?type=micro&origin=<?= urlencode($origin) ?>&destination=<?= urlencode($destination) ?>&date=<?= urlencode($date) ?>" 
               class="nav-link <?= $type == 'micro' ? 'active' : '' ?>">
                <i class="fa-solid fa-van-shuttle"></i>
                <span>Micro</span>
            </a>
        </nav>
        <div class="user-section">
            <span class="user-welcome">
                <i class="fas fa-user-circle"></i>
                <?= htmlspecialchars($username) ?>
            </span>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</header>

<section class="hero-section">
    <div class="hero-content">
        <h1>"Click. Book. Go!"</h1>
        <p class="hero-subtitle">Your Complete Transport Booking Solution</p>

        <form class="search-box" method="GET" id="searchForm">
            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

            <div class="input-group">
                <i class="fa-solid fa-location-dot"></i>
                <input type="text" 
                       name="origin" 
                       placeholder="Departure City" 
                       value="<?= htmlspecialchars($origin) ?>"
                       autocomplete="off">
            </div>

            <div class="input-group">
                <i class="fa-solid fa-location-arrow"></i>
                <input type="text" 
                       name="destination" 
                       placeholder="Destination City" 
                       value="<?= htmlspecialchars($destination) ?>"
                       autocomplete="off">
            </div>

            <div class="input-group">
                <i class="fa-solid fa-calendar-days"></i>
                <input type="date" 
                       name="date" 
                       id="travelDate" 
                       value="<?= htmlspecialchars($date) ?>"
                       min="<?= date('Y-m-d') ?>">
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
            <i class="fas fa-route"></i>
            Available <?= ucfirst(htmlspecialchars($type)) ?> Routes
            <?php if ($origin || $destination): ?>
                <span class="route-filter">
                    <?php if ($origin): ?>
                        from <strong><?= htmlspecialchars($origin) ?></strong>
                    <?php endif; ?>
                    <?php if ($destination): ?>
                        to <strong><?= htmlspecialchars($destination) ?></strong>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </h2>
        <?php if (!empty($waysData)): ?>
            <p class="results-count">
                <i class="fas fa-info-circle"></i>
                Found <?= count($waysData) ?> route<?= count($waysData) != 1 ? 's' : '' ?> for <?= date('M d, Y', strtotime($date)) ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if (!empty($waysData)): ?>
        <div class="route-grid">
            <?php foreach ($waysData as $id => $way): ?>
                <div class="route-card">
                    <div class="route-header">
                        <div class="route-title">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($way['origin']) ?></span>
                            <i class="fas fa-arrow-right"></i>
                            <span><?= htmlspecialchars($way['destination']) ?></span>
                        </div>
                        <div class="vehicle-badge">
                            <i class="fas fa-<?= $way['vehicle_type'] == 'bus' ? 'bus' : ($way['vehicle_type'] == 'taxi' ? 'taxi' : 'van-shuttle') ?>"></i>
                            <?= ucfirst(htmlspecialchars($way['vehicle_type'])) ?>
                        </div>
                    </div>

                    <div class="route-body">
                        <div class="route-info">
                            <div class="info-item">
                                <i class="fas fa-bus"></i>
                                <div>
                                    <span class="info-label">Vehicle</span>
                                    <span class="info-value"><?= htmlspecialchars($way['vehicle_name']) ?></span>
                                </div>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <span class="info-label">Departure</span>
                                    <span class="info-value"><?= date("g:i A", strtotime($way['departure_time'])) ?></span>
                                </div>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <span class="info-label">Arrival</span>
                                    <span class="info-value"><?= date("g:i A", strtotime($way['arrival_time'])) ?></span>
                                </div>
                            </div>

                            <div class="info-item price-item">
                                <i class="fas fa-tag"></i>
                                <div>
                                    <span class="info-label">Price</span>
                                    <span class="info-value price">₹<?= number_format($way['price'], 2) ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($way['transits'])): ?>
                            <div class="transit-section">
                                <h4><i class="fas fa-map-signs"></i> Transit Stops</h4>
                                <div class="transit-list">
                                    <?php foreach ($way['transits'] as $transit): ?>
                                        <div class="transit-badge">
                                            <i class="fas fa-circle"></i>
                                            <span class="transit-point"><?= htmlspecialchars($transit['point']) ?></span>
                                            <span class="transit-time"><?= date("g:i A", strtotime($transit['time'])) ?></span>
                                            <span class="transit-duration">(<?= (int) $transit['duration'] ?> min)</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="route-footer">
                        <form action="user_booking.php" method="POST">
                            <input type="hidden" name="way_id" value="<?= $id ?>">
                            <input type="hidden" name="travel_date" value="<?= htmlspecialchars($date) ?>">
                            <button type="submit" class="btn-book">
                                <i class="fas fa-ticket-alt"></i>
                                Book Now
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
            <p>We couldn't find any <?= htmlspecialchars($type) ?> routes matching your search criteria.</p>
            <p class="empty-suggestion">Try adjusting your search filters or selecting a different date.</p>
        </div>
    <?php endif; ?>
</section>

<section class="services-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-calculator"></i>
            Fare Calculator
        </h2>
    </div>
    
    <div class="services-grid">
        <div class="service-card">
            <div class="service-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <h3>Estimate Your Travel Cost</h3>
            <p class="service-description">Calculate your estimated fare based on distance and vehicle type</p>
            
            <div class="fare-form">
                <div class="form-group">
                    <label for="fromLocation">
                        <i class="fas fa-map-marker-alt"></i>
                        From Location
                    </label>
                    <input type="text" 
                           id="fromLocation" 
                           placeholder="Enter pickup location"
                           autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="toLocation">
                        <i class="fas fa-map-marker-alt"></i>
                        To Location
                    </label>
                    <input type="text" 
                           id="toLocation" 
                           placeholder="Enter destination"
                           autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="distance">
                        <i class="fas fa-road"></i>
                        Distance (km)
                    </label>
                    <input type="number" 
                           id="distance" 
                           placeholder="Enter distance in kilometers"
                           min="1"
                           step="0.1">
                </div>

                <div class="form-group">
                    <label for="vehicleType">
                        <i class="fas fa-car"></i>
                        Transport Type
                    </label>
                    <select id="vehicleType">
                        <option value="bus">Bus - ₹4/km</option>
                        <option value="micro">Micro - ₹15/km</option>
                        <option value="taxi">Taxi - ₹25/km</option>
                    </select>
                </div>

                <button class="btn-calculate" onclick="calculateFare()">
                    <i class="fas fa-calculator"></i>
                    Calculate Fare
                </button>

                <div class="result-display" id="fareResult"></div>
   
         </div>
        </div>
    </div>
</section>

<section class="contact-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-envelope"></i>
            Contact Us
        </h2>
        <p class="section-subtitle">We're here to help! Get in touch with us</p>
    </div>
    
    <div class="contact-container">
        <div class="contact-info">
            <h3><i class="fas fa-info-circle"></i> Our Information</h3>
            
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <strong>Address</strong>
                    <p>Rupandehi, Butwal-10, Lumbini, Nepal</p>
                </div>
            </div>

            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <div>
                    <strong>Phone</strong>
                    <p>+977 9748777251</p>
                </div>
            </div>

            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <strong>Email</strong>
                    <p>bikashtransportt@gmail.com</p>
                </div>
            </div>

            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <div>
                    <strong>Business Hours</strong>
                    <p>24/7 Available</p>
                </div>
            </div>
        </div>

        <div class="contact-form">
            <h3><i class="fas fa-paper-plane"></i> Send a Message</h3>
            
            <form action="contact_process.php" method="POST" id="contactForm">
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i>
                        Your Name
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           placeholder="Enter your full name"
                           required>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           placeholder="Enter your email"
                           required>
                </div>

                <div class="form-group">
                    <label for="message">
                        <i class="fas fa-comment"></i>
                        Message
                    </label>
                    <textarea name="message" 
                              id="message" 
                              rows="5" 
                              placeholder="Write your message here..."
                              required></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i>
                    Send Message
                </button>
            </form>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="footer-content">
        <p>&copy; <?= date('Y') ?> Booki    Nepal. All rights reserved.</p>
        <p>Your trusted partner for transportation booking in Nepal</p>
    </div>
</footer>

<script>
function setDate(offset) {
    const dateInput = document.getElementById('travelDate');
    if (!dateInput) return;
    
    const date = new Date();
    date.setDate(date.getDate() + offset);
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    dateInput.value = `${year}-${month}-${day}`;
}

function calculateFare() {
    const fromLocation = document.getElementById('fromLocation').value.trim();
    const toLocation = document.getElementById('toLocation').value.trim();
    const distance = parseFloat(document.getElementById('distance').value);
    const vehicleType = document.getElementById('vehicleType').value;
    const resultDisplay = document.getElementById('fareResult');
    
    // Validate inputs
    if (!fromLocation || !toLocation) {
        showNotification('Please enter both pickup and destination locations', 'warning');
        return;
    }
    
    if (!distance || distance <= 0) {
        showNotification('Please enter a valid distance greater than 0', 'warning');
        return;
    }
    
    // Define fare rates per kilometer
    const fareRates = {
        'bus': 4,
        'micro': 15,
        'taxi': 25
    };
    
    const ratePerKm = fareRates[vehicleType];
    const totalFare = (ratePerKm * distance).toFixed(2);
    
    // Display result with animation
    resultDisplay.innerHTML = `
        <div style="margin-bottom: 0.5rem;">
            <i class="fas fa-route"></i> 
            <strong>${fromLocation}</strong> → <strong>${toLocation}</strong>
        </div>
        <div style="font-size: 1.3rem; margin-bottom: 0.5rem;">
            <i class="fas fa-rupee-sign"></i> ₹${totalFare}
        </div>
        <div style="font-size: 0.9rem; opacity: 0.9;">
            ${distance} km × ₹${ratePerKm}/km via ${vehicleType.toUpperCase()}
        </div>
    `;
    
    resultDisplay.classList.add('show');
    resultDisplay.style.display = 'block';
    
    // Scroll to result
    resultDisplay.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ================================
// NOTIFICATIONS
// ================================

/**
 * Shows a notification message
 * @param {string} message - The message to display
 * @param {string} type - Type of notification (success, warning, error, info)
 */
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    // Set icon based on type
    let icon = 'info-circle';
    switch(type) {
        case 'success':
            icon = 'check-circle';
            break;
        case 'warning':
            icon = 'exclamation-triangle';
            break;
        case 'error':
            icon = 'times-circle';
            break;
    }
    
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add styles if not already present
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 100px;
                right: 20px;
                background: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                gap: 1rem;
                z-index: 10000;
                animation: slideInRight 0.3s ease;
                max-width: 400px;
            }
            
            .notification-success {
                border-left: 4px solid #10b981;
                color: #065f46;
            }
            
            .notification-warning {
                border-left: 4px solid #f59e0b;
                color: #92400e;
            }
            
            .notification-error {
                border-left: 4px solid #ef4444;
                color: #991b1b;
            }
            
            .notification-info {
                border-left: 4px solid #3b82f6;
                color: #1e40af;
            }
            
            .notification i:first-child {
                font-size: 1.5rem;
            }
            
            .notification span {
                flex: 1;
            }
            
            .notification-close {
                background: none;
                border: none;
                cursor: pointer;
                padding: 0.25rem;
                opacity: 0.6;
                transition: opacity 0.2s;
            }
            
            .notification-close:hover {
                opacity: 1;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @media (max-width: 768px) {
                .notification {
                    right: 10px;
                    left: 10px;
                    max-width: none;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// ================================
// FORM VALIDATION
// ================================

/**
 * Validates the search form before submission
 */
function validateSearchForm(event) {
    const form = event.target;
    const origin = form.querySelector('[name="origin"]').value.trim();
    const destination = form.querySelector('[name="destination"]').value.trim();
    
    if (!origin && !destination) {
        event.preventDefault();
        showNotification('Please enter at least one location (From or To)', 'warning');
        return false;
    }
    
    return true;
}

/**
 * Validates the contact form before submission
 */
function validateContactForm(event) {
    const form = event.target;
    const name = form.querySelector('[name="name"]').value.trim();
    const email = form.querySelector('[name="email"]').value.trim();
    const message = form.querySelector('[name="message"]').value.trim();
    
    if (!name || !email || !message) {
        event.preventDefault();
        showNotification('Please fill in all required fields', 'warning');
        return false;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        event.preventDefault();
        showNotification('Please enter a valid email address', 'warning');
        return false;
    }
    
    return true;
}

// ================================
// SMOOTH SCROLLING
// ================================

/**
 * Adds smooth scrolling to anchor links
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// ================================
// LOADING STATES
// ================================

/**
 * Shows loading state for forms
 */
function showLoadingState(button) {
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.style.opacity = '0.7';
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    
    return () => {
        button.disabled = false;
        button.style.opacity = '1';
        button.innerHTML = originalContent;
    };
}

// ================================
// SEARCH HISTORY (Optional Enhancement)
// ================================

/**
 * Saves recent searches to localStorage
 */
function saveSearchHistory(origin, destination, type) {
    try {
        let history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
        
        // Add new search
        history.unshift({
            origin,
            destination,
            type,
            timestamp: new Date().toISOString()
        });
        
        // Keep only last 5 searches
        history = history.slice(0, 5);
        
        localStorage.setItem('searchHistory', JSON.stringify(history));
    } catch (e) {
        console.error('Failed to save search history:', e);
    }
}

/**
 * Loads and displays search history
 */
function loadSearchHistory() {
    try {
        const history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
        return history;
    } catch (e) {
        console.error('Failed to load search history:', e);
        return [];
    }
}

// ================================
// INITIALIZATION
// ================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize smooth scrolling
    initSmoothScroll();
    
    // Add form validation
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', validateSearchForm);
    }
    
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', validateContactForm);
    }
    
    // Set minimum date for date input
    const dateInput = document.getElementById('travelDate');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
    
    // Add input animations
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
    
    // Add animation to route cards
    const routeCards = document.querySelectorAll('.route-card');
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    entry.target.style.transition = 'all 0.5s ease';
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    routeCards.forEach(card => observer.observe(card));
    
    // Save current search to history
    const urlParams = new URLSearchParams(window.location.search);
    const origin = urlParams.get('origin');
    const destination = urlParams.get('destination');
    const type = urlParams.get('type');
    
    if (origin || destination) {
        saveSearchHistory(origin || '', destination || '', type || 'bus');
    }
    
    console.log('TravelNepal Dashboard initialized successfully');
});

// ================================
// KEYBOARD SHORTCUTS
// ================================

document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const originInput = document.querySelector('[name="origin"]');
        if (originInput) {
            originInput.focus();
        }
    }
});
</script>

</body>

</html>