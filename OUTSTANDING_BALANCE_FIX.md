# Outstanding Balance Calculation Fix

## Problem Description
The outstanding balance calculation was incorrect due to improper month calculation. The system was using a simple day-based calculation that divided total days by 30, which doesn't account for varying month lengths and proper month boundaries.

## Previous (Incorrect) Calculation
```php
$months = abs(strtotime(date('Y-m-d')." 23:59:59") - strtotime($date_in." 23:59:59"));
$months = floor(($months) / (30*60*60*24));
$payable = $price * $months;
```

**Issues with this approach:**
- Assumes all months have exactly 30 days
- Doesn't account for leap years
- Doesn't properly handle month boundaries
- Can result in incorrect month counts

## New (Correct) Calculation
```php
// Calculate months from registration date to current date
$start_date = new DateTime($date_in);
$current_date = new DateTime(date('Y-m-d'));
$interval = $start_date->diff($current_date);
$months = ($interval->y * 12) + $interval->m;

// If we're past the day of the month when they registered, add 1 more month
if ($current_date->format('d') >= $start_date->format('d')) {
    $months += 1;
}

$payable = $price * $months;
```

**Benefits of this approach:**
- Uses PHP's DateTime class for accurate date calculations
- Properly handles varying month lengths
- Accounts for leap years automatically
- Correctly calculates month boundaries
- More accurate outstanding balance calculations

## How Outstanding Balance Works

### Formula:
**Outstanding Balance = Total Payable - Total Paid**

Where:
- **Total Payable** = Monthly Rate × Number of Months Owed
- **Total Paid** = Sum of all payments made through the Payments module
- **Number of Months Owed** = Months from registration date to current date

### Example Scenarios:

#### Scenario 1: New Tenant
- Registration Date: October 1, 2024
- Monthly Rate: ₱2,000
- Current Date: October 15, 2024
- Months Owed: 1 month (partial month counts as full month)
- Total Payable: ₱2,000 × 1 = ₱2,000
- Total Paid: ₱0 (no payments yet)
- **Outstanding Balance: ₱2,000**

#### Scenario 2: Tenant with Payments
- Registration Date: January 15, 2024
- Monthly Rate: ₱1,500
- Current Date: October 15, 2024
- Months Owed: 9 months (Jan to Sep = 9 months)
- Total Payable: ₱1,500 × 9 = ₱13,500
- Total Paid: ₱7,500 (5 months paid)
- **Outstanding Balance: ₱6,000**

#### Scenario 3: Fully Paid Tenant
- Registration Date: June 1, 2023
- Monthly Rate: ₱1,000
- Current Date: October 15, 2024
- Months Owed: 16 months
- Total Payable: ₱1,000 × 16 = ₱16,000
- Total Paid: ₱16,000 (all months paid)
- **Outstanding Balance: ₱0**

## Files Updated

### 1. view_payment.php
- Updated month calculation logic
- Shows accurate outstanding balance in payment details

### 2. admin_class.php
- Updated `get_tdetails()` function
- Provides accurate data for payment form

### 3. balance_report.php
- Updated balance report calculations
- Shows correct outstanding balances for all tenants

### 4. payments.php
- Updated payment listing page
- Shows accurate outstanding balances

### 5. tenants.php
- Updated tenant listing page
- Shows correct outstanding balances in tenant table

## Testing
A test file `test_outstanding_calculation.php` has been created to verify the calculations:

1. **Sample Data Tests**: Shows calculations with different registration dates
2. **Actual Tenant Data**: Tests with real tenant data from the database
3. **Calculation Details**: Explains the logic step by step

### To run the test:
1. Open your browser
2. Go to: `http://localhost/rental/test_outstanding_calculation.php`
3. Review the calculations and verify they match expected results

## Impact on System

### Positive Changes:
- **Accurate Financial Records**: Outstanding balances now reflect true amounts owed
- **Better Reporting**: Balance reports show correct data
- **Improved Decision Making**: Landlords can make informed decisions based on accurate data
- **Consistent Calculations**: All parts of the system now use the same calculation method

### Backward Compatibility:
- No database schema changes required
- Existing payment data remains intact
- All existing functionality preserved
- Only calculation logic improved

## Validation Rules

### Month Calculation Logic:
1. Calculate the difference between registration date and current date
2. Convert years to months: `years × 12`
3. Add the remaining months from the interval
4. If current day >= registration day, add 1 more month (current month counts)

### Outstanding Balance Logic:
1. Calculate total months owed using the corrected method
2. Multiply by monthly rate to get total payable amount
3. Sum all payments made through the payment system
4. Subtract total paid from total payable

## Future Considerations

### Potential Enhancements:
1. **Prorated Calculations**: Consider partial month calculations for more precision
2. **Late Fees**: Add support for late payment penalties
3. **Payment Schedules**: Support for different payment frequencies
4. **Grace Periods**: Allow for grace periods before charging for new months

### Maintenance:
- The DateTime-based calculation is more robust and requires less maintenance
- Automatically handles edge cases like leap years and varying month lengths
- More readable and maintainable code structure
