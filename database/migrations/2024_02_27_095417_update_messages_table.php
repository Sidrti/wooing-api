<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('receiver_id')->nullable()->change();
            $table->foreignId('group_id')->nullable()->constrained('groups')->comment('For group chats')->after('receiver_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            // Drop the group_id column
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
            $table->foreignId('receiver_id')->nullable(false)->change();
        });
    }
};
