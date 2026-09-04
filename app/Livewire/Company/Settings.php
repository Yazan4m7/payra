<?php

namespace App\Livewire\Company;

use Livewire\Component;

class Settings extends Component
{
    public string $name = '';
    public string $sector = '';
    public string $ssc_establishment_number = '';

    public function mount(): void
    {
        $tenant = tenant();
        $this->name = (string) $tenant->name;
        $this->sector = (string) ($tenant->sector ?? '');
        $this->ssc_establishment_number = (string) ($tenant->ssc_establishment_number ?? '');
    }

    public function save(): void
    {
        $this->authorize('company-admin');
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sector' => ['nullable', 'string', 'max:255'],
            'ssc_establishment_number' => ['nullable', 'string', 'max:100'],
        ]);

        tenant()->update([
            'name' => $data['name'],
            'sector' => $data['sector'] ?: null,
            'ssc_establishment_number' => $data['ssc_establishment_number'] ?: null,
        ]);

        session()->flash('success', __('hr.saved'));
    }

    public function render()
    {
        return view('livewire.company.settings');
    }
}
