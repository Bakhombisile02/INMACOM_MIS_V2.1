<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $inviteUrl,
        public readonly string $pin,
        public readonly string $roleLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You have been invited to INMACOM MIS');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.registration-invite');
    }
}
