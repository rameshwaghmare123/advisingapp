<?php

/*
<COPYRIGHT>

Copyright © 2022-2023, Canyon GBS LLC

All rights reserved.

This file is part of a project developed using Laravel, which is an open-source framework for PHP.
Canyon GBS LLC acknowledges and respects the copyright of Laravel and other open-source
projects used in the development of this solution.

This project is licensed under the Affero General Public License (AGPL) 3.0.
For more details, see https://github.com/canyongbs/assistbycanyongbs/blob/main/LICENSE.

Notice:
- The copyright notice in this file and across all files and applications in this
 repository cannot be removed or altered without violating the terms of the AGPL 3.0 License.
- The software solution, including services, infrastructure, and code, is offered as a
 Software as a Service (SaaS) by Canyon GBS LLC.
- Use of this software implies agreement to the license terms and conditions as stated
 in the AGPL 3.0 License.

For more information or inquiries please visit our website at
https://www.canyongbs.com or contact us via email at legal@canyongbs.com.

</COPYRIGHT>
*/

namespace Assist\AssistDataModel\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Assist\AssistDataModel\Models\Enrollment;

class EnrollmentPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->canOrElse(
            abilities: 'enrollment.view-any',
            denyResponse: 'You do not have permission to view enrollments.'
        );
    }

    public function view(User $user, Enrollment $enrollment): Response
    {
        return $user->canOrElse(
            abilities: ['enrollment.*.view', "enrollment.{$enrollment->id}.view"],
            denyResponse: 'You do not have permission to view this enrollment.'
        );
    }

    public function create(User $user): Response
    {
        return Response::deny('Enrollments cannot be created.');
    }

    public function update(User $user, Enrollment $enrollment): Response
    {
        return Response::deny('Enrollments cannot be updated.');
    }

    public function delete(User $user, Enrollment $enrollment): Response
    {
        return Response::deny('Enrollments cannot be deleted.');
    }

    public function restore(User $user, Enrollment $enrollment): Response
    {
        return Response::deny('Enrollments cannot be restored.');
    }

    public function forceDelete(User $user, Enrollment $enrollment): Response
    {
        return Response::deny('Enrollments cannot be force deleted.');
    }
}
