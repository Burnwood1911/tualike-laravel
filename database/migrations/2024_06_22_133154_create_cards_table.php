<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image');
            $table->integer('price');
            $table->integer('name_y');
            $table->integer('x_offset');
            $table->integer('name_font_size');
            $table->string('name_color');
            $table->string('type_color');
            $table->boolean('hide_qr')->default(false);
            $table->string('qr_position')->nullable();
            $table->integer('invite_x')->nullable();
            $table->integer('invite_y')->nullable();
            $table->integer('invite_font_size')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
