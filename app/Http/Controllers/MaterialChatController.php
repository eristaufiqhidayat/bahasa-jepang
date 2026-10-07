<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\MaterialChatMessage;
use App\Models\MaterialChatReport;
use App\Services\MaterialAnswerSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialChatController extends Controller
{
    private function owner(Request $r): string
    {
        $token = $r->session()->get('material_chat_owner');
        if (! $token) {
            $token = bin2hex(random_bytes(32));
            $r->session()->put('material_chat_owner', $token);
        }

        return hash('sha256', $token);
    }

    private function quota(string $owner): array
    {
        $day = now('Asia/Jakarta')->toDateString();
        $used = (int) DB::table('material_chat_quotas')->where('owner_hash', $owner)->where('day', $day)->value('used');

        return ['limit' => 20, 'used' => $used, 'remaining' => max(0, 20 - $used), 'day' => $day, 'timezone' => 'Asia/Jakarta'];
    }

    private function message(MaterialChatMessage $m): array
    {
        return ['id' => $m->id, 'question' => $m->question, 'level' => $m->level, 'response' => $m->response, 'reported' => (bool) ($m->report_exists ?? $m->report()->exists())];
    }

    public function history(Request $r)
    {
        $owner = $this->owner($r);

        return response()->json(['messages' => MaterialChatMessage::where('owner_hash', $owner)->withExists('report')->latest()->take(10)->get()->reverse()->values()->map(fn ($m) => $this->message($m)), 'quota' => $this->quota($owner)])->header('Cache-Control', 'no-store');
    }

    public function ask(Request $r, MaterialAnswerSearch $search)
    {
        $data = $r->validate(['request_id' => 'required|uuid', 'question' => 'required|string|min:2|max:1000', 'level' => 'required|in:foundation,N5,N4', 'lesson_id' => 'nullable|integer']);
        if (! empty($data['lesson_id'])) {
            abort_unless(Lesson::whereKey($data['lesson_id'])->where('status', 'published')->exists(), 404);
        }
        $owner = $this->owner($r);
        $day = now('Asia/Jakarta')->toDateString();
        $result = DB::transaction(function () use ($data, $owner, $day, $search) {
            DB::table('material_chat_quotas')->insertOrIgnore(['owner_hash' => $owner, 'day' => $day, 'used' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $quota = DB::table('material_chat_quotas')->where('owner_hash', $owner)->where('day', $day)->lockForUpdate()->first();
            $existing = MaterialChatMessage::find($data['request_id']);
            if ($existing) {
                abort_unless(hash_equals($existing->owner_hash, $owner), 404);

                return $existing;
            }
            abort_if($quota->used >= 20, 429, 'Kuota 20 pertanyaan hari ini sudah habis. Kuota diperbarui pukul 00.00 WIB.');
            $response = $search->search($data['question'], $data['level'], $data['lesson_id'] ?? null);
            $answerId = $response['answer_id'];
            unset($response['answer_id']);
            $m = MaterialChatMessage::create(['id' => $data['request_id'], 'owner_hash' => $owner, 'question' => $data['question'], 'level' => $data['level'], 'material_answer_id' => $answerId, 'response' => $response]);
            DB::table('material_chat_quotas')->where('id', $quota->id)->update(['used' => $quota->used + 1, 'updated_at' => now()]);

            return $m;
        });

        return response()->json(['message' => $this->message($result), 'quota' => $this->quota($owner)])->header('Cache-Control', 'no-store');
    }

    public function report(Request $r, MaterialChatMessage $message)
    {
        abort_unless(hash_equals($message->owner_hash, $this->owner($r)), 404);
        $data = $r->validate(['reason' => 'required|string|min:5|max:2000']);
        MaterialChatReport::firstOrCreate(['material_chat_message_id' => $message->id], ['reason' => $data['reason']]);

        return response()->json(['message' => 'Laporan disimpan untuk diperiksa pengajar.']);
    }
}
