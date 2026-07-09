<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestTestResult extends Model
{
    protected $guarded = [];

    public function test()
    {
        return $this->belongsTo(QuestTest::class, 'quest_test_id');
    }
}
