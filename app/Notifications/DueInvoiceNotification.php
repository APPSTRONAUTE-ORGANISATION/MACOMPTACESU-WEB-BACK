<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DueInvoiceNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Invoice $invoice)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('MACOMPTA Invoice')
            ->greeting('Hello!')
            ->line("Your invoice of " . $this->invoice->Activity->name . " with " . $this->invoice->User->first_name . " " . $this->invoice->User->last_name . " is due.")
            ->line('Invoice Date: ' . $this->invoice->created_at->toDateString())
            ->line('Due Date: ' . $this->invoice->due_date->toDateString())
            ->line('Invoice Amount: ' . number_format($this->invoice->total, 2))
            ->line('Please make sure to pay as soon as possible.')
            ->line('Thank you!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
