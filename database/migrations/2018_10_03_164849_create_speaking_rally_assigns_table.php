<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpeakingRallyAssignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('speaking_rally_assigns', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('会員ID');
            $table->integer('category')->comment('Topicカテゴリ');
            $table->integer('last_assigned_id')->comment('最終出題問題のid');
            $table->datetime('last_assigned_date')->nullable()->comment('最新タスク割り当て日時');
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('speaking_rally_assigns');
    }
}
