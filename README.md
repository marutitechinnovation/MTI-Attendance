# 🏢 MTI Attendance System — Web Panel & API

A QR-based employee attendance management system. Employees scan QR codes via mobile app to mark check-in/check-out. Admins manage everything from this web panel.

---

## ✨ Features

- 📲 **QR Scan Attendance** — Check-in & check-out via mobile app scan
- ☕ **Multiple Breaks** — Pause shifts multiple times; calculates true net working hours
- 🕒 **Auto-Checkout Recovery** — Forgetting to checkout automatically triggers a 24-hour auto-checkout
- 📍 **Geofencing** — Validate employee is within allowed radius of QR location
- 🗺️ **Live Map** — Leaflet.js map showing all QR locations & live attendance
- 👤 **Employee Management** — Add, edit, deactivate employees
- 📋 **Self-Registration** — Public signup page for new employees
- 🔳 **QR Code Management** — Generate, print, and manage QR codes per location
- 📊 **Attendance Logs** — Filter by date, employee, department; export CSV/Excel
- 💰 **Monthly Payroll Report** — Days worked, absent, late; export PDF/Excel
- ⚙️ **Settings** — Configure geofence radius, working hours, company info

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2 + CodeIgniter 4 |
| Database | MySQL 8.0 |
| Frontend | HTML5 + CSS3 + Vanilla JS |
| Map | Leaflet.js (OpenStreetMap) |
| QR Generation | phpqrcode |
| Container | Docker + Docker Compose |

---

## 🚀 Getting Started

### Prerequisites
- [Docker](https://www.docker.com/) installed and running

### Setup

```bash
# 1. Clone the repository
git clone <repo-url>
cd "MTI Attandance Web"

# 2. Copy environment file
cp .env.example .env

# 3. Start all Docker containers
docker-compose up -d

# 4. Install PHP dependencies (inside container)
docker exec -it mti_attendance_app composer install

# 5. Run database migrations
docker exec -it mti_attendance_app php spark migrate

# 6. (Optional) Seed demo data
docker exec -it mti_attendance_app php spark db:seed AttendanceSeeder
```

### Access

| Service | URL |
|---|---|
| 🌐 Web Admin Panel | http://localhost:8082 |
| 📱 Employee PWA | http://localhost:8082/employee/login |
| 🧑‍💻 Employee Signup | http://localhost:8082/signup |
| 🗄️ phpMyAdmin | http://localhost:8083 |
| 🔌 API Base URL | http://localhost:8082/api |

### Default Admin Login
```
Email:    admin@mti.com
Password: give me 
```

---

## 📁 Project Structure

```
MTI Attandance Web/
├── app/
│   ├── Controllers/
│   │   ├── Auth.php              # Admin web login & Public signup
│   │   ├── Dashboard.php         # Dashboard page
│   │   ├── Employees.php         # Employee management
│   │   ├── QRCodes.php           # QR code management
│   │   ├── Attendance.php        # Attendance logs
│   │   ├── Reports.php           # Reports & payroll
│   │   ├── MapView.php           # Map page
│   │   ├── Settings.php          # Settings page
│   │   └── api/
│   │       ├── AttendanceApi.php  # POST /api/attendance/scan
│   │       ├── EmployeeApi.php    # Employee CRUD API
│   │       ├── QRCodeApi.php      # QR code API
│   │       ├── ReportApi.php      # Reports API
│   │       └── MapApi.php         # Map data API
│   ├── Models/
│   │   ├── EmployeeModel.php
│   │   ├── AttendanceModel.php
│   │   ├── QRTokenModel.php
│   │   ├── SettingsModel.php
│   │   └── UserModel.php
│   ├── Views/
│   │   ├── layouts/main.php      # Admin layout (sidebar + topbar)
│   │   ├── auth/login.php
│   │   ├── dashboard/index.php
│   │   ├── employees/
│   │   ├── qrcodes/
│   │   ├── attendance/
│   │   ├── reports/
│   │   ├── map/
│   │   └── settings/
│   └── Database/
│       ├── Migrations/           # DB table migrations
│       └── Seeds/                # Demo data seeders
├── docker/
│   └── apache/vhost.conf
├── public/
│   └── assets/
│       ├── css/style.css
│       └── js/app.js
├── app/Views/employee/app.php     # Employee PWA shell
├── public/assets/js/employee-pwa.js
├── public/assets/css/employee-pwa.css
├── public/manifest.webmanifest
├── public/sw.js
├── docker-compose.yml
├── Dockerfile
├── .env.example
└── README.md
```

---

## 📱 Employee PWA

Employee mobile web app is available at:

- `/employee/login`

Current employee PWA includes:

- login/logout flow using existing employee API
- dashboard with today timeline and worked/break summary
- attendance scan flow with camera QR support + geolocation
- monthly attendance history and status chips
- holidays and profile tabs
- installable PWA manifest and service worker caching

For rollout verification, use:

- `EMPLOYEE_PWA_QA.md`

---

## 🔌 API Reference

### Scan Attendance (Mobile App)
```http
POST /api/attendance/scan
Content-Type: application/json

{
  "employee_id": 42,
  "qr_token": "abc123xyz",
  "latitude": 23.0225,
  "longitude": 72.5714
}
```

**Response (Success):**
```json
{
  "status": "success",
  "type": "check_in",
  "employee": "Rahul Sharma",
  "location": "Main Gate",
  "time": "09:02:15",
  "geofence_status": "inside"
}
```

**Response (Outside Geofence):**
```json
{
  "status": "flagged",
  "type": "check_in",
  "message": "You are 120m away from Main Gate (allowed: 50m). Marked but flagged for review."
}
```

### Other Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/employees` | List employees |
| POST | `/api/employees` | Add employee |
| PUT | `/api/employees/{id}` | Update employee |
| GET | `/api/qr-codes` | List QR codes |
| POST | `/api/qr-codes` | Generate QR |
| GET | `/api/attendance/today` | Today's attendance |
| GET | `/api/reports/daily` | Daily report |
| GET | `/api/reports/monthly` | Monthly payroll summary |
| GET | `/api/reports/export` | Export PDF/CSV/Excel |
| GET | `/api/map/live` | Live map data |
| GET | `/api/settings` | Get settings |
| PUT | `/api/settings` | Update settings |

---

## 🐳 Docker Services

| Container | Role | Port |
|---|---|---|
| `mti_attendance_app` | PHP 8.2 + Apache | 8082 |
| `mti_attendance_db` | MySQL 8.0 | 3307 |
| `mti_attendance_phpmyadmin` | DB Admin UI | 8083 |

---

## 📍 Geofencing

Each QR code has a configurable radius (default: 50m). When an employee scans:
- ✅ **Inside radius** → Attendance marked normally
- ⚠️ **Outside radius** → Attendance marked as **flagged** for admin review

Admin can change the radius:
- **Globally** → Settings page → Default Geofence Radius
- **Per QR code** → QR Code Management → Edit radius per location

---

## 📄 License

MIT License — MTI © 2026
