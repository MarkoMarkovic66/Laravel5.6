<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropHomeworkRelatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('word_card_tasks');
        Schema::dropIfExists('word_card_categories');
        Schema::dropIfExists('word_card_assigns');
        Schema::dropIfExists('listening_assigns');
        Schema::dropIfExists('speaking_rally_assigns');
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
