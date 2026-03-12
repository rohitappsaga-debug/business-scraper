<?php

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class Result extends Component
{
    public string $keyword = 'Dentists';

    public string $location = 'New York';

    public string $search = '';

    public int $currentPage = 1;

    public int $perPage = 5;

    /** @var array<int, array<string, mixed>> */
    protected array $allResults = [
        ['id' => 1, 'name' => 'Central Park Dental', 'category' => 'Cosmetic Dentistry', 'address' => '30 Central Park S, NY 10019', 'phone' => '(212) 759-2993', 'email' => 'info@cpdental.com', 'website' => 'https://cpdental.com'],
        ['id' => 2, 'name' => 'Madison Ave Dental', 'category' => 'General Dentist', 'address' => '501 Madison Ave, NY 10022', 'phone' => '(212) 355-2540', 'email' => '', 'website' => 'https://madisondental.ny'],
        ['id' => 3, 'name' => 'Empire Dental Group', 'category' => 'Pediatric Dentist', 'address' => '350 5th Ave, NY 10118', 'phone' => '(212) 643-2044', 'email' => 'contact@empiredental.com', 'website' => 'https://empiredental.com'],
        ['id' => 4, 'name' => 'Gramercy Park Dentistry', 'category' => 'General Dentistry', 'address' => '225 E 19th St, NY 10003', 'phone' => '(212) 674-6724', 'email' => 'hello@gramercydentist.com', 'website' => 'https://gramercydentist.com'],
        ['id' => 5, 'name' => 'Broadway Dental Arts', 'category' => 'Oral Surgery', 'address' => '1776 Broadway, NY 10019', 'phone' => '(212) 246-1300', 'email' => 'support@broadwaydental.com', 'website' => 'https://broadwaydentalny.com'],
        ['id' => 6, 'name' => 'Upper West Side Dental', 'category' => 'Family Dentistry', 'address' => '2109 Broadway, NY 10023', 'phone' => '(212) 873-0400', 'email' => 'info@uwsdental.com', 'website' => 'https://uwsdental.com'],
        ['id' => 7, 'name' => 'Tribeca Dental Studio', 'category' => 'Cosmetic Dentistry', 'address' => '114 Hudson St, NY 10013', 'phone' => '(212) 966-6868', 'email' => '', 'website' => 'https://tribecadentalstudio.com'],
        ['id' => 8, 'name' => 'Chelsea Dental Associates', 'category' => 'General Dentist', 'address' => '254 W 23rd St, NY 10011', 'phone' => '(212) 929-3030', 'email' => 'hello@chelseadental.com', 'website' => 'https://chelseadental.com'],
        ['id' => 9, 'name' => 'SoHo Dental Group', 'category' => 'Orthodontics', 'address' => '568 Broadway, NY 10012', 'phone' => '(212) 965-7200', 'email' => '', 'website' => 'https://sohodentalgroup.com'],
        ['id' => 10, 'name' => 'Brooklyn Smiles', 'category' => 'Family Dentistry', 'address' => '345 Court St, Brooklyn 11231', 'phone' => '(718) 625-1234', 'email' => 'contact@brooklynsmiles.com', 'website' => 'https://brooklynsmiles.com'],
    ];

    /** @return array<int, array<string, mixed>> */
    protected function filteredResults(): array
    {
        if (empty($this->search)) {
            return $this->allResults;
        }

        $term = strtolower($this->search);

        return array_values(array_filter($this->allResults, function (array $item) use ($term): bool {
            return str_contains(strtolower($item['name']), $term)
                || str_contains(strtolower($item['email']), $term)
                || str_contains(strtolower($item['category']), $term);
        }));
    }

    public function getTotalResultsProperty(): int
    {
        return count($this->filteredResults());
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->totalResults / $this->perPage));
    }

    /** @return array<int, array<string, mixed>> */
    public function getResultsProperty(): array
    {
        return array_slice($this->filteredResults(), ($this->currentPage - 1) * $this->perPage, $this->perPage);
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function nextPage(): void
    {
        if ($this->currentPage < $this->totalPages) {
            $this->currentPage++;
        }
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = max(1, min($page, $this->totalPages));
    }

    public function viewDetails(int $id): void
    {
        $this->redirectRoute('detail-result', ['id' => $id]);
    }

    public function exportCsv(): void
    {
        // Export logic will go here
        session()->flash('message', 'CSV export started.');
    }

    public function exportExcel(): void
    {
        // Export logic will go here
        session()->flash('message', 'Excel export started.');
    }

    public function render(): View
    {
        return view('livewire.result', [
            'results' => $this->results,
            'totalResults' => $this->totalResults,
            'totalPages' => $this->totalPages,
            'currentPage' => $this->currentPage,
            'perPage' => $this->perPage,
            'keyword' => $this->keyword,
            'location' => $this->location,
        ])->layout('layouts.app', ['title' => 'Scraping Results']);
    }
}
