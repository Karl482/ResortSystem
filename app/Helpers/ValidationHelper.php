<?php

/**
 * Enhanced Validation Helper - Phase 6
 * Comprehensive validation system for booking, payment, and user input
 */

require_once __DIR__ . '/ErrorHandler.php';

class ValidationHelper {
    
    /**
     * Validate booking creation data
     */
    public static function validateBookingData($data, $isAdmin = false) {
        $rules = [
            'resort_id' => 'required|integer|min:1',
            'booking_date' => 'required|date',
            'timeframe' => 'required|in:12_hours,overnight,24_hours',
            'facility_ids' => 'nullable|array'
        ];

        $customMessages = [
            'resort_id.required' => 'Please select a resort',
            'resort_id.integer' => 'Invalid resort selection',
            'booking_date.required' => 'Please select a booking date',
            'booking_date.date' => 'Please provide a valid date',
            'timeframe.required' => 'Please select a time slot',
            'timeframe.in' => 'Please select a valid time slot option',
            'facility_ids.array' => 'Invalid facility selection format'
        ];

        if ($isAdmin) {
            $rules['customer_id'] = 'required|integer|min:1';
            $customMessages['customer_id.required'] = 'Please select a customer';
            $customMessages['customer_id.integer'] = 'Invalid customer selection';
        }

        $result = ErrorHandler::validateInput($data, $rules, $customMessages);

        // Additional custom validations
        if ($result['valid']) {
            // Validate booking date is not in the past
            $bookingDate = new DateTime($result['data']['booking_date']);
            $today = new DateTime('today');

            if ($bookingDate < $today) {
                $result['valid'] = false;
                $result['errors']['booking_date'][] = 'Cannot book dates in the past';
            }

            // Validate booking date is not more than 1 year in future
            $maxDate = clone $today;
            $maxDate->modify('+1 year');

            if ($bookingDate > $maxDate) {
                $result['valid'] = false;
                $result['errors']['booking_date'][] = 'Cannot book more than 1 year in advance';
            }

            // Validate facility IDs if provided
            if (!empty($result['data']['facility_ids'])) {
                foreach ($result['data']['facility_ids'] as $facilityId) {
                    if (!is_numeric($facilityId) || $facilityId <= 0) {
                        $result['valid'] = false;
                        $result['errors']['facility_ids'][] = 'Invalid facility selection';
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Validate payment submission data
     */
    public static function validatePaymentData($data, $files) {
        $rules = [
            'booking_id' => 'required|integer|min:1',
            'amount_paid' => 'required|float|min:50|max:50000',
            'payment_reference' => 'required|sanitize|min:3|max:100'
        ];

        $customMessages = [
            'booking_id.required' => 'Booking ID is required',
            'booking_id.integer' => 'Invalid booking ID',
            'amount_paid.required' => 'Payment amount is required',
            'amount_paid.min' => 'Minimum payment amount is ₱50',
            'amount_paid.max' => 'Maximum payment amount is ₱50,000',
            'payment_reference.required' => 'Payment reference number is required',
            'payment_reference.min' => 'Reference number must be at least 3 characters',
            'payment_reference.max' => 'Reference number cannot exceed 100 characters'
        ];

        $result = ErrorHandler::validateInput($data, $rules, $customMessages);
        
        // Validate payment proof file
        if ($result['valid'] && isset($files['payment_proof'])) {
            $fileValidation = self::validatePaymentProofFile($files['payment_proof']);
            if (!$fileValidation['valid']) {
                $result['valid'] = false;
                $result['errors']['payment_proof'] = $fileValidation['errors'];
            }
        }

        return $result;
    }

    /**
     * Validate payment proof file upload
     */
    public static function validatePaymentProofFile($file) {
        $errors = [];

        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Payment proof image is required';
            return ['valid' => false, 'errors' => $errors];
        }

        // Check file size (5MB max)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size must not exceed 5MB';
        }

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'File must be a valid image (JPEG, PNG, or GIF)';
        }

        // Check if file is actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = 'File is not a valid image';
        }

        // Check image dimensions (optional - prevent extremely large images)
        if ($imageInfo && ($imageInfo[0] > 4000 || $imageInfo[1] > 4000)) {
            $errors[] = 'Image dimensions too large (max 4000x4000 pixels)';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mimeType ?? null,
            'dimensions' => $imageInfo ? [$imageInfo[0], $imageInfo[1]] : null
        ];
    }

    /**
     * Validate profile image file upload
     */
    public static function validateProfileImageFile($file) {
        $errors = [];

        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Profile image is required';
            return ['valid' => false, 'errors' => $errors];
        }

        // Check file size (5MB max)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            $errors[] = 'Image file size must not exceed 5MB';
        }

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Profile image must be a valid image (JPEG, PNG, or GIF)';
        }

        // Check if file is actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = 'The uploaded file is not a valid image';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    public static function validateQrCodeFile($file) {
        $errors = [];

        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'QR code image is required.';
            return ['valid' => false, 'errors' => $errors];
        }

        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            $errors[] = 'QR code file size must not exceed 2MB.';
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'QR code must be a valid image (JPEG, PNG, or GIF).';
        }

        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = 'The uploaded file is not a valid image.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate user registration data
     */
    public static function validateUserRegistration($data, $files = []) {
        $rules = [
            'username' => 'required|sanitize|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8|max:255',
            'confirm_password' => 'required',
            'role' => 'in:Customer,Staff,Admin'
        ];

        $customMessages = [
            'username.required' => 'Username is required',
            'username.min' => 'Username must be at least 3 characters',
            'username.max' => 'Username cannot exceed 50 characters',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'confirm_password.required' => 'Password confirmation is required',
            'role.in' => 'Invalid role selection'
        ];

        $result = ErrorHandler::validateInput($data, $rules, $customMessages);
        
        // Additional password validation
        if ($result['valid']) {
            // Check password confirmation
            $password = $result['data']['password'];
            if ($password !== $data['confirm_password']) {
                $result['valid'] = false;
                $result['errors']['confirm_password'][] = 'Password confirmation does not match';
            }

            // Check username format
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $result['data']['username'])) {
                $result['valid'] = false;
                $result['errors']['username'][] = 'Username can only contain letters, numbers, and underscores';
            }
            if (isset($data['role']) && $data['role'] === 'Admin') {
                if (empty($data['socials'])) {
                    $result['valid'] = false;
                    $result['errors']['socials'][] = 'Socials field is required for Admins.';
                }

                // Validate profile image for Admin registration
                $fileValidation = self::validateProfileImageFile($files['profileImage'] ?? null);
                if (!$fileValidation['valid']) {
                    $result['valid'] = false;
                    $result['errors']['profileImage'] = $fileValidation['errors'];
                }
            }
        }

        return $result;
    }

    /**
     * Validate admin pricing configuration
     */
    public static function validatePricingData($data) {
        $rules = [
            'resort_id' => 'required|integer|min:1',
            'timeframe_type' => 'required|in:12_hours,overnight,24_hours',
            'base_price' => 'required|float|min:0|max:100000',
            'weekend_surcharge' => 'float|min:0|max:10000',
            'holiday_surcharge' => 'float|min:0|max:10000'
        ];

        $customMessages = [
            'resort_id.required' => 'Resort selection is required',
            'timeframe_type.required' => 'Timeframe type is required',
            'timeframe_type.in' => 'Invalid timeframe type',
            'base_price.required' => 'Base price is required',
            'base_price.min' => 'Base price cannot be negative',
            'base_price.max' => 'Base price cannot exceed ₱100,000',
            'weekend_surcharge.min' => 'Weekend surcharge cannot be negative',
            'weekend_surcharge.max' => 'Weekend surcharge cannot exceed ₱10,000',
            'holiday_surcharge.min' => 'Holiday surcharge cannot be negative',
            'holiday_surcharge.max' => 'Holiday surcharge cannot exceed ₱10,000'
        ];

        return ErrorHandler::validateInput($data, $rules, $customMessages);
    }

    /**
     * Validate facility data
     */
    public static function validateFacilityData($data) {
        $rules = [
            'resort_id' => 'required|integer|min:1',
            'name' => 'required|sanitize|min:2|max:100',
            'rate' => 'required|float|min:0|max:50000',
            'short_description' => 'sanitize|max:255',
            'description' => 'sanitize|max:1000',
            'full_description' => 'sanitize|max:5000'
        ];

        $customMessages = [
            'resort_id.required' => 'Resort selection is required',
            'name.required' => 'Facility name is required',
            'name.min' => 'Facility name must be at least 2 characters',
            'name.max' => 'Facility name cannot exceed 100 characters',
            'rate.required' => 'Rate is required',
            'rate.min' => 'Rate cannot be negative',
            'rate.max' => 'Rate cannot exceed ₱50,000',
            'short_description.max' => 'Short description cannot exceed 255 characters',
            'description.max' => 'Description cannot exceed 1000 characters',
            'full_description.max' => 'Full description cannot exceed 5000 characters'
        ];

        return ErrorHandler::validateInput($data, $rules, $customMessages);
    }

    /**
     * Validate resort data
     */
    public static function validateResortData($data) {
        $rules = [
            'name' => 'required|sanitize|min:2|max:100',
            'address' => 'required|sanitize|min:5|max:255',
            'contactPerson' => 'required|sanitize|min:2|max:100',
            'shortDescription' => 'sanitize|max:255',
            'fullDescription' => 'sanitize|max:5000'
        ];

        $customMessages = [
            'name.required' => 'Resort name is required',
            'name.min' => 'Resort name must be at least 2 characters',
            'name.max' => 'Resort name cannot exceed 100 characters',
            'address.required' => 'Address is required',
            'address.min' => 'Address must be at least 5 characters',
            'address.max' => 'Address cannot exceed 255 characters',
            'contactPerson.required' => 'Contact person is required',
            'shortDescription.max' => 'Short description cannot exceed 255 characters',
            'fullDescription.max' => 'Full description cannot exceed 5000 characters'
        ];

        return ErrorHandler::validateInput($data, $rules, $customMessages);
    }

    /**
     * Validate availability blocking data
     */
    public static function validateBlockingData($data) {
        $rules = [
            'resort_id' => 'integer|min:1',
            'facility_id' => 'integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'reason' => 'required|sanitize|min:3|max:255'
        ];

        $customMessages = [
            'start_date.required' => 'Start date is required',
            'start_date.date' => 'Please provide a valid start date',
            'end_date.required' => 'End date is required',
            'end_date.date' => 'Please provide a valid end date',
            'reason.required' => 'Blocking reason is required',
            'reason.min' => 'Reason must be at least 3 characters',
            'reason.max' => 'Reason cannot exceed 255 characters'
        ];

        $result = ErrorHandler::validateInput($data, $rules, $customMessages);
        
        // Additional date validation
        if ($result['valid']) {
            $startDate = new DateTime($result['data']['start_date']);
            $endDate = new DateTime($result['data']['end_date']);
            
            if ($endDate < $startDate) {
                $result['valid'] = false;
                $result['errors']['end_date'][] = 'End date must be after start date';
            }
            
            // Check if blocking too far in the future (2 years max)
            $maxDate = new DateTime();
            $maxDate->modify('+2 years');
            
            if ($startDate > $maxDate) {
                $result['valid'] = false;
                $result['errors']['start_date'][] = 'Cannot block dates more than 2 years in advance';
            }
        }

        return $result;
    }

    /**
     * Validate search/filter parameters
     */
    public static function validateSearchParameters($data) {
        $rules = [
            'resort_id' => 'integer|min:1',
            'status' => 'in:Pending,Confirmed,Cancelled,Completed',
            'date_from' => 'date',
            'date_to' => 'date',
            'limit' => 'integer|min:1|max:1000',
            'offset' => 'integer|min:0'
        ];

        $customMessages = [
            'resort_id.integer' => 'Invalid resort ID',
            'status.in' => 'Invalid status filter',
            'date_from.date' => 'Invalid from date',
            'date_to.date' => 'Invalid to date',
            'limit.min' => 'Limit must be at least 1',
            'limit.max' => 'Limit cannot exceed 1000',
            'offset.min' => 'Offset cannot be negative'
        ];

        $result = ErrorHandler::validateInput($data, $rules, $customMessages);
        
        // Additional date range validation
        if ($result['valid'] && isset($result['data']['date_from']) && isset($result['data']['date_to'])) {
            $dateFrom = new DateTime($result['data']['date_from']);
            $dateTo = new DateTime($result['data']['date_to']);
            
            if ($dateTo < $dateFrom) {
                $result['valid'] = false;
                $result['errors']['date_to'][] = 'End date must be after start date';
            }
        }

        return $result;
    }

    /**
     * Validate password complexity
     */
    private static function validatePasswordComplexity($password) {
        // At least one uppercase letter, one lowercase letter, and one number
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password);
    }

    /**
     * Sanitize HTML content (for descriptions, notes, etc.)
     */
    public static function sanitizeHtmlContent($content) {
        // Allow basic HTML tags for rich content
        $allowedTags = '<p><br><strong><em><u><ul><ol><li><a>';
        return strip_tags($content, $allowedTags);
    }

    /**
     * Validate and sanitize phone number
     */
    public static function validatePhoneNumber($phone) {
        // Remove all non-digit characters
        $cleaned = preg_replace('/\D/', '', $phone);
        
        // Check if it's a valid Philippine mobile number
        if (preg_match('/^(09\d{9}|639\d{9}|\+639\d{9})$/', $phone) || 
            preg_match('/^09\d{9}$/', $cleaned)) {
            return [
                'valid' => true,
                'formatted' => $cleaned,
                'display' => '+63' . substr($cleaned, -10)
            ];
        }
        
        return [
            'valid' => false,
            'message' => 'Please provide a valid Philippine mobile number'
        ];
    }

    /**
     * Validate monetary amount
     */
    public static function validateAmount($amount, $min = 0, $max = 999999.99) {
        $filtered = filter_var($amount, FILTER_VALIDATE_FLOAT);
        
        if ($filtered === false) {
            return [
                'valid' => false,
                'message' => 'Please provide a valid amount'
            ];
        }
        
        if ($filtered < $min) {
            return [
                'valid' => false,
                'message' => "Amount must be at least ₱" . number_format($min, 2)
            ];
        }
        
        if ($filtered > $max) {
            return [
                'valid' => false,
                'message' => "Amount cannot exceed ₱" . number_format($max, 2)
            ];
        }
        
        // Round to 2 decimal places
        $rounded = round($filtered, 2);
        
        return [
            'valid' => true,
            'value' => $rounded,
            'formatted' => '₱' . number_format($rounded, 2)
        ];
    }

    /**
     * Batch validation for multiple records
     */
    public static function validateBatch($records, $validationFunction, $stopOnFirstError = false) {
        $results = [
            'valid' => true,
            'errors' => [],
            'validated_records' => []
        ];
        
        foreach ($records as $index => $record) {
            $validation = call_user_func($validationFunction, $record);
            
            if ($validation['valid']) {
                $results['validated_records'][$index] = $validation['data'];
            } else {
                $results['valid'] = false;
                $results['errors'][$index] = $validation['errors'];
                
                if ($stopOnFirstError) {
                    break;
                }
            }
        }
        
        return $results;
    }

    /**
     * Create validation summary for API responses
     */
    public static function createValidationSummary($validationResult) {
        if ($validationResult['valid']) {
            return [
                'success' => true,
                'data' => $validationResult['data']
            ];
        }
        
        return [
            'success' => false,
            'errors' => $validationResult['errors'],
            'error_count' => count($validationResult['errors']),
            'message' => 'Validation failed. Please check your input and try again.'
        ];
    }
}
