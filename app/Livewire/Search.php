<?php

namespace App\Livewire;

use Livewire\Component;

class Search extends Component
{
    public string $keyword = '';

    public string $location = '';

    public int $limit = 100;

    public function submit()
    {
        $this->validate([
            'keyword' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'limit' => 'required|integer|min:50',
        ]);

        // Logic for handling the search form submission
        // For now, we flash a success message.
        session()->flash('success', 'Scraping Job Started!');
    }

    public function render()
    {
        return view('livewire.search');
    }
}
