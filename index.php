<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookingNepal - Premium Vehicle Booking Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --secondary: #0ea5e9;
            --accent: #06b6d4;
            --dark: #0f172a;
            --dark-light: #1e293b;
            --dark-lighter: #334155;
            --text-primary: #ffffff;
            --text-secondary: #e2e8f0;
            --text-muted: #94a3b8;
            --success: #10b981;
            --warning: #f59e0b;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--dark);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Navigation */
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            padding: 1.2rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }

        nav.scrolled {
            padding: 0.8rem 5%;
            background: rgba(15, 23, 42, 0.95);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.02);
        }

        .logo-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .logo-text span {
            color: var(--secondary);
        }

        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 5px;
        }

        .menu-toggle span {
            width: 28px;
            height: 3px;
            background: var(--text-primary);
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2.5rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a:not(.btn-nav):hover {
            color: var(--text-primary);
        }

        .btn-nav {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 0.7rem 1.8rem;
            border-radius: 8px;
            color: var(--text-primary) !important;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
        }

        .btn-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(37, 99, 235, 0.6);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10rem 5% 5rem;
            gap: 5rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }

        .hero-content {
            flex: 1;
            max-width: 600px;
            z-index: 1;
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(37, 99, 235, 0.1);
            border: 1px solid rgba(37, 99, 235, 0.3);
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            color: var(--secondary);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .badge i {
            color: var(--success);
            animation: blink 2s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -2px;
        }

        .gradient-text {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 1.2rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            line-height: 1.7;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary, .btn-secondary {
            padding: 1rem 2.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--text-primary);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(37, 99, 235, 0.6);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
        }

        .stats {
            display: flex;
            gap: 3rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: left;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            display: block;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 0.3rem;
        }

        /* Hero Illustration */
        .hero-visual {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1;
            animation: fadeInRight 0.8s ease;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .visual-container {
            position: relative;
            width: 550px;
            height: 550px;
        }

        .hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 30px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .floating-card {
            position: absolute;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1.2rem 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .floating-card:nth-child(2) {
            top: 10%;
            right: -10%;
            animation-delay: 0.5s;
        }

        .floating-card:nth-child(3) {
            bottom: 15%;
            left: -10%;
            animation-delay: 1s;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
        }

        .card-title {
            font-weight: 600;
            margin-bottom: 0.3rem;
            font-size: 0.95rem;
        }

        .card-value {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        /* Features Section */
        .features {
            padding: 8rem 5%;
            background: linear-gradient(180deg, var(--dark) 0%, var(--dark-light) 100%);
        }

        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 5rem;
        }

        .section-badge {
            display: inline-block;
            background: rgba(37, 99, 235, 0.1);
            border: 1px solid rgba(37, 99, 235, 0.3);
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            color: var(--secondary);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .section-description {
            color: var(--text-muted);
            font-size: 1.15rem;
            line-height: 1.7;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 2.5rem;
            transition: all 0.4s ease;
            cursor: pointer;
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
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-color: rgba(37, 99, 235, 0.5);
            box-shadow: 0 20px 60px rgba(37, 99, 235, 0.2);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon-wrapper {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
            transition: transform 0.4s ease;
        }

        .feature-card:hover .feature-icon-wrapper {
            transform: scale(1.1) rotate(5deg);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .feature-card p {
            color: var(--text-muted);
            line-height: 1.7;
            font-size: 1.05rem;
        }

        /* About Section */
        .about {
            padding: 8rem 5%;
            background: var(--dark-light);
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5rem;
            align-items: center;
        }

        .about-content h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
        }

        .about-content p {
            color: var(--text-muted);
            font-size: 1.15rem;
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }

        .about-features {
            display: grid;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .about-feature-item {
            display: flex;
            align-items: start;
            gap: 1rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }

        .about-feature-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(37, 99, 235, 0.3);
        }

        .about-feature-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .about-feature-text h4 {
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
            font-weight: 600;
        }

        .about-feature-text p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin: 0;
        }

        .about-image {
            position: relative;
        }

        .about-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
        }

        /* CTA Section */
        .cta-section {
            padding: 8rem 5%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .cta-content h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
        }

        .cta-content p {
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
        }

        .cta-buttons-center {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-white {
            background: white;
            color: var(--primary);
            padding: 1rem 2.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .btn-white:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: white;
            padding: 1rem 2.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
        }

        /* Footer */
        .site-footer {
            background: var(--dark);
            padding: 5rem 5% 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .footer-container {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 4rem;
            max-width: 1400px;
            margin: 0 auto 3rem;
        }

        .footer-section h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .footer-about p {
            color: var(--text-muted);
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-links a:hover {
            color: var(--primary);
            padding-left: 5px;
        }

        .footer-contact p {
            color: var(--text-muted);
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .footer-contact i {
            color: var(--primary);
            width: 20px;
        }

        .footer-contact a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-contact a:hover {
            color: var(--primary);
        }

        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-icon {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-color: transparent;
            color: white;
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .about-container {
                grid-template-columns: 1fr;
                gap: 3rem;
            }

            .footer-container {
                grid-template-columns: 1fr 1fr;
                gap: 3rem;
            }
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: flex;
            }

            .nav-links {
                position: fixed;
                top: 80px;
                right: -100%;
                width: 80%;
                height: calc(100vh - 80px);
                background: rgba(15, 23, 42, 0.98);
                backdrop-filter: blur(20px);
                flex-direction: column;
                padding: 3rem 2rem;
                transition: right 0.4s ease;
                border-left: 1px solid rgba(255, 255, 255, 0.1);
                align-items: flex-start;
            }

            .nav-links.active {
                right: 0;
            }

            .hero {
                flex-direction: column;
                text-align: center;
                padding: 8rem 5% 4rem;
                gap: 3rem;
            }

            .hero-content h1 {
                font-size: 2.8rem;
            }

            .stats {
                justify-content: center;
            }

            .cta-buttons {
                justify-content: center;
            }

            .visual-container {
                width: 100%;
                max-width: 450px;
                height: auto;
            }

            .floating-card {
                display: none;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 2.2rem;
            }

            .cta-content h2 {
                font-size: 2.2rem;
            }

            .footer-container {
                grid-template-columns: 1fr;
                gap: 2.5rem;
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 2.2rem;
            }

            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
            }

            .section-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <nav id="navbar">
        <div class="logo" onclick="window.location.href='#home'">
            <div class="logo-icon">🚗</div>
            <div class="logo-text">Booking<span>Nepal</span></div>
        </div>
        <div class="menu-toggle" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <ul class="nav-links" id="navLinks">
            <li><a href="#home">Home</a></li>
            <li><a href="#features">Features</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#contact">Contact</a></li>
            <li><a href="Sign_in.php" class="btn-nav">Sign In</a></li>
        </ul>
    </nav>

    <section class="hero" id="home">
        <div class="hero-content">
            <div class="badge">
                <i class="fas fa-circle"></i>
                Now Available in Nepal
            </div>
            <h1>
                Nepal's Most <br>
                <span class="gradient-text">Trusted Vehicle</span><br>
                Booking Platform
            </h1>
            <p class="hero-description">
                Experience seamless vehicle booking with real-time availability, instant confirmations, and flexible cancellation policies. Built for travelers, businesses, and transport operators.
            </p>
            <div class="cta-buttons">
                <a href="Sign_in.php" class="btn-primary">
                    Get Started
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="#features" class="btn-secondary">
                    <i class="fas fa-play-circle"></i>
                    Learn More
                </a>
            </div>
            <div class="stats">
                <div class="stat-item">
                    <span class="stat-number">100+</span>
                    <span class="stat-label">Vehicles Available</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">500+</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Support Available</span>
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="visual-container">
                <img src="https://selectedfirms.co/public/assets/images/blog_cover_image/567735463_1689147486.webp" alt="Vehicle Booking Platform" class="hero-image">
                <div class="floating-card">
                    <div class="card-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="card-title">Instant Booking</div>
                    <div class="card-value">Real-time confirmation</div>
                </div>
                <div class="floating-card">
                    <div class="card-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="card-title">Secure Platform</div>
                    <div class="card-value">100% Safe & Protected</div>
                </div>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="section-header">
            <div class="section-badge">WHY CHOOSE US</div>
            <h2 class="section-title">
                Powerful Features for <span class="gradient-text">Smart Booking</span>
            </h2>
            <p class="section-description">
                Everything you need to manage your vehicle bookings efficiently and professionally
            </p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3>Easy Booking Process</h3>
                <p>Streamlined booking workflow with intuitive interface. Book vehicles in just a few clicks with real-time availability updates.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-bell"></i>
                </div>
                <h3>Smart Notifications</h3>
                <p>Receive instant email confirmations and updates. Stay informed about your booking status every step of the way.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-filter"></i>
                </div>
                <h3>Advanced Filters</h3>
                <p>Filter by budget, vehicle type (Micro, Bus, Taxi), seating capacity, and available facilities to find your perfect match.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h3>Flexible Cancellation</h3>
                <p>Cancel your booking within the allowed period hassle-free. We understand plans change and offer flexible options.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Management Dashboard</h3>
                <p>Comprehensive admin panel to track bookings, manage fleet, and analyze performance metrics in real-time.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-lock"></i>
                </div>
                <h3>Secure & Reliable</h3>
                <p>Enterprise-grade security with encrypted data transmission. Your information is protected with industry-leading standards.</p>
            </div>
        </div>
    </section>

    <section class="about" id="about">
        <div class="about-container">
            <div class="about-content">
                <div class="section-badge">ABOUT BOOKINGNEPAL</div>
                <h2>
                    Revolutionizing <span class="gradient-text">Transportation</span> in Nepal
                </h2>
                <p>
                    BookingNepal is Nepal's premier vehicle booking platform, connecting travelers with reliable transportation services across the country. We've simplified the entire booking process to save you time and provide peace of mind.
                </p>
                <p>
                    Our platform offers comprehensive vehicle management with features designed for both customers and operators. From instant booking confirmations to flexible cancellation policies, we've thought of everything.
                </p>
                <div class="about-features">
                    <div class="about-feature-item">
                        <div class="about-feature-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="about-feature-text">
                            <h4>Multiple Vehicle Types</h4>
                            <p>Choose from Microbuses, Buses, and Taxis based on your group size and budget</p>
                        </div>
                    </div>
                    <div class="about-feature-item">
                        <div class="about-feature-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="about-feature-text">
                            <h4>Email Confirmations</h4>
                            <p>Instant email notifications with booking details and confirmation codes</p>
                        </div>
                    </div>
                    <div class="about-feature-item">
                        <div class="about-feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="about-feature-text">
                            <h4>Time-Saving Solution</h4>
                            <p>Book in minutes with our streamlined process and real-time availability</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="about-image">
                <img src="https://cdn-icons-png.flaticon.com/512/14653/14653711.png" alt="About BookingNepal">
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="cta-content">
            <h2>Ready to Start Your Journey?</h2>
            <p>Join thousands of satisfied customers who trust BookingNepal for their transportation needs. Sign up today and experience hassle-free booking.</p>
            <div class="cta-buttons-center">
                <a href="Sign_in.php" class="btn-white">
                    Create Account
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="#contact" class="btn-outline">
                    <i class="fas fa-phone"></i>
                    Contact Sales
                </a>
            </div>
        </div>
    </section>

    <footer class="site-footer" id="contact">
        <div class="footer-container">
            <div class="footer-section footer-about">
                <h3>BookingNepal</h3>
                <p>Your trusted partner for seamless vehicle booking services across Nepal. We connect travelers with reliable transportation through innovative technology and exceptional customer service.</p>
                <div class="social-icons">
                    <a href="https://www.facebook.com" target="_blank" class="social-icon">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://www.instagram.com" target="_blank" class="social-icon">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://wa.me/9779748777251" target="_blank" class="social-icon">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="mailto:bikashtransportt@gmail.com" class="social-icon">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <div class="footer-links">
                    <a href="#home"><i class="fas fa-chevron-right"></i> Home</a>
                    <a href="#features"><i class="fas fa-chevron-right"></i> Features</a>
                    <a href="#about"><i class="fas fa-chevron-right"></i> About Us</a>
                    <a href="index.php"><i class="fas fa-chevron-right"></i> Sign In</a>
                </div>
            </div>

            <div class="footer-section">
                <h3>Services</h3>
                <div class="footer-links">
                    <a href="#"><i class="fas fa-chevron-right"></i> Microbus Booking</a>
                    <a href="#"><i class="fas fa-chevron-right"></i> Bus Booking</a>
                    <a href="#"><i class="fas fa-chevron-right"></i> Taxi Services</a>
                    <a href="#"><i class="fas fa-chevron-right"></i> Corporate Solutions</a>
                </div>
            </div>

            <div class="footer-section footer-contact">
                <h3>Contact Information</h3>
                <p><i class="fas fa-map-marker-alt"></i> Butwal-10, Rupandehi, Nepal</p>
                <p><i class="fas fa-phone"></i> <a href="tel:9748777251">+977 9748777251</a></p>
                <p><i class="fas fa-envelope"></i> <a href="mailto:bikashtransportt@gmail.com">bikashtransportt@gmail.com</a></p>
                <p><i class="fas fa-clock"></i> 24/7 Customer Support</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2024 BookingNepal. All rights reserved. | Designed with ❤️ for Nepal</p>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }

        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('navLinks').classList.remove('active');
            });
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>