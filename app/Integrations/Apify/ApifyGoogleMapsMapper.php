<?php

namespace App\Integrations\Apify;

class ApifyGoogleMapsMapper
{
    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function map(array $item): array
    {
        $name = $this->firstNonEmptyString($item, [
            'title',
            'name',
            'placeName',
        ]);

        $category = $this->firstNonEmptyString($item, [
            'categoryName',
            'category',
            'categories.0',
        ]);

        $address = $this->firstNonEmptyString($item, [
            'address',
            'formattedAddress',
            'location.address',
        ]);

        $website = $this->firstNonEmptyString($item, [
            'website',
            'websiteUri',
            'websiteUrl',
            'url',
        ]);

        $phone = $this->firstNonEmptyString($item, [
            'phone',
            'phoneNumber',
            'phoneNumberFormatted',
            'internationalPhoneNumber',
            'phoneNumbers.0.phoneNumber',
            'contactPhone',
            'tel',
        ]);

        $email = $this->firstNonEmptyString($item, [
            'email',
            'emailAddress',
            'contactEmail',
            'emails.0',
        ]);

        $rating = $this->firstNumeric($item, [
            'rating',
            'totalScore',
            'averageRating',
        ]);

        $reviewsCount = $this->firstInt($item, [
            'reviewsCount',
            'numberOfReviews',
            'reviewCount',
            'reviews',
        ]);

        $lat = $this->firstNumeric($item, [
            'location.lat',
            'location.latitude',
            'gpsCoordinates.latitude',
            'latitude',
        ]);

        $lng = $this->firstNumeric($item, [
            'location.lng',
            'location.longitude',
            'gpsCoordinates.longitude',
            'longitude',
        ]);

        $cid = $this->firstNonEmptyString($item, [
            'cid',
            'placeId',
            'googleId',
        ]);

        $facebook = $this->firstNonEmptyString($item, ['facebookUrl', 'facebook']);
        $instagram = $this->firstNonEmptyString($item, ['instagramUrl', 'instagram']);
        $twitter = $this->firstNonEmptyString($item, ['twitterUrl', 'twitter', 'xUrl', 'x']);
        $linkedin = $this->firstNonEmptyString($item, ['linkedinUrl', 'linkedin']);
        $youtube = $this->firstNonEmptyString($item, ['youtubeUrl', 'youtube']);
        $tiktok = $this->firstNonEmptyString($item, ['tiktokUrl', 'tiktok']);

        return [
            'name' => $name,
            'category' => $category,
            'address' => $address,
            'phone' => $phone,
            'website' => $website,
            'email' => $email,
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
            'latitude' => $lat,
            'longitude' => $lng,
            'cid' => $cid,
            'social' => array_filter([
                'facebook' => $facebook,
                'instagram' => $instagram,
                'twitter' => $twitter,
                'linkedin' => $linkedin,
                'youtube' => $youtube,
                'tiktok' => $tiktok,
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $paths
     */
    private function firstNonEmptyString(array $item, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = $this->get($item, $path);
            if (is_string($value)) {
                $value = trim($value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $paths
     */
    private function firstNumeric(array $item, array $paths): ?float
    {
        foreach ($paths as $path) {
            $value = $this->get($item, $path);
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $paths
     */
    private function firstInt(array $item, array $paths): ?int
    {
        foreach ($paths as $path) {
            $value = $this->get($item, $path);
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $array
     */
    private function get(array $array, string $path): mixed
    {
        $segments = explode('.', $path);
        $value = $array;

        foreach ($segments as $segment) {
            if (! is_array($value)) {
                return null;
            }

            if (! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
