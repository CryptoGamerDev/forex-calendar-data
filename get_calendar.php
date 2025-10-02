<?php
// get_calendar.php - Zwraca OCZYSZCZONE i POPRAWNE dane w formacie CSV
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$cleaned_filename = 'forex_data_cleaned.csv';

function cleanAndCreateCSV() {
    $raw_data = file_get_contents('https://nfs.faireconomy.media/ff_calendar_thisweek.csv');
    if ($raw_data === FALSE) {
        return false;
    }
    
    // Zapisz surowe dane
    file_put_contents('forex_data_raw.csv', $raw_data);
    
    // Użyj bardziej zaawansowanego przetwarzania CSV
    $raw_lines = explode("\n", trim($raw_data));
    $output = fopen('forex_data_cleaned.csv', 'w');
    
    // Nagłówek
    fputcsv($output, ['Title', 'Country', 'Date', 'Time', 'Impact', 'Forecast', 'Previous']);
    
    $first_line = true;
    foreach ($raw_lines as $line) {
        if ($first_line) {
            $first_line = false;
            continue;
        }
        
        if (!empty(trim($line))) {
            // Usuń znaki \r i rozdziel pola
            $clean_line = str_replace(["\r", "\n"], ' ', $line);
            $fields = str_getcsv($clean_line);
            
            if (count($fields) >= 8) {
                // Weź pierwsze 7 pól i usuń dodatkowe białe znaki
                $cleaned_fields = array_map('trim', array_slice($fields, 0, 7));
                fputcsv($output, $cleaned_fields);
            }
        }
    }
    
    fclose($output);
    return true;
}

// Jeśli oczyszczony plik nie istnieje lub jest stary, utwórz nowy
if (!file_exists($cleaned_filename) || 
    (time() - filemtime($cleaned_filename) > 3600)) {
    cleanAndCreateCSV();
}

// Zwróć oczyszczone dane
if (file_exists($cleaned_filename)) {
    readfile($cleaned_filename);
} else {
    // Fallback: spróbuj utworzyć jeszcze raz
    if (cleanAndCreateCSV()) {
        readfile($cleaned_filename);
    } else {
        http_response_code(500);
        echo "ERROR: Could not generate cleaned CSV data";
    }
}
?>
