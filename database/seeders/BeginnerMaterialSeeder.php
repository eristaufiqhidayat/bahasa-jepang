<?php

namespace Database\Seeders;

use App\Models\Character;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Vocabulary;
use Illuminate\Database\Seeder;

class BeginnerMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = [
            ['salam', 'Salam dan sapaan', 'Menyapa dengan ungkapan sopan.', 'おはようございます', 'Ohayō gozaimasu', 'Selamat pagi', 'Gunakan おはようございます untuk menyapa pada pagi hari dengan sopan. こんにちは digunakan pada siang hari. こんばんは digunakan pada malam hari. ありがとうございます berarti terima kasih.', [
                ['おはようございます', 'Ohayō gozaimasu', 'Selamat pagi'], ['こんにちは', 'Konnichiwa', 'Halo / selamat siang'], ['こんばんは', 'Konbanwa', 'Selamat malam'], ['ありがとうございます', 'Arigatō gozaimasu', 'Terima kasih']],
                [['Sapaan sopan pada pagi hari adalah ...', ['おはようございます', 'こんばんは', 'さようなら', 'ありがとう'], 0, 'おはようございます adalah sapaan pagi yang sopan.'], ['Arti ありがとうございます adalah ...', ['Selamat malam', 'Terima kasih', 'Sampai jumpa', 'Maaf'], 1, 'ありがとうございます digunakan untuk berterima kasih secara sopan.']]],
            ['perkenalan', 'Memperkenalkan diri', 'Menyebutkan nama dan asal dengan kalimat sederhana.', 'わたしはエリスです。', 'Watashi wa Erisu desu.', 'Saya Eris.', 'Pola わたしは … です digunakan untuk menyatakan identitas. Partikel は dibaca wa dalam pola ini. はじめまして digunakan ketika pertama kali bertemu. よろしくおねがいします adalah ungkapan sopan pada akhir perkenalan; terjemahannya bergantung konteks.', [
                ['わたし', 'Watashi', 'Saya'], ['はじめまして', 'Hajimemashite', 'Salam kenal'], ['インドネシア', 'Indoneshia', 'Indonesia'], ['よろしくおねがいします', 'Yoroshiku onegaishimasu', 'Mohon kerja samanya / senang berkenalan']],
                [['わたし berarti ...', ['Kamu', 'Saya', 'Dia', 'Guru'], 1, 'わたし adalah kata ganti orang pertama.'], ['Ungkapan saat pertama kali bertemu adalah ...', ['こんばんは', 'いただきます', 'はじめまして', 'おやすみなさい'], 2, 'はじめまして digunakan pada awal perkenalan pertama.']]],
            ['angka', 'Angka satu sampai sepuluh', 'Mengenal angka dasar dan cara bacanya.', 'いち、に、さん', 'Ichi, ni, san', 'Satu, dua, tiga', 'Angka dasar: 1 いち, 2 に, 3 さん, 4 よん, 5 ご, 6 ろく, 7 なな, 8 はち, 9 きゅう, 10 じゅう. Beberapa angka memiliki bacaan lain sesuai konteks; misalnya 4 dapat dibaca し dan 7 dapat dibaca しち.', [
                ['いち', 'Ichi', 'Satu'], ['に', 'Ni', 'Dua'], ['さん', 'San', 'Tiga'], ['よん', 'Yon', 'Empat'], ['ご', 'Go', 'Lima'], ['ろく', 'Roku', 'Enam'], ['なな', 'Nana', 'Tujuh'], ['はち', 'Hachi', 'Delapan'], ['きゅう', 'Kyū', 'Sembilan'], ['じゅう', 'Jū', 'Sepuluh']],
                [['さん adalah angka ...', ['Satu', 'Dua', 'Tiga', 'Empat'], 2, 'さん dibaca san dan berarti tiga.'], ['Angka sepuluh dibaca ...', ['Go', 'Hachi', 'Kyū', 'Jū'], 3, 'じゅう dibaca jū dan berarti sepuluh.']]],
            ['minuman', 'Memesan minuman', 'Memesan minuman dengan sopan.', 'コーヒーをください。', 'Kōhī o kudasai.', 'Tolong beri saya kopi.', 'Pola … をください digunakan untuk meminta sesuatu dengan sopan. Partikel を dibaca o. コーヒー berarti kopi dan おちゃ berarti teh. Kata serapan biasanya ditulis dalam katakana. Tanda ー memanjangkan vokal dalam katakana.', [
                ['コーヒー', 'Kōhī', 'Kopi'], ['おちゃ', 'Ocha', 'Teh'], ['みず', 'Mizu', 'Air'], ['ください', 'Kudasai', 'Tolong berikan']],
                [['コーヒーをください berarti ...', ['Saya tidak suka kopi', 'Tolong beri saya kopi', 'Kopi panas sekali', 'Di mana kopi?'], 1, 'をください digunakan untuk meminta sesuatu.'], ['みず berarti ...', ['Teh', 'Susu', 'Air', 'Kopi'], 2, 'みず berarti air.']]],
        ];
        foreach ($lessons as $i => $row) {
            [$slug,$title,$summary,$jp,$romaji,$translation,$content,$words,$questions] = $row;
            // Do not overwrite material that an administrator has already edited.
            $lesson = Lesson::firstOrCreate(['slug' => $slug], ['title' => $title, 'summary' => $summary, 'content' => $content, 'japanese' => $jp, 'romaji' => $romaji, 'translation' => $translation, 'position' => $i + 1, 'duration_minutes' => 5, 'status' => 'draft']);
            if (! $lesson->wasRecentlyCreated) {
                continue;
            }
            foreach ($words as [$word,$reading,$meaning]) {
                Vocabulary::create(['lesson_id' => $lesson->id, 'japanese' => $word, 'romaji' => $reading, 'meaning' => $meaning, 'category' => $title]);
            }
            foreach ($questions as $j => [$prompt,$options,$answer,$explanation]) {
                Question::create(['lesson_id' => $lesson->id, 'prompt' => $prompt, 'options' => $options, 'correct_index' => $answer, 'explanation' => $explanation, 'position' => $j + 1, 'type' => 'meaning']);
            }
        }
        $rows = [['あいうえお', 'アイウエオ', ['a', 'i', 'u', 'e', 'o']], ['かきくけこ', 'カキクケコ', ['ka', 'ki', 'ku', 'ke', 'ko']], ['さしすせそ', 'サシスセソ', ['sa', 'shi', 'su', 'se', 'so']], ['たちつてと', 'タチツテト', ['ta', 'chi', 'tsu', 'te', 'to']], ['なにぬねの', 'ナニヌネノ', ['na', 'ni', 'nu', 'ne', 'no']], ['はひふへほ', 'ハヒフヘホ', ['ha', 'hi', 'fu', 'he', 'ho']], ['まみむめも', 'マミムメモ', ['ma', 'mi', 'mu', 'me', 'mo']], ['やゆよ', 'ヤユヨ', ['ya', 'yu', 'yo']], ['らりるれろ', 'ラリルレロ', ['ra', 'ri', 'ru', 're', 'ro']], ['わをん', 'ワヲン', ['wa', 'wo', 'n']]];
        foreach (['hiragana', 'katakana'] as $k => $script) {
            $position = 0;
            foreach ($rows as $row) {
                foreach (mb_str_split($row[$k]) as $i => $symbol) {
                    Character::firstOrCreate(['script' => $script, 'symbol' => $symbol], ['romaji' => $row[2][$i], 'group' => 'Dasar', 'position' => ++$position]);
                }
            }
        }
    }
}
