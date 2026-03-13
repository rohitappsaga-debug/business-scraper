<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessEmail;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        $businesses = [
            [
                'name' => 'Central Park Dental',
                'category' => 'Cosmetic Dentistry',
                'address' => '30 Central Park S',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'US',
                'phone' => '(212) 759-2993',
                'website' => 'https://cpdental.com',
                'email' => 'info@cpdental.com',
                'rating' => 4.7,
                'reviews_count' => 120,
                'source' => 'seeder',
            ],
            [
                'name' => 'Madison Ave Dental',
                'category' => 'General Dentist',
                'address' => '501 Madison Ave',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'US',
                'phone' => '(212) 355-2540',
                'website' => 'https://madisondental.ny',
                'email' => null,
                'rating' => 4.5,
                'reviews_count' => 85,
                'source' => 'seeder',
            ],
            [
                'name' => 'Empire Dental Group',
                'category' => 'Pediatric Dentist',
                'address' => '350 5th Ave',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'US',
                'phone' => '(212) 643-2044',
                'website' => 'https://empiredental.com',
                'email' => 'contact@empiredental.com',
                'rating' => 4.8,
                'reviews_count' => 200,
                'source' => 'seeder',
            ],
            [
                'name' => 'Gramercy Park Dentistry',
                'category' => 'General Dentistry',
                'address' => '225 E 19th St',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'US',
                'phone' => '(212) 674-6724',
                'website' => 'https://gramercydentist.com',
                'email' => 'hello@gramercydentist.com',
                'rating' => 4.6,
                'reviews_count' => 95,
                'source' => 'seeder',
            ],
            [
                'name' => 'Broadway Dental Arts',
                'category' => 'Oral Surgery',
                'address' => '1776 Broadway',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'US',
                'phone' => '(212) 246-1300',
                'website' => 'https://broadwaydentalny.com',
                'email' => 'support@broadwaydental.com',
                'rating' => 4.4,
                'reviews_count' => 60,
                'source' => 'seeder',
            ],
            [
                'name' => 'Upper West Side Dental',
                'category' => 'Family Dentistry',
                'address' => '2109 Broadway',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'US',
                'phone' => '(212) 873-0400',
                'website' => 'https://uwsdental.com',
                'email' => 'info@uwsdental.com',
                'rating' => 4.9,
                'reviews_count' => 310,
                'source' => 'seeder',
            ],
            [
                'name' => 'Tribeca Dental Studio',
                'category' => 'Cosmetic Dentistry',
                'address' => '114 Hudson St',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'US',
                'phone' => '(212) 966-6868',
                'website' => 'https://tribecadentalstudio.com',
                'email' => null,
                'rating' => 4.7,
                'reviews_count' => 150,
                'source' => 'seeder',
            ],
            [
                'name' => 'Chelsea Dental Associates',
                'category' => 'General Dentist',
                'address' => '254 W 23rd St',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'US',
                'phone' => '(212) 929-3030',
                'website' => 'https://chelseadental.com',
                'email' => 'hello@chelseadental.com',
                'rating' => 4.5,
                'reviews_count' => 78,
                'source' => 'seeder',
            ],
            [
                'name' => 'SoHo Dental Group',
                'category' => 'Orthodontics',
                'address' => '568 Broadway',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'US',
                'phone' => '(212) 965-7200',
                'website' => 'https://sohodentalgroup.com',
                'email' => null,
                'rating' => 4.6,
                'reviews_count' => 112,
                'source' => 'seeder',
            ],
            [
                'name' => 'Brooklyn Smiles',
                'category' => 'Family Dentistry',
                'address' => '345 Court St',
                'city' => 'Brooklyn',
                'state' => 'NY',
                'country' => 'US',
                'phone' => '(718) 625-1234',
                'website' => 'https://brooklynsmiles.com',
                'email' => 'contact@brooklynsmiles.com',
                'rating' => 4.8,
                'reviews_count' => 230,
                'source' => 'seeder',
            ],
        ];

        foreach ($businesses as $data) {
            $data['dedup_hash'] = Business::generateDedupHash($data['name'], $data['address']);

            $business = Business::firstOrCreate(
                ['dedup_hash' => $data['dedup_hash']],
                $data
            );

            if ($business->wasRecentlyCreated && ! empty($data['email'])) {
                BusinessEmail::firstOrCreate([
                    'business_id' => $business->id,
                    'email' => $data['email'],
                ], ['verified' => false]);
            }
        }
    }
}
