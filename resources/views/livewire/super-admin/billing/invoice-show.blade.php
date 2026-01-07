<div>
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('superadmin.billing.invoices') }}" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Invoice') }} {{ $this->invoice->invoice_number }}</flux:heading>
                <div class="mt-1 flex items-center gap-2">
                    <flux:badge :color="$this->invoice->status->color()">
                        {{ $this->invoice->status->label() }}
                    </flux:badge>
                    @if($this->invoice->isOverdue())
                        <flux:text class="text-sm text-red-500">{{ $this->invoice->daysOverdue() }} days overdue</flux:text>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($this->invoice->status->canBeSent())
                <flux:button wire:click="sendInvoice" icon="paper-airplane">
                    {{ __('Send Invoice') }}
                </flux:button>
            @endif
            @if($this->invoice->status->canReceivePayment())
                <flux:button wire:click="openRecordPaymentModal" icon="banknotes" variant="primary">
                    {{ __('Record Payment') }}
                </flux:button>
            @endif
            <flux:button wire:click="downloadPdf" icon="arrow-down-tray" variant="ghost">
                {{ __('Download PDF') }}
            </flux:button>
            @if($this->invoice->status->canBeCancelled())
                <flux:button wire:click="openCancelModal" icon="x-mark" variant="danger">
                    {{ __('Cancel') }}
                </flux:button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Invoice Details --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Tenant & Plan Info --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <flux:text class="text-sm text-zinc-500">{{ __('Bill To') }}</flux:text>
                        <flux:heading size="lg" class="mt-1">{{ $this->invoice->tenant?->name ?? 'Unknown' }}</flux:heading>
                        @if($this->invoice->tenant?->contact_email)
                            <flux:text class="text-sm text-zinc-500">{{ $this->invoice->tenant->contact_email }}</flux:text>
                        @endif
                        @if($this->invoice->tenant?->contact_phone)
                            <flux:text class="text-sm text-zinc-500">{{ $this->invoice->tenant->contact_phone }}</flux:text>
                        @endif
                    </div>
                    <div class="text-right">
                        <flux:text class="text-sm text-zinc-500">{{ __('Subscription Plan') }}</flux:text>
                        <flux:heading size="lg" class="mt-1">{{ $this->invoice->subscriptionPlan?->name ?? 'N/A' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ $this->invoice->billing_period }}</flux:text>
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Line Items') }}</flux:heading>
                </div>
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Description') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-zinc-500">{{ __('Qty') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-zinc-500">{{ __('Unit Price') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-zinc-500">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->invoice->items as $item)
                            <tr>
                                <td class="px-4 py-3">{{ $item->description }}</td>
                                <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-right">{{ $this->invoice->currency }} {{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ $this->invoice->currency }} {{ number_format((float) $item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right text-sm">{{ __('Subtotal') }}</td>
                            <td class="px-4 py-2 text-right font-medium">{{ $this->invoice->currency }} {{ number_format((float) $this->invoice->subtotal, 2) }}</td>
                        </tr>
                        @if($this->invoice->discount_amount > 0)
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right text-sm text-green-600">{{ __('Discount') }}</td>
                                <td class="px-4 py-2 text-right font-medium text-green-600">-{{ $this->invoice->currency }} {{ number_format((float) $this->invoice->discount_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if($this->invoice->tax_amount > 0)
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right text-sm">{{ __('Tax') }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ $this->invoice->currency }} {{ number_format((float) $this->invoice->tax_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="border-t-2 border-zinc-300 dark:border-zinc-600">
                            <td colspan="3" class="px-4 py-3 text-right font-bold">{{ __('Total') }}</td>
                            <td class="px-4 py-3 text-right text-lg font-bold">{{ $this->invoice->currency }} {{ number_format((float) $this->invoice->total_amount, 2) }}</td>
                        </tr>
                        @if($this->invoice->amount_paid > 0)
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right text-sm text-green-600">{{ __('Paid') }}</td>
                                <td class="px-4 py-2 text-right font-medium text-green-600">-{{ $this->invoice->currency }} {{ number_format((float) $this->invoice->amount_paid, 2) }}</td>
                            </tr>
                        @endif
                        @if($this->invoice->balance_due > 0)
                            <tr class="bg-red-50 dark:bg-red-900/20">
                                <td colspan="3" class="px-4 py-3 text-right font-bold text-red-600 dark:text-red-400">{{ __('Balance Due') }}</td>
                                <td class="px-4 py-3 text-right text-lg font-bold text-red-600 dark:text-red-400">{{ $this->invoice->currency }} {{ number_format((float) $this->invoice->balance_due, 2) }}</td>
                            </tr>
                        @endif
                    </tfoot>
                </table>
            </div>

            {{-- Payment History --}}
            @if($this->invoice->payments->isNotEmpty())
                <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:heading size="lg">{{ __('Payment History') }}</flux:heading>
                    </div>
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Reference') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Method') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-zinc-500">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-zinc-500">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($this->invoice->payments as $payment)
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $payment->paid_at?->format('M d, Y g:i A') ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono text-sm">{{ $payment->payment_reference }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $payment->payment_method->label() }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <flux:badge :color="$payment->status->color()" size="sm">
                                            {{ $payment->status->label() }}
                                        </flux:badge>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Notes --}}
            @if($this->invoice->notes)
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="lg" class="mb-2">{{ __('Notes') }}</flux:heading>
                    <flux:text class="whitespace-pre-wrap text-zinc-600 dark:text-zinc-400">{{ $this->invoice->notes }}</flux:text>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Invoice Info --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-4">{{ __('Invoice Details') }}</flux:heading>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-zinc-500">{{ __('Issue Date') }}</dt>
                        <dd class="text-sm font-medium">{{ $this->invoice->issue_date->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-zinc-500">{{ __('Due Date') }}</dt>
                        <dd class="text-sm font-medium">{{ $this->invoice->due_date->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-zinc-500">{{ __('Period Start') }}</dt>
                        <dd class="text-sm font-medium">{{ $this->invoice->period_start->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-zinc-500">{{ __('Period End') }}</dt>
                        <dd class="text-sm font-medium">{{ $this->invoice->period_end->format('M d, Y') }}</dd>
                    </div>
                    @if($this->invoice->sent_at)
                        <div class="flex justify-between">
                            <dt class="text-sm text-zinc-500">{{ __('Sent At') }}</dt>
                            <dd class="text-sm font-medium">{{ $this->invoice->sent_at->format('M d, Y g:i A') }}</dd>
                        </div>
                    @endif
                    @if($this->invoice->paid_at)
                        <div class="flex justify-between">
                            <dt class="text-sm text-zinc-500">{{ __('Paid At') }}</dt>
                            <dd class="text-sm font-medium text-green-600">{{ $this->invoice->paid_at->format('M d, Y g:i A') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Reminders Sent --}}
            @if($this->invoice->reminders->isNotEmpty())
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="lg" class="mb-4">{{ __('Reminders Sent') }}</flux:heading>
                    <ul class="space-y-2">
                        @foreach($this->invoice->reminders as $reminder)
                            <li class="flex items-center justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">{{ $reminder->getTypeLabel() }}</span>
                                <span class="text-zinc-500">{{ $reminder->sent_at->format('M d') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    {{-- Record Payment Modal --}}
    <flux:modal wire:model="showRecordPaymentModal" class="max-w-lg">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Record Payment') }}</flux:heading>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Amount') }}</flux:label>
                    <flux:input type="number" step="0.01" wire:model="paymentAmount" />
                    <flux:error name="paymentAmount" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Payment Method') }}</flux:label>
                    <flux:select wire:model="paymentMethod">
                        @foreach($this->paymentMethods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="paymentMethod" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Notes') }} ({{ __('Optional') }})</flux:label>
                    <flux:textarea wire:model="paymentNotes" rows="3" />
                    <flux:error name="paymentNotes" />
                </flux:field>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showRecordPaymentModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="recordPayment">
                    {{ __('Record Payment') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Cancel Invoice Modal --}}
    <flux:modal wire:model="showCancelModal" class="max-w-lg">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Cancel Invoice') }}</flux:heading>
            <flux:text class="mb-4 text-zinc-600 dark:text-zinc-400">
                {{ __('Are you sure you want to cancel this invoice? This action cannot be undone.') }}
            </flux:text>

            <flux:field>
                <flux:label>{{ __('Reason for Cancellation') }}</flux:label>
                <flux:textarea wire:model="cancelReason" rows="3" placeholder="{{ __('Enter reason...') }}" />
                <flux:error name="cancelReason" />
            </flux:field>

            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showCancelModal', false)">
                    {{ __('Keep Invoice') }}
                </flux:button>
                <flux:button variant="danger" wire:click="cancelInvoice">
                    {{ __('Cancel Invoice') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
