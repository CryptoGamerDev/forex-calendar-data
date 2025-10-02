<?php
// update_calendar.php - Aktualizuje i naprawia dane CSV dla MT5
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

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
    $processed_count = 0;
    
    foreach ($raw_lines as $line) {
        if ($first_line) {
            $first_line = false;
            continue;
        }
        
        $line = trim($line);
        if (!empty($line)) {
            // Usuń znaki \r i zastąp znaki nowej linii w polach spacjami
            $clean_line = str_replace(["\r", "\n"], ' ', $line);
            
            // Użyj str_getcsv do poprawnego parsowania pól CSV
            $fields = str_getcsv($clean_line);
            
            if (count($fields) >= 8) {
                $processed_count++;
                
                // Weź pierwsze 7 pól i usuń dodatkowe białe znaki
                $cleaned_fields = array_map('trim', array_slice($fields, 0, 7));
                
                // Upewnij się, że wszystkie pola są ustawione
                for ($i = 0; $i < 7; $i++) {
                    if (!isset($cleaned_fields[$i])) {
                        $cleaned_fields[$i] = '';
                    }
                }
                
                fputcsv($output, $cleaned_fields);
            }
        }
    }
    
    fclose($output);
    return $processed_count;
}

function updateAndCleanForexData() {
    $csv_url = "https://nfs.faireconomy.media/ff_calendar_thisweek.csv";
    
    // Pobierz surowe dane
    $raw_data = file_get_contents($csv_url);
    
    if ($raw_data === FALSE) {
        return "ERROR: Could not fetch data from Forex Factory";
    }
    
    // Zapisz surowe dane
    file_put_contents('forex_data_raw.csv', $raw_data);
    
    // Przetwórz i oczyść dane
    $processed_count = cleanAndCreateCSV();
    
    if ($processed_count !== false) {
        $raw_count = count(file('forex_data_raw.csv')) - 1; // minus nagłówek
        $cleaned_count = count(file('forex_data_cleaned.csv')) - 1; // minus nagłówek
        
        // Sprawdź czy plik wyjściowy jest poprawny
        $cleaned_content = file_get_contents('forex_data_cleaned.csv');
        $has_carriage_returns = (strpos($cleaned_content, "\r") !== false);
        
        $status_message = "SUCCESS: Data updated and cleaned at " . date('Y-m-d H:i:s') . 
               " ($raw_count raw -> $cleaned_count cleaned events)";
               
        if ($has_carriage_returns) {
            $status_message .= " - WARNING: Still contains carriage returns";
        } else {
            $status_message .= " - CSV format is clean";
        }
        
        return $status_message;
    }
    
    return "ERROR: Failed to clean and process CSV data";
}

// Wykonaj aktualizację
$result = updateAndCleanForexData();
echo $result;

// Dodatkowe informacje diagnostyczne
echo "\n\n--- Diagnostic Info ---\n";
echo "Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";

if (file_exists('forex_data_cleaned.csv')) {
    $file_size = filesize('forex_data_cleaned.csv');
    $line_count = count(file('forex_data_cleaned.csv'));
    echo "Cleaned File: " . $file_size . " bytes, " . $line_count . " lines\n";
    
    // Pokaż pierwsze 3 linie dla weryfikacji
    $lines = file('forex_data_cleaned.csv');
    echo "First 3 lines:\n";
    for ($i = 0; $i < min(3, count($lines)); $i++) {
        echo ($i + 1) . ": " . trim($lines[$i]) . "\n";
    }
}

if (file_exists('forex_data_raw.csv')) {
    $raw_line_count = count(file('forex_data_raw.csv'));
    echo "Raw File: " . $raw_line_count . " lines\n";
}
?>
