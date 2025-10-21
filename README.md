

# Pet Visit Record System (PVRS) v1.0

A web-based system for managing clients, patients, and appointments for PetLink Caloocan veterinary clinic.

---

## Overview

The Pet Visit Record System (PVRS) is a comprehensive solution designed to replace manual record-keeping with an efficient, digital platform. It allows clinic staff to manage all aspects of the patient visit lifecycle, from client registration to medical record entry and invoicing.

### Core Features
- **Dashboard:** At-a-glance view of daily operations, KPIs, and upcoming appointments.
- **Client & Patient Management:** A complete directory of clients and their pets, with detailed visit histories.
- **Appointment Scheduling:** An interactive calendar and table for booking and managing visits.
- **Medical Records:** Digital SOAP (Subjective, Objective, Assessment, Plan) notes for each visit.
- **Billing & Invoicing:** Simple invoice generation based on services and items used during a visit.
- **User Roles:** Secure access control with distinct roles for Admin and Staff.

---

## Technology Stack
- **Frontend:** HTML, CSS, JavaScript, Chart.js
- **Backend:** PHP
- **Database:** MySQL / MariaDB
- **Server:** Apache (via XAMPP)

---

## Prerequisites

Before you begin, ensure you have the following software installed on your server machine:
- **XAMPP** (or any other Apache/MySQL/PHP stack). Recommended PHP version 7.4+.

---

## Installation Guide

Follow these steps to set up the system on a local server.

**1. Place Project Files:**
   - Unzip the main project archive.
   - Move the `petlink-php` folder into your XAMPP `htdocs` directory.
   - The final path should be `C:/xampp/htdocs/petlink-php/`.

**2. Import the Database:**
   - Open the **XAMPP Control Panel** and start the **Apache** and **MySQL** services.
   - Open your web browser and navigate to `http://localhost/phpmyadmin`.
   - Click **"New"** on the left sidebar to create a new database.
   - Enter `petlink_db` as the database name and click **"Create"**.
   - Select the newly created `petlink_db` database.
   - Click the **"Import"** tab at the top.
   - Click **"Choose File"** and select the `petlink_db.sql` file located inside the `petlink-php/database/` folder.
   - Click the **"Import"** (or "Go") button at the bottom of the page and wait for it to complete.

**3. Configure Database Connection (If Necessary):**
   - The system is pre-configured to connect to a default XAMPP MySQL setup (user: `root`, no password).
   - If your MySQL setup has a password, you must edit the connection file: `petlink-php/config/database.php`.
   - Update the `$password` variable on line 6 with your MySQL root password.

**4. Access the System:**
   - You can now access the system by navigating to: **http://localhost\petlink-php\index.php**
   - *Note: If your XAMPP uses a different port, adjust the URL accordingly (e.g., http://localhost:8080/petlink-php/).*

---

## Default Login Credentials

Two default user accounts are created by the database import:

- **Admin Account:**
  - **Username:** `admin`
  - **Password:** `123`


It is highly recommended to change these passwords after the first login.
