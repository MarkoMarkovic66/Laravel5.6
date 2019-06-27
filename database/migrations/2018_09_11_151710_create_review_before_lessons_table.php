<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReviewBeforeLessonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('review_before_lessons', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('会員ID');
            $table->bigInteger('alugo_user_id')->nullable()->comment('alugo会員ID');
            $table->integer('alugo_level')->nullable()->comment('ALUGOレベル到達目標');
            $table->integer('toeic_level')->nullable()->comment('TOEIC到達目標');
            $table->integer('toefl_level')->nullable()->comment('TOEFL到達目標');
            $table->integer('usage_case')->nullable()->comment('英語使用状況想定');
            $table->text('comment')->nullable()->comment('フリーコメント');
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
        Schema::dropIfExists('review_before_lessons');
    }
}
