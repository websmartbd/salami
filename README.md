# 🧧 SalamiPay (সালামির পাতা) - Digital Salami Platform

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892bf.svg?style=flat-square&logo=php)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Non--Commercial-red.svg?style=flat-square)](LICENSE)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square)](http://makeapullrequest.com)

**SalamiPay (সালামির পাতা)** is a modern, premium web application designed to simplify and digitize the traditional process of giving and receiving "Salami" during festivals in Bangladesh. Users can create a personalized profile, list their Mobile Financial Service (MFS) details, and share their unique link to receive gifts directly to their accounts.

---

## ✨ Key Features

### 👤 For Users
- **Personalized Profiles**: Create a unique "Salami Page" with a custom username (e.g., `সালামির.পাতা.বাংলা/username`).
- **MFS Management**: Add multiple MFS accounts (Bkash, Nagad, Rocket, Upay) and set a primary one for quick display.
- **Transaction Logs**: Real-time tracking of received Salami with details like sender name, message, and transaction ID.
- **Interactive Dashboard**: Visual statistics for total collected amount, verified vs. pending transactions, and profile views.

### 🛡️ Security & Integrity
- **Advanced Profanity Filter**: Hybrid Bengali-English filter to catch offensive language and bypass tricks.
- **Data Integrity**: Full protection against SQL injection using PHP PDO prepared statements.
- **IP Blocking System**: Advanced security to block malicious IP addresses.
- **CSRF Protection**: Native protection against cross-site request forgery.

---

## 🚀 Tech Stack

- **Backend**: Core PHP (Vanilla) with PDO.
- **Database**: MySQL/MariaDB.
- **Frontend**: HTML5, CSS3 (Glassmorphism), Vanilla JavaScript.
- **Icons**: FontAwesome 6.

---

## 🛠️ Installation Guide

### Step 1: Clone the Repository
```bash
git clone https://github.com/websmartbd/salami.git
cd salami
```

### Step 2: Database Setup
1. Create a database (e.g., `salami_db`).
2. Import `databse.sql`.

### Step 3: Configuration
Update `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_pass');
define('BASE_URL', 'https://সালামির.পাতা.বাংলা'); // No trailing slash
```

---

## 🤝 Contributing & Support

Contributions are welcome! If you want to improve the UI or add features:
1. Fork the Project.
2. Create your Feature Branch.
3. Open a Pull Request.

- **Creator**: [BM Shifat](https://facebook.com/bmshifat0)
- **Project Website**: [সালামির পাতা](https://সালামির.পাতা.বাংলা/)
- **Bug Reports**: Please use the [GitHub Issues](https://github.com/websmartbd/salami/issues) page.

---

## 📄 License
Distributed under a **Custom Non-Commercial License**. This software **MAY NOT BE SOLD or RESOLD**. See `LICENSE` for more information.

*Made with ❤️ in Bangladesh for a better digital tradition.*
