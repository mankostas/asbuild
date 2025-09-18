<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Client $client)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Welcome to ' . config('app.name'))
            ->view('emails.clients.welcome', [
                'client' => $this->client,
                'appName' => config('app.name'),
            ]);
    }
}
