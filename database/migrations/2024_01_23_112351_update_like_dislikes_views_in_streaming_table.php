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
        Schema::table('streamings', function (Blueprint $table) {
            $table->integer('like_count')->default(0)->after('status');  
            $table->integer('dislike_count')->default(0)->after('status');  
            $table->integer('views_count')->default(0)->after('status');  
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('streamings');
    }
};
