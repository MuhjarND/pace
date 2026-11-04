<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class WidenEncryptedComplaintDescription extends Migration
{
    public function up()
    {
        // Encryption expands UTF-8 input; 10,000 four-byte characters can exceed a MySQL TEXT column.
        if (DB::connection()->getDriverName()==='mysql') DB::statement('ALTER TABLE complaints MODIFY description MEDIUMTEXT NOT NULL');
    }
    public function down() { /* Do not truncate encrypted reports when rolling back this widening. */ }
}
