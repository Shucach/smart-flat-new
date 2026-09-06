<?php

namespace Tests\Feature;

use App\Models\Weather;
use App\Services\WeatherService;
use Tests\TestCase;

class WeatherTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    //    public function test_example()
    //    {
    //        $response = $this->get('/');
    //
    //        $response->assertStatus(200);
    //    }

    public function test_get_data()
    {
        $weatherService = new WeatherService;
        $date = [
            'date_from' => date('Y-m-d H:i:s'),
            'type' => Weather::TYPE_1,
        ];
        $this->assertNotFalse($weatherService->getData($date));
    }
}
