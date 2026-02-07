<?php

namespace App\Livewire\Landing;

use App\Mail\ContactFormSubmission;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ContactForm extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:255')]
    public string $church = '';

    #[Validate('nullable|string|max:50')]
    public string $size = '';

    #[Validate('required|string|max:2000')]
    public string $message = '';

    public bool $submitted = false;

    public function submit(): void
    {
        $this->validate();

        // Send email notification using Mailable
        Mail::to(config('mail.from.address', 'hello@kingdomvitals.app'))
            ->send(new ContactFormSubmission(
                senderName: $this->name,
                senderEmail: $this->email,
                church: $this->church,
                size: $this->size,
                senderMessage: $this->message,
            ));

        $this->submitted = true;

        // Reset form
        $this->reset(['name', 'email', 'church', 'size', 'message']);
    }

    public function render()
    {
        return view('livewire.landing.contact-form');
    }
}
