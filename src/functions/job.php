<?php

use Illuminate\Support\Facades\DB;

function cleanMaxAttemptJobs($attempts = 250)
{
    $rows = DB::table('jobs')->where('attempts', '>=', $attempts)->delete();
    return $rows;
}
