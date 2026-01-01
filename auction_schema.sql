-- Add auction fields to products table
ALTER TABLE products ADD COLUMN is_auction BOOLEAN DEFAULT FALSE;
ALTER TABLE products ADD COLUMN starting_bid DECIMAL(10, 2) DEFAULT 0.00;
ALTER TABLE products ADD COLUMN current_bid DECIMAL(10, 2) DEFAULT 0.00;
ALTER TABLE products ADD COLUMN auction_end TIMESTAMP NULL;
ALTER TABLE products ADD COLUMN location_lat DECIMAL(10, 8) NULL;
ALTER TABLE products ADD COLUMN location_lng DECIMAL(11, 8) NULL;
ALTER TABLE products ADD COLUMN video_url VARCHAR(255) NULL;

-- Create Bids table
CREATE TABLE IF NOT EXISTS bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    buyer_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create Product Media table for multiple images/videos
CREATE TABLE IF NOT EXISTS product_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Add phone number to users for direct communication
ALTER TABLE users ADD COLUMN phone_number VARCHAR(20) NULL;
ALTER TABLE users ADD COLUMN address TEXT NULL;
