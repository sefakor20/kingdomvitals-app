<x-mail::message>
# {{ __('Thank You for Your Donation') }}

{{ __('Dear :name,', ['name' => $donorName]) }}

{{ __('Thank you for your generous donation to :church. Your contribution makes a difference in our community.', ['church' => $branch->name]) }}

<x-mail::panel>
**{{ __('Donation Details') }}**

- **{{ __('Amount') }}:** {{ $donation->currency }} {{ number_format((float) $donation->amount, 2) }}
- **{{ __('Date') }}:** {{ $donation->donation_date->format('F j, Y') }}
- **{{ __('Type') }}:** {{ str_replace('_', ' ', ucfirst($donation->donation_type->value)) }}
- **{{ __('Receipt Number') }}:** {{ $donation->getReceiptNumber() }}
</x-mail::panel>

{{ __('Your official donation receipt is attached to this email for your records.') }}

{{ __('Thank you for your continued support.') }}

{{ __('Blessings,') }}<br>
{{ $branch->name }}
</x-mail::message>
