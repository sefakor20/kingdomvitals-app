<x-layouts.app>
    <div class="flex min-h-[60vh] items-center justify-center px-4 py-12">
        <div class="w-full max-w-2xl">
            <div class="text-center">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon name="lock-closed" class="h-10 w-10 text-red-600 dark:text-red-400" />
                </div>

                @if($isBillingAdmin)
                    <flux:heading size="xl" class="mb-4">
                        {{ __('Payment Required') }}
                    </flux:heading>

                    <flux:text class="mb-8 text-zinc-600 dark:text-zinc-400">
                        {{ __('Your access has been paused because of unpaid invoices. Settle the outstanding balance to restore full access to your records.') }}
                    </flux:text>
                @else
                    <flux:heading size="xl" class="mb-4">
                        {{ __('Service Paused') }}
                    </flux:heading>

                    <flux:text class="mb-8 text-zinc-600 dark:text-zinc-400">
                        {{ __('Access to your church\'s system is temporarily paused while billing is being settled. Please contact your church administrator to restore access.') }}
                    </flux:text>
                @endif
            </div>

            @if($isBillingAdmin && $invoices->isNotEmpty())
                <div class="mb-8 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-start text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr class="text-start text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                <th class="px-4 py-3 text-start">{{ __('Invoice') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Period') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('Amount Due') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('Days Past Due') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-sm">{{ $invoice->invoice_number }}</td>
                                    <td class="px-4 py-3">{{ $invoice->billing_period }}</td>
                                    <td class="px-4 py-3 text-end font-medium">
                                        {{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <flux:badge color="red" size="sm">{{ $invoice->daysOverdue() }} {{ __('days') }}</flux:badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="space-y-3">
                @if($isBillingAdmin)
                    <flux:button variant="primary" class="w-full" icon="credit-card" :href="route('payments.history')" wire:navigate>
                        {{ __('Pay Now') }}
                    </flux:button>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:button variant="ghost" :href="route('subscription.show')" wire:navigate>
                            {{ __('Manage Subscription') }}
                        </flux:button>

                        <flux:button variant="ghost" :href="route('plans.index')" wire:navigate>
                            {{ __('Change Plan') }}
                        </flux:button>
                    </div>
                @endif

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:button type="submit" variant="subtle" class="w-full" icon="arrow-right-start-on-rectangle">
                        {{ __('Sign Out') }}
                    </flux:button>
                </form>
            </div>

            @if($isBillingAdmin)
                <div class="mt-8 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Need help with your invoice or billing? Contact support and we\'ll get you sorted.') }}
                    </flux:text>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
