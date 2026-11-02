-- Run this file once in phpMyAdmin on the sharebite database.
ALTER TABLE donations
    ADD COLUMN latitude DECIMAL(10,7) NULL AFTER location,
    ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude;

ALTER TABLE charity_centers
    ADD COLUMN latitude DECIMAL(10,7) NULL AFTER address,
    ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude;
