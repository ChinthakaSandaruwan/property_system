-- Create wishlist table for storing customer's saved properties
CREATE TABLE IF NOT EXISTS wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_customer_property (customer_id, property_id)
);

-- Add index for better performance
CREATE INDEX idx_customer_id ON wishlists(customer_id);
CREATE INDEX idx_property_id ON wishlists(property_id);
CREATE INDEX idx_created_at ON wishlists(created_at);