# Inventory Management System

A comprehensive web-based inventory management system built with PHP and MySQL, designed to help businesses efficiently manage their inventory, orders, suppliers, and employees.

## Features

- **User Authentication & Authorization**
  - Multiple user roles (Admin, Employee, Customer)
  - Secure login and registration system
  - Role-based access control

- **Inventory Management**
  - Real-time stock tracking
  - Product categorization
  - Low stock alerts
  - Batch inventory updates

- **Order Management**
  - Shopping cart functionality
  - Order processing
  - Order history tracking
  - Invoice generation

- **Supplier Management**
  - Supplier database
  - Purchase order creation
  - Supplier performance tracking

- **Employee Management**
  - Employee profiles
  - Activity logging
  - Performance tracking

## Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Server**: Apache
- **Additional Tools**: XAMPP

## Installation

1. Clone the repository:
```bash
git clone https://github.com/WallBreakerNO4/DBMS
```

2. Import the database:
```bash
mysql -u [username] -p [database_name] < inventory_system.sql
```

3. Configure your database connection:
   - Navigate to `config/` directory
   - Update database credentials in the configuration file

4. Set up your Apache server:
   - Place the project in your web server's root directory
   - Ensure mod_rewrite is enabled

5. Start your XAMPP server and visit:
```
http://localhost/[project-directory]
```

## Directory Structure

```
├── admin/          # Admin panel files
├── api/           # API endpoints
├── assets/        # Static assets (CSS, JS, images)
├── auth/          # Authentication related files
├── cart/          # Shopping cart functionality
├── config/        # Configuration files
├── employees/     # Employee management
├── includes/      # Common PHP includes
├── inventory/     # Inventory management
├── orders/        # Order processing
├── products/      # Product management
├── suppliers/     # Supplier management
└── scripts/       # Database scripts
```

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- mod_rewrite enabled
- XAMPP (recommended)

## Security Features

- Password hashing
- SQL injection prevention
- XSS protection
- CSRF protection
- Role-based access control

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please create an issue in the repository. 