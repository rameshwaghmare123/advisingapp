<?php

namespace Assist\AssistDataModel\Models;

use Eloquent;
use Assist\Task\Models\Task;
use Assist\Audit\Models\Audit;
use Illuminate\Database\Eloquent\Model;
use Assist\Engagement\Models\Engagement;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Assist\Engagement\Models\EngagementFile;
use Illuminate\Database\Eloquent\Collection;
use Assist\Notifications\Models\Subscription;
use Assist\Engagement\Models\EngagementResponse;
use Illuminate\Notifications\DatabaseNotification;
use Assist\ServiceManagement\Models\ServiceRequest;
use Assist\Engagement\Models\EngagementFileEntities;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Assist\Notifications\Models\Contracts\Subscribable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Assist\Authorization\Models\Concerns\DefinesPermissions;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Assist\Audit\Models\Concerns\Auditable as AuditableTrait;
use Assist\Engagement\Models\Concerns\HasManyMorphedEngagements;
use Assist\Engagement\Models\Concerns\HasManyMorphedEngagementResponses;

/**
 * Assist\AssistDataModel\Models\Student
 *
 * @property-read Collection<int, Audit> $audits
 * @property-read int|null $audits_count
 * @property-read Collection<int, ServiceRequest> $cases
 * @property-read int|null $cases_count
 * @property-read Collection<int, EngagementFile> $engagementFiles
 * @property-read int|null $engagement_files_count
 * @property-read Collection<int, EngagementResponse> $engagementResponses
 * @property-read int|null $engagement_responses_count
 * @property-read Collection<int, Engagement> $engagements
 * @property-read int|null $engagements_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read Collection<int, Task> $tasks
 * @property-read int|null $tasks_count
 *
 * @method static \Assist\AssistDataModel\Database\Factories\StudentFactory factory($count = null, $state = [])
 * @method static Builder|Student newModelQuery()
 * @method static Builder|Student newQuery()
 * @method static Builder|Student query()
 *
 * @mixin Eloquent
 */
class Student extends Model implements Auditable, Subscribable
{
    use AuditableTrait;
    use HasFactory;
    use DefinesPermissions;
    use Notifiable;
    use HasManyMorphedEngagements;
    use HasManyMorphedEngagementResponses;

    protected $primaryKey = 'sisid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'sisid' => 'string',
    ];

    public function cases(): MorphMany
    {
        return $this->morphMany(
            related: ServiceRequest::class,
            name: 'respondent',
            type: 'respondent_type',
            id: 'respondent_id',
            localKey: 'sisid'
        );
    }

    public function engagementFiles(): MorphToMany
    {
        return $this->morphToMany(
            related: EngagementFile::class,
            name: 'entity',
            table: 'engagement_file_entities',
            foreignPivotKey: 'entity_id',
            relatedPivotKey: 'engagement_file_id',
            relation: 'engagementFiles',
        )
            ->using(EngagementFileEntities::class)
            ->withTimestamps();
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'concern');
    }

    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }
}
