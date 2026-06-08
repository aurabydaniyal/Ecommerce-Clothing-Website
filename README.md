# 🛍️ UHD-Wears - Premium E-Commerce Platform

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A **complete, production-ready e-commerce platform** for a premium clothing brand with customer frontend and admin panel.

## ✨ Features

### 👤 Customer Side
- User authentication (Login/Signup with password hashing)
- Remember Me (30-day cookie auto-login)
- Product browsing by categories (Men, Women, Kids, Sale)
- Search & filter (Category filter, price sort, newest, popular)
- Shopping cart with AJAX (No page reload)
- Wishlist functionality
- Secure checkout with address management
- Order tracking with status timeline
- PDF invoice download
- User profile management
- Newsletter subscription

### 👑 Admin Panel
- Dashboard with revenue charts & statistics
- Product CRUD (with multiple image upload)
- Order management (status update, notes, delete)
- User management
- Category management
- Slider management (images & videos)
- Store inventory with PDF export
- Stock alerts for low inventory

### 🎨 Design Features
- Glassmorphism UI with backdrop blur
- Neon yellow grid background
- Fully responsive (Mobile/Tablet/Desktop)
- Toast notifications (No browser alerts)
- SweetAlert2 confirmations
- Typing effect on hero sliders

## 🛠️ Tech Stack

| Technology | Purpose |
|------------|---------|
| PHP 7.4+ | Backend logic |
| MySQL | Database |
| Bootstrap 5 | Responsive framework |
| jQuery/AJAX | Dynamic content |
| Chart.js | Admin charts |
| SweetAlert2 | Popup notifications |
| html2pdf.js | PDF invoices |

## 🗄️ Database Schema (13 Tables)

| Table | Purpose |
|-------|---------|
| users | Customer/admin accounts |
| products | Product catalog with images |
| categories | Product categories |
| cart | Shopping cart items |
| wishlist | Saved items |
| orders | Customer orders |
| order_items | Items per order |
| order_tracking | Status history |
| sliders | Hero sliders |
| reviews | Product reviews |
| stock_alerts | Low stock notifications |
| notifications | User alerts |
| user_addresses | Saved addresses |

## 🚀 Installation

### Prerequisites
- XAMPP/WAMP/MAMP (PHP 7.4+ & MySQL)
- Git

### Steps
```bash
1. Clone repository
- git clone https://github.com/aurabydaniyal/Ecommerce-Clothing-Website.git

2. Move to htdocs (XAMPP) or www (WAMP)
- mv uhd-wears C:/xampp/htdocs/

3. Import database
- Open phpMyAdmin → Create database 'uhd_wears' → Import sql/uhd_wears.sql

4. Configure database
- Edit db_connection.php with your credentials

5. Run project
- http://localhost/uhd-wears/

## Default Admin Login
- Username: itxadmin
- Password: admin123

