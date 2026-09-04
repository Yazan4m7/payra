<?php

namespace App\Livewire\Holidays;

use App\Models\PublicHoliday;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Index extends Component
{
    public string $date = '';
    public string $name_ar = '';
    public string $name_en = '';
    public int $year;

    public function mount(): void
    {
        $this->year = (int) now()->year;
    }

    public function save(): void
    {
        $this->authorize('manage-hr');
        $data = $this->validate([
            'date' => ['required', 'date'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
        ]);

        $existing = PublicHoliday::withTrashed()->whereDate('date', $data['date'])->first();
        if ($existing && ! $existing->trashed()) {
            throw ValidationException::withMessages(['date' => 'A public holiday already exists on this date.']);
        }

        if ($existing) {
            $existing->restore();
            $existing->update([
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'],
                'year' => (int) substr($data['date'], 0, 4),
            ]);
        } else {
            PublicHoliday::create(array_merge($data, ['year' => (int) substr($data['date'], 0, 4)]));
        }

        $this->year = (int) substr($data['date'], 0, 4);
        $this->reset('date', 'name_ar', 'name_en');
        session()->flash('success', __('hr.saved'));
    }

    public function delete(int $id): void
    {
        $this->authorize('manage-hr');
        PublicHoliday::findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.holidays.index', [
            'holidays' => PublicHoliday::where('year', $this->year)->orderBy('date')->get(),
        ]);
    }
}
