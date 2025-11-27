<?php

/**
 * AdvancedAvailabilityChecker - Phase 6 Enhancement
 * Sophisticated availability checking with conflict resolution and optimization
 */

require_once __DIR__ . '/Booking.php';
require_once __DIR__ . '/Facility.php';
require_once __DIR__ . '/Resort.php';
require_once __DIR__ . '/BlockedResortAvailability.php';
require_once __DIR__ . '/BlockedFacilityAvailability.php';
require_once __DIR__ . '/ResortTimeframePricing.php';

class AdvancedAvailabilityChecker {
    
    private static $db;
    
    private static function getDB() {
        if (!self::$db) {
            require_once __DIR__ . '/../../config/database.php';
            try {
                self::$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
                self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$db;
    }

    /**
     * Comprehensive availability check with detailed conflict analysis
     */
    public static function checkAvailabilityDetailed($resortId, $bookingDate, $timeSlotType, $facilityIds = [], $excludeBookingId = null) {
        $result = [
            'available' => false,
            'conflicts' => [],
            'warnings' => [],
            'suggestions' => [],
            'blocking_issues' => [],
            'alternative_dates' => [],
            'alternative_facilities' => [],
            'optimization_suggestions' => []
        ];

        // 1. Basic date validation
        $dateValidation = self::validateBookingDate($bookingDate);
        if (!$dateValidation['valid']) {
            $result['conflicts'][] = $dateValidation['message'];
            return $result;
        }

        // 2. Resort-level availability check
        $resortCheck = self::checkResortAvailability($resortId, $bookingDate);
        if (!$resortCheck['available']) {
            $result['blocking_issues'] = array_merge($result['blocking_issues'], $resortCheck['issues']);
            
            // Suggest alternative dates if resort is blocked
            $result['alternative_dates'] = self::suggestAlternativeDates($resortId, $bookingDate, $timeSlotType);
            return $result;
        }

        // 3. Timeframe conflict analysis
        $timeframeCheck = self::analyzeTimeframeConflicts($resortId, $bookingDate, $timeSlotType, $excludeBookingId);
        if (!$timeframeCheck['available']) {
            $result['conflicts'] = array_merge($result['conflicts'], $timeframeCheck['conflicts']);
        }

        // 4. Facility-specific availability check
        if (!empty($facilityIds)) {
            $facilityCheck = self::checkFacilitiesAvailability($facilityIds, $bookingDate, $timeSlotType, $excludeBookingId);
            if (!$facilityCheck['available']) {
                $result['conflicts'] = array_merge($result['conflicts'], $facilityCheck['conflicts']);
                $result['alternative_facilities'] = $facilityCheck['alternative_facilities'];
            }
            $result['warnings'] = array_merge($result['warnings'], $facilityCheck['warnings']);
        }

        // 5. Advanced conflict resolution suggestions
        if (!empty($result['conflicts'])) {
            $result['suggestions'] = self::generateConflictResolutionSuggestions(
                $resortId, $bookingDate, $timeSlotType, $facilityIds, $result
            );
        }

        // 6. Resource optimization suggestions
        if (empty($result['conflicts'])) {
            $result['optimization_suggestions'] = self::generateOptimizationSuggestions(
                $resortId, $bookingDate, $timeSlotType, $facilityIds
            );
        }

        // 7. Buffer time validation
        $bufferCheck = self::checkBufferTimeRequirements($resortId, $bookingDate, $timeSlotType, $facilityIds, $excludeBookingId);
        if (!$bufferCheck['sufficient']) {
            $result['warnings'][] = $bufferCheck['warning'];
        }

        // 8. Peak time and pricing warnings
        $peakTimeCheck = self::checkPeakTimeImplications($resortId, $bookingDate, $timeSlotType);
        if (!empty($peakTimeCheck['warnings'])) {
            $result['warnings'] = array_merge($result['warnings'], $peakTimeCheck['warnings']);
        }

        // Final availability determination
        $result['available'] = empty($result['conflicts']) &&
                              empty($result['blocking_issues']);

        return $result;
    }

    /**
     * Validate booking date
     */
    private static function validateBookingDate($bookingDate) {
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        $bookingDateTime = new DateTime($bookingDate);
        $bookingDateTime->setTime(0, 0, 0);

        if ($bookingDateTime < $today) {
            return ['valid' => false, 'message' => 'Cannot book dates in the past'];
        }

        // Check if booking is within the current year
        $currentYear = $today->format('Y');
        $bookingYear = $bookingDateTime->format('Y');
        if ($bookingYear != $currentYear) {
            return ['valid' => false, 'message' => 'Bookings are only allowed for the current year'];
        }

        return ['valid' => true];
    }

    /**
     * Check resort-level availability
     */
    private static function checkResortAvailability($resortId, $bookingDate) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM BlockedResortAvailability WHERE ResortID = ? AND BlockDate = ?");
        $stmt->execute([$resortId, $bookingDate]);
        $blocks = $stmt->fetchAll(PDO::FETCH_OBJ);

        $result = ['available' => true, 'issues' => []];

        if (!empty($blocks)) {
            $result['available'] = false;
            foreach ($blocks as $block) {
                $result['issues'][] = [
                    'type' => 'resort_blocked',
                    'message' => 'Resort is blocked: ' . ($block->Reason ?? 'No reason provided'),
                    'severity' => 'high'
                ];
            }
        }

        return $result;
    }

    /**
     * Analyze timeframe conflicts with existing bookings
     */
    private static function analyzeTimeframeConflicts($resortId, $bookingDate, $timeSlotType, $excludeBookingId = null) {
        $db = self::getDB();
        
        $result = ['available' => true, 'conflicts' => []];

        // New logic: Check if there is ANY booking for the resort on the given date, regardless of timeframe.
        $sql = "SELECT b.*, u.Username as CustomerName, r.Name as ResortName
                FROM Bookings b
                LEFT JOIN Users u ON b.CustomerID = u.UserID
                LEFT JOIN Resorts r ON b.ResortID = r.ResortID
                WHERE b.ResortID = ?
                AND b.BookingDate = ?
                AND b.Status IN ('Pending', 'Confirmed')";
        
        $params = [$resortId, $bookingDate];

        if ($excludeBookingId) {
            $sql .= " AND b.BookingID != ?";
            $params[] = $excludeBookingId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $conflictingBookings = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (!empty($conflictingBookings)) {
            $result['available'] = false;
            foreach ($conflictingBookings as $booking) {
                $result['conflicts'][] = [
                    'type' => 'date_conflict', // Changed type from timeframe_conflict
                    'message' => "The resort is already booked on this date (Booking by {$booking->CustomerName}). Please choose another date.",
                    'severity' => 'high',
                    'conflicting_booking_id' => $booking->BookingID,
                    'conflicting_timeframe' => $booking->TimeSlotType
                ];
            }
        }

        return $result;
    }

    /**
     * Check facility-specific availability
     */
    private static function checkFacilitiesAvailability($facilityIds, $bookingDate, $timeSlotType, $excludeBookingId = null) {
        $result = [
            'available' => true,
            'conflicts' => [],
            'warnings' => [],
            'alternative_facilities' => []
        ];

        $db = self::getDB();

        foreach ($facilityIds as $facilityId) {
            // Get facility details
            $facility = Facility::findById($facilityId);
            if (!$facility) {
                $result['conflicts'][] = [
                    'type' => 'facility_not_found',
                    'message' => "Facility ID {$facilityId} not found",
                    'severity' => 'high'
                ];
                $result['available'] = false;
                continue;
            }


            // Check facility-level blocks
            $blockStmt = $db->prepare("SELECT * FROM BlockedFacilityAvailability WHERE FacilityID = ? AND BlockDate = ?");
            $blockStmt->execute([$facilityId, $bookingDate]);
            $blocks = $blockStmt->fetchAll(PDO::FETCH_OBJ);

            if (!empty($blocks)) {
                $result['conflicts'][] = [
                    'type' => 'facility_blocked',
                    'message' => "Facility '{$facility->name}' is blocked: " . ($blocks[0]->Reason ?? 'No reason provided'),
                    'facility_id' => $facilityId,
                    'facility_name' => $facility->name,
                    'severity' => 'high'
                ];
                $result['available'] = false;
            }

            // Check for conflicting bookings on this specific facility
            if (!Booking::isTimeSlotAvailable($facilityId, $bookingDate, $timeSlotType, $excludeBookingId)) {
                $result['conflicts'][] = [
                    'type' => 'facility_booking_conflict',
                    'message' => "Facility '{$facility->name}' has conflicting bookings for the selected timeframe",
                    'facility_id' => $facilityId,
                    'facility_name' => $facility->name,
                    'severity' => 'high'
                ];
                $result['available'] = false;
            }

        }

        return $result;
    }

    /**
     * Generate conflict resolution suggestions
     */
    private static function generateConflictResolutionSuggestions($resortId, $bookingDate, $timeSlotType, $facilityIds, $conflicts) {
        $suggestions = [];

        // If there are timeframe conflicts, suggest alternative timeframes
        foreach ($conflicts['conflicts'] as $conflict) {
            if ($conflict['type'] === 'timeframe_conflict') {
                $alternativeTimeframes = self::suggestAlternativeTimeframes($resortId, $bookingDate, $timeSlotType);
                if (!empty($alternativeTimeframes)) {
                    $suggestions[] = [
                        'type' => 'alternative_timeframe',
                        'message' => 'Consider booking a different timeframe',
                        'options' => $alternativeTimeframes
                    ];
                }
                break;
            }
        }


        // Suggest alternative dates
        $alternativeDates = self::suggestAlternativeDates($resortId, $bookingDate, $timeSlotType, 7);
        if (!empty($alternativeDates)) {
            $suggestions[] = [
                'type' => 'alternative_dates',
                'message' => 'Consider these available dates',
                'options' => $alternativeDates
            ];
        }

        return $suggestions;
    }

    /**
     * Generate optimization suggestions for successful bookings
     */
    private static function generateOptimizationSuggestions($resortId, $bookingDate, $timeSlotType, $facilityIds) {
        $suggestions = [];

        // Suggest additional facilities that might enhance the experience
        if (!empty($facilityIds)) {
            $additionalFacilities = self::suggestComplementaryFacilities($resortId, $facilityIds);
            if (!empty($additionalFacilities)) {
                $suggestions[] = [
                    'type' => 'complementary_facilities',
                    'message' => 'Consider adding these facilities to enhance your experience',
                    'options' => $additionalFacilities
                ];
            }
        }

        // Suggest cost-saving alternatives
        $costSavingOptions = self::suggestCostSavingAlternatives($resortId, $bookingDate, $timeSlotType, $facilityIds);
        if (!empty($costSavingOptions)) {
            $suggestions[] = [
                'type' => 'cost_optimization',
                'message' => 'Consider these options to reduce costs',
                'options' => $costSavingOptions
            ];
        }

        return $suggestions;
    }

    /**
     * Check buffer time requirements between bookings
     */
    private static function checkBufferTimeRequirements($resortId, $bookingDate, $timeSlotType, $facilityIds, $excludeBookingId = null) {
        // For now, we'll implement basic buffer time checking
        // In a real system, this would consider cleaning time, setup time, etc.
        
        $requiredBufferMinutes = 30; // 30 minutes buffer between bookings
        
        // This is a simplified implementation
        // Real implementation would check exact time overlaps and buffer requirements
        
        return [
            'sufficient' => true,
            'warning' => null
        ];
    }

    /**
     * Check peak time implications and pricing
     */
    private static function checkPeakTimeImplications($resortId, $bookingDate, $timeSlotType) {
        $warnings = [];
        
        // Check if it's a weekend
        $dayOfWeek = date('w', strtotime($bookingDate));
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $warnings[] = [
                'type' => 'weekend_pricing',
                'message' => 'Weekend surcharge may apply to this booking',
                'severity' => 'info'
            ];
        }

        // Check for holidays (simplified - would use a proper holiday calendar)
        $currentMonth = date('m', strtotime($bookingDate));
        $currentDay = date('d', strtotime($bookingDate));
        
        $holidays = ['12-25', '01-01', '12-31']; // Christmas, New Year's Day, New Year's Eve
        $dateString = sprintf('%02d-%02d', $currentMonth, $currentDay);
        
        if (in_array($dateString, $holidays)) {
            $warnings[] = [
                'type' => 'holiday_pricing',
                'message' => 'Holiday surcharge may apply to this booking',
                'severity' => 'info'
            ];
        }

        return ['warnings' => $warnings];
    }

    /**
     * Suggest alternative dates
     */
    private static function suggestAlternativeDates($resortId, $originalDate, $timeSlotType, $daysToCheck = 14) {
        $alternatives = [];
        $db = self::getDB();
        
        $startDate = new DateTime($originalDate);
        $startDate->modify('-7 days'); // Check 7 days before
        
        for ($i = 0; $i < $daysToCheck; $i++) {
            $checkDate = clone $startDate;
            $checkDate->modify("+$i days");
            $dateStr = $checkDate->format('Y-m-d');
            
            // Skip the original date
            if ($dateStr === $originalDate) continue;
            
            // Skip past dates
            if ($checkDate < new DateTime('today')) continue;
            
            // Quick availability check
            if (self::isDateAvailable($resortId, $dateStr, $timeSlotType)) {
                $alternatives[] = [
                    'date' => $dateStr,
                    'formatted_date' => $checkDate->format('l, F j, Y'),
                    'day_of_week' => $checkDate->format('l'),
                    'is_weekend' => ($checkDate->format('w') == 0 || $checkDate->format('w') == 6)
                ];
            }
        }
        
        return array_slice($alternatives, 0, 5); // Return up to 5 alternatives
    }

    /**
     * Simple date availability check
     */
    private static function isDateAvailable($resortId, $date, $timeSlotType) {
        return Booking::isResortTimeframeAvailable($resortId, $date, $timeSlotType, []);
    }

    /**
     * Suggest alternative timeframes
     */
    private static function suggestAlternativeTimeframes($resortId, $bookingDate, $currentTimeSlotType) {
        $allTimeframes = ['12_hours', 'overnight', '24_hours'];
        $alternatives = [];
        
        foreach ($allTimeframes as $timeframe) {
            if ($timeframe === $currentTimeSlotType) continue;
            
            if (self::isDateAvailable($resortId, $bookingDate, $timeframe)) {
                $alternatives[] = [
                    'timeframe' => $timeframe,
                    'display_name' => Booking::getTimeSlotDisplay($timeframe),
                    'base_price' => ResortTimeframePricing::calculatePrice($resortId, $timeframe, $bookingDate)
                ];
            }
        }
        
        return $alternatives;
    }



    /**
     * Suggest complementary facilities
     */
    private static function suggestComplementaryFacilities($resortId, $selectedFacilityIds) {
        $db = self::getDB();

        // Get facilities not already selected
        $placeholders = rtrim(str_repeat('?,', count($selectedFacilityIds)), ',');
        $sql = "SELECT * FROM Facilities
                WHERE ResortID = ?
                AND FacilityID NOT IN ($placeholders)
                ORDER BY Rate ASC";

        $params = [$resortId, ...$selectedFacilityIds];

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $facilities = $stmt->fetchAll(PDO::FETCH_OBJ);

        $suggestions = [];
        foreach ($facilities as $facility) {
            $suggestions[] = [
                'facility_id' => $facility->FacilityID,
                'name' => $facility->Name,
                'rate' => $facility->Rate,
                'description' => $facility->ShortDescription,
                'benefit' => 'Additional amenity for enhanced experience'
            ];
        }

        return array_slice($suggestions, 0, 3);
    }

    /**
     * Suggest cost-saving alternatives
     */
    private static function suggestCostSavingAlternatives($resortId, $bookingDate, $timeSlotType, $facilityIds) {
        $suggestions = [];
        
        // Suggest weekday alternatives if booking weekend
        $dayOfWeek = date('w', strtotime($bookingDate));
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $weekdayDates = self::suggestWeekdayAlternatives($resortId, $bookingDate, $timeSlotType);
            if (!empty($weekdayDates)) {
                $suggestions[] = [
                    'type' => 'weekday_booking',
                    'message' => 'Save on weekend surcharges by booking on weekdays',
                    'savings_percentage' => 15,
                    'alternative_dates' => $weekdayDates
                ];
            }
        }
        
        // Suggest shorter timeframes if 24_hours selected
        if ($timeSlotType === '24_hours') {
            if (self::isDateAvailable($resortId, $bookingDate, '12_hours')) {
                $basePrice24 = ResortTimeframePricing::calculatePrice($resortId, '24_hours', $bookingDate);
                $basePrice12 = ResortTimeframePricing::calculatePrice($resortId, '12_hours', $bookingDate);
                $savings = $basePrice24 - $basePrice12;
                
                if ($savings > 0) {
                    $suggestions[] = [
                        'type' => 'shorter_timeframe',
                        'message' => 'Consider 12-hour booking to save ₱' . number_format($savings, 2),
                        'original_timeframe' => '24_hours',
                        'suggested_timeframe' => '12_hours',
                        'savings_amount' => $savings
                    ];
                }
            }
        }
        
        return $suggestions;
    }

