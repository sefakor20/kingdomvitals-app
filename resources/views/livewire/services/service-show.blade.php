<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('services.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        <div class="flex items-center gap-2">
            @if($this->canEdit)
                @if($editing)
                    <flux:button variant="ghost" wire:click="cancel" wire:loading.attr="disabled" wire:target="save">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save" class="flex items-center gap-1">
                            <flux:icon.check class="size-4" />
                            {{ __('Save') }}
                        </span>
                        <span wire:loading wire:target="save" class="flex items-center gap-1">
                            <flux:icon.arrow-path class="size-4 animate-spin" />
                            {{ __('Saving...') }}
                        </span>
                    </flux:button>
                @else
                    <flux:button variant="primary" wire:click="edit" icon="pencil">
                        {{ __('Edit') }}
                    </flux:button>
                @endif
            @endif
            @if($this->canDelete && !$editing)
                <flux:button variant="danger" wire:click="confirmDelete" icon="trash">
                    {{ __('Delete') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Service Header Card -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon icon="calendar" class="size-6 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    @if($editing)
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:input wire:model="name" placeholder="{{ __('Service Name') }}" class="w-64" />
                        </div>
                        @error('name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    @else
                        <flux:heading size="xl">{{ $service->name }}</flux:heading>
                    @endif
                    <div class="mt-1 flex items-center gap-2">
                        @if($editing)
                            <flux:select wire:model="service_type" class="w-40">
                                @foreach($this->serviceTypes as $type)
                                    <flux:select.option value="{{ $type->value }}">
                                        {{ ucfirst($type->value) }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <flux:badge color="zinc" size="sm">
                                {{ ucfirst($service->service_type->value) }}
                            </flux:badge>
                        @endif
                    </div>
                </div>
            </div>
            @if($editing)
                <div class="flex items-center gap-2">
                    <flux:switch wire:model="is_active" />
                    <flux:text>{{ __('Active') }}</flux:text>
                </div>
            @else
                <flux:badge
                    :color="$service->is_active ? 'green' : 'zinc'"
                    size="lg"
                >
                    {{ $service->is_active ? __('Active') : __('Inactive') }}
                </flux:badge>
            @endif
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Service Schedule -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Schedule') }}</flux:heading>
            <dl class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Day of Week') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            @if($editing)
                                <flux:select wire:model="day_of_week">
                                    <flux:select.option value="0">{{ __('Sunday') }}</flux:select.option>
                                    <flux:select.option value="1">{{ __('Monday') }}</flux:select.option>
                                    <flux:select.option value="2">{{ __('Tuesday') }}</flux:select.option>
                                    <flux:select.option value="3">{{ __('Wednesday') }}</flux:select.option>
                                    <flux:select.option value="4">{{ __('Thursday') }}</flux:select.option>
                                    <flux:select.option value="5">{{ __('Friday') }}</flux:select.option>
                                    <flux:select.option value="6">{{ __('Saturday') }}</flux:select.option>
                                </flux:select>
                            @else
                                {{ $this->getDayName($service->day_of_week) }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Time') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            @if($editing)
                                <flux:input type="time" wire:model="time" />
                            @else
                                {{ $service->time }}
                            @endif
                        </dd>
                    </div>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Capacity') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="number" wire:model="capacity" min="1" class="w-32" placeholder="{{ __('No limit') }}" />
                        @else
                            {{ $service->capacity ?? __('No limit') }}
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Statistics (Read-Only) -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Statistics') }}</flux:heading>
            <dl class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Attendance Records') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $this->attendanceCount }}
                        </dd>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Donations') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $this->donationCount }}
                        </dd>
                    </div>
                </div>
            </dl>
        </div>
    </div>

    <!-- Attendance Records Section -->
    <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="lg">
                {{ __('Attendance Records') }}
                <span class="text-sm font-normal text-zinc-500">({{ $this->attendanceRecords->count() }})</span>
            </flux:heading>
            @if($this->canManageAttendance)
                <div class="flex items-center gap-2">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        :href="route('attendance.checkin', [$branch, $service])"
                        icon="hand-raised"
                        wire:navigate
                    >
                        {{ __('Live Check-in') }}
                    </flux:button>
                    <flux:button variant="primary" size="sm" wire:click="openAddAttendanceModal" icon="plus">
                        {{ __('Add Attendance') }}
                    </flux:button>
                </div>
            @endif
        </div>

        @if($this->attendanceRecords->isEmpty())
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No attendance records for this service.') }}</p>
        @else
            <div class="space-y-3">
                @foreach($this->attendanceRecords as $attendance)
                    <div wire:key="attendance-{{ $attendance->id }}" class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                        <div class="flex items-center gap-3">
                            @if($attendance->member)
                                @if($attendance->member->photo_url)
                                    <img src="{{ $attendance->member->photo_url }}" alt="{{ $attendance->member->fullName() }}" class="size-8 rounded-full object-cover" />
                                @else
                                    <flux:avatar size="sm" name="{{ $attendance->member->fullName() }}" />
                                @endif
                                <div>
                                    <a href="{{ route('members.show', [$branch, $attendance->member]) }}" class="text-sm font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                        {{ $attendance->member->fullName() }}
                                    </a>
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                        <span>{{ $attendance->date->format('M d, Y') }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $attendance->check_in_time }}{{ $attendance->check_out_time ? ' - ' . $attendance->check_out_time : '' }}</span>
                                        <flux:badge
                                            :color="match($attendance->check_in_method->value) {
                                                'qr' => 'blue',
                                                'kiosk' => 'purple',
                                                default => 'zinc',
                                            }"
                                            size="sm"
                                        >
                                            {{ ucfirst($attendance->check_in_method->value) }}
                                        </flux:badge>
                                    </div>
                                </div>
                            @elseif($attendance->visitor)
                                <flux:avatar size="sm" name="{{ $attendance->visitor->fullName() }}" class="ring-2 ring-purple-400" />
                                <div>
                                    <a href="{{ route('visitors.show', [$branch, $attendance->visitor]) }}" class="text-sm font-medium text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300" wire:navigate>
                                        {{ $attendance->visitor->fullName() }}
                                        <span class="text-xs font-normal text-purple-500">({{ __('Visitor') }})</span>
                                    </a>
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                        <span>{{ $attendance->date->format('M d, Y') }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $attendance->check_in_time }}{{ $attendance->check_out_time ? ' - ' . $attendance->check_out_time : '' }}</span>
                                        <flux:badge
                                            :color="match($attendance->check_in_method->value) {
                                                'qr' => 'blue',
                                                'kiosk' => 'purple',
                                                default => 'zinc',
                                            }"
                                            size="sm"
                                        >
                                            {{ ucfirst($attendance->check_in_method->value) }}
                                        </flux:badge>
                                    </div>
                                </div>
                            @else
                                <flux:avatar size="sm" name="?" />
                                <div>
                                    <span class="text-sm font-medium text-zinc-500">{{ __('Unknown') }}</span>
                                </div>
                            @endif
                        </div>
                        @if($editing && $this->canManageAttendance)
                            <div class="flex items-center gap-1">
                                <flux:button variant="ghost" size="sm" wire:click="editAttendance('{{ $attendance->id }}')" class="text-zinc-600 hover:text-zinc-700">
                                    <flux:icon icon="pencil" class="size-4" />
                                </flux:button>
                                <flux:button variant="ghost" size="sm" wire:click="deleteAttendance('{{ $attendance->id }}')" wire:confirm="{{ __('Are you sure you want to delete this attendance record?') }}" class="text-red-600 hover:text-red-700">
                                    <flux:icon icon="trash" class="size-4" />
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Add/Edit Attendance Modal -->
    <flux:modal wire:model.self="showAddAttendanceModal" name="add-attendance" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">
                {{ $editingAttendanceId ? __('Edit Attendance Record') : __('Add Attendance Record') }}
            </flux:heading>

            <form wire:submit="saveAttendance" class="space-y-4">
                <!-- Attendee Type Selector -->
                <div>
                    <flux:text class="mb-2 text-sm font-medium">{{ __('Attendee Type') }}</flux:text>
                    <div class="flex gap-4">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" wire:model.live="attendanceType" value="member" class="text-blue-600 focus:ring-blue-500" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Member') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" wire:model.live="attendanceType" value="visitor" class="text-purple-600 focus:ring-purple-500" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Visitor') }}</span>
                        </label>
                    </div>
                </div>

                <!-- Conditional Member/Visitor Select -->
                @if($attendanceType === 'member')
                    <flux:select wire:model="attendanceMemberId" :label="__('Member')" required>
                        <flux:select.option value="">{{ __('Select a member...') }}</flux:select.option>
                        @foreach($this->availableMembers as $member)
                            <flux:select.option value="{{ $member->id }}">
                                {{ $member->fullName() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('attendanceMemberId') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                @else
                    <flux:select wire:model="attendanceVisitorId" :label="__('Visitor')" required>
                        <flux:select.option value="">{{ __('Select a visitor...') }}</flux:select.option>
                        @foreach($this->availableVisitors as $visitor)
                            <flux:select.option value="{{ $visitor->id }}">
                                {{ $visitor->fullName() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('attendanceVisitorId') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                @endif

                <flux:input type="date" wire:model="attendanceDate" :label="__('Date')" required />
                @error('attendanceDate') <div class="text-sm text-red-600">{{ $message }}</div> @enderror

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:input type="time" wire:model="attendanceCheckInTime" :label="__('Check-in Time')" required />
                        @error('attendanceCheckInTime') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <flux:input type="time" wire:model="attendanceCheckOutTime" :label="__('Check-out Time')" />
                        @error('attendanceCheckOutTime') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                </div>

                <flux:select wire:model="attendanceCheckInMethod" :label="__('Check-in Method')">
                    @foreach($this->checkInMethods as $method)
                        <flux:select.option value="{{ $method->value }}">
                            {{ ucfirst($method->value) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="attendanceNotes" :label="__('Notes')" rows="2" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="closeAddAttendanceModal" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ $editingAttendanceId ? __('Update') : __('Add Attendance') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.self="showDeleteModal" name="delete-service" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Service') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $service->name]) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Service') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="service-updated" type="success">
        {{ __('Service updated successfully.') }}
    </x-toast>

    <x-toast on="attendance-added" type="success">
        {{ __('Attendance record added successfully.') }}
    </x-toast>

    <x-toast on="attendance-updated" type="success">
        {{ __('Attendance record updated successfully.') }}
    </x-toast>

    <x-toast on="attendance-deleted" type="success">
        {{ __('Attendance record deleted successfully.') }}
    </x-toast>
</section>
