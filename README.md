# Agricultural Marketplace - Setup Guide

This project is a fully responsive agricultural marketplace built with PHP, MySQL, and Bootstrap, featuring a premium design inspired by the "Fauget" e-commerce dashboard.

## Prerequisites
- XAMPP (with PHP 7.4+ and MySQL)
- Web Browser

## Installation Steps

1.  **Copy Project Files**:
    Place the `Aggricultural_Market` folder into your XAMPP `htdocs` directory (e.g., `C:\xampp\htdocs\Aggricultural_Market`).

2.  **Database Setup**:
    - Open XAMPP Control Panel and start **Apache** and **MySQL**.
    - Open [phpMyAdmin](http://localhost/phpmyadmin).
    - Create a new database named `agrimarket_db`.
    - Select the new database, go to the **Import** tab, and upload the `schema.sql` file located in the project root.

3.  **Run the Application**:
    - Open your browser and navigate to [http://localhost/Aggricultural_Market](http://localhost/Aggricultural_Market).

## System Credentials (Default)

- **Admin Account**: 
  - Email: `admin@agrimarket.com`
  - Password: `password`

- **Register New Accounts**:
  - You can register as a **Farmer** to list products and view sales reports.
  - You can register as a **Buyer** to browse products, place orders, and message farmers.

## Features
- **Responsive Theme**: Cloned "Fauget" style with teal/orange color scheme.
- **Role-Based Dashboards**: Customized tools for Farmers, Buyers, and Admins.
- **Messaging**: Real-time AJAX-powered chat system.
- **Analytics**: Beautiful charts using Chart.js for revenue and user data.
- **Product Management**: Image uploads and CRUD operations for farmers.
