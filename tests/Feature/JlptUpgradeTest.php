<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\User;
use App\Models\VirtualTestAttempt;
use App\Models\VirtualTestTemplate;
use Database\Seeders\JlptCurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JlptUpgradeTest extends TestCase
{
    use RefreshDatabase;

    private function seedUpgrade(): void
    {
        $this->seed(JlptCurriculumSeeder::class);
    }

    private function start(): array
    {
        $template = VirtualTestTemplate::where('slug', 'mini-n5')->firstOrFail();

        return $this->withSession(['virtual_test_owner' => 'student-a'])->postJson('/belajar/virtual-tests/templates/'.$template->id.'/start')->assertOk()->json();
    }

    private function url(array $attempt, string $suffix = ''): string
    {
        return '/belajar/virtual-tests/attempts/'.$attempt['id'].$suffix;
    }

    public function test_seed_preserves_haru_and_imports_all_mapping_without_duplicates_or_overwriting_admin(): void
    {
        $this->seedUpgrade();
        $this->assertDatabaseCount('lessons', 54);
        $this->assertDatabaseCount('questions', 90);
        $this->assertDatabaseCount('characters', 92);
        $this->assertDatabaseCount('vocabularies', 27);
        $this->assertDatabaseCount('course_tracks', 7);
        $this->assertDatabaseCount('virtual_test_templates', 7);
        $l = Lesson::where('reference_id', 'mnn-01')->first();
        $l->update(['content' => 'Materi edit pengajar', 'status' => 'draft']);
        $q = Question::where('reference_id', 'mnn-g-01')->first();
        $q->update(['explanation' => 'Edit pembahasan']);
        $this->seedUpgrade();
        $this->assertDatabaseCount('lessons', 54);
        $this->assertDatabaseCount('questions', 90);
        $this->assertSame('Materi edit pengajar', $l->fresh()->content);
        $this->assertSame('draft', $l->fresh()->status);
        $this->assertSame('Edit pembahasan', $q->fresh()->explanation);
    }

    public function test_public_data_includes_curriculum_and_templates_but_hides_keys_and_audio_scripts(): void
    {
        $this->seedUpgrade();
        $this->get('/')->assertOk()->assertViewHas('data', function ($d) {
            $this->assertCount(7, $d['tracks']);
            $this->assertCount(7, $d['templates']);
            $this->assertCount(50, $d['lessons']);
            foreach ($d['questions'] as $q) {
                $this->assertArrayNotHasKey('correct_index', $q);
                $this->assertArrayNotHasKey('explanation', $q);
                $this->assertArrayNotHasKey('audio_script', $q);
            }

            return true;
        });
        $this->getJson('/api/v1/lessons?level=N5')->assertOk()->assertJsonPath('total', 20);
        $this->getJson('/api/v1/lessons?level=N4')->assertOk()->assertJsonPath('total', 30);
        $this->getJson('/api/v1/tracks')->assertOk()->assertJsonCount(7);
        $audioDraft = Question::where('type', 'listening')->whereNotNull('reference_id')->first();
        $questions = $this->getJson('/api/v1/lessons/'.$audioDraft->lesson_id)->assertOk()->json('questions');
        $this->assertNotContains($audioDraft->id, array_column($questions, 'id'));
    }

    public function test_mini_test_saves_answers_recovers_and_grades_only_after_finish(): void
    {
        $this->seedUpgrade();
        $a = $this->start();
        $this->assertSame('in_progress', $a['status']);
        $this->assertCount(10, $a['questions']);
        $this->assertNull($a['result']);
        foreach ($a['questions'] as $q) {
            $this->assertArrayNotHasKey('correct_index', $q);
            $this->assertArrayNotHasKey('explanation', $q);
        }
        $snapshot = VirtualTestAttempt::find($a['id']);
        $first = $snapshot->questions[0];
        $this->postJson($this->url($a, '/answers'), ['answers' => [$first['id'] => $first['correct_index']]])->assertOk()->assertJsonPath('result', null);
        $this->getJson($this->url($a))->assertOk()->assertJsonPath('answers.'.$first['id'], $first['correct_index']);
        Question::find($first['id'])->update(['correct_index' => ($first['correct_index'] + 1) % count($first['options'])]);
        $this->postJson($this->url($a, '/finish'), ['answers' => []])->assertOk()->assertJsonPath('status', 'completed')->assertJsonPath('result.correct', 1)->assertJsonPath('result.total', 10)->assertJsonCount(10, 'result.reviews');
        $this->postJson($this->url($a, '/finish'), ['answers' => []])->assertOk()->assertJsonPath('result.correct', 1);
    }

    public function test_attempts_are_owned_by_browser_session_and_foreign_choices_rejected(): void
    {
        $this->seedUpgrade();
        $a = $this->start();
        $this->postJson($this->url($a, '/answers'), ['answers' => [999999 => 0]])->assertUnprocessable();
        $first = $a['questions'][0];
        $this->postJson($this->url($a, '/answers'), ['answers' => [$first['id'] => 5]])->assertUnprocessable();
        $this->withSession(['virtual_test_owner' => 'student-b'])->getJson($this->url($a))->assertNotFound();
        $this->postJson($this->url($a, '/finish'), ['answers' => []])->assertNotFound();
    }

    public function test_server_timer_locks_expired_attempt_and_ignores_late_answers(): void
    {
        $this->seedUpgrade();
        $a = $this->start();
        $first = VirtualTestAttempt::find($a['id'])->questions[0];
        $this->postJson($this->url($a, '/answers'), ['answers' => [$first['id'] => $first['correct_index']]])->assertOk();
        $this->travel(9)->minutes();
        $second = VirtualTestAttempt::find($a['id'])->questions[1];
        $this->postJson($this->url($a, '/finish'), ['answers' => [$second['id'] => $second['correct_index']]])->assertOk()->assertJsonPath('status', 'timed_out')->assertJsonPath('result.correct', 1);
        $this->getJson($this->url($a))->assertOk()->assertJsonPath('remaining_seconds', 0);
    }

    public function test_blueprints_or_broken_packages_cannot_be_started(): void
    {
        $this->seedUpgrade();
        $t = VirtualTestTemplate::where('slug', 'jlpt-n5')->first();
        $this->postJson('/belajar/virtual-tests/templates/'.$t->id.'/start')->assertConflict();
        $t = VirtualTestTemplate::where('slug', 'mini-n5')->first();
        Question::find($t->question_ids[0])->lesson->update(['status' => 'draft']);
        $this->postJson('/belajar/virtual-tests/templates/'.$t->id.'/start')->assertConflict();
    }

    public function test_admin_can_manage_packages_but_students_cannot_and_invalid_level_rejected(): void
    {
        $this->seedUpgrade();
        $this->get('/admin/virtual-tests')->assertRedirect('/login');
        $u = User::factory()->create();
        $this->actingAs($u)->get('/admin/virtual-tests')->assertForbidden();
        $u->is_admin = true;
        $u->save();
        $this->get('/admin/virtual-tests')->assertOk();
        $this->get('/admin/virtual-tests/create')->assertOk();
        $t = VirtualTestTemplate::where('slug', 'mini-n5')->first();
        $this->get('/admin/virtual-tests/'.$t->id.'/edit')->assertOk();
        $payload = ['title' => 'Paket guru', 'slug' => 'paket-guru', 'level' => 'N5', 'description' => 'Soal latihan', 'status' => 'published', 'duration_minutes' => 5, 'question_ids' => $t->question_ids];
        $this->post('/admin/virtual-tests', $payload)->assertRedirect('/admin/virtual-tests');
        $this->assertDatabaseHas('virtual_test_templates', ['slug' => 'paket-guru', 'duration_seconds' => 300]);
        $payload['slug'] = 'paket-salah';
        $payload['level'] = 'N4';
        $this->post('/admin/virtual-tests', $payload)->assertSessionHasErrors('question_ids');
    }
}
