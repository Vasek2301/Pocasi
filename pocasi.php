<?php
// Zde je výchozí město
$city = "Praha"; // Základní město
$apiKey = "203183342e81ddb78da9743b6ab66348"; // Nahraďte svým API klíčem

// Ověření, zda byl formulář odeslán
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['city'])) {
    $city = htmlspecialchars(trim($_POST['city'])); // Načtení města z formuláře
}

// Dotaz na API pro získání aktuálních dat o počasí
$urlCurrent = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric";
$responseCurrent = file_get_contents($urlCurrent);
$dataCurrent = json_decode($responseCurrent, true);

// Dotaz na API pro získání předpovědi na následujících 5 dní
$urlForecast = "https://api.openweathermap.org/data/2.5/forecast?q={$city}&appid={$apiKey}&units=metric";
$responseForecast = file_get_contents($urlForecast);
$dataForecast = json_decode($responseForecast, true);

// Překlad počasí do češtiny
$weatherTranslations = [
    "clear sky" => "jasno",
    "few clouds" => "malo mraků",
    "scattered clouds" => "polojasno",
    "broken clouds" => "polojasno",
    "shower rain" => "přeháňky",
    "overcast clouds" => "zamračeno",
    "rain" => "déšť",
    "thunderstorm" => "bouřka",
    "snow" => "sníh",
    "mist" => "mlha",
    "light rain" => "mrholení",
    // další potřebné překlady
];

// Array s názvy dnů bez diakritiky
$daysOfWeek = [
    'Monday' => 'Pondeli',
    'Tuesday' => 'Utery',
    'Wednesday' => 'Streda',
    'Thursday' => 'Ctvrtek',
    'Friday' => 'Patek',
    'Saturday' => 'Sobota',
    'Sunday' => 'Nedele'
];
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Počasí</title>
    <link rel="stylesheet" href="styless.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.js"></script>
    <script>
        function toggleTheme() {
            document.body.classList.toggle("dark-mode");
        }
    </script>
</head>
<body onload="document.getElementById('city').value = loadCity();">
    <header>
        <h1>Aktuální Počasí</h1>
        <nav>
            <ul>
                <li>Přepnout do tmavého režimu</li>
                <li>
                    <label class="switch">
                        <input type="checkbox" onclick="toggleTheme()">
                        <span class="slider"></span>
                    </label>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="container">
            <form method="POST" action="">
                <label for="city">Zadejte název města:</label>
                <input type="text" id="city" name="city" required>
                <input type="submit" value="Zobrazit počasí">
            </form>

            <div class="weather-info">
                <?php if (isset($dataCurrent) && $dataCurrent['cod'] == 200): ?>
                    <h2>Předpověď pro <?php echo htmlspecialchars($dataCurrent['name']); ?></h2>
                    <img src="http://openweathermap.org/img/wn/<?php echo $dataCurrent['weather'][0]['icon']; ?>@2x.png" alt="Ikona počasí">
                    <p>Teplota vzduchu: <?php echo round($dataCurrent['main']['temp']); ?> °C</p>
                    <p>Pocitová teplota: <?php echo round($dataCurrent['main']['feels_like']); ?> °C</p>
                    <p>Vlhkost: <?php echo htmlspecialchars($dataCurrent['main']['humidity']); ?>%</p>
                    <p>Rychlost větru: <?php echo round($dataCurrent['wind']['speed'] * 3.6, 2); ?> km/h</p>
                    <p>Stav: <?php echo isset($weatherTranslations[$dataCurrent['weather'][0]['description']]) ? $weatherTranslations[$dataCurrent['weather'][0]['description']] : $dataCurrent['weather'][0]['description']; ?></p>
                <?php else: ?>
                    <p>Nemohu načíst data o počasí. Zkontrolujte prosím zadané město nebo API klíč.</p>
                <?php endif; ?>
            </div>
<br>
<div class="forecast-container">
    <h2 class="forecast-title">Předpověď na 5 dní</h2>
    <div class="forecast-info">
        <table>
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Teplota (°C)</th>
                            <th>Stav</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (isset($dataForecast) && $dataForecast['cod'] == "200"):
                            foreach ($dataForecast['list'] as $forecast):
                                if (date("H:i", strtotime($forecast['dt_txt'])) == "12:00"): // Zobrazení pouze počasí v poledne
                                    $timestamp = strtotime($forecast['dt_txt']);
                                    $dayName = date("l", $timestamp); // Získání názvu dne (v angličtině)
                                    $dayNameCzech = $daysOfWeek[$dayName]; // Převod na český název bez diakritiky
                        ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($dayNameCzech . ", " . date("d.m.Y", $timestamp)); ?></td>
                                        <td><?php echo round($forecast['main']['temp']); ?> °C</td>
                                        <td><?php echo isset($weatherTranslations[$forecast['weather'][0]['description']]) ? $weatherTranslations[$forecast['weather'][0]['description']] : $forecast['weather'][0]['description']; ?></td>
                                    </tr>
                        <?php
                                endif;
                            endforeach;
                        else:
                        ?>
                            <tr><td colspan="3">Předpověď není k dispozici.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <br>
            <div class="map-container">
    <h2 class="map-title">Mapa</h2>
    <div id="map"></div>
</div>
        </div>
    </main>

    <footer>
        <p>© 2024 Václav Cieslar. Všechna práva vyhrazena.</p>
    </footer>

    <script>
        // Zobrazení mapy s použitím Leaflet.js
        document.addEventListener("DOMContentLoaded", function() {
            const lat = <?php echo isset($dataCurrent['coord']) ? $dataCurrent['coord']['lat'] : 'null'; ?>;
            const lon = <?php echo isset($dataCurrent['coord']) ? $dataCurrent['coord']['lon'] : 'null'; ?>;
            const cityName = "<?php echo isset($dataCurrent['name']) ? htmlspecialchars($dataCurrent['name']) : ''; ?>";

            if (lat && lon) {
                const map = L.map('map').setView([lat, lon], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                L.marker([lat, lon]).addTo(map)
                    .bindPopup(cityName)
                    .openPopup();
            } else {
                console.error("Souřadnice nenalezeny. Zkontrolujte načítání dat.");
            }
        });
    </script>
</body>
</html>
