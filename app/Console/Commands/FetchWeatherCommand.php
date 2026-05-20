<?php
// app/Console/Commands/FetchWeatherCommand.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\WeatherLog;
use DB;

class FetchWeatherCommand extends Command
{
    protected $signature = 'weather:fetch';
    protected $description = 'Fetch weather data from OpenWeather API and store in DB';

  public function handle()
{
    $apiKey = "b41274328f52dcb04a9f6aff4c48c85a";

    // Get all sites
    $sites = DB::table('master_sites')->get();

    foreach ($sites as $site) {
        if (!$site->latitude || !$site->longitude) {
            $this->warn("Skipping site {$site->site_name} (lat/lon missing).");
            continue;
        }

        $url = "https://api.openweathermap.org/data/2.5/weather?lat={$site->latitude}&lon={$site->longitude}&appid={$apiKey}&units=metric";

        $response = Http::get($url);

        if ($response->failed()) {
            $this->error("API Error for site {$site->site_name}: " . $response->body());
            continue;
        }
        $this->info("Site ID: {$site->id} (lat/lon missing).");
        $data = $response->json();

        WeatherLog::create([
            'city_name'     => $data['name'] ?? $site->site_name,
            'site_id'       => $site->id,   // link to site
            'temp'          => $data['main']['temp'] ?? null,
            'feels_like'    => $data['main']['feels_like'] ?? null,
            'temp_min'      => $data['main']['temp_min'] ?? null,
            'temp_max'      => $data['main']['temp_max'] ?? null,
            'humidity'      => $data['main']['humidity'] ?? null,
            'pressure'      => $data['main']['pressure'] ?? null,
            'wind_speed'    => $data['wind']['speed'] ?? null,
            'wind_deg'      => $data['wind']['deg'] ?? null,
            'clouds'        => $data['clouds']['all'] ?? null,
            'precipitation' => $data['rain']['1h'] ?? ($data['rain']['3h'] ?? ($data['snow']['1h'] ?? ($data['snow']['3h'] ?? 0))),
            'weather_main'  => $data['weather'][0]['main'] ?? null,
            'weather_desc'  => $data['weather'][0]['description'] ?? null,
            'sunrise'       => isset($data['sys']['sunrise']) ? date('Y-m-d H:i:s', $data['sys']['sunrise']) : null,
            'sunset'        => isset($data['sys']['sunset']) ? date('Y-m-d H:i:s', $data['sys']['sunset']) : null,
            isset($data['dt']) ? date('Y-m-d', $data['dt']) : date('Y-m-d'),  
            'source'          => 'Api', 
            'recorded_at'   => isset($data['dt']) ? date('Y-m-d H:i:s', $data['dt']) : now(),
        ]);

        $this->info("Weather data saved for site: {$site->site_name}");
    }
}

}
