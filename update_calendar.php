<?php
// update_calendar.php - Aktualizuje i optymalizuje dane Forex dla MT5 (zgodnie z workflow)
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: '*');

function downloadWithCurl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MQL5 Economic Calendar Downloader 1.0');
    
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && !empty($data)) {
        return $data;
    }
    
    return false;
}

function convert_time_to_24h($time_str) {
    if (empty($time_str)) return '00:00';
    
    $time_str = strtolower(trim($time_str));
    if (!preg_match('/(\d+):?(\d+)?\s*(am|pm)/', $time_str, $matches)) {
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

function convert_date_format($date_str) {
    try {
        if ($date_str && strpos($date_str, '-') !== false) {
            $parts = explode('-', $date_str);
            if (count($parts) === 3) {
                $month = $parts[0];
                $day = $parts[1];
                $year = $parts[2];
                return $year . '.' . $month . '.' . $day;
            }
        }
    } catch (Exception $e) {
        // Ignore and return original
    }
    return $date_str;
}

function clean_forecast_value($value) {
    if (empty($value) || trim($value) === '') {
        return '0';
    }
    
    $value = trim($value);
    
    // Usuń znaki specjalne jak |
    if (strpos($value, '|') !== false) {
        $value = explode('|', $value)[0];
    }
    
    // Zamień procenty na liczby (usuwając %)
    if (strpos($value, '%') !== false) {
        $value = str_replace('%', '', $value);
    }
    
    // Zamień litery na liczby (K=1000, M=1000000, B=1000000000)
    if (strpos($value, 'K') !== false) {
        $value = strval(floatval(str_replace('K', '', $value)) * 1000);
    } elseif (strpos($value, 'M') !== false) {
        $value = strval(floatval(str_replace('M', '', $value)) * 1000000);
    } elseif (strpos($value, 'B') !== false) {
        $value = strval(floatval(str_replace('B', '', $value)) * 1000000000);
    }
    
    // Usuń przecinki z liczb
    $value = str_replace(',', '', $value);
    
    return $value;
}

function downloadAndOptimizeData() {
    $csv_url = "https://nfs.faireconomy.media/ff_calendar_thisweek.csv";
    
    // Próbuj pobrać dane - najpierw file_get_contents, potem curl
    $raw_data = @file_get_contents($csv_url);
    
    if ($raw_data === FALSE || empty($raw_data)) {
        // Fallback do curl
        $raw_data = downloadWithCurl($csv_url);
        if ($raw_data === FALSE) {
            return "ERROR: Could not fetch data from Forex Factory (tried both methods)";
        }
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
    
    // Nagłówki dla zoptymalizowanego pliku (pełnego)
    $optimized_rows[] = ['Title', 'Country', 'Date', 'Time', 'Time24h', 'Impact', 'Forecast', 'Previous', 'HasForecast', 'HasPrevious', 'Importance'];
    
    // Nagłówki dla uproszczonego pliku
    $simple_rows[] = ['Title', 'Country', 'Date', 'Time24h', 'Importance', 'Forecast', 'Previous'];
    
    $seen_events = [];
    
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
        
        // Konwertuj czas i datę
        $time24h = convert_time_to_24h($time);
        $date_mql5 = convert_date_format($date);
        
        // Oczyść wartości forecast/previous
        $forecast_clean = clean_forecast_value($forecast);
        $previous_clean = clean_forecast_value($previous);
        
        // Wartość numeryczna dla impact
        $importance = (string)($impact_to_importance[$impact] ?? 0);
        
        // Flagi czy ma forecast/previous (w oparciu o oryginalne wartości, nie wyczyszczone)
        $has_forecast = (empty($forecast) || trim($forecast) === '') ? '0' : '1';
        $has_previous = (empty($previous) || trim($previous) === '') ? '0' : '1';
        
        // Dodaj do zoptymalizowanego pliku (pełnego)
        $optimized_rows[] = [
            $title, $country, $date, $time, $time24h, $impact,
            $forecast, $previous, $has_forecast, $has_previous, $importance
        ];
        
        // Dodaj do uproszczonego pliku (dla MQL5)
        $simple_rows[] = [
            $title, $country, $date_mql5, $time24h, $importance, $forecast_clean, $previous_clean
        ];
        
        $processed_count++;
    }
    
    // Zapisz zoptymalizowany plik (pełny)
    $optimized_file = fopen('forex_data_optimized.csv', 'w');
    foreach ($optimized_rows as $row) {
        fputcsv($optimized_file, $row);
    }
    fclose($optimized_file);
    
    // Zapisz uproszczony plik (dla MQL5)
    $simple_file = fopen('forex_data_simple.csv', 'w');
    foreach ($simple_rows as $row) {
        fputcsv($simple_file, $row);
    }
    fclose($simple_file);
    
    return $processed_count;
}

// Wykonaj aktualizację
echo "🔄 Starting economic calendar update...\n";
$result = downloadAndOptimizeData();

if (is_numeric($result)) {
    $optimized_count = $result;
    
    // Pobierz statystyki z pliku uproszczonego
    $simple_content = file('forex_data_simple.csv');
    $simple_count = count($simple_content) - 1;
    
    $high_impact = 0;
    $with_data = 0;
    
    for ($i = 1; $i < count($simple_content); $i++) {
        $fields = str_getcsv(trim($simple_content[$i]));
        if (count($fields) >= 5) {
            if ($fields[4] == '3') $high_impact++;
            if ($fields[5] != '0' && $fields[6] != '0') $with_data++;
        }
    }
    
    echo "✅ SUCCESS: Data optimized and saved at " . date('Y-m-d H:i:s') . "\n";
    echo "📊 Events processed: $optimized_count\n";
    echo "🎯 High impact events: $high_impact\n";
    echo "📈 Events with forecast data: $with_data\n";
    
} else {
    echo "❌ $result\n";
}

// Informacje diagnostyczne
echo "\n--- Diagnostic Info ---\n";
echo "📅 Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "🐘 PHP Version: " . PHP_VERSION . "\n";

if (file_exists('forex_data_simple.csv')) {
    $file_size = filesize('forex_data_simple.csv');
    $line_count = count(file('forex_data_simple.csv'));
    echo "📁 Simple file: {$file_size} bytes, {$line_count} lines\n";
    
    // Pokaż przykładowe dane
    echo "🔍 Sample data (first 3 events):\n";
    $sample_lines = array_slice(file('forex_data_simple.csv'), 1, 3); // Pomijamy nagłówek, bierzemy 3 wiersze
    foreach ($sample_lines as $line) {
        echo "   " . trim($line) . "\n";
    }
}
?>
