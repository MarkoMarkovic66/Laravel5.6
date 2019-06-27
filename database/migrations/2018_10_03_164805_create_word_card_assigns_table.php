<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWordCardAssignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('word_card_assigns', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('会員ID');
            $table->integer('word_card_category_id')->comment('単語カードマスタ　カテゴリid');
            $table->integer('group_no')->comment('単語カードマスタ　グループNo');
            $table->integer('last_assigned_id')->comment('最終出題問題のid');
            $table->datetime('last_assigned_date')->nullable()->comment('最新タスク割り当て日時');
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->index('user_id');
            $table->index(['word_card_category_id', 'group_no']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('word_card_assigns');
    }
}
