<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable(['actor_id', 'action', 'entity_type', 'entity_id', 'summary', 'metadata'])]
class AuditLog extends Model
{
    protected $connection = 'mongodb';
}
