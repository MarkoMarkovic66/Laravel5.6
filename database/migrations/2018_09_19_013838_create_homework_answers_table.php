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
            $table->integer('material_type')->comment('レッスン、宿題、等のジャンル');
            $table->integer('material_id')->nullable()->comment('各該当レッスン、タスク等のid');
            $table->dateTime('activity_at')->nullable()->comment('当該の学習が行われた日時');
            $table->text('questionnaire')->nullable()->comment('設問');
            $table->text('answer')->nullable()->comment('回答');
            $table->integer('sound_file_id')->nullable()->comment('sound_filesテーブルのid');
            $table->text('remark')->nullable()->comment('備考');
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->index('user_id');
            $table->index(['material_type', 'material_id']);
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
