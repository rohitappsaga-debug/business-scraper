<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Settings extends Component
{
    /**
     * The email sender name.
     */
    public string $emailSenderName = '';

    /**
     * Mount the component and load existing settings.
     */
    public function mount(): void
    {
        $user = auth()->user() ?? \App\Models\User::first();

        if (! $user) {
            $user = \App\Models\User::create([
                'name' => 'Default User',
                'email' => 'admin@example.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]);
        }

        $this->emailSenderName = $user->settings?->email_sender_name ?? '';
    }

    /**
     * Save the settings.
     */
    public function save(): void
    {
        $this->validate([
            'emailSenderName' => 'nullable|string|max:255',
        ]);

        $user = auth()->user() ?? \App\Models\User::first();

        if (! $user) {
            // Create a default user if none exists (common for single-user local apps)
            $user = \App\Models\User::create([
                'name' => 'Default User',
                'email' => 'admin@example.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]);
        }

        \App\Models\UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            ['email_sender_name' => $this->emailSenderName]
        );

        session()->flash('success', 'Settings saved successfully!');
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('livewire.settings');
    }
}
