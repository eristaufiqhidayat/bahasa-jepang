<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use App\Models\Vocabulary;
use Database\Seeders\MockupMaterialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockupMaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_mockup_data_matches_and_seed_preserves_edits_without_duplicates(): void
    {
        $this->seed(MockupMaterialSeeder::class);
        $this->assertDatabaseCount('vocabularies', 27);
        $this->assertDatabaseCount('questions', 13);
        foreach (['ありがとう', 'おはようございます', 'みず', 'コーヒー', 'おちゃ', 'ともだち', 'せんせい', 'がっこう', 'えき'] as $jp) {
            $this->assertDatabaseHas('vocabularies', ['japanese' => $jp]);
        }
        $q = Question::where('prompt', 'おはようございます')->firstOrFail();
        $this->assertSame(['Selamat malam', 'Selamat pagi', 'Terima kasih'], $q->options);
        $this->assertSame(1, $q->correct_index);
        $v = Vocabulary::where('japanese', 'えき')->firstOrFail();
        $v->update(['meaning' => 'Stasiun kereta', 'audio_path' => 'audio/custom.mp3']);
        $q->update(['explanation' => 'Penjelasan admin']);
        $this->seed(MockupMaterialSeeder::class);
        $this->assertDatabaseCount('vocabularies', 27);
        $this->assertDatabaseCount('questions', 13);
        $this->assertSame('Stasiun kereta', $v->fresh()->meaning);
        $this->assertSame('audio/custom.mp3', $v->fresh()->audio_path);
        $this->assertSame('Penjelasan admin', $q->fresh()->explanation);
    }

    public function test_three_option_question_edit_and_answer_validation(): void
    {
        $this->seed();
        $q = Question::where('prompt', 'あ')->firstOrFail();
        $q->lesson->update(['status' => 'published']);
        $this->postJson('/belajar/questions/'.$q->id.'/answer', ['answer_index' => 0])->assertJson(['correct' => true]);
        $this->postJson('/belajar/questions/'.$q->id.'/answer', ['answer_index' => 3])->assertUnprocessable();
        $this->postJson('/api/v1/questions/'.$q->id.'/answer', ['answer_index' => 3])->assertUnprocessable();
        $u = User::factory()->create();
        $u->is_admin = true;
        $u->save();
        $data = ['lesson_id' => $q->lesson_id, 'prompt' => 'あ', 'type' => 'reading', 'option_0' => 'a', 'option_1' => 'i', 'option_2' => 'u', 'option_3' => '', 'correct_index' => 0, 'explanation' => 'あ dibaca a.', 'position' => 1];
        $this->actingAs($u)->put('/admin/questions/'.$q->id, $data)->assertRedirect();
        $this->assertCount(3, $q->fresh()->options);
        $data['correct_index'] = 3;
        $this->put('/admin/questions/'.$q->id, $data)->assertSessionHasErrors('correct_index');
    }
}
