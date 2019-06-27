<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReviewServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('review_services', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('会員ID');
            $table->bigInteger('alugo_user_id')->nullable()->comment('alugo会員ID');
            $table->integer('eval_satisfy')->nullable()->comment('総合満足度評価値');
            $table->integer('eval_recommend')->nullable()->comment('推奨度評価値');
            $table->integer('eval_best_contents')->nullable()->comment('特に満足した商品');
            $table->integer('eval_worst_contents')->nullable()->comment('特に不満足な商品');
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
        Schema::dropIfExists('review_services');
    }
}
