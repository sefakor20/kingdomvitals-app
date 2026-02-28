<div class="space-y-6">
    <div class="text-center">
        <span class="label-mono text-emerald-600 dark:text-emerald-400">Step 4 of 5</span>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
            <span class="text-gradient-emerald">Add Worship Services</span>
        </h1>
        <p class="mt-2 text-secondary">
            Define your regular worship services to track attendance accurately.
        </p>
    </div>

    <div class="space-y-4">
        {{-- Add service form --}}
        <div class="rounded-xl border border-dashed border-black/20 bg-black/[0.02] p-4 dark:border-white/20 dark:bg-white/[0.02]">
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
                <flux:button wire:click="addService" variant="ghost" size="sm" icon="plus">
                    Add Service
                </flux:button>
            </div>
        </div>

        {{-- Services list --}}
        @if(count($services) > 0)
            <div class="divide-y divide-black/5 overflow-hidden rounded-xl border border-black/10 dark:divide-white/5 dark:border-white/10">
                @foreach($services as $index => $service)
                    <div class="flex items-center justify-between bg-white/50 p-4 dark:bg-white/5">
                        <div class="flex items-center gap-4">
                            <div class="flex size-10 flex-shrink-0 items-center justify-center rounded-full bg-emerald-500/10">
                                <flux:icon name="calendar" class="size-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p class="font-medium text-primary">{{ $service['name'] }}</p>
                                <p class="text-sm text-muted">
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
            <div class="rounded-xl border border-black/5 bg-black/[0.02] py-10 text-center dark:border-white/5 dark:bg-white/[0.02]">
                <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-500/10">
                    <flux:icon name="calendar-days" class="size-7 text-emerald-400" />
                </div>
                <p class="mt-4 font-medium text-secondary">No services added yet.</p>
                <p class="mt-1 text-sm text-muted">Add at least one worship service to continue.</p>
            </div>
        @endif

        @error('services')
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-between pt-4">
        <flux:button wire:click="goBack" variant="ghost" icon="arrow-left">
            Back
        </flux:button>

        <button wire:click="completeServicesStep" class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold">
            Continue
            <flux:icon name="arrow-right" variant="mini" class="ml-2 inline size-4" />
        </button>
    </div>
</div>
