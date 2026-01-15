<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Children Directory') }}</flux:heading>
            <flux:subheading>{{ __('Manage children for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" href="{{ route('children.dashboard', $branch) }}" icon="chart-bar">
                {{ __('Dashboard') }}
            </flux:button>
            <flux:button variant="ghost" href="{{ route('children.age-groups', $branch) }}" icon="user-group">
                {{ __('Age Groups') }}
            </flux:button>
            @if($this->canCreateChild)
                <flux:button variant="primary" wire:click="createChild" icon="plus">
                    {{ __('Add Child') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Children') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="users" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Unassigned') }}</flux:text>
                <div class="rounded-full bg-amber-100 p-2 dark:bg-amber-900">
                    <flux:icon icon="exclamation-triangle" class="size-4 text-amber-600 dark:text-amber-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['unassigned']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('With Emergency Contact') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="phone" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['withEmergencyContact']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('With Medical Info') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="heart" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['withMedicalInfo']) }}</flux:heading>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="ageGroupFilter">
                <flux:select.option value="">{{ __('All Age Groups') }}</flux:select.option>
                <flux:select.option value="unassigned">{{ __('Unassigned') }}</flux:select.option>
                @foreach($this->ageGroups as $ageGroup)
                    <flux:select.option value="{{ $ageGroup->id }}">
                        {{ $ageGroup->name }} ({{ $ageGroup->min_age }}-{{ $ageGroup->max_age }})
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="householdFilter">
                <flux:select.option value="">{{ __('All Households') }}</flux:select.option>
                @foreach($this->households as $household)
                    <flux:select.option value="{{ $household->id }}">
                        {{ $household->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="mb-6 flex flex-col gap-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-end">
        <div class="flex-1">
            <flux:input wire:model.live="minAge" type="number" :label="__('Min Age')" min="0" max="17" placeholder="0" />
        </div>
        <div class="flex-1">
            <flux:input wire:model.live="maxAge" type="number" :label="__('Max Age')" min="0" max="17" placeholder="17" />
        </div>
        @if($this->hasActiveFilters)
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" class="shrink-0">
                {{ __('Clear Filters') }}
            </flux:button>
        @endif
    </div>

    <!-- Children Table -->
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @if($this->children->isEmpty())
            <div class="flex flex-col items-center justify-center py-12">
                <div class="rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
                    <flux:icon icon="users" class="size-8 text-zinc-400" />
                </div>
                <flux:heading size="lg" class="mt-4">{{ __('No Children Found') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    @if($this->hasActiveFilters)
                        {{ __('Try adjusting your filters.') }}
                    @else
                        {{ __('No children are registered in this branch.') }}
                    @endif
                </flux:text>
            </div>
        @else
            <div class="mb-2 px-6 pt-4">
                <flux:text class="text-sm text-zinc-500">{{ __('Showing :count children', ['count' => $this->children->count()]) }}</flux:text>
            </div>
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Name') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Age') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Age Group') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Household') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Info') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach($this->children as $child)
                        <tr wire:key="child-{{ $child->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" name="{{ $child->fullName() }}" />
                                    <div>
                                        <flux:text class="font-medium">{{ $child->last_name }}, {{ $child->first_name }}</flux:text>
                                        @if($child->gender)
                                            <flux:text class="text-sm text-zinc-500">{{ $child->gender->value }}</flux:text>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $child->date_of_birth?->age ?? '-' }} {{ __('years') }}</flux:text>
                                <flux:text class="text-sm text-zinc-500">{{ $child->date_of_birth?->format('M d, Y') ?? '-' }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($child->ageGroup)
                                    <flux:badge :color="$child->ageGroup->color ?? 'zinc'">
                                        {{ $child->ageGroup->name }}
                                    </flux:badge>
                                @else
                                    <flux:badge color="amber">{{ __('Unassigned') }}</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $child->household?->name ?? '-' }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex gap-2">
                                    @if($child->emergencyContacts->isNotEmpty())
                                        <flux:tooltip content="{{ __(':count emergency contacts', ['count' => $child->emergencyContacts->count()]) }}">
                                            <flux:badge color="green" size="sm" icon="phone">{{ $child->emergencyContacts->count() }}</flux:badge>
                                        </flux:tooltip>
                                    @endif
                                    @if($child->medicalInfo)
                                        <flux:tooltip content="{{ __('Has medical info') }}">
                                            <flux:badge color="purple" size="sm" icon="heart"></flux:badge>
                                        </flux:tooltip>
                                    @endif
                                    @if($child->medicalInfo?->hasAllergies())
                                        <flux:tooltip content="{{ __('Has allergies') }}">
                                            <flux:badge color="red" size="sm" icon="exclamation-triangle"></flux:badge>
                                        </flux:tooltip>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                    <flux:menu>
                                        <flux:menu.item href="{{ route('members.show', [$branch, $child]) }}" icon="eye">
                                            {{ __('View Profile') }}
                                        </flux:menu.item>
                                        <flux:menu.item wire:click="editChild('{{ $child->id }}')" icon="pencil">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="openAssignAgeGroupModal('{{ $child->id }}')" icon="user-group">
                                            {{ __('Assign Age Group') }}
                                        </flux:menu.item>
                                        @if(!$child->ageGroup)
                                            <flux:menu.item wire:click="autoAssignAgeGroup('{{ $child->id }}')" icon="sparkles">
                                                {{ __('Auto-Assign') }}
                                            </flux:menu.item>
                                        @endif
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="openEmergencyContactsModal('{{ $child->id }}')" icon="phone">
                                            {{ __('Emergency Contacts') }}
                                        </flux:menu.item>
                                        <flux:menu.item wire:click="openMedicalInfoModal('{{ $child->id }}')" icon="heart">
                                            {{ __('Medical Info') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!-- Assign Age Group Modal -->
    <flux:modal wire:model="showAssignAgeGroupModal" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Assign Age Group') }}</flux:heading>

            @if($selectedChild)
                <flux:text>
                    {{ __('Assign :name to an age group', ['name' => $selectedChild->fullName()]) }}
                </flux:text>

                <flux:select wire:model="selectedAgeGroupId" :label="__('Age Group')">
                    <flux:select.option :value="null">{{ __('No Age Group') }}</flux:select.option>
                    @foreach($this->allAgeGroups as $ageGroup)
                        <flux:select.option value="{{ $ageGroup->id }}">
                            {{ $ageGroup->name }} ({{ $ageGroup->min_age }}-{{ $ageGroup->max_age }} {{ __('years') }})
                            @if(!$ageGroup->is_active) - {{ __('Inactive') }} @endif
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelAssignAgeGroup">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="assignAgeGroup">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Emergency Contacts Modal -->
    <flux:modal wire:model="showEmergencyContactsModal" class="max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Emergency Contacts') }}</flux:heading>

            @if($selectedChild)
                <div class="flex items-center justify-between gap-4">
                    <flux:text class="text-zinc-500">{{ __('Emergency contacts for :name', ['name' => $selectedChild->fullName()]) }}</flux:text>
                    <flux:button variant="primary" size="sm" wire:click="openAddContactModal" icon="plus">
                        {{ __('Add Contact') }}
                    </flux:button>
                </div>

                @if($selectedChild->emergencyContacts->isEmpty())
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-6 text-center dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon icon="phone" class="mx-auto size-8 text-zinc-400" />
                        <flux:text class="mt-2 text-zinc-500">{{ __('No emergency contacts added yet.') }}</flux:text>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($selectedChild->emergencyContacts as $contact)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <flux:text class="font-medium">{{ $contact->name }}</flux:text>
                                        @if($contact->is_primary)
                                            <flux:badge color="green" size="sm">{{ __('Primary') }}</flux:badge>
                                        @endif
                                        @if($contact->can_pickup)
                                            <flux:badge color="blue" size="sm">{{ __('Can Pickup') }}</flux:badge>
                                        @endif
                                    </div>
                                    <flux:text class="text-sm text-zinc-500">{{ $contact->relationship }}</flux:text>
                                    <flux:text class="text-sm">{{ $contact->phone }}</flux:text>
                                    @if($contact->phone_secondary)
                                        <flux:text class="text-sm text-zinc-500">{{ $contact->phone_secondary }}</flux:text>
                                    @endif
                                    @if($contact->email)
                                        <flux:text class="text-sm text-zinc-500">{{ $contact->email }}</flux:text>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <flux:button variant="ghost" size="sm" wire:click="editContact('{{ $contact->id }}')" icon="pencil" />
                                    <flux:button variant="ghost" size="sm" wire:click="confirmDeleteContact('{{ $contact->id }}')" icon="trash" class="text-red-500 hover:text-red-700" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            <div class="flex justify-end">
                <flux:button variant="ghost" wire:click="closeEmergencyContactsModal">
                    {{ __('Close') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Add Contact Modal -->
    <flux:modal wire:model="showAddContactModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Emergency Contact') }}</flux:heading>

            <form wire:submit="addEmergencyContact" class="space-y-4">
                <flux:input wire:model="contactName" :label="__('Name')" required />

                <flux:input wire:model="contactRelationship" :label="__('Relationship')" placeholder="{{ __('e.g., Mother, Father, Grandmother') }}" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="contactPhone" :label="__('Phone')" required />
                    <flux:input wire:model="contactPhoneSecondary" :label="__('Secondary Phone')" />
                </div>

                <flux:input wire:model="contactEmail" type="email" :label="__('Email')" />

                <div class="flex gap-6">
                    <flux:checkbox wire:model="contactIsPrimary" :label="__('Primary Contact')" />
                    <flux:checkbox wire:model="contactCanPickup" :label="__('Authorized for Pickup')" />
                </div>

                <flux:textarea wire:model="contactNotes" :label="__('Notes')" rows="2" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" type="button" wire:click="$set('showAddContactModal', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Add Contact') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Contact Modal -->
    <flux:modal wire:model="showEditContactModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Emergency Contact') }}</flux:heading>

            <form wire:submit="updateContact" class="space-y-4">
                <flux:input wire:model="contactName" :label="__('Name')" required />

                <flux:input wire:model="contactRelationship" :label="__('Relationship')" placeholder="{{ __('e.g., Mother, Father, Grandmother') }}" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="contactPhone" :label="__('Phone')" required />
                    <flux:input wire:model="contactPhoneSecondary" :label="__('Secondary Phone')" />
                </div>

                <flux:input wire:model="contactEmail" type="email" :label="__('Email')" />

                <div class="flex gap-6">
                    <flux:checkbox wire:model="contactIsPrimary" :label="__('Primary Contact')" />
                    <flux:checkbox wire:model="contactCanPickup" :label="__('Authorized for Pickup')" />
                </div>

                <flux:textarea wire:model="contactNotes" :label="__('Notes')" rows="2" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" type="button" wire:click="cancelEditContact">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Update Contact') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Contact Confirmation Modal -->
    <flux:modal wire:model="showDeleteContactModal" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Contact') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete this emergency contact?') }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDeleteContact">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="deleteContact">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Medical Info Modal -->
    <flux:modal wire:model="showMedicalInfoModal" class="max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Medical Information') }}</flux:heading>

            @if($selectedChild)
                <flux:text class="text-zinc-500">{{ __('Medical information for :name', ['name' => $selectedChild->fullName()]) }}</flux:text>

                <form wire:submit="saveMedicalInfo" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:textarea wire:model="allergies" :label="__('Allergies')" rows="2" placeholder="{{ __('List any allergies...') }}" />
                        <flux:textarea wire:model="medicalConditions" :label="__('Medical Conditions')" rows="2" placeholder="{{ __('Any medical conditions...') }}" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:textarea wire:model="medications" :label="__('Medications')" rows="2" placeholder="{{ __('Current medications...') }}" />
                        <flux:textarea wire:model="specialNeeds" :label="__('Special Needs')" rows="2" placeholder="{{ __('Any special needs...') }}" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:input wire:model="dietaryRestrictions" :label="__('Dietary Restrictions')" />
                        <flux:input wire:model="bloodType" :label="__('Blood Type')" placeholder="{{ __('e.g., A+, B-, O+') }}" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:input wire:model="doctorName" :label="__('Doctor Name')" />
                        <flux:input wire:model="doctorPhone" :label="__('Doctor Phone')" />
                    </div>

                    <flux:input wire:model="insuranceInfo" :label="__('Insurance Information')" />

                    <flux:textarea wire:model="emergencyInstructions" :label="__('Emergency Instructions')" rows="3" placeholder="{{ __('Special instructions in case of emergency...') }}" />

                    <div class="flex justify-end gap-3 pt-4">
                        <flux:button variant="ghost" type="button" wire:click="closeMedicalInfoModal">
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            {{ __('Save Medical Info') }}
                        </flux:button>
                    </div>
                </form>
            @endif
        </div>
    </flux:modal>

    <!-- Create Child Modal -->
    <flux:modal wire:model="showCreateChildModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Child') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('Register a new child member (under 18 years old)') }}</flux:text>

            <form wire:submit="storeChild" class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="firstName" :label="__('First Name')" required />
                    <flux:input wire:model="middleName" :label="__('Middle Name')" />
                    <flux:input wire:model="lastName" :label="__('Last Name')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="childDateOfBirth"
                        type="date"
                        :label="__('Date of Birth')"
                        :max="now()->format('Y-m-d')"
                        :min="now()->subYears(17)->format('Y-m-d')"
                        required
                    />
                    <flux:select wire:model="childGender" :label="__('Gender')">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                        <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="childHouseholdId" :label="__('Household')">
                        <flux:select.option value="">{{ __('No Household') }}</flux:select.option>
                        @foreach($this->allHouseholds as $household)
                            <flux:select.option value="{{ $household->id }}">
                                {{ $household->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="childAgeGroupId" :label="__('Age Group')">
                        <flux:select.option value="">{{ __('Auto-assign') }}</flux:select.option>
                        @foreach($this->allAgeGroups as $ageGroup)
                            <flux:select.option value="{{ $ageGroup->id }}">
                                {{ $ageGroup->name }} ({{ $ageGroup->min_age }}-{{ $ageGroup->max_age }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" type="button" wire:click="cancelCreateChild">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Add Child') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Child Modal -->
    <flux:modal wire:model="showEditChildModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Child') }}</flux:heading>

            <form wire:submit="updateChild" class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="firstName" :label="__('First Name')" required />
                    <flux:input wire:model="middleName" :label="__('Middle Name')" />
                    <flux:input wire:model="lastName" :label="__('Last Name')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="childDateOfBirth"
                        type="date"
                        :label="__('Date of Birth')"
                        :max="now()->format('Y-m-d')"
                        :min="now()->subYears(17)->format('Y-m-d')"
                        required
                    />
                    <flux:select wire:model="childGender" :label="__('Gender')">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                        <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="childHouseholdId" :label="__('Household')">
                        <flux:select.option value="">{{ __('No Household') }}</flux:select.option>
                        @foreach($this->allHouseholds as $household)
                            <flux:select.option value="{{ $household->id }}">
                                {{ $household->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="childAgeGroupId" :label="__('Age Group')">
                        <flux:select.option value="">{{ __('Auto-assign') }}</flux:select.option>
                        @foreach($this->allAgeGroups as $ageGroup)
                            <flux:select.option value="{{ $ageGroup->id }}">
                                {{ $ageGroup->name }} ({{ $ageGroup->min_age }}-{{ $ageGroup->max_age }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" type="button" wire:click="cancelEditChild">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="child-created" type="success">
        {{ __('Child created successfully.') }}
    </x-toast>

    <x-toast on="child-updated" type="success">
        {{ __('Child updated successfully.') }}
    </x-toast>

    <x-toast on="age-group-assigned" type="success">
        {{ __('Age group assigned successfully.') }}
    </x-toast>

    <x-toast on="age-group-auto-assigned" type="success">
        {{ __('Age group automatically assigned.') }}
    </x-toast>

    <x-toast on="contact-added" type="success">
        {{ __('Emergency contact added successfully.') }}
    </x-toast>

    <x-toast on="contact-updated" type="success">
        {{ __('Emergency contact updated successfully.') }}
    </x-toast>

    <x-toast on="contact-deleted" type="success">
        {{ __('Emergency contact deleted successfully.') }}
    </x-toast>

    <x-toast on="medical-info-saved" type="success">
        {{ __('Medical information saved successfully.') }}
    </x-toast>
</section>
