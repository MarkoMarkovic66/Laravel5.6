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
        Schema::dropIfExists('listening_tasks');

        Schema::create('listening_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('listening_type', 4)->nullable()->comment('レベル毎の問題区分　"B"：listening-B , "C"：listening-C , "D"：listening-D');
            $table->string('unit_name', 255)->nullable()->comment('Unit名');
            $table->string('subject_name', 255)->nullable()->comment('Subject名');
            $table->text('hearing_text')->nullable()->comment('パラグラフ');
            $table->text('question')->nullable()->comment('設問文');
            $table->text('answer')->nullable()->comment('回答文');
            $table->text('file_url1')->nullable()->comment('音源ファイルパス1');
            $table->text('file_url2')->nullable()->comment('音源ファイルパス2');
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->index('listening_type');
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
