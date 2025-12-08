# Complete Reschedule System Documentation

## Overview
A comprehensive reschedule system with two modes:
1. **Direct Admin Reschedule** - Admins can directly reschedule bookings
2. **Request-Based Reschedule** - Customers and Staff submit requests that require admin approval

## Database Setup

### Run Migration
Execute: `scripts/migrations/add_reschedule_requests_table.sql`

```sql
CREATE TABLE RescheduleRequests (
    RequestID INT AUTO_INCREMENT PRIMARY KEY,
    BookingID INT NOT NULL,
    RequestedBy INT NOT NULL,
    CurrentDate DATE NOT NULL,
    CurrentTimeSlot VARCHAR(50) NOT NULL,
    RequestedDate DATE NOT NULL,
    RequestedTimeSlot VARCHAR(50) NOT NULL,
    Reason TEXT,
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    ReviewedBy INT NULL,
    ReviewedAt DATETIME NULL,
    ReviewNotes TEXT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Features by User Role

### 1. Admin Features

#### Direct Reschedule (Unified Management)
- **Location**: Unified Booking Management page
- **Access**: Actions dropdown → Reschedule
- **Capabilities**:
  - Immediately reschedule any booking
  - Real-time availability checking
  - No approval needed
  - Cannot reschedule cancelled bookings
  - Logs changes in audit trail
  - Sends notification to customer

#### Reschedule Request Management
- **Location**: Admin Sidebar → Booking → Reschedule Requests
- **Access**: All admins
- **Capabilities**:
  - View all pending reschedule requests
  - Filter by resort
  - See requester info (customer or staff)
  - View current and requested date/time
  - Read reason for reschedule
  - Approve requests (updates booking automatically)
  - Reject requests with explanation
  - Availability checked before approval
  - Badge shows pending request count

### 2. Customer Features

#### Request Reschedule
- **Location**: My Bookings page
- **Access**: "Request Reschedule" button on Confirmed/Pending bookings
- **Capabilities**:
  - Select new date and time slot
  - Provide reason for reschedule
  - Submit request for admin review
  - View current booking details
  - Only one pending request per booking
  - Cannot reschedule Cancelled/Completed bookings
  - Receive notification when approved/rejected

### 3. Staff Features

#### Request Reschedule
- **Location**: Staff Dashboard → Upcoming Bookings
- **Access**: "Request Reschedule" button in Actions column
- **Capabilities**:
  - Reschedule ANY booking (not just their own)
  - Select new date and time slot
  - Provide reason visible to admin and customer
  - Submit request for admin approval
  - View customer and booking details
  - Cannot reschedule Cancelled/Completed bookings
  - Helps manage bookings on behalf of customers

## Files Created

### Models
- `app/Models/RescheduleRequest.php` - Manages reschedule requests

### Views
- `app/Views/admin/reschedule_requests.php` - Admin interface for managing requests
- Reschedule modal added to:
  - `app/Views/booking/my_bookings.php` (Customer)
  - `app/Views/admin/staff_dashboard.php` (Staff)
  - `app/Views/admin/unified_booking_management.php` (Admin direct)

### Controllers
- `app/Controllers/AdminController.php`:
  - `rescheduleBooking()` - Direct admin reschedule
  - `rescheduleRequests()` - View pending requests
  - `approveRescheduleRequest()` - Approve request
  - `rejectRescheduleRequest()` - Reject request
  - `getAvailableTimeSlotsForReschedule()` - AJAX availability check

- `app/Controllers/UserController.php`:
  - `submitRescheduleRequest()` - Submit request (Customer/Staff)
  - `cancelRescheduleRequest()` - Cancel pending request

### Helpers
- `app/Helpers/Notification.php`:
  - `sendBookingRescheduleNotification()` - Notify customer of reschedule

### Migrations
- `scripts/migrations/add_reschedule_requests_table.sql`

## Workflow Examples

### Scenario 1: Admin Direct Reschedule
1. Admin goes to Unified Management
2. Clicks Actions → Reschedule on a booking
3. Selects new date (available slots load automatically)
4. Selects time slot
5. Optionally adds reason
6. Clicks "Reschedule Booking"
7. Booking is immediately updated
8. Customer receives notification
9. Change logged in audit trail

### Scenario 2: Customer Request
1. Customer goes to My Bookings
2. Clicks "Request Reschedule" on their booking
3. Sees current booking details
4. Selects new date and time slot
5. Provides reason
6. Submits request
7. Request status: Pending
8. Admin reviews and approves/rejects
9. Customer receives notification of decision

### Scenario 3: Staff Request
1. Staff views Staff Dashboard
2. Sees upcoming bookings with Actions column
3. Clicks "Request Reschedule" for any booking
4. Sees customer and booking details
5. Selects new date and time slot
6. Provides reason (visible to admin and customer)
7. Submits request
8. Admin reviews and approves/rejects
9. Customer receives notification of decision

## Security & Validation

### Access Control
- Customers can only request reschedules for their own bookings
- Staff can request reschedules for any booking
- Only admins can approve/reject requests
- Only admins can directly reschedule bookings

### Business Rules
- Cannot reschedule Cancelled bookings
- Cannot reschedule Completed bookings
- Only one pending request per booking at a time
- Availability is verified before approval
- All changes logged in audit trail

### Data Validation
- Date must be in the future
- Time slot must be valid
- Reason is required for requests
- Booking must exist and be accessible

## Notifications

### Customer Notifications
- When admin directly reschedules their booking
- When their reschedule request is approved
- When their reschedule request is rejected

### Admin Notifications
- When new reschedule request is submitted (future enhancement)

## Integration Points

### Existing Systems
- ✅ Booking Management System
- ✅ Audit Trail System
- ✅ Notification System
- ✅ Availability Checking
- ✅ User Permission System

### Database Tables
- Uses: Bookings, RescheduleRequests, Users, Resorts, BookingFacilities
- Updates: Bookings (when approved)
- Logs: BookingAuditTrail

## UI Locations

### Admin
- Unified Management → Actions → Reschedule (direct)
- Sidebar → Booking → Reschedule Requests (manage requests)
- Badge shows pending request count

### Customer
- My Bookings → Request Reschedule button

### Staff
- Staff Dashboard → Upcoming Bookings → Actions → Request Reschedule

## Benefits

### For Admins
- Full control with direct reschedule
- Centralized request management
- Visibility into all reschedule activity
- Ability to approve/reject with notes

### For Customers
- Self-service reschedule requests
- Transparent process with status updates
- Clear communication of reasons

### For Staff
- Help customers without admin privileges
- Manage bookings proactively
- Streamline customer service

### For Business
- Audit trail of all changes
- Controlled reschedule process
- Better customer service
- Reduced admin workload for simple requests
