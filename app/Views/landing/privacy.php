<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - MTI Attendance</title>
    
    <!-- QR Code Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230d6efd'%3E%3Cpath d='M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0v-3zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5zM.5 16a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 1 0v2.5h2.5a.5.5 0 0 1 0 1h-3zm12 0a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 1 0v2.5h2.5a.5.5 0 0 1 0 1h-3zM3 3h10v10H3V3z'/%3E%3Cpath d='M4 4h8v8H4z'/%3E%3C/svg%3E">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #1e2d5a;
            --secondary-color: #ffffff;
            --accent-color: #4361ee;
            --accent-light: #dbe4ff;
            --blue-gradient: linear-gradient(135deg, #1e2d5a 0%, #4361ee 100%);
            --bg-light: #f0f4f8;
            --text-main: #202124;
            --text-muted: #5f6368;
            --white: #ffffff;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(67, 97, 238, 0.15);
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

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            position: sticky;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary-color); letter-spacing: -0.5px; text-decoration: none; }

        /* Header Section */
        .page-header {
            padding: 6rem 5% 4rem;
            text-align: center;
            background: var(--blue-gradient);
            color: var(--white);
            position: relative;
        }

        .header-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .page-header h1 { 
            font-size: clamp(2rem, 5vw, 3.5rem); 
            margin-bottom: 1rem; 
            letter-spacing: -1px; 
            color: var(--white); 
        }

        .page-header p { 
            font-size: 1.2rem; 
            color: rgba(255,255,255,0.9); 
        }

        /* Content Section */
        .content-section {
            padding: 4rem 5%;
            max-width: 1000px;
            margin: -3rem auto 4rem;
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 2;
        }

        .policy-block {
            margin-bottom: 2.5rem;
        }

        .policy-block h2 {
            font-size: 1.8rem;
            color: var(--primary-dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .policy-block h2::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 24px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .policy-block p {
            margin-bottom: 1rem;
            color: var(--text-muted);
            font-size: 1.05rem;
        }

        .policy-block ul {
            margin-left: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--text-muted);
        }

        .policy-block li {
            margin-bottom: 0.5rem;
        }

        .policy-block strong {
            color: var(--text-main);
        }

        .last-updated {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        /* Footer */
        footer { 
            background: #1a252f; 
            color: rgba(255,255,255,0.6); 
            text-align: center; 
            padding: 3rem 5%; 
            font-size: 0.9rem; 
        }

        .footer-logo {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 1rem;
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-section {
                padding: 2rem 1.5rem;
                margin-top: -2rem;
                border-radius: var(--radius-md);
            }
            .page-header {
                padding: 4rem 5% 3rem;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="<?= base_url() ?>" class="logo">MTI Attendance</a>
    </nav>

    <!-- Header -->
    <header class="page-header">
        <div class="header-content">
            <h1>Privacy Policy</h1>
            <p>Your privacy is important to us. Learn how we handle your data in the MTI Attendance System.</p>
        </div>
    </header>

    <!-- Content -->
    <main class="content-section">
        <div class="policy-block">
            <h2>Introduction</h2>
            <p>Welcome to <strong>MTI Attendance</strong>. This Privacy Policy explains how our application collects, uses, and protects your information when you use our mobile application and web panel.</p>
            <p>By using the MTI Attendance System, you agree to the collection and use of information in accordance with this policy.</p>
        </div>

        <div class="policy-block">
            <h2>Information We Collect</h2>
            <p>Our app specifically collects data necessary for employee attendance tracking and verification:</p>
            <ul>
                <li><strong>Personal Identification:</strong> Name, Employee ID, and Email address (provided by your administrator).</li>
                <li><strong>Location Data:</strong> When you scan a QR code to check-in or out, we collect your precise <strong>Latitude and Longitude</strong> to verify you are within the allowed geofence radius.</li>
                <li><strong>Camera Access:</strong> We require access to your camera solely for the purpose of <strong>scanning QR codes</strong> to mark attendance.</li>
                <li><strong>Device Information:</strong> We may collect basic device information (model, OS version) to ensure app compatibility and security.</li>
            </ul>
        </div>

        <div class="policy-block">
            <h2>How We Use Your Information</h2>
            <p>The information we collect is used for the following purposes:</p>
            <ul>
                <li>To record and track daily attendance, breaks, and work hours.</li>
                <li>To verify that attendance scans are performed at designated work locations using Geofencing technology.</li>
                <li>To generate payroll reports and productivity analytics for your organization's management.</li>
                <li>To manage and secure your user account.</li>
            </ul>
        </div>

        <div class="policy-block">
            <h2>Data Storage & Security</h2>
            <p>Your data is stored securely on our central servers. We implement industry-standard security measures to protect your information against unauthorized access, alteration, or disclosure.</p>
            <p>Attendance logs and location data are accessible only to authorized administrators within your organization and our technical support team for maintenance purposes.</p>
        </div>

        <div class="policy-block">
            <h2>Geofencing & Location Permissions</h2>
            <p>The MTI Attendance App requires <strong>Location Permissions</strong>. We only capture your location at the exact moment you perform a QR scan. We do <strong>not</strong> track your continuous background location or monitor your movements outside of attendance marking actions.</p>
        </div>

        <div class="policy-block">
            <h2>Data Sharing</h2>
            <p>We do not sell, trade, or rent your personal data to third parties. Data is shared only with your employer as part of the attendance management service we provide.</p>
        </div>

        <div class="policy-block">
            <h2>Your Rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li>View your attendance records through the application.</li>
                <li>Request corrections to any inaccurate data.</li>
                <li>Request deletion of your account (subject to your organization's data retention policies).</li>
            </ul>
        </div>

        <div class="policy-block">
            <h2>Contact Us</h2>
            <p>If you have any questions about this Privacy Policy or how your data is handled, please contact your organization's HR department or reach out to us at:</p>
            <p><strong>Email:</strong> support@mti-attendance.com</p>
        </div>

        <div class="last-updated">
            Last Updated: <?= date('F d, Y') ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <span class="footer-logo">MTI Attendance</span>
        <p>&copy; <?= date('Y') ?> MTI Attendance. All rights reserved.</p>
    </footer>

</body>
</html>
