<?php
// update_calendar.php - Aktualizuje i optymalizuje dane Forex dla MT5
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function downloadAndOptimizeData() {
    $csv_url = "https://nfs.faireconomy.media/ff_calendar_thisweek.csv";
    
    // Pobierz dane używając file_get_contents z kontekstem
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'MQL5 Economic Calendar Downloader'
        ]
    ]);
    
    $raw_data = file_get_contents($csv_url, false, $context);
    
    if ($raw_data === FALSE) {
        return "ERROR: Could not fetch data from Forex Factory";
    }
    
    // Zapisz surowe dane
    file_put_contents('forex_data_raw.csv', $raw_data);
    
    // Przetwórz dane i utwórz zoptymalizowane pliki
    $raw_lines = explode("\n", trim($raw_data));
    $optimized_rows = [];
    $simple_rows = [];
    
    // Mapowanie Impact na wartości numeryczne
    $impact_to_importance = [
        'High' => 3,
        'Medium' => 2,
        'Low' => 1,
        'Holiday' => 0
    ];
    
    // Nagłówki dla zoptymalizowanego pliku
    $optimized_rows[] = ['Title', 'Country', 'Date', 'Time', 'Time24h', 'Impact', 'Forecast', 'Previous', 'HasForecast', 'HasPrevious', 'Importance'];
    
    // Nagłówki dla uproszczonego pliku
    $simple_rows[] = ['Title', 'Country', 'Date', 'Time24h', 'Importance', 'Forecast', 'Previous'];
    
    $seen_events = [];
    
    // Funkcja do konwersji czasu na 24h
    function convertTimeTo24h($time_str) {
        if (empty($time_str)) return '';
        
        $time_str = strtolower(trim($time_str));
        if (!preg_match('/(\d+):?(\d+)?(am|pm)/', $time_str, $matches)) {
            return $time_str;
        }
        
        $hour = (int)$matches[1];
        $minute = isset($matches[2]) ? $matches[2] : '00';
        $period = $matches[3];
        
        if ($period == 'pm' && $hour != 12) {
            $hour += 12;
        } elseif ($period == 'am' && $hour == 12) {
            $hour = 0;
        }
        
        return sprintf("%02d:%s", $hour, $minute);
    }
    
    // Przetwarzanie wierszy
    $first_line = true;
    $processed_count = 0;
    
    foreach ($raw_lines as $line) {
        if ($first_line) {
            $first_line = false;
            continue;
        }
        
        $line = trim($line);
        if (empty($line)) continue;
        
        $fields = str_getcsv($line);
        if (count($fields) < 8) continue;
        
        list($title, $country, $date, $time, $impact, $forecast, $previous) = array_slice($fields, 0, 7);
        
        // Sprawdź duplikaty
        $key = $title . $country . $date . $time;
        if (isset($seen_events[$key])) continue;
        $seen_events[$key] = true;
        
        // Konwertuj czas
        $time24h = convertTimeTo24h($time);
        
        // Ustaw domyślne wartości
        $forecast = (empty($forecast) || trim($forecast) === '') ? 'N/A' : trim($forecast);
        $previous = (empty($previous) || trim($previous) === '') ? 'N/A' : trim($previous);
        
        $has_forecast = $forecast !== 'N/A' ? '1' : '0';
        $has_previous = $previous !== 'N/A' ? '1' : '0';
        $importance = (string)($impact_to_importance[$impact] ?? 0);
        
        // Dodaj do zoptymalizowanego pliku
        $optimized_rows[] = [
            $title, $country, $date, $time, $time24h, $impact,
            $forecast, $previous, $has_forecast, $has_previous, $importance
        ];
        
        // Dodaj do uproszczonego pliku
        $simple_rows[] = [
            $title, $country, $date, $time24h, $importance, $forecast, $previous
        ];
        
        $processed_count++;
    }
    
    // Zapisz zoptymalizowany plik
    $optimized_file = fopen('forex_data_optimized.csv', 'w');
    foreach ($optimized_rows as $row) {
        fputcsv($optimized_file, $row);
    }
    fclose($optimized_file);
    
    // Zapisz uproszczony plik
    $simple_file = fopen('forex_data_simple.csv', 'w');
    foreach ($simple_rows as $row) {
        fputcsv($simple_file, $row);
    }
    fclose($simple_file);
    
    return $processed_count;
}

// Wykonaj aktualizację
$result = downloadAndOptimizeData();

if (is_numeric($result)) {
    $optimized_count = $result;
    
    // Pobierz statystyki
    $simple_content = file('forex_data_simple.csv');
    $simple_count = count($simple_content) - 1; // Bez nagłówka
    
    $high_impact = 0;
    $with_data = 0;
    
    for ($i = 1; $i < count($simple_content); $i++) {
        $fields = str_getcsv(trim($simple_content[$i]));
        if (count($fields) >= 5) {
            if ($fields[4] == '3') $high_impact++;
            if ($fields[5] != 'N/A' && $fields[6] != 'N/A') $with_data++;
        }
    }
    
    echo "SUCCESS: Data optimized and saved at " . date('Y-m-d H:i:s') . "\n";
    echo "Events processed: $optimized_count\n";
    echo "High impact events: $high_impact\n";
    echo "Events with forecast data: $with_data\n";
    
} else {
    echo $result;
}

// Informacje diagnostyczne
echo "\n\n--- Diagnostic Info ---\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";

if (file_exists('forex_data_simple.csv')) {
    $file_size = filesize('forex_data_simple.csv');
    $line_count = count(file('forex_data_simple.csv'));
    echo "Simple file: {$file_size} bytes, {$line_count} lines\n";
}
?>
