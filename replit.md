# Overview

This is Market Nest, a professional e-commerce marketplace built with PHP and SQLite. The application features a complete e-commerce platform with user management, vendor system, product catalog, shopping cart, order management, and advanced shipment tracking capabilities. The project has been successfully rebranded and redesigned with modern, Amazon-level UI and is running on port 5000.

# User Preferences

Preferred communication style: Simple, everyday language.
UI Design Preference: Professional, modern design comparable to Amazon with clean layouts and excellent user experience.

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

## Recent Design Updates
- **Date**: September 8, 2025
- **Complete Rebranding**: Transformed from "OrangeCart" to "Market Nest" with professional branding
- **UI Overhaul**: Implemented Amazon-style modern design with:
  - Professional color scheme (slate/blue instead of orange)
  - Modern typography using Inter font family
  - Enhanced homepage with hero section and improved layouts
  - Professional login/signup pages with split-screen design
  - Improved navigation with better search functionality
  - Consistent branding with "MN" logo throughout
- **User Experience**: Enhanced forms, better spacing, hover effects, and responsive design
- **Color Palette**: Primary (#0f172a), Secondary (#1e293b), Accent (#3b82f6), Warning (#f59e0b), Success (#10b981)

# External Dependencies

## Core Requirements
- **PHP**: Version 8.2 with PDO SQLite support
- **Database**: SQLite database with foreign key constraints enabled
- **Web Server**: PHP built-in development server configured for port 5000

## Styling and UI
- **Tailwind CSS**: CDN-based utility-first CSS framework for responsive design
- **Typography**: Inter font family for professional appearance
- **Color Scheme**: Professional blue/slate theme with Market Nest branding
  - Primary: Deep slate (#0f172a) for headers and text
  - Secondary: Slate (#1e293b) for navigation elements  
  - Accent: Blue (#3b82f6) for buttons and highlights
  - Supporting colors: Warning (#f59e0b), Success (#10b981)
- **Design Language**: Modern, clean, Amazon-inspired professional interface