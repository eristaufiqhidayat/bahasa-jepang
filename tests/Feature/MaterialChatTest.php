<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\MaterialAnswer;
use App\Models\MaterialChatMessage;
use App\Models\MaterialChatReport;
use App\Models\User;
use App\Services\MaterialAnswerSearch;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MaterialChatSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MaterialChatTest extends TestCase
{
    use RefreshDatabase;

    private function answer(array $fields = []): MaterialAnswer
    {
        return MaterialAnswer::create($fields + ['slug' => 'wa', 'question' => 'Apa fungsi partikel は?', 'answer' => 'は menandai topik. Dibaca wa.', 'keywords' => ['wa', 'は'], 'level' => 'N5', 'source_label' => 'Japantest · Bab 1', 'status' => 'published', 'review_status' => 'reviewed']);
    }

    private function ask(string $question = 'Apa fungsi partikel wa?', ?string $id = null, array $extra = [])
    {
        return $this->postJson('/belajar/tanya-materi', $extra + ['question' => $question, 'level' => 'N5', 'request_id' => $id ?? (string) Str::uuid()]);
    }

    public function test_synonyms_sources_retry_and_history(): void
    {
        $this->answer();
        $id = (string) Str::uuid();
        $this->ask(id: $id)->assertOk()->assertJsonPath('message.response.matched', true)->assertJsonPath('message.response.source.label', 'Japantest · Bab 1')->assertJsonPath('quota.remaining', 19);
        $this->ask(id: $id)->assertOk()->assertJsonPath('quota.used', 1);
        $this->getJson('/belajar/tanya-materi')->assertOk()->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.id', $id)->assertDontSee('owner_hash');
        $this->assertDatabaseCount('material_chat_messages', 1);
    }

    public function test_daily_quota_counts_unknown_questions_and_resets_at_wib_midnight(): void
    {
        $this->travelTo(now()->setTimezone('Asia/Jakarta')->setTime(23, 59, 50));
        for ($i = 0; $i < 20; $i++) {
            $this->ask('Pertanyaan belum tersedia '.$i)->assertOk()->assertJsonPath('message.response.matched', false);
        }
        $this->ask()->assertStatus(429);
        $this->assertDatabaseCount('material_chat_messages', 20);
        $this->travel(20)->seconds();
        $this->ask()->assertOk()->assertJsonPath('quota.used', 1)->assertJsonPath('quota.timezone', 'Asia/Jakarta');
        $this->assertDatabaseCount('material_chat_quotas', 2);
    }

    public function test_drafts_levels_ambiguous_queries_and_lesson_context(): void
    {
        $draft = Lesson::create(['title' => 'Rahasia', 'slug' => 'rahasia', 'summary' => 'Draft rahasia', 'content' => 'Isi rahasia', 'status' => 'draft']);
        $this->answer(['lesson_id' => $draft->id]);
        $this->ask()->assertOk()->assertJsonPath('message.response.matched', false);
        $this->ask(extra: ['lesson_id' => $draft->id])->assertNotFound();
        $draft->update(['status' => 'published']);
        $this->ask(extra: ['lesson_id' => $draft->id])->assertOk()->assertJsonPath('message.response.matched', true);
        $this->ask(extra: ['level' => 'foundation'])->assertOk()->assertJsonPath('message.response.matched', false);
        $this->ask(extra: ['level' => 'N3'])->assertUnprocessable();
        $this->answer(['slug' => 'wa-lain', 'question' => 'Pertanyaan lain', 'keywords' => ['wa']]);
        $this->ask()->assertOk()->assertJsonPath('message.response.matched', false)->assertJsonCount(2, 'message.response.suggestions');
        $this->ask('Apa fungsi partikel は?')->assertOk()->assertJsonPath('message.response.matched', true);
    }

    public function test_reports_and_history_are_owned_by_session_and_reports_are_idempotent(): void
    {
        $this->answer();
        $id = $this->ask()->json('message.id');
        $this->postJson('/belajar/tanya-materi/'.$id.'/report', ['reason' => 'Sumber perlu diperbaiki'])->assertOk();
        $this->postJson('/belajar/tanya-materi/'.$id.'/report', ['reason' => 'Duplikat laporan'])->assertOk();
        $this->assertDatabaseCount('material_chat_reports', 1);
        $this->withSession(['material_chat_owner' => 'another-browser'])->getJson('/belajar/tanya-materi')->assertOk()->assertJsonCount(0, 'messages');
        $this->postJson('/belajar/tanya-materi/'.$id.'/report', ['reason' => 'Tidak boleh melihat'])->assertNotFound();
        $this->ask(id: $id)->assertNotFound();
    }

    public function test_history_returns_only_the_last_ten_messages_in_chronological_order(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->ask('Belum tersedia '.$i)->assertOk();
        }
        $this->getJson('/belajar/tanya-materi')->assertOk()->assertJsonCount(10, 'messages')->assertJsonPath('messages.0.question', 'Belum tersedia 2')->assertJsonPath('messages.9.question', 'Belum tersedia 11');
    }

    public function test_admin_can_manage_answers_and_review_reports_but_visitors_cannot(): void
    {
        $this->get('/admin/material-answers')->assertRedirect('/login');
        $admin = User::factory()->create(['is_admin' => true]);
        $a = $this->answer();
        $message = MaterialChatMessage::create(['id' => (string) Str::uuid(), 'owner_hash' => 'test', 'level' => 'N5', 'question' => 'wa', 'response' => ['matched' => true, 'answer' => 'Jawaban'], 'material_answer_id' => $a->id]);
        $report = MaterialChatReport::create(['material_chat_message_id' => $message->id, 'reason' => 'Periksa sumber']);
        $this->actingAs($admin)->get('/admin/material-answers')->assertOk()->assertSee('Periksa sumber');
        $this->get('/admin/material-answers/create')->assertOk();
        $fields = ['slug' => 'hiragana', 'question' => 'Apa itu hiragana?', 'answer' => 'Aksara Jepang', 'keywords' => 'hiragana, kana', 'level' => 'foundation', 'source_label' => 'Fondasi', 'status' => 'draft', 'review_status' => 'unreviewed'];
        $this->post('/admin/material-answers', $fields)->assertRedirect('/admin/material-answers');
        $created = MaterialAnswer::where('slug', 'hiragana')->firstOrFail();
        $this->get('/admin/material-answers/'.$created->id.'/edit')->assertOk();
        $this->put('/admin/material-answers/'.$created->id, array_replace($fields, ['status' => 'published']))->assertRedirect('/admin/material-answers');
        $this->assertSame(['hiragana', 'kana'], $created->fresh()->keywords);
        $this->post('/admin/chat-reports/'.$report->id.'/resolve', ['admin_note' => 'Sumber telah diperbaiki'])->assertRedirect();
        $this->assertSame('resolved', $report->fresh()->status);
        $this->delete('/admin/material-answers/'.$created->id)->assertRedirect();
        $this->assertDatabaseMissing('material_answers', ['id' => $created->id]);
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get('/admin/material-answers')->assertForbidden();
    }

    public function test_seeder_preserves_edits_and_bank_supports_foundation_n5_and_basic_n4(): void
    {
        $this->seed(DatabaseSeeder::class);
        Lesson::whereIn('slug', ['salam', 'perkenalan', 'angka', 'minuman'])->update(['status' => 'published']);
        $this->seed(MaterialChatSeeder::class);
        $count = MaterialAnswer::count();
        $this->assertGreaterThanOrEqual(80, $count);
        $search = app(MaterialAnswerSearch::class);
        foreach ([['Apa itu hiragana?', 'foundation'], ['koohii', 'foundation'], ['Apa fungsi partikel wa?', 'N5'], ['nagara', 'N4']] as [$q, $level]) {
            $this->assertTrue($search->search($q, $level, null)['matched'], $q);
        }
        $a = MaterialAnswer::where('slug', 'bab-1-konsep')->firstOrFail();
        $a->update(['answer' => 'Koreksi pengajar', 'status' => 'draft']);
        $this->seed(MaterialChatSeeder::class);
        $this->assertSame($count, MaterialAnswer::count());
        $this->assertSame('Koreksi pengajar', $a->fresh()->answer);
        $this->assertSame('draft', $a->fresh()->status);
        $this->assertFalse($search->search('Apa fungsi partikel wa?', 'N5', null)['matched']);
        $this->assertFalse($search->search('Pertanyaan tentang perpajakan', 'N5', null)['matched']);
        $this->assertSame(0, DB::table('material_chat_reports')->count());
    }
}
