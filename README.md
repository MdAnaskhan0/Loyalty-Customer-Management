# Fashion Optics Loyalty Customer Management System

A comprehensive web application for managing loyalty customers across multiple branches of Fashion Optics Ltd.

## Features

- **User Authentication**: Secure login system with session management
- **Customer Management**: Add, view, and manage customer information
- **Duplicate Prevention**: Smart duplicate checking by name and phone number
- **Search & Filter**: Advanced search functionality with date range filtering
- **Data Export**: Export customer data to CSV format
- **Database Backup**: Download complete database as SQL backup
- **Responsive Design**: Mobile-friendly interface using Bootstrap 5

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

## Installation

1. Clone or download the project files to your web server
2. Create a MySQL database named `loyal_customer`
3. Import the following SQL structure:

```sql
CREATE TABLE `customersinfo` (
  `customerId` int(11) NOT NULL AUTO_INCREMENT,
  `customerName` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `branch` varchar(100) NOT NULL,
  `entryDate` date NOT NULL,
  PRIMARY KEY (`customerId`)
);

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `users` (`username`, `password`) VALUES
('admin', 'password123'); -- Change this password after installation
```

4. Update database credentials in all PHP files if needed:
   - `index.php`
   - `loyalCustomer.php`
   - `downloadDatabase.php`

5. Access the application through your web browser

## File Structure

```
├── index.php              # Login page
├── loyalCustomer.php      # Main customer management interface
├── downloadDatabase.php   # Database backup functionality
├── changePassword.php     # Password change utility (to be implemented)
├── logout.php            # Session destruction script
└── logo.png              # Company logo (should be added)
```

## Usage

1. **Login**: Access the system with valid credentials
2. **Add Customers**: Use the form to input new customer information
   - System automatically checks for duplicates
   - Default date is set to previous day
3. **Search & Filter**: Use the search bar and date filters to find specific customers
4. **Export Data**: Download customer records as CSV file
5. **Database Backup**: Download a complete SQL backup through the account menu

## Security Notes

- Change the default database credentials in production
- Implement password hashing for user accounts
- Consider adding SSL encryption for production use
- Regularly backup the database using the provided functionality

## Customization

- Modify the branch list in `loyalCustomer.php` to match your locations
- Adjust the styling in the CSS sections to match your brand
- Update the footer information with your company details

## Support

For technical support or customization requests, please contact the development team at Fashion Group IT.

## License

This software is proprietary and developed for Fashion Optics Ltd. All rights reserved.
