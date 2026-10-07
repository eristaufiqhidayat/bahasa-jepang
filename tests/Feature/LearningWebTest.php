<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearningWebTest extends TestCase
{
    use RefreshDatabase;

    private function lesson(string $status = 'draft'): Lesson
    {
        return Lesson::create(['title' => 'Belajar <script> Jepang', 'slug' => 'belajar', 'summary' => 'Ringkasan', 'content' => 'Materi Indonesia', 'status' => $status]);
    }

    private function question(Lesson $l): Question
    {
        return Question::create(['lesson_id' => $l->id, 'prompt' => 'Arti salam', 'type' => 'meaning', 'options' => ['Pagi', 'Siang', 'Malam', 'Halo'], 'correct_index' => 0, 'explanation' => 'Pembahasan rahasia']);
    }

    public function test_public_page_uses_only_published_database_content_and_no_answer_keys(): void
    {
        $l = $this->lesson();
        $q = $this->question($l);
        $this->get('/')->assertOk()->assertViewHas('data', fn ($d) => count($d['lessons']) === 0);
        $l->update(['status' => 'published']);
        $this->get('/belajar')->assertOk()->assertViewHas('data', fn ($d) => count($d['lessons']) === 1 && $d['lessons'][0]['content'] === 'Materi Indonesia' && ! array_key_exists('correct_index', $d['questions'][0]) && ! array_key_exists('explanation', $d['questions'][0]));
    }

    public function test_preview_requires_admin_and_contains_drafts(): void
    {
        $this->lesson();
        $this->get('/pratinjau')->assertRedirect('/login');
        $u = User::factory()->create();
        $this->actingAs($u)->get('/pratinjau')->assertForbidden();
        $u->is_admin = true;
        $u->save();
        $this->get('/pratinjau')->assertOk()->assertViewHas('data', fn ($d) => $d['preview'] && count($d['lessons']) === 1);
    }

    public function test_grading_respects_publication_and_admin_preview(): void
    {
        $l = $this->lesson();
        $q = $this->question($l);
        $this->postJson('/belajar/questions/'.$q->id.'/answer', ['answer_index' => 0])->assertNotFound();
        $this->postJson('/pratinjau/questions/'.$q->id.'/answer', ['answer_index' => 0])->assertUnauthorized();
        $u = User::factory()->create();
        $u->is_admin = true;
        $u->save();
        $this->actingAs($u)->postJson('/pratinjau/questions/'.$q->id.'/answer', ['answer_index' => 0])->assertJson(['correct' => true]);
        $l->update(['status' => 'published']);
        $this->postJson('/belajar/questions/'.$q->id.'/answer', ['answer_index' => 1])->assertJson(['correct' => false, 'correct_index' => 0]);
        $this->postJson('/belajar/questions/'.$q->id.'/answer', ['answer_index' => 4])->assertUnprocessable();
    }

    public function test_audio_serves_without_symlink_and_does_not_expose_drafts(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('audio/lessons/salam.mp3', 'audio');
        $l = $this->lesson();
        $l->update(['audio_path' => 'audio/lessons/salam.mp3']);
        $this->get('/belajar/audio/lessons/'.$l->id)->assertNotFound();
        $l->update(['status' => 'published']);
        $this->get('/belajar/audio/lessons/'.$l->id)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/belajar/audio/users/1')->assertNotFound();
        $this->get('/belajar/audio/lessons/999')->assertNotFound();
    }
}
