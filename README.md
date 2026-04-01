# 🧧 SalamiPay (সালামির পাতা) - Digital Salami Collection Platform

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892bf.svg?style=flat-square&logo=php)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](https://opensource.org/licenses/MIT)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square)](http://makeapullrequest.com)

**SalamiPay (সালামির পাতা)** is a modern, premium web application designed to simplify and digitize the traditional process of giving and receiving "Salami" during festivals (like Eid) in Bangladesh. Users can create a personalized profile, list their Mobile Financial Service (MFS) details, and share their unique link to receive gifts directly to their accounts.

---

## ✨ Key Features

### 👤 For Users
- **Personalized Profiles**: Create a unique "Salami Page" with a custom username (e.g., `yoursite.com/username`).
- **MFS Management**: Add multiple MFS accounts (Bkash, Nagad, Rocket, Upay) and set a primary one for quick display.
- **Transaction Logs**: Real-time tracking of received Salami with details like sender name, message, and transaction ID.
- **Interactive Dashboard**: Visual statistics for total collected amount, verified vs. pending transactions, and profile views.
- **Privacy & Security**: Built-in protection against spam and offensive content.

### 🛡️ For Administrators
- **User Management**: View, edit, or suspend user accounts.
- **Log Verification**: Audit and verify transaction logs submitted by senders.
- **IP Blocking System**: Advanced security to block malicious IP addresses from accessing the platform.
- **Analytics**: High-level overview of platform growth and activity.

### 🎨 Design & UX
- **Premium Aesthetics**: Modern "Glassmorphism" UI with dark/light mode support.
- **Animations**: Smooth micro-animations and transitions for a premium feel.
- **Responsive Design**: Fully optimized for mobile, tablet, and desktop screens.
- **SEO Optimized**: Meta tags and semantic HTML for better search engine visibility.

---

## 🚀 Tech Stack

- **Backend**: Core PHP (Vanilla) with PDO (PHP Data Objects).
- **Database**: MySQL/MariaDB.
- **Frontend**: HTML5, CSS3 (Custom Styles), JavaScript (Vanilla).
- **Icons**: FontAwesome 6 (Pro).
- **Security**: 
  - CSRF Token Protection.
  - SQL Injection Prevention (PDO prepared statements).
  - XSS Filtering.
  - Advanced Bengali + English Profanity Filter.
  - IP-based Rate Limiting & Blocking.

---

## 🛠️ Installation Guide

Follow these steps to set up the project on your local machine or server.

### Prerequisites
- PHP >= 7.4
- MySQL / MariaDB
- Apache Web Server (with `mod_rewrite` enabled)

### Step 1: Clone the Repository
```bash
git clone https://github.com/websmartbd/salami.git
cd salami
```

### Step 2: Database Setup
1. Create a new database in your MySQL server (e.g., `salami_db`).
2. Import the `databse.sql` file into your database.
   ```bash
   mysql -u your_username -p salami_db < databse.sql
   ```

### Step 3: Configuration
1. Navigate to the `includes/` directory.
2. Open `config.php` and update the database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'salami_db');
   define('DB_USER', 'your_mysql_user');
   define('DB_PASS', 'your_mysql_password');
   define('BASE_URL', 'http://example.com');
   ```

### Step 4: Permissions
Ensure the `uploads/` directory has write permissions for the web server:
```bash
chmod -R 775 uploads/
```

### Step 5: .htaccess
Ensure your Apache server allows `.htaccess` overrides (`AllowOverride All`) to enable clean URLs.

---

## 📂 Project Structure

```text
├── assets/             # CSS, JS, and Image assets
├── includes/           # Core logic, functions, and config
│   ├── config.php      # Database & Environment config
│   ├── functions.php   # Reusable helper functions
│   ├── header.php      # Global header component
│   └── footer.php      # Global footer component
├── uploads/            # User profile images and media
├── admin.php           # Administrative dashboard
├── dashboard.php       # User personal dashboard
├── profile.php         # Public profile viewer
├── index.php           # Landing page
├── register.php        # User registration (Multi-step)
├── login.php           # User authentication
└── databse.sql         # Database schema
```

---

## 🤝 Contributing

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

---

## 📞 Support & Community

- **Creator**: [BM Shifat](https://facebook.com/bmshifat0)
- **Project Website**: [সালামির পাতা](https://সালামির.পাতা.বাংলা/)
- **Bug Reports**: Please use the [GitHub Issues](https://github.com/websmartbd/salami/issues) page.

---
*Made with ❤️ in Bangladesh for a better digital tradition.*
