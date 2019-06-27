<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTaskCalendarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_task_calendars', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('会員ID');
            $table->tinyInteger('day_number')->default(0)->comment('曜日番号 0:日 1:月...6:土');
            $table->integer('task_type')->comment('タスク種別');
            $table->integer('task_id')->comment('タスクid');
            $table->integer('task_period_order')->comment('タスク順序');
            $table->text('comment')->nullable()->comment('備考');
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->index(['user_id', 'day_number']);
            $table->index(['user_id', 'task_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_task_calendars');
    }
}
