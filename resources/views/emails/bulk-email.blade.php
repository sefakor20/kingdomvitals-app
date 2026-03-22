<x-mail::message>
{!! $body !!}

@if($branch)
{{ __('Best regards,') }}<br>
{{ $branch->name }}
@endif
</x-mail::message>
