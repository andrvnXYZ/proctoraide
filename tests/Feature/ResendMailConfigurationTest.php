<?php

namespace Tests\Feature;

use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendMailConfigurationTest extends TestCase
{
    public function test_resend_mail_transport_can_be_resolved(): void
    {
        config([
            'mail.default' => 'resend',
            'services.resend.key' => 're_test_key',
        ]);

        $this->assertInstanceOf(
            ResendTransport::class,
            Mail::mailer()->getSymfonyTransport(),
        );
    }
}
