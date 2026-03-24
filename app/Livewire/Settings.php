<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
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
        $user = auth()->user() ?? User::first();

        if (! $user) {
            $user = User::create([
                'name' => 'Default User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
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

        $user = auth()->user() ?? User::first();

        if (! $user) {
            // Create a default user if none exists (common for single-user local apps)
            $user = User::create([
                'name' => 'Default User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
            ]);
        }

        UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            ['email_sender_name' => $this->emailSenderName]
        );

        session()->flash('success', 'Settings saved successfully!');
    }

    public function confirmLogout(): void
    {
        $this->dispatch('open-confirm-modal', [
            'title' => 'Logout',
            'message' => 'Are you sure you want to logout of your session?',
            'confirmButton' => 'Logout',
            'type' => 'danger',
            'confirmActionUrl' => route('logout'),
        ]);
    }

    #[On('logout')]
    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('login');
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('livewire.settings')->layout('layouts.app', ['title' => 'Settings']);
    }
}
