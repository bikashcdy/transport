    <!DOCTYPE html>('server.php')
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Transport Management System(TMS)</title>
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
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

            .hero-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 2rem;
            }

            .hero h1 {
                font-size: 3.5rem;
                margin-bottom: 1rem;
                font-weight: 700;
            }

            .hero p {
                font-size: 1.2rem;
                margin-bottom: 2rem;
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }

            .hero-buttons {
                display: flex;
                gap: 1rem;
                justify-content: center;
                margin-top: 2rem;
            }

            .btn-primary {
                background: #4f46e5;
                color: white;
                padding: 1rem 2rem;
                border: none;
                border-radius: 25px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s;
            }

            .btn-secondary {
                background: #10b981;
                color: white;
                padding: 1rem 2rem;
                border: none;
                border-radius: 25px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s;
            }

            .btn-primary:hover, .btn-secondary:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }

            .overview {
                padding: 4rem 0;
                background: #f8fafc;
            }

            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 2rem;
            }

            .feature-card {
                background: white;
                padding: 2rem;
                border-radius: 15px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.1);
                text-align: center;
                transition: transform 0.3s;
            }

            .feature-card:hover {
                transform: translateY(-5px);
            }

        
            .services {
                padding: 4rem 0;
                background: #f8fafc;
                min-height: 100vh;
            }

            .services-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 2rem;
            }

            .section-title {
                text-align: center;
                font-size: 2.5rem;
                margin-bottom: 3rem;
                color: #1a1b5e;
            }

            .services-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
                gap: 2rem;
                margin-bottom: 3rem;
            }

            .service-card {
                background: white;
                padding: 2rem;
                border-radius: 15px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.1);
                transition: transform 0.3s;
            }

            .service-card:hover {
                transform: translateY(-5px);
            }

            .service-icon {
                font-size: 3rem;
                margin-bottom: 1rem;
                display: block;
            }

            .service-card h3 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
                color: #1a1b5e;
            }

            .service-card p {
                margin-bottom: 1.5rem;
                color: #666;
            }

        
            .booking-form, .fare-form, .cancel-form {
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

            .form-group input, .form-group select {
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
                background: rgba(255,255,255,0.1);
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
                    <li><a href="#services">Services</a></li>
                    <!-- <li><a href="#resources">Resources</a></li>
                    <li><a href="#blog">Blog</a></li> -->
                      <li><a href="#about">About Us</a></li> 
                    <li><a href="#contact">Contact Us</a></li>
                </ul>
                <a href="#demo" class="cta-btn">Free Demo</a>
            </nav>
        </header>

    
        <section class="hero" id="home">
            <div class="hero-container">
                <h1>Transport Management Software</h1>
                <p>Powerful and flexible tools for your Transport.TMS is a comprehensive Transport management system in , used by transporters all over the world to connect and analyze day to day transport operations keeping the customer well informed about their goods.</p>
                <div class="hero-buttons">
                    <a href="#demo" class="btn-primary">Request Demo</a>
                    <a href="#contact" class="btn-secondary">WhatsApp Us</a>
                </div>
            </div>
        </section>
        
        <section class="overview" id="overview">
            <div class="services-container">
                <h2 class="section-title">Why Choose TMS?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <span class="service-icon"> </span>
                        <h3>Complete Transport Solution</h3>
                        <p>End-to-end transport management with booking, User Management, and vehicle Schedules.</p>
                    </div>
                    <div class="feature-card">
                        <span class="service-icon">📊</span>
                        <h3>Real-time Analytics</h3>
                        <p>Get insights into your transport operations with detailed reports and analytics.</p>
                    </div>
                    <div class="feature-card">
                        <span class="service-icon">🌍</span>
                        <h3>Global Reach</h3>
                        <p>Used by transporters worldwide for efficient logistics management.</p>
                    </div>
                </div>
            </div>
        </section>

    
        <section class="services" id="services" style="display: none;">
            <div class="services-container">
                <h2 class="section-title">Transport Management Services</h2>
                <div class="services-grid">
                    
                
                    <div class="service-card">
                        <span class="service-icon">💰</span>
                        <h3>Fare Details & Calculation</h3>
                        <p>Get instant fare calculations based on distance, vehicle type, and route preferences. Our dynamic pricing system ensures transparent and competitive rates.</p>

    <div class="fare-form">
    <h4>Calculate Fare</h4>

    <div class="form-group">
        <label>From:</label>
        <input type="text" id="fromLocation" placeholder="Enter pickup location">
    </div>

    <div class="form-group">
        <label>To:</label>
        <input type="text" id="toLocation" placeholder="Enter destination">
    </div>

    <div class="form-group">
        <label>Distance (in km):</label>
        <input type="number" id="distance" placeholder="Enter distance in kilometers">
    </div>

    <div class="form-group">
        <label>Transport Type:</label>
        <select id="vehicleType">
            <option value="taxi">Taxi</option>
            <option value="bus">Bus</option>
            <option value="micro">Micro</option>
        </select>
    </div>

    <button class="form-btn" onclick="calculateFare()">Calculate Fare</button>

    <div class="result-display" id="fareResult"></div>
</div>

<script>
function calculateFare() {
    let distance = document.getElementById("distance").value;
    let vehicleType = document.getElementById("vehicleType").value;
    let farePerKm = 0;

    if (vehicleType === "taxi") {
        farePerKm = 25; 
    } else if (vehicleType === "bus") {
        farePerKm = 10; 
    } else if (vehicleType === "micro") {
        farePerKm = 15; 
    }

    if (distance > 0) {
        let totalFare = farePerKm * distance;
        document.getElementById("fareResult").innerHTML = 
            `Estimated Fare for ${distance} km by ${vehicleType.toUpperCase()}: Rs. ${totalFare}`;
    } else {
        document.getElementById("fareResult").innerHTML = 
            "⚠️ Please enter a valid distance.";
    }
}
</script>

                    <div class="service-card">
                        <span class="service-icon">🎫</span>
                        <h3>Booking & Ticketing</h3>
                        <p>Easy online booking system with instant confirmation. Book your transport, get digital receipts, and track your shipment in real-time.</p>
                        
                        <div class="booking-form">
                            <h4>Book Transport</h4>
                            <div class="form-group">
                                <label>Customer Name:</label>
                                <input type="text" id="customerName" placeholder="Enter your name">
                            </div>
                            <div class="form-group">
                                <label>Phone:</label>
                                <input type="tel" id="customerPhone" placeholder="Enter phone number">
                            </div>
                            <div class="form-group">
                                <label>Pickup Date:</label>
                                <input type="date" id="pickupDate">
                            </div>
                            <div class="form-group">
                                <label>Service Type:</label>
                                <select id="serviceType">
                                    <option value="express">Express Delivery</option>
                                    <option value="standard">Standard Shipping</option>
                                    <option value="bulk">Bulk Transport</option>
                                </select>
                            </div>
                            <button class="form-btn" onclick="bookTransport()">Book Now</button>
                            <div class="result-display" id="bookingResult"></div>
                        </div>
                    </div>

                    <div class="service-card">
                        <span class="service-icon">❌</span>
                        <h3>Cancel Booking</h3>
                        <p>Hassle-free cancellation process with flexible policies. Cancel or modify your bookings online with automatic refund processing.</p>
                        
                        <div class="cancel-form">
                            <h4>Cancel Booking</h4>
                            <div class="form-group">
                                <label>Booking ID:</label>
                                <input type="text" id="bookingId" placeholder="Enter booking ID">
                            </div>
                            <div class="form-group">
                                <label>Phone Number:</label>
                                <input type="tel" id="cancelPhone" placeholder="Registered phone number">
                            </div>
                            <div class="form-group">
                                <label>Reason for Cancellation:</label>
                                <select id="cancelReason">
                                    <option value="change-plans">Change in Plans</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="better-option">Found Better Option</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <button class="form-btn" onclick="cancelBooking()">Cancel Booking</button>
                            <div class="result-display" id="cancelResult"></div>
                        </div>
                    </div>

                    <div class="service-card">
                        <span class="service-icon">📱</span>
                        <h3>Real-time Tracking</h3>
                        <p>Track your shipments in real-time with GPS monitoring, delivery updates, and instant notifications.</p>
                        
                        <div class="booking-form">
                            <h4>Track Shipment</h4>
                            <div class="form-group">
                                <label>Tracking ID:</label>
                                <input type="text" id="trackingId" placeholder="Enter tracking number">
                            </div>
                            <button class="form-btn" onclick="trackShipment()">Track Now</button>
                            <div class="result-display" id="trackingResult"></div>
                        </div>
                    </div>

                
                    <div class="service-card">
                        <span class="service-icon">🚚</span>
                        <h3>Fleet Management</h3>
                        <p>Comprehensive fleet management tools for transport companies including vehicle maintenance and route optimization.</p>
                        
                        <div class="booking-form">
                            <h4>Fleet Overview</h4>
                            <div class="form-group">
                                <label>Company ID:</label>
                                <input type="text" id="companyId" placeholder="Enter company ID">
                            </div>
                            <button class="form-btn" onclick="viewFleet()">View Fleet Status</button>
                            <div class="result-display" id="fleetResult"></div>
                        </div>
                    </div>

                    <div class="service-card">
                        <span class="service-icon">🗺️</span>
                        <h3>Route Planning</h3>
                        <p>Optimize your transport routes for efficiency, cost-effectiveness, and timely deliveries.</p>
                        
                        <div class="booking-form">
                            <h4>Plan Route</h4>
                            <div class="form-group">
                                <label>Start Location:</label>
                                <input type="text" id="startLocation" placeholder="Enter start location">
                            </div>
                            <div class="form-group">
                                <label>End Location:</label>
                                <input type="text" id="endLocation" placeholder="Enter destination">
                            </div>
                            <div class="form-group">
                                <label>Priority:</label>
                                <select id="routePriority">
                                    <option value="fastest">Fastest Route</option>
                                    <option value="cheapest">Most Economical</option>
                                    <option value="scenic">Avoid Traffic</option>
                                </select>
                            </div>
                            <button class="form-btn" onclick="planRoute()">Plan Route</button>
                            <div class="result-display" id="routeResult"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    
        <footer class="footer" id="contact">
            <div class="services-container">
                <p>&copy; 2024 FleatAable - Transport Management Software. All rights reserved.</p>
                <p>Powerful and flexible tools for your Transport needs.</p>
            </div>
        </footer>

        <script>
            function showSection(sectionId) {
                
                document.getElementById('home').style.display = 'none';
                document.getElementById('overview').style.display = 'none';
                document.getElementById('services').style.display = 'none';
                
                if (sectionId === 'home') {
                    document.getElementById('home').style.display = 'block';
                    document.getElementById('overview').style.display = 'block';
                } else {
                    document.getElementById(sectionId).style.display = 'block';
                }
            }

        
            function trackShipment() {
                const trackingId = document.getElementById('trackingId').value;
                
                if (!trackingId) {
                    alert('Please enter tracking ID');
                    return;
                }
                
                const statuses = ['Picked up', 'In transit', 'Out for delivery', 'Delivered'];
                const randomStatus = statuses[Math.floor(Math.random() * statuses.length)];
                const location = ['Mumbai', 'Delhi', 'Bangalore', 'Chennai'][Math.floor(Math.random() * 4)];
                
                const resultDiv = document.getElementById('trackingResult');
                resultDiv.innerHTML = `
                    <h5>Tracking Status</h5>
                    <p><strong>Tracking ID:</strong> ${trackingId}</p>
                    <p><strong>Status:</strong> ${randomStatus}</p>
                    <p><strong>Current Location:</strong> ${location}</p>
                    <p><strong>Last Updated:</strong> ${new Date().toLocaleString()}</p>
                `;
                resultDiv.style.display = 'block';
            }

        
            function viewFleet() {
                const companyId = document.getElementById('companyId').value;
                
                if (!companyId) {
                    alert('Please enter company ID');
                    return;
                }
                
                const resultDiv = document.getElementById('fleetResult');
                resultDiv.innerHTML = `
                    <h5>Fleet Status</h5>
                    <p><strong>Company ID:</strong> ${companyId}</p>
                    <p><strong>Total Vehicles:</strong> 45</p>
                    <p><strong>Active:</strong> 38</p>
                    <p><strong>In Maintenance:</strong> 7</p>
                    <p><strong>Utilization Rate:</strong> 84%</p>
                `;
                resultDiv.style.display = 'block';
            }

        
            function planRoute() {
                const start = document.getElementById('startLocation').value;
                const end = document.getElementById('endLocation').value;
                const priority = document.getElementById('routePriority').value;
                
                if (!start || !end) {
                    alert('Please enter both locations');
                    return;
                }
                
                const distance = Math.floor(Math.random() * 500) + 50;
                const time = Math.floor(distance / 60 * Math.random() + 2);
                const fuel = Math.floor(distance * 0.08);
                
                const resultDiv = document.getElementById('routeResult');
                resultDiv.innerHTML = `
                    <h5>Optimized Route</h5>
                    <p><strong>From:</strong> ${start}</p>
                    <p><strong>To:</strong> ${end}</p>
                    <p><strong>Distance:</strong> ${distance} km</p>
                    <p><strong>Est. Time:</strong> ${time} hours</p>
                    <p><strong>Fuel Cost:</strong> ₹${fuel * 100}</p>
                    <p><strong>Priority:</strong> ${priority}</p>
                `;
                resultDiv.style.display = 'block';
            }

            function calculateFare() {
                const from = document.getElementById('fromLocation').value;
                const to = document.getElementById('toLocation').value;
                const vehicleType = document.getElementById('vehicleType').value;
                const weight = parseFloat(document.getElementById('weight').value) || 0;
                
                if (!from || !to || !weight) {
                    alert('Please fill in all fields');
                    return;
                }
                
                
                const baseRates = {
                    truck: 15,
                    van: 12,
                    trailer: 18,
                    container: 20
                };
                
                const distance = Math.floor(Math.random() * 500) + 50;
                const baseFare = baseRates[vehicleType] * distance;
                const weightCharge = weight * 2;
                const totalFare = baseFare + weightCharge;
                
                const resultDiv = document.getElementById('fareResult');
                resultDiv.innerHTML = `
                    <h5>Fare Calculation</h5>
                    <p><strong>Route:</strong> ${from} → ${to}</p>
                    <p><strong>Distance:</strong> ${distance} km</p>
                    <p><strong>Base Fare:</strong> ₹${baseFare}</p>
                    <p><strong>Weight Charge:</strong> ₹${weightCharge}</p>
                    <p><strong>Total Fare:</strong> ₹${totalFare}</p>
                `;
                resultDiv.style.display = 'block';
            }
            
            function bookTransport() {
                const name = document.getElementById('customerName').value;
                const phone = document.getElementById('customerPhone').value;
                const date = document.getElementById('pickupDate').value;
                const service = document.getElementById('serviceType').value;
                
                if (!name || !phone || !date) {
                    alert('Please fill in all required fields');
                    return;
                }
                
                const bookingId = 'TMS' + Date.now().toString().slice(-6);
                
                const resultDiv = document.getElementById('bookingResult');
                resultDiv.innerHTML = `
                    <h5>Booking Confirmed!</h5>
                    <p><strong>Booking ID:</strong> ${bookingId}</p>
                    <p><strong>Customer:</strong> ${name}</p>
                    <p><strong>Phone:</strong> ${phone}</p>
                    <p><strong>Pickup Date:</strong> ${date}</p>
                    <p><strong>Service:</strong> ${service}</p>
                    <p>Confirmation sent to your phone number!</p>
                `;
                resultDiv.style.display = 'block';
            }
            
            function cancelBooking() {
                const bookingId = document.getElementById('bookingId').value;
                const phone = document.getElementById('cancelPhone').value;
                const reason = document.getElementById('cancelReason').value;
                
                if (!bookingId || !phone) {
                    alert('Please provide booking ID and phone number');
                    return;
                }
                
                const resultDiv = document.getElementById('cancelResult');
                resultDiv.innerHTML = `
                    <h5>Cancellation Processed</h5>
                    <p><strong>Booking ID:</strong> ${bookingId}</p>
                    <p><strong>Status:</strong> Cancelled Successfully</p>
                    <p><strong>Reason:</strong> ${reason}</p>
                    <p><strong>Refund:</strong> Will be processed in 3-5 business days</p>
                    <p>Cancellation confirmation sent to ${phone}</p>
                `;
                resultDiv.style.display = 'block';
            }
            
        
            document.addEventListener('DOMContentLoaded', function() {
            
                showSection('home');
                
            
                document.querySelectorAll('.nav-menu a').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const target = this.getAttribute('href').replace('#', '');
                        showSection(target);
                    });
                });
            });
            
        
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = this.getAttribute('href').replace('#', '');
                    showSection(target);
                });
            });
        </script>
    </body>
    </html>