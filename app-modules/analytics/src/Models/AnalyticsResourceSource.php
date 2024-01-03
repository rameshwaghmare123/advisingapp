<?php

namespace AdvisingApp\Analytics\Models;

use App\Models\BaseModel;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use AdvisingApp\Audit\Models\Concerns\Auditable as AuditableTrait;

class AnalyticsResourceSource extends BaseModel implements Auditable
{
    use SoftDeletes;
    use AuditableTrait;

    protected $fillable = [
        'name',
    ];

    public function resources(): HasMany
    {
        return $this->hasMany(AnalyticsResource::class, 'source_id');
    }
}
