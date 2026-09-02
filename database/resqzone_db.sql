-- =========================================================
-- RESQZONE - Hazard Intelligence & Safe-Zone Analytics
-- Database: resqzone_db
-- DEMO / PROTOTYPE DATA ONLY - Not real government data
-- =========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `resqzone_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `resqzone_db`;

-- ---------------------------------------------------------
-- Table: users
-- ---------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','authority') NOT NULL DEFAULT 'authority',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Password for both demo accounts is: ResQzone@123
INSERT INTO `users` (`name`,`email`,`password`,`role`) VALUES
('System Administrator','admin@resqzone.gov.demo','$2b$10$kn4sT7xCgUyzarEPkOlKAOG2NsPR8cLfiQGDsKs4FVv9ylswJRvKW','admin'),
('District Disaster Authority','authority@resqzone.gov.demo','$2b$10$kn4sT7xCgUyzarEPkOlKAOG2NsPR8cLfiQGDsKs4FVv9ylswJRvKW','authority');

-- ---------------------------------------------------------
-- Table: habitations
-- ---------------------------------------------------------
CREATE TABLE `habitations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `district` VARCHAR(120) NOT NULL,
  `latitude` DECIMAL(10,6) NOT NULL,
  `longitude` DECIMAL(10,6) NOT NULL,
  `population` INT NOT NULL DEFAULT 0,
  `vulnerable_population` INT NOT NULL DEFAULT 0,
  `flood_risk` TINYINT NOT NULL DEFAULT 0,        -- 0-100
  `landslide_risk` TINYINT NOT NULL DEFAULT 0,     -- 0-100
  `cloudburst_risk` TINYINT NOT NULL DEFAULT 0,    -- 0-100
  `coastal_erosion_risk` TINYINT NOT NULL DEFAULT 0, -- 0-100
  `historical_events` INT NOT NULL DEFAULT 0,
  `infrastructure_risk` TINYINT NOT NULL DEFAULT 0, -- 0-100 (higher = weaker infra)
  `primary_hazard` VARCHAR(60) DEFAULT NULL,
  `risk_score` DECIMAL(5,2) DEFAULT NULL,
  `risk_level` VARCHAR(20) DEFAULT NULL,
  `priority_score` DECIMAL(5,2) DEFAULT NULL,
  `priority` VARCHAR(20) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_district (district),
  INDEX idx_risk_level (risk_level),
  INDEX idx_priority (priority)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: hazard_data (historical event log per habitation)
