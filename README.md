# 🏘️ AHON-BRGY
### Web-Based Poverty Profiling and Assistance Monitoring System

![SDG 1](https://img.shields.io/badge/SDG%201-No%20Poverty-red)
![SDG 10](https://img.shields.io/badge/SDG%2010-Reduced%20Inequalities-pink)
![SDG 16](https://img.shields.io/badge/SDG%2016-Strong%20Institutions-blue)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange)

---

## 📌 Project Overview

**AHON-BRGY** is a web-based system that helps barangay officials digitally manage poverty profiling and track assistance distribution. It solves the problem of manual, paper-based record keeping which leads to:

- Duplicate assistance distribution
- Inaccurate or outdated records
- Slow data retrieval and report generation
- Lack of transparency in social support programs

**Primary SDG:** SDG 1 — No Poverty  
**Supporting SDGs:** SDG 10 (Reduced Inequalities), SDG 16 (Strong Institutions)

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, JavaScript |
| Backend | PHP 7.4+ |
| Database | MySQL (via XAMPP) |
| Server | Apache (via XAMPP) |
| Version Control | GitHub |

---

## 🚀 How to Run / Install

### Requirements
- XAMPP (Apache + MySQL)
- VS Code (or any code editor)
- Web browser

### Steps

1. **Install XAMPP** from [apachefriends.org](https://www.apachefriends.org)
2. **Start XAMPP** — click Start on Apache and MySQL
3. **Clone this repo** into your htdocs folder:
   ```bash
   git clone https://github.com/jswa26/ahon-brgy.git C:\xampp\htdocs\ahon-brgy
   ```
4. **Set up the database:**
   - Open `http://localhost/phpmyadmin`
   - Click **Import** tab
   - Upload the file: `docs/ahon_brgy_db.sql`
   - Click **Go**
5. **Open the app:**
   - Go to `http://localhost/ahon-brgy/src/login.php`

---

## 🔑 Sample Credentials

| Username | Password | Role |
|----------|----------|------|
| `admin`  | `admin123` | Admin |
| `staff1` | `staff123` | Staff |

> ⚠️ These are demo credentials only. Do NOT use in production.

---

## 📁 Project Structure

```
ahon-brgy/
├── docs/
│   ├── ahon_brgy_db.sql       ← Database setup script
│   └── architecture.png       ← System architecture diagram
├── src/
│   ├── style.css              ← Global design system
│   ├── db.php                 ← Database connection
│   ├── sidebar.php            ← Navigation component
│   ├── login.php              ← Login page
│   ├── dashboard.php          ← Main dashboard
│   ├── households.php         ← Household CRUD
│   ├── assistance.php         ← Assistance tracking
│   ├── reports.php            ← Reports & statistics
│   ├── users.php              ← User management (admin)
│   └── logout.php             ← Session logout
└── README.md
```

---

## ✅ Features

- **Household Registration** — Add, edit, delete household profiles with poverty level classification
- **Assistance Tracking** — Record and monitor all types of assistance given
- **Duplicate Detection** — Prevents same household receiving same aid type in one month
- **Dashboard** — Real-time statistics and summaries
- **Reports** — Poverty distribution, assistance trends, households needing attention
- **User Management** — Admin can create/delete staff accounts
- **Print Reports** — Browser-print-friendly report page

---

## 📸 Screenshots

> Add screenshots here after running the system locally.

---

## 👥 Team Members

- Paul Joshua Ygoña
- Edeve Cavalida
- Glenn Kurt Sancha
