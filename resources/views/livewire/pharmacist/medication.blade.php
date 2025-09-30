<?php

use Livewire\Volt\Component;
use App\Models\Medication;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

new class extends Component {
    public $medications;
    public $name;
    public $status = 'In Stock';
    public $stock_level;
    public $expiry;
    public $supplier;
    public $editMode = false;
    public $medicationId;
    public $showModal = false;
    public $search = '';
    public $startDate;
    public $endDate;
    public $restockAmount = 50; // Default restock amount

    protected $rules = [
        'name' => 'required|string|max:255',
        'status' => 'required|in:In Stock,Low Stock,Out of Stock',
        'stock_level' => 'required|integer|min:0',
        'expiry' => 'required|date',
        'supplier' => 'nullable|string|max:255',
        'restockAmount' => 'nullable|integer|min:1',
    ];

    protected $messages = [
        'name.required' => 'Medication name is required! 💊',
        'status.required' => 'Please select a stock status! 📦',
        'stock_level.required' => 'Stock level is required! 🔢',
        'expiry.required' => 'Expiry date is required! 📅',
        'restockAmount.min' => 'Restock amount must be at least 1! 🔢',
    ];

    public function mount()
    {
        $this->loadMedications();
    }

    public function loadMedications()
    {
        $query = Medication::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->startDate, fn($q) => $q->whereDate('expiry', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('expiry', '<=', $this->endDate));

        $this->medications = $query->latest()->get();
    }

    public function create()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            Medication::create([
                'name' => $this->name,
                'status' => $this->status,
                'stock_level' => $this->stock_level,
                'expiry' => $this->expiry,
                'supplier' => $this->supplier,
            ]);

            DB::commit();

            $this->reset(['name', 'status', 'stock_level', 'expiry', 'supplier']);
            $this->showModal = false;
            $this->loadMedications();
            session()->flash('message', '✅ Medication added successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', '😱 Error adding medication: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $this->editMode = true;
        $this->medicationId = $id;
        $med = Medication::findOrFail($id);
        $this->name = $med->name;
        $this->status = $med->status;
        $this->stock_level = $med->stock_level;
        $this->expiry = $med->expiry instanceof \Carbon\Carbon ? $med->expiry->format('Y-m-d') : $med->expiry;
        $this->supplier = $med->supplier;
        $this->showModal = true;
    }

    public function update()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $med = Medication::findOrFail($this->medicationId);
            $med->update([
                'name' => $this->name,
                'status' => $this->status,
                'stock_level' => $this->stock_level,
                'expiry' => $this->expiry,
                'supplier' => $this->supplier,
            ]);

            DB::commit();

            $this->reset(['name', 'status', 'stock_level', 'expiry', 'supplier', 'editMode', 'medicationId', 'showModal']);
            $this->loadMedications();
            session()->flash('message', '✅ Medication updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', '😱 Error updating medication: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            Medication::findOrFail($id)->delete();
            $this->loadMedications();
            session()->flash('message', '🗑️ Medication deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', '😱 Deletion failed: ' . $e->getMessage());
        }
    }

    public function restock($id, $amount = null)
    {
        $amount = $amount ?? $this->restockAmount;
        $med = Medication::findOrFail($id);
        $med->increment('stock_level', $amount);
        $med->update(['status' => 'In Stock']);
        $this->loadMedications();
        session()->flash('message', '✅ Stock restocked with ' . $amount . ' units!');
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'status', 'stock_level', 'expiry', 'supplier', 'editMode', 'medicationId', 'showModal', 'restockAmount']);
    }
}; ?>

