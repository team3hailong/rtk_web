-- Migration: Add Cloudinary support to transaction_history table
-- Date: 2025-08-01
-- Description: Add payment_image_public_id column to store Cloudinary public_id

-- Add payment_image_public_id column to transaction_history table
ALTER TABLE transaction_history 
ADD COLUMN payment_image_public_id VARCHAR(255) NULL 
COMMENT 'Cloudinary public_id for payment proof image' 
AFTER payment_image;

-- Create index for better performance when searching by public_id
CREATE INDEX idx_transaction_history_public_id ON transaction_history(payment_image_public_id);

-- Update existing records if needed (optional - for migration from local storage)
-- UPDATE transaction_history 
-- SET payment_image_public_id = NULL 
-- WHERE payment_image IS NOT NULL AND payment_image_public_id IS NULL;
