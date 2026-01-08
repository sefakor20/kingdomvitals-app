<div class="space-y-6">
    <div class="text-center">
        <flux:heading size="xl">Add Worship Services</flux:heading>
        <flux:text class="mt-2">
            Define your regular worship services to track attendance accurately.
        </flux:text>
    </div>

    <div class="space-y-4">
        <!-- Add service form -->
        <div class="rounded-lg border border-dashed border-stone-300 dark:border-stone-700 p-4">
            <div class="grid gap-4 sm:grid-cols-4">
                <flux:field>
                    <flux:label>Service Name</flux:label>
                    <flux:input
                        wire:model="newServiceName"
                        placeholder="e.g., Morning Worship"
                    />
                    <flux:error name="newServiceName" />
                </flux:field>

                <flux:field>
                    <flux:label>Day</flux:label>
                    <flux:select wire:model="newServiceDay">
                        @foreach($this->daysOfWeek as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="newServiceDay" />
                </flux:field>

                <flux:field>
                    <flux:label>Time</flux:label>
                    <flux:input
                        wire:model="newServiceTime"
                        type="time"
                    />
                    <flux:error name="newServiceTime" />
                </flux:field>

                <flux:field>
                    <flux:label>Type</flux:label>
                    <flux:select wire:model="newServiceType">
                        @foreach($this->serviceTypes as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="newServiceType" />
                </flux:field>
            </div>

            <div class="mt-4">
                <flux:button wire:click="addService" variant="ghost" size="sm">
                    <flux:icon name="plus" variant="micro" class="mr-1" />
                    Add Service
                </flux:button>
            </div>
        </div>

        <!-- Services list -->
        @if(count($services) > 0)
            <div class="rounded-lg border border-stone-200 dark:border-stone-700 divide-y divide-stone-200 dark:divide-stone-700">
                @foreach($services as $index => $service)
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 p-2">
                                <flux:icon name="calendar" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p class="font-medium text-stone-900 dark:text-white">{{ $service['name'] }}</p>
                                <p class="text-sm text-stone-500 dark:text-stone-400">
                                    {{ $this->daysOfWeek[$service['day_of_week']] }} at {{ \Carbon\Carbon::parse($service['time'])->format('g:i A') }}
                                    <span class="mx-1">&bull;</span>
                                    {{ ucfirst($service['service_type']) }}
                                </p>
                            </div>
                        </div>
                        <flux:button wire:click="removeService({{ $index }})" variant="ghost" size="sm">
                            <flux:icon name="x-mark" variant="micro" />
                        </flux:button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-stone-500 dark:text-stone-400">
                <flux:icon name="calendar-days" class="mx-auto h-12 w-12 mb-2 opacity-50" />
                <p>No services added yet.</p>
                <p class="text-sm">Add at least one worship service to continue.</p>
            </div>
        @endif

        @error('services')
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-between pt-4">
        <flux:button wire:click="goBack" variant="ghost">
            <flux:icon name="arrow-left" variant="micro" class="mr-2" />
            Back
        </flux:button>

        <flux:button wire:click="completeServicesStep" variant="primary">
            Continue
            <flux:icon name="arrow-right" variant="micro" class="ml-2" />
        </flux:button>
    </div>
</div>
