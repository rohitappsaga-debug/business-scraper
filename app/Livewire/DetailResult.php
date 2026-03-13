<?php

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class DetailResult extends Component
{
    public int $id = 0;

    /** @var array<string, mixed> */
    public array $business = [];

    /** @var array<int, array<string, mixed>> */
    protected array $allBusinesses = [
        [
            'id' => 1,
            'name' => 'Central Park Dental',
            'category' => 'Cosmetic Dentistry',
            'description' => 'Modern dental studio providing comprehensive cosmetic treatments including veneers, teeth whitening, and invisalign. Located in the heart of Manhattan with views of Central Park.',
            'address' => '30 Central Park S, New York, NY 10019, United States',
            'city_state' => 'New York, NY',
            'postal_country' => '10019, United States',
            'phone' => '+1 (212) 555-0198',
            'email' => 'hello@centralparkdental.com',
            'website' => 'centralparkdental.com',
            'website_url' => 'https://centralparkdental.com',
            'maps_url' => 'https://maps.google.com/?q=30+Central+Park+S,+New+York,+NY+10019',
            'rating' => '4.7',
            'review_count' => 120,
            'status' => 'Active',
            'verification' => 'Verified',
            'email_status' => 'Found',
            'last_updated' => '12 Oct 2023, 14:45 PM',
            'hours' => [
                ['day' => 'Mon - Fri', 'time' => '9:00 AM - 6:00 PM'],
                ['day' => 'Saturday', 'time' => '10:00 AM - 4:00 PM'],
                ['day' => 'Sunday', 'time' => 'Closed'],
            ],
            'social' => [
                'facebook' => 'https://facebook.com',
                'instagram' => 'https://instagram.com',
                'youtube' => 'https://youtube.com',
            ],
        ],
        [
            'id' => 2,
            'name' => 'Madison Ave Dental',
            'category' => 'General Dentist',
            'description' => 'Comprehensive dental care for every stage of life, from routine cleanings to complex restorations.',
            'address' => '501 Madison Ave, New York, NY 10022, United States',
            'city_state' => 'New York, NY',
            'postal_country' => '10022, United States',
            'phone' => '+1 (212) 355-2540',
            'email' => '',
            'website' => 'madisondental.ny',
            'website_url' => 'https://madisondental.ny',
            'maps_url' => 'https://maps.google.com/?q=501+Madison+Ave,+New+York,+NY+10022',
            'rating' => '4.5',
            'review_count' => 85,
            'status' => 'Active',
            'verification' => 'Verified',
            'email_status' => 'Not Found',
            'last_updated' => '10 Oct 2023, 09:00 AM',
            'hours' => [
                ['day' => 'Mon - Fri', 'time' => '8:00 AM - 5:00 PM'],
                ['day' => 'Saturday', 'time' => '9:00 AM - 2:00 PM'],
                ['day' => 'Sunday', 'time' => 'Closed'],
            ],
            'social' => [],
        ],
        [
            'id' => 3,
            'name' => 'Empire Dental Group',
            'category' => 'Pediatric Dentist',
            'description' => 'Specializing in dental care for children and adolescents.',
            'address' => '350 5th Ave, New York, NY 10118, United States',
            'city_state' => 'New York, NY',
            'postal_country' => '10118, United States',
            'phone' => '+1 (212) 643-2044',
            'email' => 'contact@empiredental.com',
            'website' => 'empiredental.com',
            'website_url' => 'https://empiredental.com',
            'maps_url' => 'https://maps.google.com/?q=350+5th+Ave,+New+York,+NY+10118',
            'rating' => '4.8',
            'review_count' => 200,
            'status' => 'Active',
            'verification' => 'Verified',
            'email_status' => 'Found',
            'last_updated' => '11 Oct 2023, 11:30 AM',
            'hours' => [
                ['day' => 'Mon - Fri', 'time' => '9:00 AM - 6:00 PM'],
                ['day' => 'Saturday', 'time' => '10:00 AM - 3:00 PM'],
                ['day' => 'Sunday', 'time' => 'Closed'],
            ],
            'social' => [
                'facebook' => 'https://facebook.com',
            ],
        ],
        [
            'id' => 4,
            'name' => 'Gramercy Park Dentistry',
            'category' => 'General Dentistry',
            'description' => 'Full-service dental practice in the heart of Gramercy Park.',
            'address' => '225 E 19th St, New York, NY 10003, United States',
            'city_state' => 'New York, NY',
            'postal_country' => '10003, United States',
            'phone' => '+1 (212) 674-6724',
            'email' => 'hello@gramercydentist.com',
            'website' => 'gramercydentist.com',
            'website_url' => 'https://gramercydentist.com',
            'maps_url' => 'https://maps.google.com/?q=225+E+19th+St,+New+York,+NY+10003',
            'rating' => '4.6',
            'review_count' => 95,
            'status' => 'Active',
            'verification' => 'Verified',
            'email_status' => 'Found',
            'last_updated' => '09 Oct 2023, 14:00 PM',
            'hours' => [
                ['day' => 'Mon - Fri', 'time' => '8:30 AM - 5:30 PM'],
                ['day' => 'Saturday', 'time' => 'Closed'],
                ['day' => 'Sunday', 'time' => 'Closed'],
            ],
            'social' => [],
        ],
        [
            'id' => 5,
            'name' => 'Broadway Dental Arts',
            'category' => 'Oral Surgery',
            'description' => 'Expert oral surgery and dental care on Broadway.',
            'address' => '1776 Broadway, New York, NY 10019, United States',
            'city_state' => 'New York, NY',
            'postal_country' => '10019, United States',
            'phone' => '+1 (212) 246-1300',
            'email' => 'support@broadwaydental.com',
            'website' => 'broadwaydentalny.com',
            'website_url' => 'https://broadwaydentalny.com',
            'maps_url' => 'https://maps.google.com/?q=1776+Broadway,+New+York,+NY+10019',
            'rating' => '4.4',
            'review_count' => 60,
            'status' => 'Active',
            'verification' => 'Verified',
            'email_status' => 'Found',
            'last_updated' => '08 Oct 2023, 10:15 AM',
            'hours' => [
                ['day' => 'Mon - Fri', 'time' => '9:00 AM - 5:00 PM'],
                ['day' => 'Saturday', 'time' => 'Closed'],
                ['day' => 'Sunday', 'time' => 'Closed'],
            ],
            'social' => [],
        ],
        [
            'id' => 6,
            'name' => 'Upper West Side Dental',
            'category' => 'Family Dentistry',
            'description' => 'Family-oriented dental practice serving the Upper West Side community.',
            'address' => '2109 Broadway, New York, NY 10023, United States',
            'city_state' => 'New York, NY',
            'postal_country' => '10023, United States',
            'phone' => '+1 (212) 873-0400',
            'email' => 'info@uwsdental.com',
            'website' => 'uwsdental.com',
            'website_url' => 'https://uwsdental.com',
            'maps_url' => 'https://maps.google.com/?q=2109+Broadway,+New+York,+NY+10023',
            'rating' => '4.9',
            'review_count' => 310,
            'status' => 'Active',
            'verification' => 'Verified',
            'email_status' => 'Found',
            'last_updated' => '07 Oct 2023, 08:00 AM',
            'hours' => [
                ['day' => 'Mon - Fri', 'time' => '8:00 AM - 7:00 PM'],
                ['day' => 'Saturday', 'time' => '9:00 AM - 4:00 PM'],
                ['day' => 'Sunday', 'time' => 'Closed'],
            ],
            'social' => [
                'facebook' => 'https://facebook.com',
                'instagram' => 'https://instagram.com',
            ],
        ],
        [
            'id' => 7,
            'name' => 'Tribeca Dental Studio',
            'category' => 'Cosmetic Dentistry',
            'description' => 'Boutique cosmetic dental studio in Tribeca.',
            'address' => '114 Hudson St, New York, NY 10013, United States',
            'city_state' => 'New York, NY',
            'postal_country' => '10013, United States',
            'phone' => '+1 (212) 966-6868',
            'email' => '',
            'website' => 'tribecadentalstudio.com',
            'website_url' => 'https://tribecadentalstudio.com',
            'maps_url' => 'https://maps.google.com/?q=114+Hudson+St,+New+York,+NY+10013',
            'rating' => '4.7',
            'review_count' => 150,
            'status' => 'Active',
            'verification' => 'Verified',
            'email_status' => 'Not Found',
            'last_updated' => '06 Oct 2023, 16:00 PM',
            'hours' => [
                ['day' => 'Mon - Fri', 'time' => '10:00 AM - 6:00 PM'],
                ['day' => 'Saturday', 'time' => '11:00 AM - 4:00 PM'],
                ['day' => 'Sunday', 'time' => 'Closed'],
            ],
            'social' => [
                'instagram' => 'https://instagram.com',
            ],
        ],
        [
            'id' => 8,
            'name' => 'Chelsea Dental Associates',
            'category' => 'General Dentist',
            'description' => 'Comprehensive dental care in Chelsea for the whole family.',
            'address' => '254 W 23rd St, New York, NY 10011, United States',
            'city_state' => 'New York, NY',
            'postal_country' => '10011, United States',
            'phone' => '+1 (212) 929-3030',
            'email' => 'hello@chelseadental.com',
            'website' => 'chelseadental.com',
            'website_url' => 'https://chelseadental.com',
            'maps_url' => 'https://maps.google.com/?q=254+W+23rd+St,+New+York,+NY+10011',
            'rating' => '4.5',
            'review_count' => 78,
            'status' => 'Active',
            'verification' => 'Verified',
            'email_status' => 'Found',
            'last_updated' => '05 Oct 2023, 12:00 PM',
            'hours' => [
                ['day' => 'Mon - Fri', 'time' => '9:00 AM - 6:00 PM'],
                ['day' => 'Saturday', 'time' => '10:00 AM - 3:00 PM'],
                ['day' => 'Sunday', 'time' => 'Closed'],
            ],
            'social' => [],
        ],
        [
            'id' => 9,
            'name' => 'SoHo Dental Group',
            'category' => 'Orthodontics',
            'description' => 'Orthodontic specialists offering braces and Invisalign in SoHo.',
            'address' => '568 Broadway, New York, NY 10012, United States',
            'city_state' => 'New York, NY',
            'postal_country' => '10012, United States',
            'phone' => '+1 (212) 965-7200',
            'email' => '',
            'website' => 'sohodentalgroup.com',
            'website_url' => 'https://sohodentalgroup.com',
            'maps_url' => 'https://maps.google.com/?q=568+Broadway,+New+York,+NY+10012',
            'rating' => '4.6',
            'review_count' => 112,
            'status' => 'Active',
            'verification' => 'Verified',
            'email_status' => 'Not Found',
            'last_updated' => '04 Oct 2023, 09:30 AM',
            'hours' => [
                ['day' => 'Mon - Fri', 'time' => '9:00 AM - 5:00 PM'],
                ['day' => 'Saturday', 'time' => 'Closed'],
                ['day' => 'Sunday', 'time' => 'Closed'],
            ],
            'social' => [],
        ],
        [
            'id' => 10,
            'name' => 'Brooklyn Smiles',
            'category' => 'Family Dentistry',
            'description' => 'Friendly family dental practice in the heart of Brooklyn.',
            'address' => '345 Court St, Brooklyn, NY 11231, United States',
            'city_state' => 'Brooklyn, NY',
            'postal_country' => '11231, United States',
            'phone' => '+1 (718) 625-1234',
            'email' => 'contact@brooklynsmiles.com',
            'website' => 'brooklynsmiles.com',
            'website_url' => 'https://brooklynsmiles.com',
            'maps_url' => 'https://maps.google.com/?q=345+Court+St,+Brooklyn,+NY+11231',
            'rating' => '4.8',
            'review_count' => 230,
            'status' => 'Active',
            'verification' => 'Verified',
            'email_status' => 'Found',
            'last_updated' => '03 Oct 2023, 10:00 AM',
            'hours' => [
                ['day' => 'Mon - Fri', 'time' => '9:00 AM - 6:00 PM'],
                ['day' => 'Saturday', 'time' => '10:00 AM - 4:00 PM'],
                ['day' => 'Sunday', 'time' => 'Closed'],
            ],
            'social' => [
                'facebook' => 'https://facebook.com',
                'instagram' => 'https://instagram.com',
            ],
        ],
    ];

    public function mount(int $id): void
    {
        $this->id = $id;
        $found = array_filter($this->allBusinesses, fn (array $b): bool => $b['id'] === $id);
        $this->business = $found ? array_values($found)[0] : [];
    }

    public function backToResults(): void
    {
        $this->redirectRoute('result');
    }

    public function exportLead(): void
    {
        session()->flash('message', 'Lead exported successfully.');
    }

    public function openGoogleMaps(): void
    {
        if (!empty($this->business['maps_url'])) {
            $this->dispatch('open-url', url: $this->business['maps_url']);
        }
    }

    public function render(): View
    {
        return view('livewire.detail-result', [
            'business' => $this->business,
        ])->layout('layouts.app', ['title' => 'Business Lead Details']);
    }
}
