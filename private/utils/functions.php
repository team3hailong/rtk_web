<?php

/**
 * Calculates the end time based on a start time and a duration string.
 *
 * Handles durations like "X tháng", "Y năm", "vĩnh viễn".
 * Case-insensitive and allows for some flexibility in spacing and leading characters.
 *
 * @param string $startTime The starting timestamp in 'Y-m-d H:i:s' format.
 * @param string $durationText The duration text (e.g., "1 tháng", "/ 2 năm", "Vĩnh viễn").
 * @return string|null The calculated end time in 'Y-m-d H:i:s' format, or null on failure or invalid format.
 */
function calculateEndTime(string $startTime, string $durationText): ?string {
    try {
        $date = new DateTime($startTime);
        // Trim whitespace AND common leading non-alphanumeric chars like '/'
        $trimmedDuration = trim($durationText, " /\t\n\r\0\x0B");
        $durationTextLower = mb_strtolower($trimmedDuration, 'UTF-8'); // Use mb_strtolower for Unicode

        // Check for lifetime first
        if ($durationTextLower === 'vĩnh viễn') {
            // Set a very far future date for 'lifetime'
            return $date->setDate(9999, 12, 31)->format('Y-m-d H:i:s');
        }

        // Use regex to extract number and unit (tháng or năm)
        // Allows for number, optional space, unit
        // Regex updated slightly for clarity, \s* handles spaces correctly
        if (preg_match('/^(\d+)\s*(tháng|thang)$/u', $durationTextLower, $matches)) {
            $months = (int)$matches[1];
            if ($months > 0) {
                $date->modify("+$months months");
                return $date->format('Y-m-d H:i:s');
            }
        } elseif (preg_match('/^(\d+)\s*(năm|nam)$/u', $durationTextLower, $matches)) {
            $years = (int)$matches[1];
            if ($years > 0) {
                $date->modify("+$years years");
                return $date->format('Y-m-d H:i:s');
            }
        }

        // If no match or invalid number, log and return null
        // Log the original input for better debugging
        error_log("Could not parse duration text (original: '" . $durationText . "', processed: '" . $durationTextLower . "')");
        return null;

    } catch (Exception $e) {
        // Log the original input for better debugging
        error_log("Error calculating end time for duration (original: '" . $durationText . "'): " . $e->getMessage());
        return null;
    }
}

// Add other utility functions here if needed...
/**
 * Format a number as currency
 * @param float $amount The amount to format
 * @param string $currency The currency symbol (default: 'đ')
 * @return string Formatted currency string
 */
function format_currency($amount, $currency = 'đ') {
    return number_format($amount, 0, ',', '.') . ' ' . $currency;
}
function generateRandomPassword(int $length = 12): string {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=[]{}|;,.<>/?';
    $password = '';
    $characterCount = strlen($characters);
    for ($i = 0; $length > $i; $i++) {
        $password .= $characters[random_int(0, $characterCount - 1)];
    }
    return $password;
}

?>
