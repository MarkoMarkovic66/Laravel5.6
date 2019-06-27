<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHomeworkAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('homework_answers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('会員ID');
            $table->integer('category')->comment('1:レッスン 2:宿題（タスク）');
            $table->integer('task_type')->nullable()->comment('2:宿題(タスク）の場合のタスク種別');
            $table->integer('activity_id')->nullable()->comment('各該当のレッスンid、タスクidなど');
            $table->dateTime('activity_at')->nullable()->comment('当該の学習が行われた日時');
            $table->text('questionnaire')->nullable()->comment('設問');
            $table->text('answer')->nullable()->comment('回答');
            $table->integer('sound_file_id')->nullable()->comment('sound_filesテーブルのid');
            $table->text('remark')->nullable()->comment('備考');
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->index('user_id');
            $table->index(['category', 'activity_id']);
            $table->index(['task_type', 'activity_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('homework_answers');
    }
}
