<?php

namespace Assist\Assistant\Notifications;

use App\Models\User;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Notifications\MailMessage;
use Assist\Assistant\Models\AssistantChat;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Assist\Assistant\Services\AIInterface\Enums\AIChatMessageFrom;

class SendAssistantTranscriptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected AssistantChat $chat,
        protected User $sender
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $message = MailMessage::make()
            ->emailTemplate($this->resolveEmailTemplate())
            ->greeting("Hello {$notifiable->name},");

        $senderIsNotifiable = $this->sender->is($notifiable);

        if ($senderIsNotifiable) {
            $message->subject("Assistant Chat Transcript: {$this->chat->name}")
                ->line('Here is a copy of your chat with Canyon:');
        } else {
            $message->subject("An Assistant Chat Transcript has been shared with you: {$this->chat->name}")
                ->line("Here is a copy of {$this->sender->name}'s chat with Canyon:");
        }

        $this->chat
            ->messages
            ->each(function ($chatMessage) use ($senderIsNotifiable, $message) {
                if ($chatMessage->from === AIChatMessageFrom::User) {
                    if ($senderIsNotifiable) {
                        $message->line("You: {$chatMessage->message}");
                    } else {
                        $message->line("{$this->sender->name}: {$chatMessage->message}");
                    }
                } else {
                    $message->line("Canyon: {$chatMessage->message}");
                }
            });

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }

    private function resolveEmailTemplate(): ?EmailTemplate
    {
        return null;
    }
}
