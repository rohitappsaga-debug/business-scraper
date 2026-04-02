<?php

namespace App\Services;

use Illuminate\Support\Str;

class GeoExpansionService
{
    /**
     * Map of countries to their states and major cities.
     * In a real production app, this could be stored in a database or fetched from a geo-API.
     */
    protected array $geoData = [
        'India' => [
            'states' => [
                'Andhra Pradesh' => ['Visakhapatnam', 'Vijayawada', 'Guntur', 'Nellore', 'Kurnool', 'Tirupati'],
                'Arunachal Pradesh' => ['Itanagar', 'Naharlagun'],
                'Assam' => ['Guwahati', 'Silchar', 'Dibrugarh', 'Jorhat', 'Nagaon'],
                'Bihar' => ['Patna', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Purnia', 'Darbhanga'],
                'Chhattisgarh' => ['Raipur', 'Bhilai', 'Bilaspur', 'Korba'],
                'Goa' => ['Panaji', 'Vasco da Gama', 'Margao'],
                'Gujarat' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Junagadh', 'Gandhinagar', 'Anand', 'Navsari', 'Morbi', 'Nadiad', 'Surendranagar', 'Bharuch', 'Mehsana', 'Bhuj', 'Porbandar', 'Palanpur', 'Valsad', 'Vapi'],
                'Haryana' => ['Faridabad', 'Gurugram', 'Panipat', 'Ambala', 'Yamunanagar', 'Rohtak', 'Hisar', 'Karnal'],
                'Himachal Pradesh' => ['Shimla', 'Dharamshala', 'Solan', 'Mandi'],
                'Jharkhand' => ['Ranchi', 'Jamshedpur', 'Dhanbad', 'Bokaro'],
                'Karnataka' => ['Bengaluru', 'Mysuru', 'Hubli-Dharwad', 'Mangaluru', 'Belagavi', 'Kalaburagi', 'Davanagere', 'Ballari', 'Vijayapura', 'Shivamogga'],
                'Kerala' => ['Thiruvananthapuram', 'Kochi', 'Kozhikode', 'Kollam', 'Thrissur', 'Alappuzha', 'Palakkad', 'Malappuram'],
                'Madhya Pradesh' => ['Indore', 'Bhopal', 'Jabalpur', 'Gwalior', 'Ujjain', 'Sagar', 'Ratlam'],
                'Maharashtra' => ['Mumbai', 'Pune', 'Nagpur', 'Thane', 'Nashik', 'Kalyan-Dombivli', 'Vasai-Virar', 'Aurangabad', 'Navi Mumbai', 'Solapur', 'Mira-Bhayandar', 'Bhiwandi', 'Amravati', 'Nanded', 'Kolhapur', 'Akola', 'Panvel', 'Latur', 'Dhule'],
                'Manipur' => ['Imphal'],
                'Meghalaya' => ['Shillong'],
                'Mizoram' => ['Aizawl'],
                'Nagaland' => ['Kohima', 'Dimapur'],
                'Odisha' => ['Bhubaneswar', 'Cuttack', 'Rourkela', 'Sambalpur', 'Berhampur'],
                'Punjab' => ['Ludhiana', 'Amritsar', 'Jalandhar', 'Patiala', 'Bathinda', 'Mohali'],
                'Rajasthan' => ['Jaipur', 'Jodhpur', 'Kota', 'Bikaner', 'Ajmer', 'Udaipur', 'Bhilwara'],
                'Sikkim' => ['Gangtok'],
                'Tamil Nadu' => ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem', 'Tiruppur', 'Erode', 'Vellore', 'Thoothukudi', 'Nagercoil'],
                'Telangana' => ['Hyderabad', 'Warangal', 'Nizamabad', 'Khammam', 'Karimnagar', 'Mahbubnagar'],
                'Tripura' => ['Agartala'],
                'Uttar Pradesh' => ['Lucknow', 'Kanpur', 'Ghaziabad', 'Agra', 'Meerut', 'Varanasi', 'Prayagraj', 'Bareilly', 'Aligarh', 'Moradabad', 'Saharanpur', 'Gorakhpur', 'Noida', 'Firozabad', 'Jhansi'],
                'Uttarakhand' => ['Dehradun', 'Haridwar', 'Roorkee', 'Haldwani'],
                'West Bengal' => ['Kolkata', 'Howrah', 'Durgapur', 'Asansol', 'Siliguri', 'Maheshtala', 'Rajpur Sonarpur'],
                'Delhi' => ['New Delhi', 'North Delhi', 'South Delhi', 'East Delhi', 'West Delhi'],
                'Jammu & Kashmir' => ['Srinagar', 'Jammu', 'Anantnag'],
                'Puducherry' => ['Pondicherry'],
                'Chandigarh' => ['Chandigarh'],
            ],
            'major_cities' => ['Mumbai', 'Delhi', 'Bengaluru', 'Hyderabad', 'Ahmedabad', 'Chennai', 'Kolkata', 'Surat', 'Pune', 'Jaipur'],
        ],
        'USA' => [
            'states' => [
                'California' => ['Los Angeles', 'San Diego', 'San Jose', 'San Francisco', 'Fresno', 'Sacramento', 'Long Beach', 'Oakland'],
                'Texas' => ['Houston', 'San Antonio', 'Dallas', 'Austin', 'Fort Worth', 'El Paso', 'Arlington', 'Corpus Christi'],
                'Florida' => ['Jacksonville', 'Miami', 'Tampa', 'Orlando', 'St. Petersburg'],
                'New York' => ['New York City', 'Buffalo', 'Rochester', 'Yonkers'],
                'Illinois' => ['Chicago', 'Aurora', 'Rockford', 'Joliet'],
                'Pennsylvania' => ['Philadelphia', 'Pittsburgh', 'Allentown'],
                'Ohio' => ['Columbus', 'Cleveland', 'Cincinnati'],
                'Georgia' => ['Atlanta', 'Augusta', 'Columbus'],
                'North Carolina' => ['Charlotte', 'Raleigh', 'Greensboro'],
                'Michigan' => ['Detroit', 'Grand Rapids', 'Warren'],
                'Washington' => ['Seattle', 'Spokane', 'Tacoma'],
                'Arizona' => ['Phoenix', 'Tucson', 'Mesa'],
            ],
            'major_cities' => ['New York City', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego'],
        ],
        'UK' => [
            'states' => [
                'England' => ['London', 'Birmingham', 'Manchester', 'Leeds', 'Liverpool', 'Newcastle', 'Sheffield', 'Bristol', 'Nottingham', 'Leicester'],
                'Scotland' => ['Glasgow', 'Edinburgh', 'Aberdeen', 'Dundee'],
                'Wales' => ['Cardiff', 'Swansea', 'Newport'],
                'Northern Ireland' => ['Belfast', 'Londonderry'],
            ],
            'major_cities' => ['London', 'Birmingham', 'Manchester', 'Glasgow'],
        ],
        'Canada' => [
            'states' => [
                'Ontario' => ['Toronto', 'Ottawa', 'Mississauga', 'Brampton', 'Hamilton'],
                'Quebec' => ['Montreal', 'Quebec City', 'Laval'],
                'British Columbia' => ['Vancouver', 'Surrey', 'Victoria'],
                'Alberta' => ['Calgary', 'Edmonton'],
            ],
            'major_cities' => ['Toronto', 'Montreal', 'Vancouver', 'Ottawa'],
        ],
        'Australia' => [
            'states' => [
                'New South Wales' => ['Sydney', 'Newcastle', 'Wollongong'],
                'Victoria' => ['Melbourne', 'Geelong', 'Ballarat'],
                'Queensland' => ['Brisbane', 'Gold Coast', 'Townsville'],
                'Western Australia' => ['Perth'],
            ],
            'major_cities' => ['Sydney', 'Melbourne', 'Brisbane', 'Perth'],
        ],
        'Germany' => [
            'states' => [
                'Bavaria' => ['Munich', 'Nuremberg', 'Augsburg'],
                'Berlin' => ['Berlin'],
                'Hamburg' => ['Hamburg'],
                'North Rhine-Westphalia' => ['Cologne', 'Düsseldorf', 'Dortmund', 'Essen'],
                'Baden-Württemberg' => ['Stuttgart', 'Mannheim', 'Karlsruhe'],
            ],
            'major_cities' => ['Berlin', 'Hamburg', 'Munich', 'Cologne', 'Frankfurt'],
        ],
        'France' => [
            'states' => [
                'Île-de-France' => ['Paris', 'Boulogne-Billancourt', 'Saint-Denis'],
                'Provence-Alpes-Côte d\'Azur' => ['Marseille', 'Nice', 'Toulon'],
                'Auvergne-Rhône-Alpes' => ['Lyon', 'Saint-Étienne', 'Grenoble'],
            ],
            'major_cities' => ['Paris', 'Marseille', 'Lyon', 'Toulouse', 'Nice'],
        ],
        'Japan' => [
            'states' => [
                'Tokyo' => ['Shinjuku', 'Shibuya', 'Minato'],
                'Osaka' => ['Osaka City', 'Sakai', 'Higashiosaka'],
                'Aichi' => ['Nagoya', 'Toyohashi', 'Okazaki'],
                'Kanagawa' => ['Yokohama', 'Kawasaki', 'Sagamihara'],
            ],
            'major_cities' => ['Tokyo', 'Yokohama', 'Osaka', 'Nagoya', 'Sapporo'],
        ],
        'Brazil' => [
            'states' => [
                'São Paulo' => ['São Paulo City', 'Guarulhos', 'Campinas'],
                'Rio de Janeiro' => ['Rio de Janeiro City', 'São Gonçalo', 'Duque de Caxias'],
                'Minas Gerais' => ['Belo Horizonte', 'Uberlândia', 'Contagem'],
            ],
            'major_cities' => ['São Paulo', 'Rio de Janeiro', 'Brasília', 'Salvador'],
        ],
        'UAE' => [
            'states' => [
                'Dubai' => ['Dubai City', 'Jebel Ali'],
                'Abu Dhabi' => ['Abu Dhabi City', 'Al Ain'],
                'Sharjah' => ['Sharjah City'],
            ],
            'major_cities' => ['Dubai', 'Abu Dhabi', 'Sharjah'],
        ],
    ];

    /**
     * Determine the geographic "level" of a location string.
     */
    public function detectLevel(string $location): string
    {
        $location = trim($location);
        
        // 1. Check if it's a known country
        foreach ($this->geoData as $country => $data) {
            if (Str::lower($location) === Str::lower($country)) {
                return 'COUNTRY';
            }
        }

        // 2. Check if it's a known state
        foreach ($this->geoData as $country => $data) {
            foreach ($data['states'] as $state => $cities) {
                if (Str::lower($location) === Str::lower($state)) {
                    return 'STATE';
                }
            }
        }

        // 3. Check if it's a known major city
        foreach ($this->geoData as $country => $data) {
            foreach ($data['major_cities'] as $city) {
                if (Str::lower($location) === Str::lower($city)) {
                    return 'CITY';
                }
            }
        }

        // 4. Heuristic detection for common keywords
        $lowered = Str::lower($location);
        if (Str::contains($lowered, 'district')) return 'DISTRICT';
        if (Str::contains($lowered, 'county')) return 'DISTRICT';
        if (Str::contains($lowered, 'village')) return 'VILLAGE';
        if (Str::contains($lowered, 'block')) return 'DISTRICT';
        if (Str::contains($lowered, 'taluka')) return 'DISTRICT';

        // 5. If it contains a comma (e.g. "City, State"), default to CITY
        if (Str::contains($location, ',')) return 'CITY';

        // Default to city/locality if unknown
        return 'CITY';
    }

    /**
     * Expand a location into sub-queries based on its level.
     */
    public function expand(string $location): array
    {
        $level = $this->detectLevel($location);
        $queries = [];

        if ($level === 'COUNTRY') {
            $country = $this->getMatchedCountry($location);
            if ($country) {
                // If country, return all states for parallel processing
                foreach ($this->geoData[$country]['states'] as $state => $cities) {
                    $queries[] = ['location' => $state, 'level' => 'STATE'];
                }
            }
        } elseif ($level === 'STATE') {
            $stateInfo = $this->getMatchedState($location);
            if ($stateInfo) {
                // If state, return all major cities in that state
                foreach ($stateInfo['cities'] as $city) {
                    $queries[] = ['location' => "$city, {$stateInfo['state']}", 'level' => 'CITY'];
                }
            }
        }

        return $queries;
    }

    /**
     * Get target result count based on level.
     */
    public function getTargetCount(string $level): int
    {
        return match ($level) {
            'COUNTRY' => 1000,
            'STATE' => 500,
            'DISTRICT' => 300,
            'CITY' => 150,
            default => 100,
        };
    }

    protected function getMatchedCountry(string $location): ?string
    {
        foreach ($this->geoData as $country => $data) {
            if (Str::lower($location) === Str::lower($country)) {
                return $country;
            }
        }
        return null;
    }

    protected function getMatchedState(string $location): ?array
    {
        foreach ($this->geoData as $country => $data) {
            foreach ($data['states'] as $state => $cities) {
                if (Str::lower($location) === Str::lower($state)) {
                    return ['state' => $state, 'cities' => $cities, 'country' => $country];
                }
            }
        }
        return null;
    }
}
