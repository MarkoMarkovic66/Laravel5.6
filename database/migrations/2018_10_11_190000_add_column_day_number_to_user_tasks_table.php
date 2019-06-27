<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnDayNumberToUserTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_tasks', function (Blueprint $table) {
            $table->dropColumn('task_period_order');
        });

        Schema::table('user_tasks', function (Blueprint $table) {

            $table->integer('task_type')
                    ->nullable()
                    ->after('task_id')
                    ->comment('タスク種別id');

            $table->integer('day_number')
                    ->nullable()
                    ->after('task_type')
                    ->comment('曜日番号 0:日曜日...6:土曜日');

            $table->integer('task_period_order')
                    ->nullable()
                    ->after('day_number')
                    ->comment('タスク出題枠番号');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_tasks', function (Blueprint $table) {
            $table->dropColumn('task_type');
            $table->dropColumn('day_number');
            $table->dropColumn('task_period_order');
        });
    }
}
