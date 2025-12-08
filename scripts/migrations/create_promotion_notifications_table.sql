-- Create PromotionNotifications table
CREATE TABLE IF NOT EXISTS PromotionNotifications (
    PromotionID INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(255) NOT NULL,
    Message TEXT NOT NULL,
    ImageURL VARCHAR(500),
    CTAButtonText VARCHAR(100) DEFAULT 'Book Now',
    DiscountCode VARCHAR(50),
    ExpirationDate DATE,
    CreatedBy INT NOT NULL,
    Status ENUM('Draft', 'Active', 'Sent', 'Expired') DEFAULT 'Draft',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (CreatedBy) REFERENCES Users(UserID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create PromotionSentLog table to track who received the promotion
CREATE TABLE IF NOT EXISTS PromotionSentLog (
    LogID INT AUTO_INCREMENT PRIMARY KEY,
    PromotionID INT NOT NULL,
    CustomerID INT NOT NULL,
    SentAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (PromotionID) REFERENCES PromotionNotifications(PromotionID) ON DELETE CASCADE,
    FOREIGN KEY (CustomerID) REFERENCES Users(UserID) ON DELETE CASCADE,
    INDEX idx_promotion_customer (PromotionID, CustomerID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
