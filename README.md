# MyMoney - Digital Wallet Application 
 
A secure digital wallet system built with **Laravel 11** featuring multi-module architecture, ACID-compliant transactions, and user-friendly money management. 
 
--- 
 
## 📋 Table of Contents 
 
- [Overview](#-overview) 
- [Features](#-features) 
- [Tech Stack](#-tech-stack) 
- [System Requirements](#-system-requirements) 
 
--- 
 
## 🔍 Overview 
 
MyMoney is a full-featured digital wallet application that allows users to: 
 
- Register with KYC verification (NID, address, date of birth) 
- Verify email via OTP 
- Send and receive money securely 
- Request payments from other users 
- View transaction history and download PDF receipts 
- Manage profile and security settings 
 
The system implements **optimistic locking** for concurrent balance updates, **idempotency** for duplicate prevention, and **Redis** for high-performance caching and session management. 
 
--- 
 
## ✨ Features 
 
### 🔐 Authentication & User Management 
- User registration with KYC details (name, email, NID, address, DOB) 
- NID number extraction via OCR (Tesseract.js) 
- Email verification with OTP (6-digit code) 
- Secure login with email/password 
- Password reset via email 
- Profile management with photo upload 
- User settings (smart contact name check, OTP receiver confirmation) 
 
### 💳 Wallet System 
- Each user receives a unique account 
- Initial wallet balance: **৳100,000.00** 
- ACID-compliant balance updates with **optimistic locking** 
- Balance reservation for pending transactions (prevents double-spending) 
- Balance history audit trail 
- Redis caching for fast balance retrieval 
 
### 💸 Money Transfers 
- Send money to other users (by account number or user ID) 
- Recipient preview (name, photo, account number) 
- Receiver OTP confirmation (optional security feature) 
- Idempotent operations with transaction attempt tracking 
- Concurrent transfer handling with retry logic 
 
### 📊 Dashboard 
- Account number display 
- Available balance 
- Recent transactions (last 10) 
- Pending money requests count 
- Completed transfers count 
 
### 🔔 Notifications 
- In-app notification system 
- Wallet event notifications (activation, transfers, requests) 
- Real-time notification counts 
- Transaction receipts (PDF export) 
 
### 💰 Money Requests 
- Request money from other users 
- Accept or decline received requests 
- Automatic transfer on acceptance 
 
--- 
 
## 🛠️ Tech Stack 
 
| Category | Technology | 
|----------|------------| 
| **Backend Framework** | Laravel 11 (PHP 8.2+) | 
| **Database** | MySQL 5.7+ | 
| **Caching** | Redis (via Predis) | 
| **Session Storage** | Redis | 
| **Queue System** | Redis | 
| **Frontend** | Blade Templates, Bootstrap 5, Tailwind CSS | 
| **JavaScript** | Vanilla JS, Tesseract.js (OCR), Axios | 
| **Authentication** | Laravel Native Auth (customized) | 
| **PDF Generation** | Laravel DOMPDF | 
| **Notifications** | Laravel Notifications | 
| **OCR** | Tesseract.js (client-side NID extraction) | 
 
--- 
 
## 📦 System Requirements 
 
- **PHP** >= 8.2 
- **Composer** (latest version) 
- **MySQL** >= 5.7 
- **Redis** >= 6.0 (for caching, sessions, and queue) 
- **Node.js** >= 18.0 
- **NPM** >= 9.0 
- **Web Server** (Apache/Nginx) or PHP's built-in server
