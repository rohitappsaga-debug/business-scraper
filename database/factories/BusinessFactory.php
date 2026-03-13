<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        $address = $this->faker->streetAddress();

        return [
            'scraping_job_id' => null,
            'name' => $name,
            'category' => $this->faker->randomElement([
                'General Dentist', 'Cosmetic Dentistry', 'Pediatric Dentist',
                'Family Dentistry', 'Oral Surgery', 'Orthodontics',
            ]),
            'address' => $address,
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'country' => 'US',
            'phone' => $this->faker->phoneNumber(),
            'website' => 'https://www.'.$this->faker->domainName(),
            'email' => $this->faker->safeEmail(),
            'rating' => $this->faker->randomFloat(1, 3.0, 5.0),
            'reviews_count' => $this->faker->numberBetween(10, 500),
            'latitude' => $this->faker->latitude(24, 49),
            'longitude' => $this->faker->longitude(-125, -66),
            'source' => 'seeder',
            'dedup_hash' => Business::generateDedupHash($name, $address),
        ];
    }
}
