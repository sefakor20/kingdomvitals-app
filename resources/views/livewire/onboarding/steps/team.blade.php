<div class="space-y-6">
    <div class="text-center">
        <span class="label-mono text-emerald-600 dark:text-emerald-400">Step 2 of 5</span>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
            <span class="text-gradient-emerald">Invite Your Team</span>
        </h1>
        <p class="mt-2 text-secondary">
            Add team members who will help manage your church. They will receive an email invitation.
        </p>
    </div>

    <div class="space-y-4">
        {{-- Add team member form --}}
        <div class="rounded-xl border border-dashed border-black/20 bg-black/[0.02] p-4 dark:border-white/20 dark:bg-white/[0.02]">
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
                <flux:button wire:click="addTeamMember" variant="ghost" size="sm" icon="plus">
                    Add Team Member
                </flux:button>
            </div>
        </div>

        {{-- Team members list --}}
        @if(count($teamMembers) > 0)
            <div class="divide-y divide-black/5 overflow-hidden rounded-xl border border-black/10 dark:divide-white/5 dark:border-white/10">
                @foreach($teamMembers as $index => $member)
                    <div class="flex items-center justify-between bg-white/50 p-4 dark:bg-white/5">
                        <div class="flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-full bg-purple-500/10">
                                <flux:icon name="user" class="size-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p class="font-medium text-primary">{{ $member['email'] }}</p>
                                <p class="text-sm text-muted">{{ ucfirst($member['role']) }}</p>
                            </div>
                        </div>
                        <flux:button wire:click="removeTeamMember({{ $index }})" variant="ghost" size="sm">
                            <flux:icon name="x-mark" variant="micro" />
                        </flux:button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-black/5 bg-black/[0.02] py-10 text-center dark:border-white/5 dark:bg-white/[0.02]">
                <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-purple-500/10">
                    <flux:icon name="users" class="size-7 text-purple-400" />
                </div>
                <p class="mt-4 font-medium text-secondary">No team members added yet.</p>
                <p class="mt-1 text-sm text-muted">You can always add team members later.</p>
            </div>
        @endif

        @error('teamMembers')
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-between pt-4">
        <flux:button wire:click="goBack" variant="ghost" icon="arrow-left">
            Back
        </flux:button>

        <div class="flex gap-3">
            <flux:button wire:click="skipTeamStep" variant="ghost">
                Skip for now
            </flux:button>
            <button wire:click="completeTeamStep" class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold">
                {{ count($teamMembers) > 0 ? 'Send Invites & Continue' : 'Continue' }}
                <flux:icon name="arrow-right" variant="mini" class="ml-2 inline size-4" />
            </button>
        </div>
    </div>
</div>
