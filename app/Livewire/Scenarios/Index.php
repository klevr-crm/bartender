<?php

declare(strict_types=1);

namespace App\Livewire\Scenarios;

use App\Models\Scenario;
use Illuminate\View\View;
use Livewire\Component;

final class Index extends Component
{
    public function render(): View
    {
        return view('livewire.scenarios.index', [
            'scenarios' => Scenario::with('persona')->get(),
        ]);
    }
}
