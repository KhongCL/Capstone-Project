**TrafAnalyz Web Application**
TrafAnalyz is a complementary web analytics dashboard designed to simplify web traffic analysis for individuals, businesses, and organizations. This project provides a user-friendly interface for importing, visualizing, and analyzing web traffic data, with features for both end-users and administrators.

Features
Real-time Analytics: Visualize web traffic data instantly.
User Behavior Tracking: Understand visitor interactions with your website.
CSV Import: Seamlessly upload and process analytics data in CSV format.
Admin Tools: Manage user accounts, configure CSV mappings, and oversee data validation.
Export Options: Generate reports in PDF and CSV formats.
Setup Instructions
Prerequisites
To run the TrafAnalyz web application locally, ensure you have the following installed:

XAMPP (or a similar local web server environment with PHP and MySQL).
Installation Steps
Extract Zip File
Extract the zip file named AAPP011-4-2_GROUP ASSIGNMENT_CAPSTONE_PROJECT_The LOLcalhosts_KHONG CHEE LEONG_TP075846.zip.
Inside the zip file, you will find:

A folder named TrafAnalyz.
A database file named trafanalyz.sql.
Import Database

Open phpMyAdmin and create a new database named trafanalyz.
Import the trafanalyz.sql file into the trafanalyz database using the Import tab in phpMyAdmin.
Copy Website Folder

Copy and paste the entire TrafAnalyz folder into htdocs.
Start XAMPP Services

Open the XAMPP Control Panel and start Apache and MySQL.
Access URLs
Use the following URLs to access the TrafAnalyz web application:

Admin Login: http://localhost/trafanalyz/admin_login.php?key=trafanalyz
Admin Register: http://localhost/trafanalyz/admin_register.php?key=trafanalyz
General Website (End-User): http://localhost/trafanalyz/
