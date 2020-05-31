<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function cleanMaxAttemptJobs($attempts = 250)
{
    $rows = DB::table('jobs')->where('attempts', '>=', $attempts)->delete();
    return $rows;
}

function dropIndexIfExist($tableName, $indexName)
{
    Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName) {
        $sm            = Schema::getConnection()->getDoctrineSchemaManager();
        $doctrineTable = $sm->listTableDetails($tableName);

        if ($doctrineTable->hasIndex($indexName)) {
            $table->dropIndex($indexName);
        }
    });
}
