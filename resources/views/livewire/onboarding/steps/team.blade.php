<div class="space-y-6">
    <div class="text-center">
        <flux:heading size="xl">Invite Your Team</flux:heading>
        <flux:text class="mt-2">
            Add team members who will help manage your church. They will receive an email invitation.
        </flux:text>
    </div>

    <div class="space-y-4">
        <!-- Add team member form -->
        <div class="rounded-lg border border-dashed border-stone-300 dark:border-stone-700 p-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>Email Address</flux:label>
                        <flux:input
                            wire:model="newTeamEmail"
                            type="email"
                            placeholder="team@example.com"
                        />
                        <flux:error name="newTeamEmail" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Role</flux:label>
                    <flux:select wire:model="newTeamRole">
                        @foreach($this->teamRoles as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="newTeamRole" />
                </flux:field>
            </div>

            <div class="mt-4">
                <flux:button wire:click="addTeamMember" variant="ghost" size="sm">
                    <flux:icon name="plus" variant="micro" class="mr-1" />
                    Add Team Member
                </flux:button>
            </div>
        </div>

        <!-- Team members list -->
        @if(count($teamMembers) > 0)
            <div class="rounded-lg border border-stone-200 dark:border-stone-700 divide-y divide-stone-200 dark:divide-stone-700">
                @foreach($teamMembers as $index => $member)
                    <div class="flex items-center justify-between p-4">
                        <div>
                            <p class="font-medium text-stone-900 dark:text-white">{{ $member['email'] }}</p>
                            <p class="text-sm text-stone-500 dark:text-stone-400">{{ ucfirst($member['role']) }}</p>
                        </div>
                        <flux:button wire:click="removeTeamMember({{ $index }})" variant="ghost" size="sm">
                            <flux:icon name="x-mark" variant="micro" />
                        </flux:button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-stone-500 dark:text-stone-400">
                <flux:icon name="users" class="mx-auto h-12 w-12 mb-2 opacity-50" />
                <p>No team members added yet.</p>
                <p class="text-sm">You can always add team members later.</p>
            </div>
        @endif

        @error('teamMembers')
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-between pt-4">
        <flux:button wire:click="goBack" variant="ghost">
            <flux:icon name="arrow-left" variant="micro" class="mr-2" />
            Back
        </flux:button>

        <div class="flex gap-3">
            <flux:button wire:click="skipTeamStep" variant="ghost">
                Skip for now
            </flux:button>
            <flux:button wire:click="completeTeamStep" variant="primary">
                {{ count($teamMembers) > 0 ? 'Send Invites & Continue' : 'Continue' }}
                <flux:icon name="arrow-right" variant="micro" class="ml-2" />
            </flux:button>
        </div>
    </div>
</div>
