<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWordCardTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('word_card_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('word_card_category_id')->nullable()->comment('単語カードカテゴリid');
            $table->integer('group_no')->nullable()->comment('単語セットのグループNo');
            $table->text('word')->nullable()->comment('単語設問');
            $table->text('answer')->nullable()->comment('回答');
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->index('word_card_category_id');
            $table->index('group_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('word_card_tasks');
    }
}
