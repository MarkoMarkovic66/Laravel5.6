<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateListeningTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('listening_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('unit_name', 255)->nullable()->comment('ユニット名');
            $table->string('subject_name', 255)->nullable()->comment('サブジェクト名');
            $table->text('question')->nullable()->comment('設問');
            $table->text('answer')->nullable()->comment('回答');
            $table->text('file_url')->nullable()->comment('音源ファイルパス');
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('listening_tasks');
    }
}
