<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Member Availability') }}</flux:heading>
            <flux:subheading>{{ __('Track when members are unavailable for duty assignments') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button href="{{ route('duty-rosters.index', $branch) }}" variant="ghost" icon="arrow-left" wire:navigate>
                {{ __('Back to Rosters') }}
            </flux:button>
            @if ($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('Add Unavailability') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Month Navigation and Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" icon="chevron-left" wire:click="previousMonth" />
            <flux:heading size="lg">
                {{ \Carbon\Carbon::parse($monthFilter . '-01')->format('F Y') }}
            </flux:heading>
            <flux:button variant="ghost" size="sm" icon="chevron-right" wire:click="nextMonth" />
        </div>

        <div class="w-full sm:w-64">
            <flux:select wire:model.live="memberFilter">
                <flux:select.option value="">{{ __('All Members') }}</flux:select.option>
                @foreach($this->members as $member)
                    <flux:select.option value="{{ $member->id }}">
                        {{ $member->fullName() }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if($this->unavailabilities->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="calendar" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No unavailabilities found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($memberFilter)
                    {{ __('This member has no unavailabilities recorded for this month.') }}
                @else
                    {{ __('No members have been marked as unavailable for this month.') }}
                @endif
            </flux:text>
            @if ($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Add Unavailability') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Date') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Member') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Reason') }}
                        </th>
                        <th scope="col" class="relative px-4 py-3">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach($this->unavailabilities as $unavailability)
                        <tr wire:key="unavailability-{{ $unavailability->id }}">
                            <td class="whitespace-nowrap px-4 py-4">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $unavailability->unavailable_date->format('D, M j, Y') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $unavailability->member?->fullName() ?? '-' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $unavailability->reason ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium">
                                @can('delete', $unavailability)
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete('{{ $unavailability->id }}')" class="text-red-600 hover:text-red-700">
                                        {{ __('Delete') }}
                                    </flux:button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-unavailability" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Unavailability') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <flux:select wire:model="member_id" :label="__('Member')" required>
                    <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                    @foreach($this->members as $member)
                        <flux:select.option value="{{ $member->id }}">
                            {{ $member->fullName() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:checkbox wire:model.live="isDateRange" :label="__('Date Range')" />

                <flux:input wire:model="unavailable_date" type="date" :label="$isDateRange ? __('Start Date') : __('Date')" required />

                @if($isDateRange)
                    <flux:input wire:model="end_date" type="date" :label="__('End Date')" required />
                @endif

                <flux:input wire:model="reason" :label="__('Reason')" placeholder="{{ __('e.g., Traveling, Family commitment') }}" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Unavailability') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Modal -->
    <flux:modal wire:model.self="showDeleteModal" name="delete-unavailability" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Unavailability') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to remove this unavailability for :member on :date?', [
                    'member' => $deletingUnavailability?->member?->fullName() ?? '',
                    'date' => $deletingUnavailability?->unavailable_date?->format('M j, Y') ?? '',
                ]) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Toasts -->
    <x-toast on="unavailability-created" type="success">{{ __('Unavailability added successfully.') }}</x-toast>
    <x-toast on="unavailability-deleted" type="success">{{ __('Unavailability removed successfully.') }}</x-toast>
</section>
