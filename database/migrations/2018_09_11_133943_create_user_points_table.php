<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_points', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('会員ID');
            $table->bigInteger('alugo_user_id')->nullable()->comment('alugo会員ID');
            $table->tinyInteger('point_type')->nullable()->comment('0:付与 1:使用');
            $table->integer('point_value')->nullable()->comment('ポイント値');
            $table->dateTime('point_at')->nullable()->comment('付与日or使用日');
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
        Schema::dropIfExists('user_points');
    }
}
