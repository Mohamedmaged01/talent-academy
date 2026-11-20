<?php

namespace App\Services;

use App\Models\Question;

/**
 * Bulk-add questions from text, one question per line:
 *
 *   Question text | option A; option B; option C | correct answer | points | type
 *
 * Points and type are optional. With options the type defaults to "mcq"; with no
 * options and a true/false answer it is "true_false"; otherwise "short_answer".
 * Lines starting with # are ignored. Commas work as the column separator when a
 * line has no "|" (for spreadsheets saved as CSV).
 */
class QuestionImporter
{
    /** Accepted spellings for true/false answers, English and Arabic. */
    private const TRUE_WORDS = ['true', 't', 'yes', 'صح', 'صحيح', 'نعم'];

    private const FALSE_WORDS = ['false', 'f', 'no', 'خطأ', 'خطا', 'خاطئ', 'لا'];

    /**
     * @return array{questions: list<array>, errors: list<string>}
     */
    public function parse(string $text): array
    {
        $questions = [];
        $errors = [];
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text); // strip a UTF-8 BOM from Excel exports

        foreach (preg_split('/\r\n|\r|\n/', $text) as $index => $line) {
            $line = trim($line);
            $lineNumber = $index + 1;

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $columns = array_map('trim', str_contains($line, '|') ? explode('|', $line) : str_getcsv($line));

            if (count($columns) < 3) {
                $errors[] = __('Line :line: expected at least "question | options | answer".', ['line' => $lineNumber]);

                continue;
            }

            [$body, $optionText, $answer] = $columns;
            $points = isset($columns[3]) && is_numeric($columns[3]) ? max(1, min(100, (int) $columns[3])) : 1;
            $options = array_values(array_filter(array_map('trim', explode(';', $optionText)), fn ($o) => $o !== ''));
            $type = strtolower($columns[4] ?? '');

            if (! in_array($type, Question::TYPES, true)) {
                $type = match (true) {
                    count($options) >= 2 => 'mcq',
                    in_array(Question::normalize($answer), [...self::TRUE_WORDS, ...self::FALSE_WORDS], true) => 'true_false',
                    default => 'short_answer',
                };
            }

            if ($body === '' || $answer === '') {
                $errors[] = __('Line :line: the question and the answer are required.', ['line' => $lineNumber]);

                continue;
            }

            if ($type === 'mcq') {
                if (count($options) < 2) {
                    $errors[] = __('Line :line: a multiple-choice question needs at least two options.', ['line' => $lineNumber]);

                    continue;
                }

                // Accept the answer as the option text or as its letter (A, B, C…).
                $letterIndex = strlen($answer) === 1 ? ord(strtoupper($answer)) - 65 : -1;
                if (! in_array(Question::normalize($answer), array_map([Question::class, 'normalize'], $options), true)) {
                    if ($letterIndex >= 0 && isset($options[$letterIndex])) {
                        $answer = $options[$letterIndex];
                    } else {
                        $errors[] = __('Line :line: the correct answer must be one of the options.', ['line' => $lineNumber]);

                        continue;
                    }
                }
            }

            if ($type === 'true_false') {
                $normalized = Question::normalize($answer);
                $answer = in_array($normalized, self::TRUE_WORDS, true) ? 'true' : 'false';
                $options = [];
            }

            $questions[] = [
                'type' => $type,
                'body' => mb_substr($body, 0, 2000),
                'options' => $type === 'mcq' ? $options : null,
                'correct_answer' => mb_substr($answer, 0, 250),
                'points' => $points,
                'difficulty' => 'medium',
            ];
        }

        return ['questions' => $questions, 'errors' => $errors];
    }
}
