<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnSrAnswerIdToHomeworkAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('homework_answers', function (Blueprint $table) {
            $table->integer('sr_answer_id')
                    ->nullable()
                    ->after('sound_file_id')
                    ->comment('SR FB取得用id');
            $table->text('feedback')
                    ->nullable()
                    ->after('sr_answer_id')
                    ->comment('FeedBackテキスト');
            $table->datetime('feedback_date')
                    ->nullable()
                    ->after('feedback')
                    ->comment('FeedBack日時');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('homework_answers', function (Blueprint $table) {
            $table->dropColumn('sr_answer_id');
            $table->dropColumn('feedback');
            $table->dropColumn('feedback_date');
        });
    }
}
