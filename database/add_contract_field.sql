-- Migration script to add contract_file field to tenants table
-- Run this script if you already have an existing database

ALTER TABLE `tenants` ADD COLUMN `contract_file` varchar(255) DEFAULT NULL COMMENT 'Path to uploaded contract PDF';
