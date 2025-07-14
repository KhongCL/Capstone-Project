# **TrafAnalyz: Complementary Web Analytics Dashboard**

TrafAnalyz is a user-friendly, web-based analytics dashboard designed to simplify web traffic analysis, particularly for Malaysian consumers. It serves as a complementary tool to existing analytics platforms like Google Analytics, addressing common challenges such as steep learning curves, limited flexibility, and low accessibility for non-technical users.

By providing an intuitive interface, comparative visualizations, annotation options, and simplified data import from GA4 CSV reports, TrafAnalyz empowers users to gain meaningful insights from their web traffic data without requiring specialized expertise.

---

## ✨ **Key Features**

TrafAnalyz offers role-specific functionalities for both end-users and administrators:

### **For End-Users:**

* **Secure Account Access:** Registration and secure login.
* **GA4 CSV Import:** Upload and validate CSV files generated from Google Analytics 4 reports.
* **Interactive Data Visualizations:** View key web traffic metrics (**Sessions**, **Users**, **Engagement Rate**, **Traffic Source Distribution**, etc.) through interactive trend charts, number widgets, and pie/bar charts.
* **Comparative Analysis:** Upload two CSV files for side-by-side metric comparison.
* **Annotation Tools:** Add, edit, and delete annotations on traffic trend charts for contextual insights.
* **Saved Configurations:** Save and load preferred analysis setups.
* **Data Export:** Export data tables and current dashboard views to CSV and basic PDF formats.
* **Sample Data:** Option to explore the dashboard with pre-loaded sample data.

### **For Administrators:**

* **CSV Format Management:** Define, update, and manage CSV data import formats and default mappings.
* **User Account Management:** View, suspend, restore, and delete registered user accounts.
* **Enhanced Reporting:** Export detailed PDF reports including timestamps and user information.

---

## 🚀 **Technologies Used**

* **Frontend:** HTML, CSS, JavaScript
* **Backend:** PHP
* **Database:** MySQL

---

## 💻 **Local Setup Instructions**

To run the TrafAnalyz web application locally, you will need to have **XAMPP** (or a similar local web server environment with PHP and MySQL) installed and running on your computer.

1.  **Extract Project Files:**
    * Extract the contents of the provided zip file (e.g., `AAPP011-4-2_GROUP ASSIGNMENT_CAPSTONE_PROJECT_The LOLcalhosts_KHONG CHEE LEONG_TP075846.zip`).
    * Inside, you will find a folder named `TrafAnalyz` and a database dump file named `trafanalyz.sql`.

2.  **Import Database:**
    * Start **Apache** and **MySQL** services from your XAMPP Control Panel.
    * Open your web browser and navigate to `http://localhost/phpmyadmin/`.
    * In phpMyAdmin, create a **new database** named `trafanalyz`.
    * Select the newly created `trafanalyz` database.
    * Go to the `Import` tab, choose the `trafanalyz.sql` file from your extracted project folder, and click `Go` to import the database schema and initial data.

3.  **Copy Website Folder:**
    * Copy the entire `TrafAnalyz` directory folder (from your extracted project files).
    * Paste this folder into your XAMPP web server's document root: `C:\xampp\htdocs` (or the equivalent directory if using a different OS or web server).

4.  **Access TrafAnalyz:**
    * After ensuring Apache and MySQL are running in XAMPP, you can access the TrafAnalyz web application using the following URLs in your browser:
        * **General Website (End-User):** `http://localhost/trafanalyz/`
        * **Admin Login:** `http://localhost/trafanalyz/admin_login.php?key=trafanalyz`
        * **Admin Register:** `http://localhost/trafanalyz/admin_register.php?key=trafanalyz`

---

## 📚 **Further Information**

For more detailed information regarding TrafAnalyz, including comprehensive design documents, functional and non-functional requirements, and testing procedures, please refer to the full project documentation.

---

## 👥 **Team**

Developed by **The LOLcalhosts** Capstone Project Team.

*Thank you for exploring TrafAnalyz! We hope it simplifies your web traffic analysis journey.*
