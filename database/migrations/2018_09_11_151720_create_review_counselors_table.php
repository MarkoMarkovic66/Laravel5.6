<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReviewCounselorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('review_counselors', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('会員ID');
            $table->bigInteger('alugo_user_id')->nullable()->comment('alugo会員ID');
            $table->integer('counselor_id')->nullable()->comment('対象カウンセラーID');
            $table->integer('eval')->nullable()->comment('カウンセラー評価値');
            $table->integer('eval_factor')->nullable()->comment('評価要因');
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
        Schema::dropIfExists('review_counselors');
    }
}
