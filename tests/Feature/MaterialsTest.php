<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\User;
use App\Models\Vocabulary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->is_admin = true;
        $u->save();

        return $u;
    }

    private function lesson(array $extra = []): Lesson
    {
        return Lesson::create(array_merge(['title' => 'Salam', 'slug' => 'salam', 'summary' => 'Sapaan', 'content' => 'Selamat pagi', 'status' => 'draft', 'position' => 1, 'duration_minutes' => 5], $extra));
    }

    private function data(array $extra = []): array
    {
        return array_merge(['title' => 'Salam', 'slug' => 'salam', 'summary' => 'Sapaan', 'content' => 'おはようございます', 'status' => 'draft', 'position' => 1, 'duration_minutes' => 5], $extra);
    }

    public function test_guests_and_non_admin_cannot_manage_material(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/lessons')->assertForbidden();
    }

    public function test_admin_login_and_logout(): void
    {
        $u = $this->admin();
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_non_admin_cannot_login_to_admin_panel(): void
    {
        $u = User::factory()->create();
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_lesson_create_update_delete_and_duplicate_slug(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/lessons', $this->data())->assertRedirect('/admin/lessons');
        $l = Lesson::first();
        $this->post('/admin/lessons', $this->data())->assertSessionHasErrors('slug');
        $this->put('/admin/lessons/'.$l->id, $this->data(['status' => 'published']))->assertRedirect();
        $this->assertDatabaseHas('lessons', ['id' => $l->id, 'status' => 'published']);
        $this->delete('/admin/lessons/'.$l->id)->assertRedirect();
        $this->assertDatabaseMissing('lessons', ['id' => $l->id]);
    }

    public function test_all_admin_screens_render(): void
    {
        $this->seed();
        $this->actingAs($this->admin())->get('/admin')->assertOk();
        foreach (['lessons', 'vocabularies', 'characters', 'questions'] as $type) {
            $this->get('/admin/'.$type)->assertOk();
            $this->get('/admin/'.$type.'/create')->assertOk();
            $this->get('/admin/'.$type.'/1/edit')->assertOk();
        }
    }

    public function test_vocabulary_character_and_question_crud(): void
    {
        $l = $this->lesson();
        $this->actingAs($this->admin());
        $cases = [['vocabularies', ['lesson_id' => $l->id, 'japanese' => 'みず', 'romaji' => 'Mizu', 'meaning' => 'Air', 'category' => 'Minuman'], Vocabulary::class, 'meaning', 'Air minum'], ['characters', ['script' => 'hiragana', 'symbol' => 'あ', 'romaji' => 'a', 'group' => 'Dasar', 'position' => 1], Character::class, 'romaji', 'A'], ['questions', ['lesson_id' => $l->id, 'prompt' => 'Arti みず?', 'type' => 'meaning', 'option_0' => 'Air', 'option_1' => 'Teh', 'option_2' => 'Kopi', 'option_3' => 'Susu', 'correct_index' => 0, 'explanation' => 'みず berarti air.', 'position' => 1], Question::class, 'explanation', 'Air dalam bahasa Jepang.']];
        foreach ($cases as [$type,$data,$class,$field,$value]) {
            $this->post('/admin/'.$type, $data)->assertRedirect();
            $item = $class::first();
            $this->put('/admin/'.$type.'/'.$item->id, array_merge($data, [$field => $value]))->assertRedirect();
            $this->assertEquals($value, $item->fresh()->$field);
            $this->delete('/admin/'.$type.'/'.$item->id)->assertRedirect();
            $this->assertNull($class::find($item->id));
        }
    }

    public function test_upload_replace_delete_and_reject_non_audio(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $audio = fn () => UploadedFile::fake()->create('voice.mp3', 20, 'audio/mpeg');
        $this->post('/admin/lessons', $this->data(['audio' => $audio()]));
        $l = Lesson::first();
        $old = $l->audio_path;
        Storage::disk('public')->assertExists($old);
        $this->put('/admin/lessons/'.$l->id, $this->data(['audio' => $audio()]))->assertRedirect();
        Storage::disk('public')->assertMissing($old);
        $new = $l->fresh()->audio_path;
        $this->put('/admin/lessons/'.$l->id, $this->data(['remove_audio' => 1]))->assertRedirect();
        Storage::disk('public')->assertMissing($new);
        $this->post('/admin/lessons', $this->data(['slug' => 'bad', 'audio' => UploadedFile::fake()->create('bad.php', 1, 'text/x-php')]))->assertSessionHasErrors('audio');
    }

    public function test_api_filters_drafts_and_hides_answer_keys(): void
    {
        $l = $this->lesson();
        $q = Question::create(['lesson_id' => $l->id, 'prompt' => 'Arti?', 'type' => 'meaning', 'options' => ['A', 'B', 'C', 'D'], 'correct_index' => 2, 'explanation' => 'Alasan', 'position' => 1]);
        $this->getJson('/api/v1/lessons')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/lessons/'.$l->id)->assertNotFound();
        $this->postJson('/api/v1/questions/'.$q->id.'/answer', ['answer_index' => 2])->assertNotFound();
        $l->update(['status' => 'published']);
        $this->getJson('/api/v1/lessons/'.$l->id)->assertOk()->assertJsonMissingPath('questions.0.correct_index')->assertJsonMissingPath('questions.0.explanation');
        $this->postJson('/api/v1/questions/'.$q->id.'/answer', ['answer_index' => 2])->assertJson(['correct' => true, 'correct_index' => 2]);
        $this->postJson('/api/v1/questions/'.$q->id.'/answer', ['answer_index' => 1])->assertJson(['correct' => false]);
        $this->postJson('/api/v1/questions/'.$q->id.'/answer', ['answer_index' => 8])->assertUnprocessable();
    }

    public function test_delete_lesson_cascades_and_removes_child_audio(): void
    {
        Storage::fake('public');
        $l = $this->lesson();
        Storage::disk('public')->put('audio/word.mp3', 'demo');
        $v = Vocabulary::create(['lesson_id' => $l->id, 'japanese' => '水', 'romaji' => 'mizu', 'meaning' => 'Air', 'category' => 'Minuman', 'audio_path' => 'audio/word.mp3']);
        $this->actingAs($this->admin())->delete('/admin/lessons/'.$l->id)->assertRedirect();
        $this->assertDatabaseMissing('vocabularies', ['id' => $v->id]);
        Storage::disk('public')->assertMissing('audio/word.mp3');
    }

    public function test_seed_is_repeatable_and_preserves_edits(): void
    {
        $this->seed();
        $this->assertDatabaseCount('lessons', 4);
        $this->assertDatabaseCount('characters', 92);
        $l = Lesson::first();
        $l->update(['title' => 'Diperbarui']);
        $this->seed();
        $this->assertDatabaseCount('lessons', 4);
        $this->assertEquals('Diperbarui', $l->fresh()->title);
    }

    public function test_listening_question_requires_audio(): void
    {
        $l = $this->lesson();
        $this->actingAs($this->admin())->post('/admin/questions', ['lesson_id' => $l->id, 'prompt' => 'Dengar', 'type' => 'listening', 'option_0' => 'A', 'option_1' => 'B', 'option_2' => 'C', 'option_3' => 'D', 'correct_index' => 0, 'explanation' => 'Alasan', 'position' => 1])->assertSessionHasErrors('audio');
    }
}
