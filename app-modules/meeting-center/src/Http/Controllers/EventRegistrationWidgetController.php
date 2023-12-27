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

namespace AdvisingApp\MeetingCenter\Http\Controllers;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use AdvisingApp\Survey\Models\Survey;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;
use AdvisingApp\Survey\Models\SurveySubmission;
use AdvisingApp\Form\Actions\GenerateFormKitSchema;
use AdvisingApp\MeetingCenter\Models\EventAttendee;
use AdvisingApp\MeetingCenter\Enums\EventAttendeeStatus;
use AdvisingApp\Form\Actions\GenerateSubmissibleValidation;
use AdvisingApp\MeetingCenter\Models\EventRegistrationForm;
use AdvisingApp\Application\Models\ApplicationAuthentication;
use AdvisingApp\Form\Actions\ResolveSubmissionAuthorFromEmail;
use AdvisingApp\Form\Filament\Blocks\EducatableEmailFormFieldBlock;
use AdvisingApp\MeetingCenter\Models\EventRegistrationFormAuthentication;
use AdvisingApp\IntegrationGoogleRecaptcha\Settings\GoogleRecaptchaSettings;
use AdvisingApp\Form\Notifications\AuthenticateEventRegistrationFormNotification;

class EventRegistrationWidgetController extends Controller
{
    public function view(GenerateFormKitSchema $generateSchema, EventRegistrationForm $form): JsonResponse
    {
        return response()->json(
            [
                'name' => $form->event->title,
                'description' => $form->event->description,
                // TODO: Maybe get rid of this? It would never not be authenticated.
                'is_authenticated' => true,
                'authentication_url' => URL::signedRoute('event-registration.request-authentication', ['form' => $form]),
                'recaptcha_enabled' => $form->recaptcha_enabled,
                ...($form->recaptcha_enabled ? [
                    'recaptcha_site_key' => app(GoogleRecaptchaSettings::class)->site_key,
                ] : []),
                'schema' => $generateSchema($form),
                'primary_color' => Color::all()[$form->primary_color ?? 'blue'],
                'rounding' => $form->rounding,
            ],
        );
    }

    public function requestAuthentication(Request $request, EventRegistrationForm $form): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $attendee = EventAttendee::firstOrNew(
            [
                'email' => $data['email'],
                'event_id' => $form->event_id,
            ],
        );

        if (empty($attendee->status)) {
            $attendee->status = EventAttendeeStatus::Pending;
        }

        // TODO: When an EventAttendee is created, we should try and match it to an entity, perhaps in an observer.
        $attendee->save();

        $code = random_int(100000, 999999);

        $authentication = new EventRegistrationFormAuthentication();
        $authentication->author()->associate($attendee);
        $authentication->submissible()->associate($form);
        $authentication->code = Hash::make($code);
        $authentication->save();

        Notification::route('mail', $attendee->email)->notify(new AuthenticateEventRegistrationFormNotification($authentication, $code));

        return response()->json([
            'message' => "We've sent an authentication code to {$attendee->email}.",
            'authentication_url' => URL::signedRoute('event-registration.authenticate', [
                'form' => $form,
                'authentication' => $authentication,
            ]),
        ]);
    }

    public function authenticate(Request $request, Survey $survey, ApplicationAuthentication $authentication): JsonResponse
    {
        if ($authentication->isExpired()) {
            return response()->json([
                'is_expired' => true,
            ]);
        }

        $request->validate([
            'code' => ['required', 'integer', 'digits:6', function (string $attribute, int $value, Closure $fail) use ($authentication) {
                if (Hash::check($value, $authentication->code)) {
                    return;
                }

                $fail('The provided code is invalid.');
            }],
        ]);

        return response()->json([
            'submission_url' => URL::signedRoute('surveys.submit', [
                'survey' => $authentication,
                'application' => $authentication->submissible,
            ]),
        ]);
    }

    public function store(
        Request $request,
        GenerateSubmissibleValidation $generateValidation,
        ResolveSubmissionAuthorFromEmail $resolveSubmissionAuthorFromEmail,
        Survey $survey,
    ): JsonResponse {
        $authentication = $request->query('authentication');

        if (filled($authentication)) {
            $authentication = ApplicationAuthentication::findOrFail($authentication);
        }

        if (
            $survey->is_authenticated &&
            ($authentication?->isExpired() ?? true)
        ) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $validator = Validator::make(
            $request->all(),
            $generateValidation($survey)
        );

        if ($validator->fails()) {
            return response()->json(
                [
                    'errors' => (object) $validator->errors(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        /** @var SurveySubmission $submission */
        $submission = $survey->submissions()->make();

        if ($authentication) {
            $submission->author()->associate($authentication->author);

            $authentication->delete();
        }

        $submission->submitted_at = now();

        $submission->save();

        $data = $validator->validated();

        unset($data['recaptcha-token']);

        if ($survey->is_wizard) {
            foreach ($survey->steps as $step) {
                $stepFields = $step->fields()->pluck('type', 'id')->all();

                foreach ($data[$step->label] as $fieldId => $response) {
                    $submission->fields()->attach(
                        $fieldId,
                        ['id' => Str::orderedUuid(), 'response' => $response],
                    );

                    if ($submission->author) {
                        continue;
                    }

                    if ($stepFields[$fieldId] !== EducatableEmailFormFieldBlock::type()) {
                        continue;
                    }

                    $author = $resolveSubmissionAuthorFromEmail($response);

                    if (! $author) {
                        continue;
                    }

                    $submission->author()->associate($author);
                }
            }
        } else {
            $surveyFields = $survey->fields()->pluck('type', 'id')->all();

            foreach ($data as $fieldId => $response) {
                $submission->fields()->attach(
                    $fieldId,
                    ['id' => Str::orderedUuid(), 'response' => $response],
                );

                if ($submission->author) {
                    continue;
                }

                if ($surveyFields[$fieldId] !== EducatableEmailFormFieldBlock::type()) {
                    continue;
                }

                $author = $resolveSubmissionAuthorFromEmail($response);

                if (! $author) {
                    continue;
                }

                $submission->author()->associate($author);
            }
        }

        $submission->save();

        return response()->json(
            [
                'message' => 'Survey submitted successfully.',
            ]
        );
    }
}
