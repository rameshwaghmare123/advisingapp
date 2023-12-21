<?php

/*
<COPYRIGHT>

    Copyright © 2022-2023, Canyon GBS LLC. All rights reserved.

    Advising App™ is licensed under the Elastic License 2.0. For more details,
    see https://github.com/canyongbs/advisingapp/blob/main/LICENSE.

    Notice:

    - You may not provide the software to third parties as a hosted or managed
      service, where the service provides users with access to any substantial set of
      the features or functionality of the software.
    - You may not move, change, disable, or circumvent the license key functionality
      in the software, and you may not remove or obscure any functionality in the
      software that is protected by the license key.
    - You may not alter, remove, or obscure any licensing, copyright, or other notices
      of the licensor in the software. Any use of the licensor’s trademarks is subject
      to applicable law.
    - Canyon GBS LLC respects the intellectual property rights of others and expects the
      same in return. Canyon GBS™ and Advising App™ are registered trademarks of
      Canyon GBS LLC, and we are committed to enforcing and protecting our trademarks
      vigorously.
    - The software solution, including services, infrastructure, and code, is offered as a
      Software as a Service (SaaS) by Canyon GBS LLC.
    - Use of this software implies agreement to the license terms and conditions as stated
      in the Elastic License 2.0.

    For more information or inquiries please visit our website at
    https://www.canyongbs.com or contact us via email at legal@canyongbs.com.

</COPYRIGHT>
*/

namespace AdvisingApp\Notification\Notifications\Channels;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use AdvisingApp\Notification\Messages\TwilioMessage;
use AdvisingApp\Notification\Models\OutboundDeliverable;
use AdvisingApp\Notification\Notifications\SmsNotification;
use AdvisingApp\Notification\Notifications\BaseNotification;
use AdvisingApp\Notification\Enums\NotificationDeliveryStatus;
use AdvisingApp\Notification\DataTransferObjects\SmsChannelResultData;
use AdvisingApp\Notification\DataTransferObjects\NotificationResultData;

class SmsChannel
{
    public function send(object $notifiable, BaseNotification $notification): void
    {
        $deliverable = $notification->beforeSend($notifiable, SmsChannel::class);

        if ($deliverable === false) {
            // Do anything else we need to to notify sending party that notification was not sent
            return;
        }

        $smsData = $this->handle($notifiable, $notification);

        $notification->afterSend($notifiable, $deliverable, $smsData);
    }

    public function handle(object $notifiable, BaseNotification $notification): NotificationResultData
    {
        /** @var SmsNotification $notification */

        /** @var TwilioMessage $twilioMessage */
        $twilioMessage = $notification->toSms($notifiable);

        $client = new Client(config('services.twilio.account_sid'), config('services.twilio.auth_token'));

        $messageContent = [
            'from' => $twilioMessage->getFrom(),
            'body' => $twilioMessage->getContent(),
        ];

        if (! app()->environment('local')) {
            $messageContent['statusCallback'] = route('inbound.webhook.twilio', ['event' => 'status_callback']);
        }

        $result = SmsChannelResultData::from([
            'success' => false,
        ]);

        try {
            $message = $client->messages->create(
                ! is_null(config('services.twilio.test_to_number')) ? config('services.twilio.test_to_number') : $twilioMessage->getRecipientPhoneNumber(),
                $messageContent
            );

            $result->success = true;
            $result->message = $message;
        } catch (TwilioException $e) {
            $result->error = $e->getMessage();
        }

        return $result;
    }

    public static function afterSending(object $notifiable, OutboundDeliverable $deliverable, SmsChannelResultData $result): void
    {
        if ($result->success) {
            $deliverable->update([
                'external_reference_id' => $result->message->sid,
                'external_status' => $result->message->status,
                'delivery_status' => NotificationDeliveryStatus::Successful,
            ]);
        } else {
            $deliverable->update([
                'delivery_status' => NotificationDeliveryStatus::Failed,
                'delivery_response' => $result->error,
            ]);
        }
    }
}
