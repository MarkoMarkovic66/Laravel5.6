<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserListeningAssignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_listening_assigns', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('会員ID');
            $table->integer('listening_type')->comment('問題種別');
            $table->integer('assigned_id')->comment('最終出題問題のid');
            $table->datetime('assigned_date')->nullable()->comment('最新タスク割り当て日時');
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->index('user_id');
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
        Schema::dropIfExists('user_listening_assigns');
    }
}
