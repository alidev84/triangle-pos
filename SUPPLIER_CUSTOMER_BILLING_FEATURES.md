# Supplier Billing and Customer Wholesale Features

## Overview
This document describes the new Supplier Billing and Customer Wholesale functionality added to the Triangle POS system.

## Features Added

### 1. Customer Type Classification
- Added `customer_type` field to customers table
- Customers can be classified as either "Retail" or "Wholesale" buyers
- Updated customer create/edit forms to include customer type selection
- Updated customer listing to display customer type with badges

### 2. Supplier Billing Report
**Location**: Reports > Supplier Billing Report

**Features**:
- Select any supplier from dropdown
- View all purchase bills from selected supplier within date range
- Filter by payment status (Paid/Unpaid/Partial)
- Display for each bill:
  - Bill Reference #
  - Bill Date
  - Total Amount
  - Payment Status
- Summary section showing:
  - Total Purchase Amount
  - Total Paid
  - Total Remaining (Credit due)
- Record payments directly from the report
- Payment history tracking

### 3. Customer Wholesale Report
**Location**: Reports > Customer Wholesale Report

**Features**:
- Filter customers by type (Retail/Wholesale)
- Select any customer from filtered list
- View all sales invoices from selected customer within date range
- Filter by payment status (Paid/Unpaid/Partial)
- Display for each invoice:
  - Bill Reference #
  - Bill Date
  - Total Amount
  - Payment Status
- Summary section showing:
  - Total Sales Amount
  - Total Paid
  - Total Remaining (Credit due)
- Record payments directly from the report
- Payment history tracking

### 4. Payment Recording
**Features**:
- Record payments for both suppliers and customers
- Payment details include:
  - Payment amount
  - Payment date
  - Payment method (Cash, Bank Transfer, Check, Credit Card)
  - Optional notes
- Payments are linked to specific bills/invoices
- Automatic balance calculation updates

## Database Changes

### New Tables
1. `supplier_payments` - Tracks payments made to suppliers
2. `customer_payments` - Tracks payments received from customers

### Modified Tables
1. `customers` - Added `customer_type` field (enum: 'retail', 'wholesale')

## Usage Instructions

### For Supplier Billing:
1. Navigate to Reports > Supplier Billing Report
2. Select date range and supplier
3. Click "Filter Report" to view supplier's purchase history
4. Use "Record Payment" button to add new payments
5. View summary totals at the bottom

### For Customer Wholesale:
1. Navigate to Reports > Customer Wholesale Report
2. Select customer type (Retail/Wholesale)
3. Select date range and customer
4. Click "Filter Report" to view customer's sales history
5. Use "Record Payment" button to add new payments
6. View summary totals at the bottom

### For Customer Management:
1. When creating/editing customers, select appropriate customer type
2. Customer type is displayed in the customer listing with color-coded badges
3. Wholesale customers appear in the Customer Wholesale Report
4. Retail customers can also be viewed in the same report by changing the filter

## Technical Implementation

### Models
- `Customer` - Enhanced with sales relationship and payment tracking
- `Supplier` - Enhanced with purchases relationship and payment tracking
- `SupplierPayment` - New model for supplier payment tracking
- `CustomerPayment` - New model for customer payment tracking

### Livewire Components
- `SupplierBillingReport` - Handles supplier billing report logic
- `CustomerWholesaleReport` - Handles customer wholesale report logic

### Views
- Supplier billing report views in `Modules/Reports/Resources/views/supplier-billing/`
- Customer wholesale report views in `Modules/Reports/Resources/views/customer-wholesale/`
- Livewire component views in `resources/views/livewire/reports/`

## Permissions
The new reports use the existing `access_reports` permission, so users with report access can use these features.

## Notes
- All monetary values are stored in cents (multiplied by 100) for precision
- Payment history is maintained for audit purposes
- Reports are filterable by date range for better analysis
- UI is consistent with existing POS system design

