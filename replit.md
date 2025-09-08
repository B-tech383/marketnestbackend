# Overview

This is an OrangeCart e-commerce application built with PHP and SQLite. The application features a complete e-commerce platform with user management, vendor system, product catalog, shopping cart, order management, and advanced shipment tracking capabilities. The project has been successfully configured for the Replit environment and is running on port 5000.

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Backend Architecture
- **Framework**: PHP 8.2 with native server capabilities
- **Database**: SQLite for development with complete e-commerce schema
- **Authentication**: Session-based authentication with role-based access control
- **File Structure**: Modular PHP classes for database operations and business logic

## Database Design
- **Users Management**: Complete user registration, authentication, and role management
- **Product Catalog**: Categories, products, inventory, and vendor management
- **Order System**: Shopping cart, orders, order items, and payment tracking
- **Shipping & Tracking**: Advanced shipment tracking with history and real-time updates
- **Additional Features**: Wishlist, reviews, notifications, coupons, and badges

## Key Components
- **Authentication System**: User registration, login, role-based access (customer, vendor, admin)
- **Product Management**: Category-based product organization with vendor support
- **Shopping Cart**: Session-based cart management with real-time updates
- **Order Processing**: Complete order lifecycle from cart to delivery
- **Tracking System**: Advanced shipment tracking with multiple service levels
- **Vendor Portal**: Vendor application system and product management dashboard
- **Admin Panel**: Complete administrative interface for user, vendor, and order management

## Recent Setup Changes
- **Date**: September 8, 2025
- **Database Migration**: Converted from MySQL to SQLite for Replit compatibility
- **Path Corrections**: Fixed all include/require path references to use __DIR__ for consistency
- **SQL Compatibility**: Updated INSERT IGNORE to INSERT OR IGNORE for SQLite
- **Environment Configuration**: Configured for Replit hosting with proper domain handling
- **Sample Data**: Initialized with categories and admin user for immediate testing

# External Dependencies

## Core Requirements
- **PHP**: Version 8.2 with PDO SQLite support
- **Database**: SQLite database with foreign key constraints enabled
- **Web Server**: PHP built-in development server configured for port 5000

## Styling and UI
- **Tailwind CSS**: CDN-based utility-first CSS framework for responsive design
- **Custom Styling**: Orange-themed color scheme with primary orange (#f97316) branding