<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting ZipArchive test...\n";

// Check if ZipArchive is available
if (class_exists('ZipArchive')) {
    echo "ZipArchive is available!\n";
    echo "PHP Version: " . phpversion() . "\n";
    
    // Try to create a ZipArchive object
    try {
        $zip = new ZipArchive();
        echo "Successfully created ZipArchive object\n";
    } catch (Exception $e) {
        echo "Error creating ZipArchive object: " . $e->getMessage() . "\n";
    }
} else {
    echo "ZipArchive is NOT available!\n";
    echo "PHP Version: " . phpversion() . "\n";
}

echo "PHP Extensions loaded: \n";
foreach (get_loaded_extensions() as $ext) {
    echo "- $ext\n";
}

echo "Test completed.\n";
?>
