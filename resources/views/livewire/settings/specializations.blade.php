<?php


use App\Models\User;
use App\Models\Specialization;
use App\Models\StaffSpecialization;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public $selectedSpecializations = [];
    public $availableSpecializations = [];

    /**
     * Mount the component.
     */
    public function mount()
    {
        $this->availableSpecializations = Specialization::all();
        $this->selectedSpecializations = StaffSpecialization::where('user_id', auth()->id())
            ->pluck('specialization_id')
            ->toArray();
    }

    /**
     * Update the staff specializations for the currently authenticated user.
     */
    public function updateSpecializations()
    {
        $validated = $this->validate([
            'selectedSpecializations' => 'required|array',
            'selectedSpecializations.*' => 'exists:specializations,id',
        ]);

        // Delete existing specializations
        StaffSpecialization::where('user_id', auth()->id())->delete();

        // Insert new specializations
        $specializations = array_map(function($specializationId) {
            return [
                'user_id' => auth()->id(),
                'specialization_id' => $specializationId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $validated['selectedSpecializations']);

        StaffSpecialization::insert($specializations);

        $this->dispatch('specializations-updated');
    }
}

?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Specializations')" :subheading="__('Update your specializations')">
        <form wire:submit="updateSpecializations" class="my-6 w-full space-y-6">
            <div class="space-y-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('Select Your Specializations') }}
                </label>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($availableSpecializations as $specialization)
                        <label class="flex items-center space-x-3">
                            <input 
                                type="checkbox" 
                                wire:model="selectedSpecializations" 
                                value="{{ $specialization->id }}"
                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                            >
                            <span class="text-sm text-gray-700 dark:text-gray-200">
                                {{ $specialization->name }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-specializations-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="specializations-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
