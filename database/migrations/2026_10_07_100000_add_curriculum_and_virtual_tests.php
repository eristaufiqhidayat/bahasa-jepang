<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $t) {
            $t->string('level')->nullable()->index();
            $t->string('reference_id')->nullable()->unique();
            $t->unsignedSmallInteger('chapter')->nullable();
            $t->json('patterns')->nullable();
            $t->json('source_reference')->nullable();
            $t->string('review_status')->default('unreviewed');
        });
        Schema::table('questions', function (Blueprint $t) {
            $t->string('level')->nullable()->index();
            $t->string('reference_id')->nullable()->unique();
            $t->string('section')->nullable();
            $t->string('skill')->nullable();
            $t->string('item_type')->nullable();
            $t->json('source_reference')->nullable();
            $t->text('audio_script')->nullable();
            $t->string('review_status')->default('unreviewed');
        });
        Schema::create('course_tracks', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->text('description');
            $t->json('focus');
            $t->string('status')->default('planned');
            $t->unsignedSmallInteger('position');
            $t->timestamps();
        });
        Schema::create('virtual_test_templates', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->string('level')->index();
            $t->string('mode')->default('mini');
            $t->string('status')->default('draft');
            $t->text('description');
            $t->unsignedInteger('duration_seconds');
            $t->json('sections');
            $t->json('question_ids');
            $t->json('official_score_reference')->nullable();
            $t->timestamps();
        });
        Schema::create('virtual_test_attempts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('virtual_test_template_id')->nullable()->constrained()->nullOnDelete();
            $t->string('owner_hash', 64)->index();
            $t->string('title');
            $t->string('level');
            $t->string('status')->default('in_progress');
            $t->json('questions');
            $t->json('answers');
            $t->json('result')->nullable();
            $t->timestamp('started_at');
            $t->timestamp('expires_at');
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_test_attempts');
        Schema::dropIfExists('virtual_test_templates');
        Schema::dropIfExists('course_tracks');
        Schema::table('questions', fn (Blueprint $t) => $t->dropColumn(['level', 'reference_id', 'section', 'skill', 'item_type', 'source_reference', 'audio_script', 'review_status']));
        Schema::table('lessons', fn (Blueprint $t) => $t->dropColumn(['level', 'reference_id', 'chapter', 'patterns', 'source_reference', 'review_status']));
    }
};
