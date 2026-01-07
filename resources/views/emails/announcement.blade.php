<x-mail::message>
# {{ $announcement->title }}

{{ __('Dear :name,', ['name' => $tenant->name]) }}

{!! nl2br(e($announcement->content)) !!}

@if($priority->value !== 'normal')
<x-mail::panel>
@if($priority->value === 'urgent')
**{{ __('This is an urgent announcement that requires your immediate attention.') }}**
@else
**{{ __('This is an important announcement. Please review carefully.') }}**
@endif
</x-mail::panel>
@endif

{{ __('Best regards,') }}<br>
{{ config('app.name') }}
</x-mail::message>
