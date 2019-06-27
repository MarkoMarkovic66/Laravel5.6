<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnUserListeningAssignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_listening_assigns', function (Blueprint $table) {
            $table->dropColumn('listening_type');
        });
        Schema::table('user_listening_assigns', function (Blueprint $table) {
            $table->string('listening_type', 4)
                    ->after('user_id')
                    ->comment('問題種別');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
