<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_admin')->default(false));
        Schema::create('lessons', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('summary');
            $t->longText('content');
            $t->string('japanese')->nullable();
            $t->string('romaji')->nullable();
            $t->text('translation')->nullable();
            $t->string('audio_path')->nullable();
            $t->unsignedInteger('position')->default(1);
            $t->unsignedSmallInteger('duration_minutes')->default(5);
            $t->string('status')->default('draft')->index();
            $t->timestamps();
        });
        Schema::create('vocabularies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $t->string('japanese');
            $t->string('reading')->nullable();
            $t->string('romaji');
            $t->string('meaning');
            $t->string('category')->default('Umum');
            $t->text('example')->nullable();
            $t->text('example_translation')->nullable();
            $t->string('audio_path')->nullable();
            $t->timestamps();
        });
        Schema::create('characters', function (Blueprint $t) {
            $t->id();
            $t->string('script');
            $t->string('symbol');
            $t->string('romaji');
            $t->string('group')->default('Dasar');
            $t->unsignedInteger('position')->default(1);
            $t->string('audio_path')->nullable();
            $t->unique(['script', 'symbol']);
            $t->timestamps();
        });
        Schema::create('questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $t->text('prompt');
            $t->string('type')->default('meaning');
            $t->json('options');
            $t->unsignedTinyInteger('correct_index');
            $t->text('explanation');
            $t->string('audio_path')->nullable();
            $t->unsignedInteger('position')->default(1);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['questions', 'characters', 'vocabularies', 'lessons'] as $name) {
            Schema::dropIfExists($name);
        }Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin'));
    }
};
