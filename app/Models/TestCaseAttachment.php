<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestCaseAttachment extends Model
{
    protected $fillable = [
        'test_case_id',
        'user_id',
        'name',
        'path',
        'thumb',
        'type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
