<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFavoritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('会員ID');
            $table->bigInteger('alugo_user_id')->nullable()->comment('alugo会員ID');
            $table->string('category', 255)->nullable()->comment('お気に入りカテゴリ');
            $table->text('title')->nullable()->comment('お気に入りタイトル');
            $table->text('url')->nullable()->comment('お気に入りURL');
            $table->string('remark', 255)->nullable()->comment('備考');
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
            $table->index('user_id');
            $table->index('alugo_user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('favorites');
    }
}
