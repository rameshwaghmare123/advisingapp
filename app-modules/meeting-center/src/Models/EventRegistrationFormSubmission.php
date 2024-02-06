<?php

/*
<COPYRIGHT>

    Copyright © 2016-2024, Canyon GBS LLC. All rights reserved.

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

namespace AdvisingApp\MeetingCenter\Models;

use App\Models\User;
use AdvisingApp\Form\Models\Submission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use AdvisingApp\MeetingCenter\Enums\EventAttendeeStatus;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use AdvisingApp\Form\Enums\FormSubmissionRequestDeliveryMethod;

/**
 * @mixin IdeHelperEventRegistrationFormSubmission
 */
class EventRegistrationFormSubmission extends Submission
{
    protected $fillable = [
        'canceled_at',
        'form_id',
        'attendee_status',
        'request_method',
        'request_note',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'immutable_datetime',
        'canceled_at' => 'immutable_datetime',
        'request_method' => FormSubmissionRequestDeliveryMethod::class,
        'attendee_status' => EventAttendeeStatus::class,
    ];

    public function submissible(): BelongsTo
    {
        return $this
            ->belongsTo(EventRegistrationForm::class, 'form_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(
            EventRegistrationFormField::class,
            'event_registration_form_field_submission',
            'submission_id',
            'field_id',
        )
            ->withPivot(['id', 'response']);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(EventAttendee::class, 'event_attendee_id');
    }
}
