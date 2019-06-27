<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserInquiriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_inquiries', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('会員ID');
            $table->string('member_name', 255)->nullable()->comment('お名前');
            $table->string('email', 255)->nullable()->comment('メールアドレス');
            $table->string('phone', 255)->nullable()->comment('電話番号');
            $table->integer('inquiry_type')->nullable()->comment('お問い合わせ種別');
            $table->text('question')->nullable()->comment('お問い合わせ内容');
            $table->tinyInteger('is_agreed_campaign_info')->nullable()->comment('キャンペーン情報受領可否');
            $table->tinyInteger('is_agreed_personal_info')->nullable()->comment('個人情報取り扱い同意');
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
        Schema::dropIfExists('user_inquiries');
    }
}
