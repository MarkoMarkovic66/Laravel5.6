<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWordCardCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('word_card_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category', 255)->nullable()->comment('単語設問のカテゴリ文言');
            $table->text('grades')->nullable()->comment('対象グレード（複数カンマ区切り）');
            $table->integer('priority')->default(0)->comment('優先度（低い値が優先度高い）');
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
        Schema::dropIfExists('word_card_categories');
    }
}
