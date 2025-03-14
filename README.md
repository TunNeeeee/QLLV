# Thesis Advisor Management System

## Overview
The Thesis Advisor Management System is a web-based application designed to facilitate the management and tracking of thesis guidance activities between students and faculty members. It provides a structured platform to record student details, faculty advisors, and their interactions throughout the thesis advisory process.

## Features
- **User Roles**: The system supports three main user roles:
  - **Students**: Register for thesis guidance, view assigned advisor details, and check guidance status.
  - **Faculty Members**: Manage assigned students, add progress notes, and approve or reject thesis topics.
  - **Administrators**: Manage faculty and student records, assign students to faculty members, and generate reports on thesis progress.

- **Core Features**:
  - User Authentication & Role-based Access Control
  - Student Registration & Thesis Assignment
  - Advisor Management Dashboard
  - Progress Tracking and Notes
  - Reporting & Analytics

## Technologies Used
- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Hosting**: Shared Hosting or Cloud Hosting (cPanel, AWS, DigitalOcean, etc.)

## Installation
1. **Clone the Repository**: 
   ```bash
   git clone <repository-url>
   cd thesis-management-system
   ```

2. **Set Up the Database**:
   - Import the SQL script located in `database/thesis_management.sql` to create the necessary database and tables.

3. **Configure Database Connection**:
   - Update the database connection settings in `config/config.php` with your database credentials.

4. **Run the Application**:
   - Access the application via your web server (e.g., `http://localhost/thesis-management-system/index.php`).

## Usage
- Users can register and log in to access their respective dashboards.
- Administrators can manage users and oversee the entire thesis management process.
- Faculty can monitor their assigned students and manage thesis topics.
- Students can view their assigned advisors and track their thesis progress.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.