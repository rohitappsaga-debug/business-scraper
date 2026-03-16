<?php

namespace App\Exports;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BusinessesExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters = []) {}

    /** @return Builder<Business> */
    public function query(): Builder
    {
        $query = Business::query()->with('businessEmails');

        if (! empty($this->filters['job_id'])) {
            $query->where('scraping_job_id', (int) $this->filters['job_id']);
        }

        if (! empty($this->filters['location'])) {
            $query->where(function (Builder $q) {
                $q->where('city', 'like', '%'.$this->filters['location'].'%')
                    ->orWhere('state', 'like', '%'.$this->filters['location'].'%')
                    ->orWhere('country', 'like', '%'.$this->filters['location'].'%');
            });
        }

        if (! empty($this->filters['category'])) {
            $query->where('category', 'like', '%'.$this->filters['category'].'%');
        }

        if (! empty($this->filters['search'])) {
            $term = '%'.$this->filters['search'].'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if (! empty($this->filters['min_rating'])) {
            $query->where('rating', '>=', (float) $this->filters['min_rating']);
        }

        if (! empty($this->filters['has_email'])) {
            $query->whereNotNull('email')->where('email', '!=', '');
        }

        return $query->orderByDesc('created_at');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Category',
            'Address',
            'City',
            'State',
            'Country',
            'Phone',
            'Website',
            'Email',
            'Rating',
            'Reviews',
            'Source',
            'Created At',
        ];
    }

    /**
     * @param  Business  $business
     * @return list<mixed>
     */
    public function map($business): array
    {
        return [
            $business->id,
            $business->name,
            $business->category,
            $business->address,
            $business->city,
            $business->state,
            $business->country,
            $business->phone,
            $business->website,
            $business->email ?? $business->businessEmails->pluck('email')->first(),
            $business->rating,
            $business->reviews_count,
            $business->source,
            $business->created_at?->toDateTimeString(),
        ];
    }
}
