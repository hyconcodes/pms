<?php

use App\Models\User;
use App\Models\Specialization;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public $selectedSpecialization = '';
    public $availableSpecializations = [];

    /**
     * Mount the component.
     */
    public function mount()
    {
        $this->availableSpecializations = Specialization::all();
        $this->selectedSpecialization = auth()->user()->specialization_id ?? '';
    }

    /**
     * Update the user's specialization.
     */
    public function updateSpecializations()
    {
        $validated = $this->validate([
            'selectedSpecialization' => 'required|exists:specializations,id',
        ]);

        auth()->user()->update([
            'specialization_id' => $validated['selectedSpecialization']
        ]);

        $this->dispatch('specializations-updated');
    }
}

?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Specialization')" :subheading="__('Update your specialization')">
        <form wire:submit="updateSpecializations" class="my-6 w-full space-y-6">
            <div class="space-y-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('Select Your Specialization') }}
                </label>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($availableSpecializations as $specialization)
                        <label class="flex items-center space-x-3">
                            <input 
                                type="radio"
                                wire:model="selectedSpecialization" 
                                value="{{ $specialization->id }}"
                                class="border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
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
