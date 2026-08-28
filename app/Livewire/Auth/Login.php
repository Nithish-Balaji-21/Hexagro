<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Support\UnitScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    /** @var Collection<int, User> */
    public $users;

    public function mount(): void
    {
        $this->users = User::query()->orderBy('name')->get();
    }

    public function loginAs(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        if ($user->password_hash !== null) {
            $this->addError('login', 'Password sign-in is not available yet.');

            return;
        }

        Auth::login($user);
        app(UnitScope::class)->initializeForUser($user);
        session()->regenerate();

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
