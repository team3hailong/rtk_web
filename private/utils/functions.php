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
        $durationTextLower = mb_strtolower(trim($durationText));
        
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
        
        // Handle duration formats like "/ 3 tháng" or "/ 1 năm" by removing the initial slash
        if (preg_match('/^\/\s*(\d+)\s*(tháng|thang|năm|nam)$/u', $durationTextLower, $matches)) {
            $amount = (int)$matches[1];
            $unit = $matches[2];
            
            if ($amount > 0) {
                if ($unit === 'tháng' || $unit === 'thang') {
                    $date->modify("+$amount months");
                } elseif ($unit === 'năm' || $unit === 'nam') {
                    $date->modify("+$amount years");
                }
                return $date->format('Y-m-d H:i:s');
            }
        }
        
        // If all above failed, try a simple approach with number of days
        // Example: If duration was 30 days, 60 days, 365 days
        if (preg_match('/^(\d+)\s*days?$/i', $durationTextLower, $matches)) {
            $days = (int)$matches[1];
            if ($days > 0) {
                $date->modify("+$days days");
                return $date->format('Y-m-d H:i:s');
            }
        }
        
        // Default: Add 30 days if we can't determine the duration
        error_log("Could not parse duration text: '$durationText', defaulting to 30 days");
        $date->modify('+30 days');
        return $date->format('Y-m-d H:i:s');
        
    } catch (Exception $e) {
        error_log("Error calculating end time: " . $e->getMessage());
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
