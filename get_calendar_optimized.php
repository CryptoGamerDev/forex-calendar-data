<?php
// get_calendar_optimized.php - Zwraca pełne zoptymalizowane dane
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$optimized_filename = 'forex_data_optimized.csv';

// Sprawdź czy plik istnieje i jest aktualny
if (!file_exists($optimized_filename) || (time() - filemtime($optimized_filename) > 7200)) {
    // Plik nie istnieje lub jest stary - utwórz go
    include 'update_calendar.php';
    exit;
}

// Zwróć zoptymalizowane dane
if (file_exists($optimized_filename)) {
    readfile($optimized_filename);
} else {
    http_response_code(500);
    echo "ERROR: No optimized data available";
}
?>
