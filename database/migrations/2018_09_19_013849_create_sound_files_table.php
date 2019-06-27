<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSoundFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sound_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('会員ID');
            $table->integer('material_type')->comment('レッスン、宿題、等のジャンル');
            $table->integer('material_id')->nullable()->comment('各該当レッスン、タスク等のid');
            $table->dateTime('activity_at')->nullable()->comment('当該の学習が行われた日時');
            $table->text('file_url')->nullable()->comment('音声ファイルURL');
            $table->text('file_name')->nullable()->comment('音声ファイル名');
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
        Schema::dropIfExists('sound_files');
    }
}
