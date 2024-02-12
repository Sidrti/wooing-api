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
        Schema::create('streamings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('meeting_id');
            $table->enum('type',['STREAM','VIDEO','AUDIO'])->default('STREAM');
            $table->string('status')->default('ACTIVE'); // You can define appropriate data type for status
            $table->integer('like_count')->default(0);
            $table->integer('dislike_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->timestamps();
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
