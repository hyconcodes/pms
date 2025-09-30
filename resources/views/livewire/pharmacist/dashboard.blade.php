<?php

use Livewire\Volt\Component;
use App\Models\Medication;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

new class extends Component {
    public $medications;
    public $lowStock;
    public $expiringSoon;
    public $totalValue;

    // Modal state
    public $showModal = false;
    public $selectedMedication;
    public $name;
    public $status;
    public $stock_level;
    public $expiry;
    public $supplier;

    public function mount()
    {
        try {
            $this->loadMedications();
            $this->loadLowStock();
            $this->loadExpiringSoon();
            $this->loadTotalValue();
        } catch (\Exception $e) {
            Log::error('Failed to initialize pharmacist dashboard', [
                'pharmacist_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Failed to load dashboard data. Please try again later.');
        }
    }

    public function loadMedications()
    {
        try {
            $this->medications = Medication::orderBy('created_at', 'desc')->take(5)->get();
        } catch (QueryException $e) {
            Log::error('Database error while loading medications', [
                'pharmacist_id' => auth()->id(),
                'error' => $e->getMessage(),
                'query' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);
            session()->flash('error', 'Unable to load medication data. Our team has been notified.');
            $this->medications = collect();
        } catch (\Exception $e) {
            Log::error('Unexpected error while loading medications', [
                'pharmacist_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'An unexpected error occurred. Please try again later.');
            $this->medications = collect();
        }
    }

    public function loadLowStock()
    {
        try {
            $this->lowStock = Medication::where('stock_level', '<', 20)->count();
        } catch (\Exception $e) {
            Log::error('Failed to load low stock count', [
                'pharmacist_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->lowStock = 0;
        }
    }

    public function loadExpiringSoon()
    {
        try {
            $this->expiringSoon = Medication::where('expiry', '<=', now()->addMonths(3))->count();
        } catch (\Exception $e) {
            Log::error('Failed to load expiring soon count', [
                'pharmacist_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->expiringSoon = 0;
        }
    }

    public function loadTotalValue()
    {
        try {
            $this->totalValue = Medication::sum('stock_level'); // Assuming average price per unit
        } catch (\Exception $e) {
            Log::error('Failed to load total inventory value', [
                'pharmacist_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->totalValue = 0;
        }
    }

    public function openEditModal($medicationId)
    {
        try {
            $this->selectedMedication = Medication::findOrFail($medicationId);
            $this->name = $this->selectedMedication->name;
            $this->status = $this->selectedMedication->status;
            $this->stock_level = $this->selectedMedication->stock_level;
            $this->expiry = $this->selectedMedication->expiry;
            $this->supplier = $this->selectedMedication->supplier;
            $this->showModal = true;
        } catch (\Exception $e) {
            Log::error('Failed to open edit modal', [
                'pharmacist_id' => auth()->id(),
                'medication_id' => $medicationId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Unable to open medication details. Please try again.');
        }
    }

    public function closeModal()
    {
        $this->reset(['showModal', 'selectedMedication', 'name', 'status', 'stock_level', 'expiry', 'supplier']);
    }

    public function saveMedication()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string|max:50',
            'stock_level' => 'required|integer|min:0',
            'expiry' => 'required|date',
            'supplier' => 'required|string|max:255',
        ]);

        try {
            $this->selectedMedication->update([
                'name' => $this->name,
                'status' => $this->status,
                'stock_level' => $this->stock_level,
                'expiry' => $this->expiry,
                'supplier' => $this->supplier,
            ]);

            session()->flash('message', 'Medication updated successfully.');
            session()->flash('alert-type', 'success');

            $this->closeModal();
            $this->loadMedications();
            $this->loadLowStock();
            $this->loadExpiringSoon();
            $this->loadTotalValue();
        } catch (\Exception $e) {
            Log::error('Failed to save medication', [
                'pharmacist_id' => auth()->id(),
                'medication_id' => $this->selectedMedication->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to update medication. Please try again.');
        }
    }
}; ?>

<div class="min-h-screen p-4 sm:p-6 lg:p-4">
    @if (session()->has('message'))
        <div class="fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg {{ session('alert-type') === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700' }} border">
            <div class="flex items-center">
                @if (session('alert-type') === 'error')
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @else
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                @endif
                <span class="font-medium">{{ session('message') }}</span>
                <button type="button" class="ml-4 text-gray-500 hover:text-gray-700" @click="$el.parentElement.parentElement.remove()">
                    <span class="sr-only">Close</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            @if (session('details'))
                <div class="mt-2 text-sm">
                    {{ session('details') }}
                </div>
            @endif
        </div>
    @endif

    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-zinc-900 dark:text-white">Pharmacy Dashboard</h2>
            <flux:button href="{{ route('pharmacist.meds') }}" variant="primary" size="sm" class="px-4 py-2">
                Manage Medications
            </flux:button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Low Stock Alert -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Low Stock Items</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $lowStock }}</p>
                    </div>
                </div>
            </div>

            <!-- Expiring Soon -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900 flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Expiring Soon</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $expiringSoon }}</p>
                    </div>
                </div>
            </div>

            <!-- Total Inventory Value -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v11a2 2 0 01-2 2H6a2 2 0 01-2-2V7m16 0H4m12 0v11a2 2 0 002 2h4a2 2 0 002-2V7m-8 0v11a2 2 0 01-2 2H8a2 2 0 01-2-2V7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Inventory Stock Value</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalValue }}</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6 hidden">
                <h3 class="text-lg font-semibold mb-4 text-zinc-800 dark:text-white">Quick Actions</h3>
                <div class="space-y-2">
                    <flux:button href="" size="sm" variant="outline" class="w-full">
                        Add New Medication
                    </flux:button>
                    <flux:button href="" size="sm" variant="outline" class="w-full">
                        View Reports
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- Recent Medications -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold mb-4 text-zinc-800 dark:text-white">Recent Medications</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Expiry</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Supplier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($medications as $medication)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-white">{{ $medication->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $medication->status === 'In Stock' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $medication->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-white">{{ $medication->stock_level }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-white">
                                    {{ $medication->expiry ? \Carbon\Carbon::parse($medication->expiry)->format('M Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-white">{{ $medication->supplier }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <flux:button wire:click="openEditModal({{ $medication->id }})" size="xs" variant="outline">
                                        Edit
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-zinc-500 dark:text-zinc-300">No medications found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Medication Modal -->
    @if($showModal && $selectedMedication)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-lg w-full max-w-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Edit Medication</h3>
                    <button wire:click="closeModal" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveMedication" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Medication Name</label>
                        <flux:input id="name" wire:model="name" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Status</label>
                        <flux:select id="status" wire:model="status" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="In Stock">In Stock</option>
                            <option value="Out of Stock">Out of Stock</option>
                            <option value="Discontinued">Discontinued</option>
                        </flux:select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="stock_level" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Stock Level</label>
                        <flux:input id="stock_level" type="number" wire:model="stock_level" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        @error('stock_level')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="expiry" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Expiry Date</label>
                        <flux:input id="expiry" type="date" wire:model="expiry" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        @error('expiry')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="supplier" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Supplier</label>
                        <flux:input id="supplier" wire:model="supplier" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        @error('supplier')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3 pt-4">
                        <flux:button type="button" wire:click="closeModal" variant="outline" class="w-full">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary" class="w-full">
                            Save Changes
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
