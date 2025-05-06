-- Drop existing tables if needed (in reverse order of dependencies)
DROP TABLE IF EXISTS EXPORT_HISTORY;
DROP TABLE IF EXISTS COMPARISON_FILE_LINK;
DROP TABLE IF EXISTS SAVED_COMPARISON;
DROP TABLE IF EXISTS ANNOTATION;
DROP TABLE IF EXISTS PROCESSED_DATA_POINT;
DROP TABLE IF EXISTS COLUMN_MAPPING;
DROP TABLE IF EXISTS CSV_UPLOAD;
DROP TABLE IF EXISTS CSV_FORMAT;
DROP TABLE IF EXISTS SOURCE_TYPE;
DROP TABLE IF EXISTS METRIC_TYPE;
DROP TABLE IF EXISTS USER;

-- Create USER table
CREATE TABLE USER (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    FullName VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role ENUM('Admin', 'End-User') NOT NULL DEFAULT 'End-User',
    AccountStatus ENUM('Active', 'Suspended') NOT NULL DEFAULT 'Active',
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create METRIC_TYPE table
CREATE TABLE METRIC_TYPE (
    MetricTypeID INT AUTO_INCREMENT PRIMARY KEY,
    MetricName VARCHAR(255) NOT NULL UNIQUE,
    Description VARCHAR(500)
);

-- Create SOURCE_TYPE table
CREATE TABLE SOURCE_TYPE (
    SourceTypeID INT AUTO_INCREMENT PRIMARY KEY,
    SourceName VARCHAR(255) NOT NULL UNIQUE
);

-- Create CSV_FORMAT table
CREATE TABLE CSV_FORMAT (
    FormatID INT AUTO_INCREMENT PRIMARY KEY,
    AdminUserID INT NOT NULL,
    FormatName VARCHAR(255) NOT NULL UNIQUE,
    ReportType VARCHAR(255) NOT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    LastModifiedDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (AdminUserID) REFERENCES USER(UserID)
);

-- Create CSV_UPLOAD table
CREATE TABLE CSV_UPLOAD (
    UploadID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    FileName VARCHAR(255) NOT NULL,
    UploadDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FileSize INT NOT NULL,
    IsValidated BOOLEAN NOT NULL DEFAULT FALSE,
    ReportType VARCHAR(255) NOT NULL,
    DataDateStart DATE NOT NULL,
    DataDateEnd DATE NOT NULL,
    AccountName VARCHAR(255),
    PropertyName VARCHAR(255),
    IsSampleData BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (UserID) REFERENCES USER(UserID)
);

-- Create COLUMN_MAPPING table
CREATE TABLE COLUMN_MAPPING (
    MappingID INT AUTO_INCREMENT PRIMARY KEY,
    FormatID INT NOT NULL,
    CSVColumnName VARCHAR(255) NOT NULL,
    SystemFieldName VARCHAR(255) NOT NULL,
    FOREIGN KEY (FormatID) REFERENCES CSV_FORMAT(FormatID),
    UNIQUE KEY (FormatID, CSVColumnName)
);

-- Create PROCESSED_DATA_POINT table
CREATE TABLE PROCESSED_DATA_POINT (
    DataPointID INT AUTO_INCREMENT PRIMARY KEY,
    UploadID INT NOT NULL,
    SourceTypeID INT NOT NULL,
    MetricTypeID INT NOT NULL,
    DataDate DATE NOT NULL,
    Value DECIMAL(18,4) NOT NULL,
    PeriodType ENUM('Daily', 'Weekly', 'Monthly') DEFAULT 'Daily',
    FOREIGN KEY (UploadID) REFERENCES CSV_UPLOAD(UploadID),
    FOREIGN KEY (SourceTypeID) REFERENCES SOURCE_TYPE(SourceTypeID),
    FOREIGN KEY (MetricTypeID) REFERENCES METRIC_TYPE(MetricTypeID),
    INDEX (DataDate),
    INDEX (UploadID, SourceTypeID, MetricTypeID)
);

-- Create ANNOTATION table
CREATE TABLE ANNOTATION (
    AnnotationID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    UploadID INT NOT NULL,
    DataDate DATE NOT NULL,
    AnnotationText TEXT NOT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES USER(UserID),
    FOREIGN KEY (UploadID) REFERENCES CSV_UPLOAD(UploadID),
    INDEX (DataDate)
);

-- Create SAVED_COMPARISON table
CREATE TABLE SAVED_COMPARISON (
    ComparisonID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    ComparisonName VARCHAR(255) NOT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES USER(UserID)
);

-- Create COMPARISON_FILE_LINK table
CREATE TABLE COMPARISON_FILE_LINK (
    ComparisonFileLinkID INT AUTO_INCREMENT PRIMARY KEY,
    ComparisonID INT NOT NULL,
    UploadID INT NOT NULL,
    FileOrder INT NOT NULL CHECK (FileOrder IN (1, 2)),
    FOREIGN KEY (ComparisonID) REFERENCES SAVED_COMPARISON(ComparisonID),
    FOREIGN KEY (UploadID) REFERENCES CSV_UPLOAD(UploadID),
    UNIQUE KEY (ComparisonID, FileOrder)
);

-- Create EXPORT_HISTORY table
CREATE TABLE EXPORT_HISTORY (
    ExportID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    ExportTimestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ExportType VARCHAR(50) NOT NULL,
    ExportedDataDescription TEXT,
    FOREIGN KEY (UserID) REFERENCES USER(UserID)
);

-- Insert initial data for admin user
INSERT INTO USER (FullName, Email, PasswordHash, Role, AccountStatus)
VALUES ('System Admin', 'admin@trafanalyz.com', '$2y$10$YourHashedPasswordHere', 'Admin', 'Active');

-- Insert initial metric types
INSERT INTO METRIC_TYPE (MetricName, Description) VALUES
('Sessions', 'Number of sessions/visits'),
('Engaged sessions', 'Number of engaged sessions'),
('Engagement rate', 'Percentage of engaged sessions'),
('Average engagement time per session', 'Average time in seconds of engagement per session'),
('Events per session', 'Average number of events per session'),
('Event count', 'Total number of events'),
('Key events', 'Number of key events'),
('Session key event rate', 'Rate of sessions with key events'),
('Total revenue', 'Total revenue generated');

-- Insert initial source types
INSERT INTO SOURCE_TYPE (SourceName) VALUES
('Direct'),
('Organic Search'),
('Paid Search'),
('Social'),
('Email'),
('Referral'),
('Unassigned');

-- Insert initial CSV format for GA4 Traffic Acquisition
INSERT INTO CSV_FORMAT (AdminUserID, FormatName, ReportType)
VALUES (1, 'GA4 Traffic Acquisition', 'Session primary channel group (Default channel group)');

-- Insert column mappings for GA4 Traffic Acquisition
INSERT INTO COLUMN_MAPPING (FormatID, CSVColumnName, SystemFieldName) VALUES
(1, 'Session primary channel group (Default channel group)', 'traffic_source'),
(1, 'Sessions', 'visits'),
(1, 'Engaged sessions', 'engaged_sessions'),
(1, 'Engagement rate', 'bounce_rate'),
(1, 'Average engagement time per session', 'avg_session_duration'),
(1, 'Events per session', 'events_per_session'),
(1, 'Event count', 'event_count'),
(1, 'Key events', 'key_events'),
(1, 'Session key event rate', 'session_key_event_rate'),
(1, 'Total revenue', 'total_revenue');