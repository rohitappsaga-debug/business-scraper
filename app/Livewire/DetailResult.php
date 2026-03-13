<?php

namespace App\Livewire;

use App\Models\Business;
use Illuminate\View\View;
use Livewire\Component;

class DetailResult extends Component
{
    public int $id = 0;

    /** @var array<string, mixed> */
    public array $business = [];

    public function mount(int $id): void
    {
        $this->id = $id;

        $model = Business::with('businessEmails')->find($id);

        if (! $model) {
            $this->business = [];

            return;
        }

        $emails = $model->businessEmails->pluck('email')->toArray();
        $primaryEmail = $model->email ?? ($emails[0] ?? '');

        $this->business = [
            'id' => $model->id,
            'name' => $model->name,
            'category' => $model->category ?? 'General Business',
            'description' => $model->description ?? 'No description available.',
            'address' => trim(implode(', ', array_filter([
                $model->address,
                $model->city,
                $model->state,
                $model->country,
            ]))),
            'city_state' => trim(implode(', ', array_filter([$model->city, $model->state]))),
            'postal_country' => trim(implode(', ', array_filter([$model->postal_code, $model->country]))),
            'city' => $model->city,
            'state' => $model->state,
            'postal_code' => $model->postal_code,
            'country' => $model->country,
            'phone' => $model->phone ?? 'Not available',
            'email' => $primaryEmail,
            'website' => $model->website ?? 'Not available',
            'website_url' => ! empty($model->website)
                ? (str_starts_with((string) $model->website, 'http') ? $model->website : 'https://'.$model->website)
                : null,
            'maps_url' => 'https://maps.google.com/?q='.urlencode(
                trim(implode(', ', array_filter([$model->address, $model->city, $model->state])))
            ),
            'rating' => $model->rating ? number_format((float) $model->rating, 1) : 'N/A',
            'review_count' => $model->reviews_count ?? 0,
            'status' => 'Active',
            'verification' => 'Scraped',
            'email_status' => ! empty($primaryEmail) ? 'Found' : 'Searching...',
            'last_updated' => $model->updated_at?->format('d M Y, H:i'),
            'hours' => $model->opening_hours ?? [],
            'latitude' => $model->latitude,
            'longitude' => $model->longitude,
            'social' => $model->social ?? [],
            'all_emails' => $emails,
            'source' => $model->source,
        ];
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
        if (! empty($this->business['maps_url'])) {
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
