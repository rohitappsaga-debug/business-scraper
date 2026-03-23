<?php

namespace Database\Seeders;

use App\Models\SocialLink;
use Illuminate\Database\Seeder;

class SocialLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SocialLink::create([
            'business_id' => 1,
            'platform' => 'facebook',
            'url' => 'https://facebook.com/testbusiness',
            'is_active' => true,
        ]);

        SocialLink::create([
            'business_id' => 1,
            'platform' => 'instagram',
            'url' => 'https://instagram.com/testbusiness',
            'is_active' => true,
        ]);
    }
}
