ALTER TABLE users ADD COLUMN membership_type ENUM('basic', 'vvip') DEFAULT 'basic' AFTER status;
