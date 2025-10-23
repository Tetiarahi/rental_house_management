# Tenant Contract Upload Feature

## Overview

This feature allows you to upload and manage PDF contracts for tenants in the House Rental Management System.

## New Features Added

### 1. Database Changes

- Added `contract_file` column to the `tenants` table
- Column stores the filename of uploaded PDF contracts

### 2. File Upload Functionality

- **File Type**: Only PDF files are accepted
- **File Size Limit**: Maximum 20MB per file
- **Storage Location**: `assets/uploads/contracts/`
- **Naming Convention**: `contract_[timestamp]_[unique_id].pdf`

### 3. User Interface Updates

#### Tenant Management Form (`manage_tenant.php`)

- Added file upload field for contract PDF
- Real-time file validation (type and size)
- Preview of selected file before upload
- Display current contract file when editing existing tenant

#### Tenant Listing (`tenants.php`)

- Added "Contract" column to tenant table
- Shows "View Contract" button for tenants with uploaded contracts
- Shows "No Contract" badge for tenants without contracts
- Direct link to view/download contract PDFs

### 4. Error Handling

The system now provides specific error messages for:

- Invalid file type (non-PDF files)
- File size exceeding 20MB limit
- File upload failures
- House already occupied
- Invalid email format

## Installation Instructions

### For New Installations

1. Use the updated `database/house_rental_db.sql` file which includes the new `contract_file` column

### For Existing Installations

1. Run the migration script: `database/add_contract_field.sql`
2. Ensure the `assets/uploads/contracts/` directory exists and is writable

## Usage Instructions

### Adding a Contract to a New Tenant

1. Go to Tenants page
2. Click "New Tenant"
3. Fill in tenant details
4. In the "Contract PDF" field, select a PDF file (max 20MB)
5. Click Save

### Adding/Updating a Contract for Existing Tenant

1. Go to Tenants page
2. Click "Edit" for the desired tenant
3. In the "Contract PDF" field, select a new PDF file
4. Click Save (this will replace the old contract file)

### Viewing a Contract

1. Go to Tenants page
2. In the "Contract" column, click "View Contract" button
3. The PDF will open in a new tab/window

## File Management

- When a tenant's contract is updated, the old file is automatically deleted
- When a tenant is deleted, their contract file should be manually removed from the server
- Contract files are stored with unique names to prevent conflicts

## Security Considerations

- Only PDF files are accepted
- File size is limited to 20MB
- Files are stored outside the web root for security
- Direct access to files requires going through the application

## Technical Details

- File validation is performed both client-side (JavaScript) and server-side (PHP)
- Unique filenames prevent conflicts and overwriting
- Error codes 5, 6, and 7 are reserved for file upload related errors