    /**
     * Suggest weekday alternatives to weekend bookings
     */
    private static function suggestWeekdayAlternatives($resortId, $weekendDate, $timeSlotType) {
        $alternatives = [];
        $date = new DateTime($weekendDate);
        
        // Check the week of the weekend booking
        for ($i = 1; $i <= 5; $i++) { // Monday to Friday
            $checkDate = clone $date;
            $checkDate->modify('monday this week')->modify("+".($i-1)." days");
            
            if ($checkDate >= new DateTime('today') && self::isDateAvailable($resortId, $checkDate->format('Y-m-d'), $timeSlotType)) {
                $alternatives[] = [
                    'date' => $checkDate->format('Y-m-d'),
                    'formatted_date' => $checkDate->format('l, F j, Y'),
                    'day_name' => $checkDate->format('l')
                ];
            }
        }
        
        return array_slice($alternatives, 0, 3);
    }

    /**
     * Get comprehensive availability report for admin dashboard
     */
    public static function getAvailabilityReport($resortId, $startDate, $endDate) {
        $db = self::getDB();
        
        $report = [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_days' => 0,
            'available_days' => 0,
            'blocked_days' => 0,
            'partially_booked_days' => 0,
            'fully_booked_days' => 0,
            'peak_demand_dates' => [],
            'low_demand_dates' => []
        ];
        
        $currentDate = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        
        while ($currentDate <= $endDateTime) {
            $dateStr = $currentDate->format('Y-m-d');
            $report['total_days']++;
            
            // Check if date is blocked
            $blockStmt = $db->prepare("SELECT COUNT(*) FROM BlockedResortAvailability WHERE ResortID = ? AND BlockDate = ?");
            $blockStmt->execute([$resortId, $dateStr]);
            
            if ($blockStmt->fetchColumn() > 0) {
                $report['blocked_days']++;
            } else {
                // Check booking status for each timeframe
                $timeframes = ['12_hours', 'overnight', '24_hours'];
                $availableTimeframes = 0;
                
                foreach ($timeframes as $timeframe) {
                    if (self::isDateAvailable($resortId, $dateStr, $timeframe)) {
                        $availableTimeframes++;
                    }
                }
                
                if ($availableTimeframes == 3) {
                    $report['available_days']++;
                    $report['low_demand_dates'][] = $dateStr;
                } elseif ($availableTimeframes == 0) {
                    $report['fully_booked_days']++;
                    $report['peak_demand_dates'][] = $dateStr;
                } else {
                    $report['partially_booked_days']++;
                }
            }
            
            $currentDate->modify('+1 day');
        }
        
        // Calculate utilization percentage
        if ($report['total_days'] > 0) {
            $report['utilization_percentage'] = round(
                (($report['fully_booked_days'] + $report['partially_booked_days']) / ($report['total_days'] - $report['blocked_days'])) * 100, 2
            );
        }
        
        return $report;
    }
}
