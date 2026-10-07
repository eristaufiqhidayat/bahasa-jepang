<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_answers', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->foreignId('lesson_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('level')->default('foundation')->index();
            $t->string('question');
            $t->text('answer');
            $t->json('keywords');
            $t->string('source_label');
            $t->string('status')->default('draft');
            $t->string('review_status')->default('unreviewed');
            $t->timestamps();
        });
        Schema::create('material_chat_quotas', function (Blueprint $t) {
            $t->id();
            $t->string('owner_hash', 64);
            $t->date('day');
            $t->unsignedSmallInteger('used')->default(0);
            $t->unique(['owner_hash', 'day']);
            $t->timestamps();
        });
        Schema::create('material_chat_messages', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('owner_hash', 64)->index();
            $t->text('question');
            $t->string('level');
            $t->foreignId('material_answer_id')->nullable()->constrained()->nullOnDelete();
            $t->json('response');
            $t->timestamps(6);
        });
        Schema::create('material_chat_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('material_chat_message_id')->unique()->constrained()->cascadeOnDelete();
            $t->text('reason');
            $t->string('status')->default('open');
            $t->text('admin_note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['material_chat_reports', 'material_chat_messages', 'material_chat_quotas', 'material_answers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
