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

namespace Assist\Engagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Assist\Engagement\Models\Engagement;
use Assist\Engagement\Models\EngagementDeliverable;

class EngagementSeeder extends Seeder
{
    public function run(): void
    {
        // For Student - deliver now
        Engagement::factory()
            ->count(10)
            ->has(EngagementDeliverable::factory()->deliverySuccessful()->count(1), 'engagementDeliverables')
            ->forStudent()
            ->deliverNow()
            ->create();

        // For Student - deliver later
        Engagement::factory()
            ->count(7)
            ->has(EngagementDeliverable::factory()->count(1), 'engagementDeliverables')
            ->forStudent()
            ->deliverLater()
            ->create();

        // For Prospect - deliver now
        Engagement::factory()
            ->count(10)
            ->has(EngagementDeliverable::factory()->deliverySuccessful()->count(1), 'engagementDeliverables')
            ->forProspect()
            ->deliverNow()
            ->create();

        // For Prospect - deliver later
        Engagement::factory()
            ->count(7)
            ->has(EngagementDeliverable::factory()->count(1), 'engagementDeliverables')
            ->forProspect()
            ->deliverLater()
            ->create();
    }
}
