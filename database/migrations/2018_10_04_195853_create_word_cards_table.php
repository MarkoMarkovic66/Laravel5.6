<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWordCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('word_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('word_card_category_id')->nullable()->comment('単語カードカテゴリid');
            $table->text('word')->nullable()->comment('単語設問');
            $table->text('answer')->nullable()->comment('回答');
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->index('word_card_category_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('word_cards');
    }
}
