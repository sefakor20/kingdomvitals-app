<x-mail::message>
{!! nl2br(e($messageBody)) !!}

{{ __('Best regards,') }}<br>
{{ $branch->name }}
</x-mail::message>
