<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Login')]
class Login extends Component
{
    public string $username = '';

    public string $password = '';

    public bool $remember = false;

    public bool $showPassword = false;

    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirect(route('search'), navigate: true);
        }
    }

    protected array $rules = [
        'username' => 'required',
        'password' => 'required',
    ];

    public function togglePassword(): void
    {
        $this->showPassword = ! $this->showPassword;
    }

    public function authenticate(): void
    {
        $this->validate();

        if (auth()->attempt(['email' => $this->username, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            $this->redirectIntended(route('search'));

            return;
        }

        $this->addError('username', 'The provided credentials do not match our records.');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
