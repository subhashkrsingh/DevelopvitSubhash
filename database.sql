CREATE DATABASE IF NOT EXISTS `clinic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `clinic`;

CREATE TABLE IF NOT EXISTS `medical_examinations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `clims_id` VARCHAR(100) UNIQUE NOT NULL,
  `serial_no` VARCHAR(100),
  `attested_by_eic` VARCHAR(255),
  `exam_date` DATE,
  `full_name` VARCHAR(255),
  `age_sex` VARCHAR(50),
  `aadhar_no` VARCHAR(50),
  `address` TEXT,
  `mobile_no` VARCHAR(20),
  `demo_exam_date` DATE,
  `contractor_agency` VARCHAR(255),
  `ntpc_eic` VARCHAR(255),

  `diabetes` VARCHAR(10),
  `hypertension` VARCHAR(10),
  `vertigo` VARCHAR(10),
  `epilepsy` VARCHAR(10),
  `height_phobia` VARCHAR(10),
  `skin_diseases` VARCHAR(10),
  `asthma` VARCHAR(10),
  `alcohol_intake` VARCHAR(10),
  `mental_illness` VARCHAR(10),
  `tobacco_chewing` VARCHAR(10),
  `cancer` VARCHAR(10),
  `piles` VARCHAR(10),
  `hearing_problem` VARCHAR(10),
  `chronic_illness` VARCHAR(10),
  `deformity` VARCHAR(10),
  `past_accident` VARCHAR(10),
  `smoking` VARCHAR(10),
  `medicine_history` TEXT,
  `contractor_signature` VARCHAR(255),

  `height` DECIMAL(10,2),
  `weight` DECIMAL(10,2),
  `bp` VARCHAR(50),
  `bmi` VARCHAR(20),
  `chest_insp` VARCHAR(50),
  `chest_exp` VARCHAR(50),
  `pulse_spo2_temp` VARCHAR(100),
  `pallor` VARCHAR(20),
  `icterus` VARCHAR(20),
  `clubbing` VARCHAR(20),
  `built` VARCHAR(20),
  `tongue` VARCHAR(100),
  `teeth` VARCHAR(100),
  `other_finding` TEXT,

  `cardio_system` TEXT,
  `respiratory_system` TEXT,
  `cns` TEXT,
  `system_other` TEXT,

  `distant_r_with` VARCHAR(20),
  `distant_r_without` VARCHAR(20),
  `distant_l_with` VARCHAR(20),
  `distant_l_without` VARCHAR(20),
  `near_r_with` VARCHAR(20),
  `near_r_without` VARCHAR(20),
  `near_l_with` VARCHAR(20),
  `near_l_without` VARCHAR(20),
  `colour_vision` VARCHAR(100),
  `eye_disorder` TEXT,

  `lmp` VARCHAR(50),
  `menstrual_cycle` VARCHAR(50),
  `pregnancy_duration` VARCHAR(50),

  `cbc` VARCHAR(255),
  `random_blood_sugar` VARCHAR(50),
  `urine_rm` VARCHAR(255),
  `blood_group` VARCHAR(10),
  `lft_kft` VARCHAR(255),
  `ecg` VARCHAR(20),
  `chest_xray` VARCHAR(20),
  `height_pass_test` VARCHAR(20),
  `other_tests` TEXT,

  `opinion` VARCHAR(100),
  `remarks` TEXT,
  `worker_signature` VARCHAR(255),
  `doctor_signature` VARCHAR(255),
  `worker_photo` VARCHAR(500),

  `record_status` ENUM('draft', 'partial', 'completed', 'submitted') DEFAULT 'draft',
  `current_container` INT DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `form26` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `examination_id` INT NOT NULL,
  `serial_number` VARCHAR(100),
  `patient_name` VARCHAR(255),
  `father_name` VARCHAR(255),
  `address` TEXT,
  `employment_details` VARCHAR(255),
  `designation` VARCHAR(255),
  `age` INT,
  `fitness_status` VARCHAR(50),
  `further_exam_period` VARCHAR(255),
  `previous_certificate_no` VARCHAR(100),
  `surgeon_signature` VARCHAR(255),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_form26_exam` (`examination_id`),
  CONSTRAINT `fk_form26_exam` FOREIGN KEY (`examination_id`) REFERENCES `medical_examinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `form27` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `examination_id` INT NOT NULL,
  `serial_number` VARCHAR(100),
  `department` VARCHAR(255),
  `name` VARCHAR(255),
  `sex` VARCHAR(20),
  `age` INT,
  `start_date` DATE,
  `leave_transfer` TEXT,
  `occupation` VARCHAR(255),
  `raw_materials` TEXT,
  `exam_date` DATE,
  `result` VARCHAR(50),
  `signs_symptoms` TEXT,
  `tests_result` TEXT,
  `suspension_details` TEXT,
  `certificate_issued` VARCHAR(255),
  `recertified_date` DATE,
  `surgeon_signature` VARCHAR(255),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_form27_exam` (`examination_id`),
  CONSTRAINT `fk_form27_exam` FOREIGN KEY (`examination_id`) REFERENCES `medical_examinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO `medical_examinations` (
  `clims_id`, `full_name`, `age_sex`, `address`, `mobile_no`, `record_status`, `current_container`
) VALUES (
  'CLIMS-NTPC-2026-000', 'Test User', '30/M', 'Test Address, NTPC Dadri', '9999999999', 'draft', 1
)
ON DUPLICATE KEY UPDATE
  `updated_at` = NOW();
