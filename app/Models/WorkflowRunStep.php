<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowRunStep extends Model
{
    protected $fillable = [
        'workflow_run_id',
        'node_id',
        'status',
        'input_ref',
        'output_ref',
        'error_json',
        'sequence',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'error_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<WorkflowRun, $this>
     */
    public function workflowRun(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class);
    }
}
