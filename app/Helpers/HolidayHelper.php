<?php

class HolidayHelper {
    /**
     * A list of fixed-date Philippine holidays.
     * Format: 'm-d'
     * @var array
     */
    private static $holidays = [
        '01-01' => "New Year's Day",
        '02-14' => "Valentine's Day",
        '02-25' => "EDSA People Power Revolution Anniversary",
        '04-09' => "Araw ng Kagitingan",
        '05-01' => "Labor Day",
        '06-12' => "Independence Day",
        '07-27' => "Iglesia ni Cristo Day",
        '08-21' => "Ninoy Aquino Day",
        '10-24' => "United Nations Day",
        '10-31' => "Halloween / All Saints’ Eve",
        '11-01' => "All Saints' Day",
        '11-30' => "Bonifacio Day",
        '12-08' => "Feast of the Immaculate Conception",
        '12-10' => "Human Rights Day",
        '12-24' => "Christmas Eve",
        '12-25' => "Christmas Day",
        '12-30' => "Rizal Day",
        '12-31' => "New Year's Eve",
    ];

    /**
     * Get the list of all holidays with their names.
     *
     * @return array Associative array of holidays ('m-d' => 'Name').
     */
    public static function getHolidays() {
        return self::$holidays;
    }

    /**
     * Check if a given date is a Philippine holiday.
     *
     * @param string $dateString The date to check in 'Y-m-d' format.
     * @return bool True if the date is a holiday, false otherwise.
     */
    public static function isHoliday($dateString) {
        if (empty($dateString)) {
            return false;
        }
        
        try {
            $date = new DateTime($dateString);
            $monthDay = $date->format('m-d');
            return array_key_exists($monthDay, self::$holidays);
        } catch (Exception $e) {
            // Log error or handle invalid date format
            error_log("Invalid date provided to HolidayHelper: " . $dateString);
            return false;
        }
    }
}