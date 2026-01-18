CREATE DATABASE IF NOT EXISTS account_manager_db;
USE account_manager_db;

CREATE TABLE profiles (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  mobile varchar(15) NOT NULL,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE transactions (
  id int(11) NOT NULL AUTO_INCREMENT,
  profile_id int(11) NOT NULL,
  type enum('credit','debit') NOT NULL,
  amount decimal(10,2) NOT NULL,
  payment_method varchar(50) NOT NULL,
  note text DEFAULT NULL,
  transaction_date date NOT NULL,
  created_at datetime DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY profile_id (profile_id),
  CONSTRAINT fk_profile_transaction FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

