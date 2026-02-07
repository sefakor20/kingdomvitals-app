<x-mail::message>
# New Contact Form Submission

You have received a new message from the Kingdom Vitals website.

**From:** {{ $senderName }}

**Email:** {{ $senderEmail }}

@if($church)
**Church:** {{ $church }}
@endif

@if($size)
**Church Size:** {{ $size }}
@endif

---

**Message:**

{{ $senderMessage }}

<x-mail::button :url="'mailto:' . $senderEmail">
Reply to {{ $senderName }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
