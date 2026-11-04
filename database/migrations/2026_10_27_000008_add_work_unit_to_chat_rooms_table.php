<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddWorkUnitToChatRoomsTable extends Migration
{
    public function up()
    {
        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->string('work_unit')->nullable()->after('admin_id');
            $table->index(['work_unit', 'status']);
        });

        DB::table('chat_rooms')->orderBy('id')->chunkById(100, function ($rooms) {
            foreach ($rooms as $room) {
                $workUnit = DB::table('users')->where('id', $room->user_id)->value('work_unit');
                $updates = ['work_unit' => $workUnit];

                if ($room->status === 'open') {
                    $assigned = DB::table('users')->where('id', $room->admin_id)->first();
                    if (!$workUnit || !$assigned || $assigned->work_unit !== $workUnit
                        || !in_array($assigned->role, ['admin', 'operator'], true)) {
                        $updates['admin_id'] = $workUnit
                            ? DB::table('users')->whereIn('role', ['operator', 'admin'])
                                ->where('work_unit', $workUnit)->orderByDesc('is_online')
                                ->orderByRaw("CASE WHEN role = 'operator' THEN 0 ELSE 1 END")
                                ->orderBy('id')->value('id')
                            : null;
                    }
                }

                DB::table('chat_rooms')->where('id', $room->id)->update($updates);
            }
        });
    }

    public function down()
    {
        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->dropIndex(['work_unit', 'status']);
            $table->dropColumn('work_unit');
        });
    }
}
