<?php

namespace App\Livewire\Compliance;

use App\Models\ComplianceSetting;
use App\Services\ComplianceSettingsService;
use Livewire\Component;

class Settings extends Component
{
    public string $version_label = '';
    public string $effective_date = '';
    public string $json = '{}';

    public function mount(): void
    {
        if (! ComplianceSetting::exists()) {
            $this->loadTemplate();
        }
    }

    public function save(ComplianceSettingsService $service): void
    {
        $this->authorize('manage-hr');
        $data = $this->validate([
            'version_label' => ['required', 'string', 'max:100'],
            'effective_date' => ['required', 'date'],
            'json' => ['required', 'json'],
        ]);

        $payload = $service->validatePayload(json_decode($data['json'], true, 512, JSON_THROW_ON_ERROR));
        ComplianceSetting::create([
            'version_label' => $data['version_label'],
            'effective_date' => $data['effective_date'],
            'settings' => $payload,
            'created_by' => auth()->id(),
        ]);

        $this->reset('version_label', 'effective_date');
        session()->flash('success', __('hr.settings_version_created'));
    }

    public function loadLatest(): void
    {
        if ($setting = ComplianceSetting::latest('effective_date')->latest('id')->first()) {
            $this->json = json_encode($setting->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    public function loadTemplate(): void
    {
        $path = base_path('COMPLIANCE-SETTINGS-TEMPLATE.json');
        $this->json = is_file($path) ? file_get_contents($path) : '{}';
    }

    public function render()
    {
        return view('livewire.compliance.settings', [
            'versions' => ComplianceSetting::with('creator')->orderByDesc('effective_date')->orderByDesc('id')->get(),
        ]);
    }
}
