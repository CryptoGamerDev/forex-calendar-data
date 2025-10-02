<?php
// get_calendar.php - Zwraca zoptymalizowane dane dla MQL5 (uproszczona wersja)
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$simple_filename = 'forex_data_simple.csv';
$optimized_filename = 'forex_data_optimized.csv';

// Sprawdź czy pliki istnieją i są aktualne (mniej niż 4 godziny)
$files_exist = file_exists($simple_filename) && file_exists($optimized_filename);
$files_fresh = $files_exist && (time() - filemtime($simple_filename) < 14400);

if (!$files_exist || !$files_fresh) {
    // Pliki nie istnieją lub są stare - utwórz je
    include 'update_calendar.php';
    exit;
}

// Zwróć uproszczone dane (optymalne dla EA)
if (file_exists($simple_filename)) {
    readfile($simple_filename);
} else {
    // Fallback: zwróć zoptymalizowane dane
    if (file_exists($optimized_filename)) {
        readfile($optimized_filename);
    } else {
        // Ostateczny fallback: zwróć surowe dane
        if (file_exists('forex_data_raw.csv')) {
            readfile('forex_data_raw.csv');
        } else {
            http_response_code(500);
            echo "ERROR: No economic calendar data available";
        }
    }
}
?>
