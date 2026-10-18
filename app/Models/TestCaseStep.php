<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestCaseStep extends Model
{
    protected $fillable = [
        'test_case_id',
        'position',
        'action',
        'expected_result',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }
}