-- ---------------------------------------------------------
CREATE TABLE `hazard_data` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `habitation_id` INT NOT NULL,
  `hazard_type` VARCHAR(60) NOT NULL,
  `event_date` DATE DEFAULT NULL,
  `severity` TINYINT DEFAULT 0,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (habitation_id) REFERENCES habitations(id) ON DELETE CASCADE,
  INDEX idx_hab (habitation_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: relocation_sites
-- ---------------------------------------------------------
CREATE TABLE `relocation_sites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `district` VARCHAR(120) NOT NULL,
  `latitude` DECIMAL(10,6) NOT NULL,
  `longitude` DECIMAL(10,6) NOT NULL,
  `available_land_acres` DECIMAL(8,2) DEFAULT 0,
  `current_population` INT NOT NULL DEFAULT 0,
  `max_capacity` INT NOT NULL DEFAULT 0,
  `water_availability` TINYINT NOT NULL DEFAULT 0, -- 0-100
  `electricity` TINYINT NOT NULL DEFAULT 0,         -- 0-100
  `healthcare` TINYINT NOT NULL DEFAULT 0,          -- 0-100
  `schools` TINYINT NOT NULL DEFAULT 0,             -- 0-100
  `road_connectivity` TINYINT NOT NULL DEFAULT 0,   -- 0-100
  `hazard_risk` TINYINT NOT NULL DEFAULT 0,         -- 0-100 (lower=safer)
  `distance_from_red_zone_km` DECIMAL(6,2) DEFAULT 0,
  `suitability_score` DECIMAL(5,2) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_district_site (district)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: relocation_recommendations
-- ---------------------------------------------------------
CREATE TABLE `relocation_recommendations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `habitation_id` INT NOT NULL,
  `site_id` INT NOT NULL,
  `suitability_score` DECIMAL(5,2) DEFAULT NULL,
  `reason` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (habitation_id) REFERENCES habitations(id) ON DELETE CASCADE,
  FOREIGN KEY (site_id) REFERENCES relocation_sites(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: risk_assessments (historical snapshot log)
-- ---------------------------------------------------------
CREATE TABLE `risk_assessments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `habitation_id` INT NOT NULL,
  `hazard_score` DECIMAL(5,2),
  `vulnerability_score` DECIMAL(5,2),
  `exposure_score` DECIMAL(5,2),
  `historical_score` DECIMAL(5,2),
  `risk_score` DECIMAL(5,2),
  `risk_level` VARCHAR(20),
  `assessed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (habitation_id) REFERENCES habitations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: alerts
-- ---------------------------------------------------------
CREATE TABLE `alerts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` ENUM('CRITICAL','WARNING','INFO') NOT NULL DEFAULT 'INFO',
  `title` VARCHAR(200) NOT NULL,
  `message` VARCHAR(500) NOT NULL,
  `related_habitation_id` INT DEFAULT NULL,
  `related_site_id` INT DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (related_habitation_id) REFERENCES habitations(id) ON DELETE SET NULL,
  FOREIGN KEY (related_site_id) REFERENCES relocation_sites(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: system_logs
-- ---------------------------------------------------------
CREATE TABLE `system_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(255) NOT NULL,
  `details` VARCHAR(500) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: risk_config (configurable weights for admin)
-- ---------------------------------------------------------
CREATE TABLE `risk_config` (
  `config_key` VARCHAR(60) PRIMARY KEY,
  `config_value` DECIMAL(6,3) NOT NULL,
  `description` VARCHAR(255)
) ENGINE=InnoDB;

INSERT INTO `risk_config` (`config_key`,`config_value`,`description`) VALUES
('weight_hazard', 0.40, 'Weight of hazard risk in overall risk score'),
('weight_vulnerability', 0.25, 'Weight of population vulnerability'),
('weight_exposure', 0.20, 'Weight of exposure'),
('weight_historical', 0.15, 'Weight of historical disaster impact'),
('priority_weight_risk', 0.50, 'Weight of overall risk in priority score'),
('priority_weight_vulnerability', 0.25, 'Weight of vulnerability in priority score'),
('priority_weight_exposure', 0.15, 'Weight of population exposure in priority score'),
('priority_weight_historical', 0.10, 'Weight of historical impact in priority score');

-- =========================================================
-- SAMPLE DEMO DATA - 15 Habitations (fictional Indian-style names)
-- =========================================================
INSERT INTO `habitations`
(`name`,`district`,`latitude`,`longitude`,`population`,`vulnerable_population`,`flood_risk`,`landslide_risk`,`cloudburst_risk`,`coastal_erosion_risk`,`historical_events`,`infrastructure_risk`,`primary_hazard`)
VALUES
('Shivapur','Raigad',18.2345,73.1234,4820,1420,88,20,40,5,6,62,'Flood'),
('Konkangaon','Ratnagiri',16.9944,73.3120,3150,980,70,55,30,60,4,58,'Coastal Erosion'),
('Girijanpada','Pune',18.6120,73.7550,2210,610,25,82,45,0,5,50,'Landslide'),
('Naukund','Rudraprayag',30.5200,78.9800,1580,720,30,90,60,0,7,70,'Landslide'),
('Meghdoot Basti','Cherrapunji',25.2993,91.7362,2680,860,55,60,92,0,8,55,'Cloudburst'),
('Sundarpalli','Kendrapara',20.5000,86.4200,5230,2150,90,10,35,72,9,66,'Flood'),
('Vasanttola','Malda',25.0100,88.1400,3890,1340,80,15,25,10,5,48,'Flood'),
('Chandanwadi','Sindhudurg',16.0100,73.4500,1920,540,40,48,20,68,3,42,'Coastal Erosion'),
('Bhilaipada','Nagpur',21.1300,79.0900,2440,610,20,12,18,0,1,30,'Cloudburst'),
('Tistagram','Darjeeling',26.9000,88.3600,3010,1120,45,78,55,0,6,60,'Landslide'),
('Kolawadi','Thane',19.2500,73.1300,4260,1580,75,30,42,0,5,54,'Flood'),
('Uttarpara Colony','Howrah',22.5900,88.3100,6120,2380,84,8,28,15,7,64,'Flood'),
('Ramnagar Basti','Nainital',29.3900,79.4500,1740,510,22,74,38,0,4,45,'Landslide'),
('Machhligaon','Puri',19.8100,85.8300,2870,940,60,5,30,80,6,52,'Coastal Erosion'),
('Devipada','Wayanad',11.6850,76.1320,1360,480,28,88,44,0,5,58,'Landslide');

-- Historical hazard event logs
INSERT INTO `hazard_data` (`habitation_id`,`hazard_type`,`event_date`,`severity`,`description`) VALUES
(1,'Flood','2023-07-14',80,'Monsoon river overflow, embankment breach'),
(1,'Flood','2022-08-02',70,'Flash flood after 3-day rainfall'),
(2,'Coastal Erosion','2023-06-10',65,'Shoreline retreat of 12m'),
(3,'Landslide','2023-07-22',75,'Slope failure after heavy rain'),
(4,'Landslide','2021-02-07',90,'Major landslide, road cut off'),
(5,'Cloudburst','2023-06-16',95,'Extreme rainfall in 2 hours'),
(6,'Flood','2023-09-01',88,'River breach, mass evacuation'),
(6,'Flood','2020-08-20',60,'Seasonal flooding'),
(7,'Flood','2022-07-30',55,'Waterlogging, crop damage'),
(8,'Coastal Erosion','2023-05-18',50,'Coastal land loss'),
(10,'Landslide','2023-08-05',68,'Debris flow near settlement'),
(12,'Flood','2023-08-22',82,'Urban flooding, drainage failure'),
(14,'Coastal Erosion','2023-04-12',72,'Fishing hamlet erosion damage'),
(15,'Landslide','2022-06-29',80,'Hillside collapse near school');

-- =========================================================
-- SAMPLE DEMO DATA - 10 Relocation Sites
-- =========================================================
INSERT INTO `relocation_sites`
(`name`,`district`,`latitude`,`longitude`,`available_land_acres`,`current_population`,`max_capacity`,`water_availability`,`electricity`,`healthcare`,`schools`,`road_connectivity`,`hazard_risk`,`distance_from_red_zone_km`)
VALUES
('New Shivapur Nagar','Raigad',18.3100,73.0500,120.5,1200,5000,85,90,75,70,88,12,'88'),
('Ratnagiri Rehab Township','Ratnagiri',17.0500,73.2200,95.0,900,3800,78,82,65,60,80,10.5,'80'),
('Sahyadri Safe Colony','Pune',18.5600,73.6200,140.0,2100,6000,90,88,80,85,92,5,'92'),
('Uttarakhand Model Village','Rudraprayag',30.4600,78.8600,60.0,300,2200,70,60,55,50,60,18,'60'),
('Meghalaya Highland Settlement','Cherrapunji',25.2500,91.6500,80.0,700,3000,88,70,60,55,68,22,'68'),
('Kendrapara New Basti','Kendrapara',20.4200,86.3000,110.0,1500,5500,80,85,70,65,75,15,'75'),
('Malda Riverside Rehab','Malda',24.9500,88.0500,70.0,850,3200,75,78,58,60,70,20,'70'),
('Sindhudurg Coastal Shift','Sindhudurg',15.9500,73.3800,55.0,400,2100,72,65,50,45,72,25,'72'),
('Darjeeling Hillside Haven','Darjeeling',26.8200,88.2800,65.0,600,2600,68,72,55,50,65,28,'65'),
('Thane Green Enclave','Thane',19.3100,72.9800,150.0,2500,7000,92,90,85,80,90,3,'90');

-- =========================================================
-- SAMPLE DEMO DATA - Alerts
-- =========================================================
INSERT INTO `alerts` (`type`,`title`,`message`,`related_habitation_id`,`related_site_id`) VALUES
('CRITICAL','Risk score spike detected','Risk score for Sundarpalli increased from 81 to 91 following recent flood events.',6,NULL),
('CRITICAL','Immediate assessment required','Uttarpara Colony has reached a Risk Score above 85 and requires immediate field assessment.',12,NULL),
('WARNING','Relocation site nearing capacity','Sahyadri Safe Colony is approaching 80% of its maximum capacity.',NULL,3),
('WARNING','Coastal erosion accelerating','Machhligaon shoreline retreat rate has doubled in the last quarter.',14,NULL),
('INFO','New hazard dataset uploaded','Monsoon-season hazard layer for FY2026 has been added to the GIS map.',NULL,NULL),
('INFO','Relocation recommendation generated','Smart recommendation engine matched Naukund to Uttarakhand Model Village.',4,4);

-- =========================================================
-- Views used by risk-engine after PHP-side calculation
-- (risk_score / risk_level / priority / suitability_score are
--  computed and written back by includes/risk-engine.php on
--  first run via recalculate-all, so the app works immediately
--  even before recalculation using approximate seed values below)
-- =========================================================
UPDATE habitations SET risk_score = 91.4, risk_level='CRITICAL', priority_score=92.1, priority='IMMEDIATE' WHERE id=1;
UPDATE habitations SET risk_score = 78.2, risk_level='HIGH', priority_score=74.0, priority='SHORT-TERM' WHERE id=2;
UPDATE habitations SET risk_score = 68.5, risk_level='MODERATE', priority_score=61.2, priority='MEDIUM-TERM' WHERE id=3;
UPDATE habitations SET risk_score = 83.6, risk_level='HIGH', priority_score=79.4, priority='SHORT-TERM' WHERE id=4;
UPDATE habitations SET risk_score = 79.9, risk_level='HIGH', priority_score=75.8, priority='SHORT-TERM' WHERE id=5;
UPDATE habitations SET risk_score = 92.7, risk_level='CRITICAL', priority_score=94.0, priority='IMMEDIATE' WHERE id=6;
UPDATE habitations SET risk_score = 66.3, risk_level='MODERATE', priority_score=58.7, priority='MEDIUM-TERM' WHERE id=7;
UPDATE habitations SET risk_score = 60.1, risk_level='MODERATE', priority_score=52.0, priority='MEDIUM-TERM' WHERE id=8;
UPDATE habitations SET risk_score = 35.4, risk_level='LOW', priority_score=30.2, priority='MONITOR' WHERE id=9;
UPDATE habitations SET risk_score = 74.8, risk_level='HIGH', priority_score=68.9, priority='SHORT-TERM' WHERE id=10;
UPDATE habitations SET risk_score = 71.2, risk_level='HIGH', priority_score=65.4, priority='SHORT-TERM' WHERE id=11;
UPDATE habitations SET risk_score = 88.9, risk_level='CRITICAL', priority_score=86.6, priority='IMMEDIATE' WHERE id=12;
UPDATE habitations SET risk_score = 63.0, risk_level='MODERATE', priority_score=55.1, priority='MEDIUM-TERM' WHERE id=13;
UPDATE habitations SET risk_score = 76.4, risk_level='HIGH', priority_score=70.3, priority='SHORT-TERM' WHERE id=14;
UPDATE habitations SET risk_score = 69.7, risk_level='MODERATE', priority_score=60.5, priority='MEDIUM-TERM' WHERE id=15;

UPDATE relocation_sites SET suitability_score = 91.2 WHERE id=1;
UPDATE relocation_sites SET suitability_score = 82.4 WHERE id=2;
UPDATE relocation_sites SET suitability_score = 94.6 WHERE id=3;
UPDATE relocation_sites SET suitability_score = 68.9 WHERE id=4;
UPDATE relocation_sites SET suitability_score = 74.1 WHERE id=5;
UPDATE relocation_sites SET suitability_score = 79.8 WHERE id=6;
UPDATE relocation_sites SET suitability_score = 71.5 WHERE id=7;
UPDATE relocation_sites SET suitability_score = 65.3 WHERE id=8;
UPDATE relocation_sites SET suitability_score = 70.2 WHERE id=9;
UPDATE relocation_sites SET suitability_score = 96.0 WHERE id=10;

INSERT INTO `relocation_recommendations` (`habitation_id`,`site_id`,`suitability_score`,`reason`) VALUES
(1,1,91.2,'Low hazard exposure, strong road connectivity, and adequate carrying capacity in the same district.'),
(6,10,96.0,'Highest suitability in region: excellent infrastructure, healthcare and very low hazard risk.'),
(12,10,96.0,'Nearby high-capacity site with strong utilities and minimal flood exposure.'),
(4,4,68.9,'Only viable site within the district with acceptable road access despite moderate hazard risk.');
