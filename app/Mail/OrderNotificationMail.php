<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Correo genérico de confirmación sobre un pedido (creación, asignación, entrega) */
class OrderNotificationMail extends Mailable
{
    public function __construct(
        public Order $order,
        public string $subjectLine,
        public string $heading,
        public string $intro,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order');
    }
}