<main class="min-h-screen py-4 sm:py-8 px-3 sm:px-6 lg:px-8 rounded">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 sm:mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-teal-800 dark:text-teal-100">Pharmacy Medications</h1>
                <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 mt-1">Manage medicines, stock levels & expiry dates.</p>
            </div>
            <flux:button wire:click="$set('showModal', true)"
                class="mt-4 sm:mt-0 inline-flex items-center gap-2 px-3 py-2 sm:px-4 sm:py-2 rounded-lg shadow-sm text-sm font-medium text-white bg-teal-700 hover:bg-teal-800">
                ➕ Add Medication
            </flux:button>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="mb-4 sm:mb-6 rounded-lg bg-teal-600 text-white px-3 py-2 sm:px-4 sm:py-3 shadow-md flex items-center justify-between">
                <span>{{ session('message') }}</span>
                <button class="ml-2 sm:ml-3 text-white hover:text-zinc-200" onclick="this.parentElement.remove()">✕</button>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="mb-4 sm:mb-6 rounded-lg bg-red-600 text-white px-3 py-2 sm:px-4 sm:py-3 shadow-md flex items-center justify-between">
                <span>{{ session('error') }}</span>
                <button class="ml-2 sm:ml-3 text-white hover:text-zinc-200" onclick="this.parentElement.remove()">✕</button>
            </div>
        @endif

        <!-- Filters -->
        <div class="mb-4 sm:mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="🔍 Search by name" />
            <flux:input wire:model.live="startDate" type="date" placeholder="Start expiry" />
            <flux:input wire:model.live="endDate" type="date" placeholder="End expiry" />
        </div>

        <!-- Medications Table -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-teal-50 dark:bg-teal-900/40">
                        <tr>
                            <th class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-semibold text-teal-700 uppercase tracking-wide">Name</th>
                            <th class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-semibold text-teal-700 uppercase tracking-wide">Status</th>
                            <th class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-semibold text-teal-700 uppercase tracking-wide">Stock</th>
                            <th class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-semibold text-teal-700 uppercase tracking-wide">Expiry</th>
                            <th class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-semibold text-teal-700 uppercase tracking-wide">Supplier</th>
                            <th class="px-3 py-2 sm:px-6 sm:py-3 text-right text-xs font-semibold text-teal-700 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @forelse ($medications as $med)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/40 transition">
                                <td class="px-3 py-2 sm:px-6 sm:py-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $med->name }}</td>
                                <td class="px-3 py-2 sm:px-6 sm:py-4 text-sm">
                                    @if($med->status === 'In Stock')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">✅ In Stock</span>
                                    @elseif($med->status === 'Low Stock')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">⚠️ Low Stock</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">❌ Out of Stock</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 sm:px-6 sm:py-4 text-sm text-zinc-700 dark:text-zinc-300">{{ $med->stock_level }} units</td>
                                <td class="px-3 py-2 sm:px-6 sm:py-4 text-sm text-zinc-700 dark:text-zinc-300">{{ $med->expiry instanceof \Carbon\Carbon ? $med->expiry->format('M Y') : $med->expiry }}</td>
                                <td class="px-3 py-2 sm:px-6 sm:py-4 text-sm text-zinc-700 dark:text-zinc-300">{{ $med->supplier ?: '—' }}</td>
                                <td class="px-3 py-2 sm:px-6 sm:py-4 text-right text-sm space-y-2 sm:space-y-0 sm:space-x-2">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2">
                                        <div class="flex items-center gap-2">
                                            <flux:input wire:model="restockAmount" type="number" min="1" class="w-16 sm:w-20 text-xs" placeholder="Qty" />
                                            <flux:button wire:click="restock({{ $med->id }})" class="text-teal-600 hover:text-teal-800 text-xs whitespace-nowrap">📦 Restock</flux:button>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <flux:button wire:click="edit({{ $med->id }})" class="text-blue-600 hover:text-blue-800">✎</flux:button>
                                            <flux:button x-on:click.prevent="confirm('Delete this medication?') && $wire.delete({{ $med->id }})" class="text-red-600 hover:text-red-800">🗑</flux:button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-4 sm:px-6 sm:py-6 text-center text-zinc-500 dark:text-zinc-400 text-sm">No medications found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Medication Modal -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-3 sm:p-4">
            <div class="w-full max-w-md sm:max-w-xl bg-white dark:bg-zinc-800 rounded-xl shadow-xl overflow-hidden relative p-4 sm:p-6">
                <button type="button" wire:click="cancelEdit" class="absolute top-3 right-3 sm:top-4 sm:right-4 text-zinc-400 hover:text-zinc-600">✕</button>
                <h3 class="text-lg sm:text-xl font-semibold text-zinc-900 dark:text-zinc-100 mb-4 sm:mb-6">{{ $editMode ? 'Edit Medication' : 'Add New Medication' }}</h3>

                <form wire:submit.prevent="{{ $editMode ? 'update' : 'create' }}" class="space-y-4 sm:space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name</label>
                        <flux:input wire:model="name" class="w-full mt-1" required />
                        @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Status</label>
                        <flux:select wire:model="status" class="w-full mt-1">
                            <option value="In Stock">✅ In Stock</option>
                            <option value="Low Stock">⚠️ Low Stock</option>
                            <option value="Out of Stock">❌ Out of Stock</option>
                        </flux:select>
                        @error('status') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Stock Level (units)</label>
                        <flux:input wire:model="stock_level" type="number" min="0" class="w-full mt-1" required />
                        @error('stock_level') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Expiry Date</label>
                        <flux:input wire:model="expiry" type="date" class="w-full mt-1" required />
                        @error('expiry') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Supplier</label>
                        <flux:input wire:model="supplier" class="w-full mt-1" placeholder="e.g. PharmaCorp" />
                        @error('supplier') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 sm:gap-3 pt-3 sm:pt-4">
                        <flux:button type="button" wire:click="cancelEdit" class="px-3 py-2 text-sm font-medium text-zinc-700 bg-zinc-100 rounded-lg hover:bg-zinc-200">Cancel</flux:button>
                        <flux:button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700">{{ $editMode ? 'Update' : 'Create' }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</main>
