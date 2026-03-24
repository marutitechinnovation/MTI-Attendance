<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTI Attendance - Modern Cloud-Based System</title>
    
    <!-- QR Code Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230d6efd'%3E%3Cpath d='M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0v-3zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5zM.5 16a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 1 0v2.5h2.5a.5.5 0 0 1 0 1h-3zm12 0a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 1 0v2.5h2.5a.5.5 0 0 1 0 1h-3zM3 3h10v10H3V3z'/%3E%3Cpath d='M4 4h8v8H4z'/%3E%3C/svg%3E">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee; /* Admin accent blue */
            --primary-dark: #1e2d5a; /* Admin dark blue */
            --secondary-color: #ffffff;
            --accent-color: #4361ee;
            --accent-light: #dbe4ff; /* Light blue for highlights on dark backgrounds */
            --blue-gradient: linear-gradient(135deg, #1e2d5a 0%, #4361ee 100%);
            --bg-light: #f0f4f8; /* Soft blue-gray for background */
            --text-main: #202124;
            --text-muted: #5f6368;
            --white: #ffffff;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(67, 97, 238, 0.15); /* Blue-tinted shadow */
            --shadow-lg: 0 12px 24px rgba(67, 97, 238, 0.2);
            --radius-md: 12px;
            --radius-lg: 20px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Typography */
        h1, h2, h3 { color: var(--text-main); font-weight: 800; line-height: 1.2; }
        
        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary-color); letter-spacing: -0.5px; }

        .nav-links { display: flex; gap: 2rem; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text-main); font-weight: 600; transition: var(--transition); }
        .nav-links a:hover { color: var(--primary-color); }
        
        .btn {
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
            box-shadow: 0 4px 14px 0 rgba(67, 97, 238, 0.39);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.5);
            background: #3655e0; /* Slightly darker and more vibrant than base */
            color: var(--white);
        }

        .btn-outline {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        .btn-outline:hover {
            background: rgba(67, 97, 238, 0.05); /* Gentle blue tint */
            transform: translateY(-2px);
            color: var(--primary-color);
        }

        /* Hero Section */
        .hero {
            padding: 10rem 5% 6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-height: 100vh;
            background: var(--blue-gradient); /* Admin login gradient */
            color: var(--white);
            position: relative;
            overflow: hidden;
        }
        
        /* Optional abstract shapes for hero background */
        .hero::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 500px;
            height: 500px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            z-index: 0;
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -100px;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            z-index: 0;
        }

        .hero-content {
            max-width: 800px;
            animation: fadeUp 1s ease-out;
            position: relative;
            z-index: 1;
        }

        .hero h1 { font-size: clamp(2.5rem, 5vw, 4.5rem); margin-bottom: 1.5rem; letter-spacing: -1px; color: var(--white); }
        .hero h1 span { color: var(--accent-light); display: inline-block; } /* Soft light blue accent */
        .hero p { font-size: 1.25rem; color: rgba(255,255,255,0.9); margin-bottom: 2.5rem; }
        
        .hero-buttons { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; }
        .hero-buttons .btn-primary { 
            background: var(--white); 
            color: var(--primary-color); 
            box-shadow: 0 4px 14px 0 rgba(0,0,0,0.1);
        }
        .hero-buttons .btn-primary:hover { 
            background: var(--white); 
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            color: var(--primary-dark);
        }
        .hero-buttons .btn-outline { 
            border-color: rgba(255,255,255,0.6); 
            color: var(--white); 
        }
        .hero-buttons .btn-outline:hover { 
            background: rgba(255,255,255,0.1); 
            border-color: var(--white);
            color: var(--white); 
        }
        
        /* Showcase Section */
        .showcase { padding: 6rem 5%; background: var(--bg-light); border-bottom: 1px solid rgba(0,0,0,0.05); }
        .showcase .section-header { text-align: center; margin-bottom: 4rem; }
        .showcase .section-header h2 { font-size: 2.5rem; margin-bottom: 1rem; color: var(--primary-color); }
        .showcase .section-header p { color: var(--text-muted); font-size: 1.1rem; max-width: 600px; margin: 0 auto; }
        
        .showcase-row {
            display: flex;
            align-items: center;
            gap: 4rem;
            max-width: 1200px;
            margin: 0 auto 6rem;
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 3rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
        }
        .showcase-row:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .showcase-row:nth-child(even) {
            flex-direction: row-reverse;
            /* Give alternate rows a slightly different background tint if desired */
            background: linear-gradient(to right, #ffffff, #f8faff);
        }
        .showcase-row:nth-child(odd) {
             background: linear-gradient(to left, #ffffff, #f8faff);
        }
        .showcase-text {
            flex: 1;
        }
        .showcase-text h3 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        .showcase-text p {
            font-size: 1.1rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        .showcase-text ul {
            list-style: none;
            padding: 0;
            margin-bottom: 1.5rem;
        }
        .showcase-text ul li {
            margin-bottom: 0.5rem;
            position: relative;
            padding-left: 1.5rem;
            color: var(--text-main);
        }
        .showcase-text a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid rgba(67, 97, 238, 0.35);
            transition: var(--transition);
        }
        .showcase-text a:hover {
            color: var(--primary-dark);
            border-bottom-color: var(--primary-dark);
        }

        .showcase-text ul li::before {
            content: '✓';
            color: var(--primary-color);
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        .showcase-image {
            flex: 1;
            display: flex;
            justify-content: center;
        }
        .image-placeholder {
            width: 100%;
            height: 350px;
            background: #e9ecef;
            border-radius: var(--radius-lg);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-weight: 600;
            border: 2px dashed #ced4da;
            box-shadow: var(--shadow-sm);
            margin: 0 auto;
            padding: 1rem;
            text-align: center;
        }
        .mobile-placeholder {
            max-width: 250px;
            height: 500px;
            border-radius: 30px;
        }

        /* Contact Section */
        .contact { padding: 6rem 5%; background: var(--blue-gradient); position: relative; }

        .contact-container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--white);
            padding: 4rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            color: var(--text-main);
        }

        .contact-header { margin-bottom: 2.5rem; text-align: left; }
        .contact-header h2 { font-size: 2.5rem; color: var(--primary-dark); margin-bottom: 0.5rem; font-weight: 800; }
        .contact-header p { color: var(--text-muted); font-size: 1.05rem; }

        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem; color: var(--primary-dark); }
        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5eb;
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
            background: var(--bg-light);
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
        }
        textarea.form-control { resize: vertical; min-height: 120px; }

        .form-submit { width: 100%; font-size: 1.1rem; padding: 1rem; margin-top: 1rem;}

        /* Alerts */
        .alert { padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; text-align: center; font-weight: 600; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Footer */
        footer { background: #1a252f; color: rgba(255,255,255,0.6); text-align: center; padding: 2rem 5%; font-size: 0.9rem; }

        /* Animations */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links { display: none; } /* Simple mobile menu approach for now */
            .hero-buttons { flex-direction: column; }
            .hero-buttons .btn { width: 100%; }
            .contact-container { padding: 2rem 1.5rem; }
            .features-grid { grid-template-columns: 1fr; }
            .showcase-row, .showcase-row:nth-child(even) {
                flex-direction: column;
                gap: 2rem;
                text-align: center;
            }
            .showcase-text ul li {
                text-align: left;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">MTI Attendance</div>
        <div class="nav-links">
            <a href="#showcase">Platform</a>
            <a href="#contact">Contact</a>
            <a href="<?= base_url('employee') ?>">Employee app (PWA)</a>
            <a href="<?= base_url('login') ?>" class="btn btn-outline">Sign In</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Smart Attendance Management for the <span>Modern Workforce</span></h1>
            <p>Streamline your HR process with geo-fencing, QR code scanning, and real-time synchronization. Say goodbye to manual tracking.</p>
            <div class="hero-buttons">
                <a href="<?= base_url('login') ?>" class="btn btn-primary" style="animation: pulse 2s infinite ease-in-out;">Get Started Now</a>
                <a href="#showcase" class="btn btn-outline">See Platform</a>
                <a href="<?= base_url('employee') ?>" class="btn btn-outline">Employee app (PWA)</a>
            </div>
        </div>
    </section>

    <!-- Showcase Section -->
    <section id="showcase" class="showcase">
        <div class="section-header">
            <h2>Experience The Platform</h2>
            <p>Seamlessly manage attendance across both our intuitive web dashboard and companion mobile app.</p>
        </div>
        <div class="showcase-container">
            <!-- Web Portal Screenshots -->
            <div class="showcase-row">
                <div class="showcase-text">
                    <h3>Powerful Web Admin Dashboard</h3>
                    <p>Take complete control of your organization's attendance with our comprehensive web portal. Beautiful, easy to read dashboards give you all the information you need at a glance.</p>
                    <ul>
                        <li>Real-time attendance overview</li>
                        <li>Detailed employee statistics</li>
                        <li>Leave management oversight</li>
                    </ul>
                </div>
                <div class="showcase-image">
                    <div class="image-placeholder" style="padding:0; border:none; background:transparent; height:auto;">
                        <img src="<?= base_url('assets/images/landing/admin-dashboard.png') ?>" alt="Web Admin Dashboard" style="width: 100%; border-radius: var(--radius-lg); box-shadow: var(--shadow-md);">
                    </div>
                </div>
            </div>

            <div class="showcase-row">
                <div class="showcase-text">
                    <h3>Detailed Reports & Data Tables</h3>
                    <p>Say goodbye to messy spreadsheets. View, filter, and export detailed attendance logs quickly. Making payroll processing faster and more accurate.</p>
                    <ul>
                        <li>Filter by date, department, or employee</li>
                        <li>One-click CSV & PDF exports</li>
                        <li>Complete audit trails</li>
                    </ul>
                </div>
                <div class="showcase-image">
                    <div class="image-placeholder" style="padding:0; border:none; background:transparent; height:auto;">
                        <img src="<?= base_url('assets/images/landing/admin-reports.png') ?>" alt="Detailed Reports" style="width: 100%; border-radius: var(--radius-lg); box-shadow: var(--shadow-md);">
                    </div>
                </div>
            </div>
            
            <!-- Mobile App Screenshots -->
            <div class="showcase-row">
                <div class="showcase-text">
                    <h3>Companion Mobile App</h3>
                    <p>Empower your workforce with our intuitive mobile application. Employees can manage their attendance directly from their smartphones wherever they are.</p>
                    <ul>
                        <li>Quick action dashboard</li>
                        <li>Real-time sync with web portal</li>
                        <li>Push notifications for shift updates</li>
                    </ul>
                    <p style="margin-top: 1rem;">Prefer the browser? Open the <a href="<?= base_url('employee') ?>">installable employee web app (PWA)</a>.</p>
                </div>
                <div class="showcase-image">
                    <div class="image-placeholder mobile-placeholder" style="padding:0; border:none; background:transparent; height:auto;">
                        <img src="<?= base_url('assets/images/landing/mobile-timeline.png') ?>" alt="Mobile App Dashboard" style="width: 100%; border-radius: 30px; box-shadow: var(--shadow-md);">
                    </div>
                </div>
            </div>

            <div class="showcase-row">
                <div class="showcase-text">
                    <h3>Seamless QR Check-In</h3>
                    <p>Clocking in has never been easier. Just open the app, point the camera at the location QR code, and you're checked in securely, saving time during shift changes.</p>
                    <ul>
                        <li>Frictionless attendance marking</li>
                        <li>Location-verified secure scans</li>
                        <li>Works efficiently on all devices</li>
                    </ul>
                </div>
                <div class="showcase-image">
                    <div class="image-placeholder mobile-placeholder" style="padding:0; border:none; background:transparent; height:auto;">
                        <img src="<?= base_url('assets/images/landing/mobile-scan.png') ?>" alt="Mobile QR Scanner" style="width: 100%; border-radius: 30px; box-shadow: var(--shadow-md);">
                    </div>
                </div>
            </div>

            <div class="showcase-row" style="margin-bottom: 0;">
                <div class="showcase-text">
                    <h3>Employee Profile & Settings</h3>
                    <p>Let employees access their own data securely. They can view their attendance history, personal details, and customize their app settings to their preferences.</p>
                    <ul>
                        <li>Self-service data checking</li>
                        <li>Secure credential management</li>
                        <li>Dark/Light mode support</li>
                    </ul>
                </div>
                <div class="showcase-image">
                    <div class="image-placeholder mobile-placeholder" style="padding:0; border:none; background:transparent; height:auto;">
                        <img src="<?= base_url('assets/images/landing/mobile-profile.png') ?>" alt="Mobile Profile" style="width: 100%; border-radius: 30px; box-shadow: var(--shadow-md);">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="contact-container">
            <div class="contact-header">
                <h2>Get In Touch</h2>
                <p>Have questions? We'd love to hear from you.</p>
            </div>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success">
                    <?= session()->getFlashdata('success') ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-error">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('contact') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="john@example.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="message">Message</label>
                    <textarea id="message" name="message" class="form-control" placeholder="How can we help you?" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary form-submit">Send Message</button>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; <?= date('Y') ?> MTI Attendance. All rights reserved. | <a href="<?= base_url('employee') ?>" style="color: rgba(255,255,255,0.6); text-decoration: none;">Employee app (PWA)</a> | <a href="<?= base_url('privacy') ?>" target="_blank" style="color: rgba(255,255,255,0.6); text-decoration: none;">Privacy Policy</a></p>
    </footer>



    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
