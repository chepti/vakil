<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Relationship;
use App\Services\PersonMatcher;
use App\Support\HebrewDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * ייבוא CSV לדמויות: מסך העלאה → תצוגה מקדימה עם התאמה לדמויות קיימות
 * (או יצירת דמות חדשה) → אישור וביצוע בפועל.
 *
 * מבנה עמודות ה-CSV (ראה vakil-part-b-import.csv):
 *   row_id, relation, ref_row_id, first_name, last_name, maiden_name, gender,
 *   phone, city, current_occupation, birth_date_estimate, bio, source_page
 *
 * relation: root_of_branch (אין הורה עדיין) | spouse (ref_row_id = בן/בת הזוג) |
 *           child (ref_row_id = הורה אחד; ההורה השני מזוהה אוטומטית אם יש לו בן/בת-זוג שנוצר גם הוא בייבוא).
 */
class PersonImportController extends Controller
{
    private const COLUMNS = [
        'row_id', 'relation', 'ref_row_id', 'first_name', 'last_name', 'maiden_name',
        'gender', 'phone', 'city', 'current_occupation', 'birth_date_estimate', 'bio', 'source_page',
    ];

    private const SESSION_PREFIX = 'people_import.';

    public function create()
    {
        return Inertia::render('People/Import/Upload');
    }

    public function template()
    {
        $example = [
            'root1', 'root_of_branch', '', 'ישראל', 'ישראלי', '', 'male', '050-1234567',
            'תל אביב', 'מהנדס', '1970-01-01', 'תיאור קצר', 'עמוד לדוגמה',
        ];
        $example2 = [
            'root1_c1', 'child', 'root1', 'דנה', 'ישראלי', '', 'female', '',
            'תל אביב', 'סטודנטית', '2000-01-01', '', 'עמוד לדוגמה',
        ];

        return response()->streamDownload(function () use ($example, $example2) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::COLUMNS);
            fputcsv($out, $example);
            fputcsv($out, $example2);
            fclose($out);
        }, 'תבנית-ייבוא-דמויות.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $rows = $this->parseCsv($request->file('csv_file')->getRealPath());

        if (empty($rows)) {
            return back()->withErrors(['csv_file' => 'לא נמצאו שורות תקינות בקובץ']);
        }

        $known = array_column($rows, null, 'row_id');

        // ילדים רווקים (בלי שורת spouse משלהם בקובץ) יורשים את הכתובת של ההורה שאליו הם מקושרים
        $marriedRowIds = [];
        foreach ($rows as $r) {
            if ($r['relation'] === 'spouse' && $r['ref_row_id'] !== '') {
                $marriedRowIds[$r['ref_row_id']] = true;
            }
        }
        foreach ($rows as &$row) {
            if ($row['relation'] === 'child' && trim($row['city']) === '' && ! isset($marriedRowIds[$row['row_id']])) {
                $parent = $known[$row['ref_row_id']] ?? null;
                if ($parent && trim($parent['city']) !== '') {
                    $row['city']            = $parent['city'];
                    $row['city_inherited']  = true;
                }
            }
        }
        unset($row);
        $known = array_column($rows, null, 'row_id'); // רענון עם הכתובות שהושלמו

        $matcher = new PersonMatcher();

        foreach ($rows as &$row) {
            $candidates = $matcher->findCandidates($row);

            $row['candidates'] = $candidates->take(5)->map(fn($c) => [
                'id'         => $c['person']->id,
                'full_name'  => $c['person']->full_name,
                'city'       => $c['person']->city,
                'phone'      => $c['person']->phone,
                'score'      => $c['score'],
            ])->all();

            $row['suggested_decision'] = $matcher->isConfidentMatch($candidates)
                ? 'match:' . $candidates->first()['person']->id
                : 'new';

            $row['branch_root'] = $this->findBranchRoot($row['row_id'], $known);
        }
        unset($row);

        $token = (string) Str::uuid();
        session()->put(self::SESSION_PREFIX . $token, $rows);

        // קיבוץ לתצוגה — לפי שורש הענף, לפי סדר הופעה בקובץ
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['branch_root']][] = $row;
        }

        // רשימת כל הדמויות הקיימות — לחיפוש/שיוך ידני שאינו מוגבל להצעות האוטומטיות
        $allPeople = Person::select('id', 'first_name', 'last_name', 'city', 'phone')
            ->orderBy('first_name')
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'full_name' => $p->full_name,
                'city'      => $p->city,
                'phone'     => $p->phone,
            ]);

        return Inertia::render('People/Import/Review', [
            'token'      => $token,
            'grouped'    => array_values($grouped),
            'allPeople'  => $allPeople,
        ]);
    }

    public function commit(Request $request)
    {
        $data = $request->validate([
            'token'                    => 'required|string',
            'rows'                     => 'required|array',
            'rows.*.row_id'            => 'required|string',
            'rows.*.decision'          => 'required|string',
            'rows.*.first_name'        => 'required|string|max:100',
            'rows.*.last_name'         => 'nullable|string|max:100',
            'rows.*.maiden_name'       => 'nullable|string|max:100',
            'rows.*.gender'            => 'required|in:male,female',
            'rows.*.phone'             => 'nullable|string|max:30',
            'rows.*.city'              => 'nullable|string|max:100',
            'rows.*.current_occupation'=> 'nullable|string|max:255',
            'rows.*.birth_date_estimate'=> 'nullable|date',
            'rows.*.bio'               => 'nullable|string',
        ]);

        $batch = session()->get(self::SESSION_PREFIX . $data['token']);
        if (! $batch) {
            return back()->withErrors(['token' => 'פג תוקף התצוגה המקדימה — יש להעלות את הקובץ מחדש']);
        }

        $structureByRowId = array_column($batch, null, 'row_id');
        $editedByRowId     = array_column($data['rows'], null, 'row_id');

        $personIdByRowId = [];
        $resolvedRowIds  = []; // כולל שורות שדולגו (skip) — כדי לא לחכות להן לנצח
        $created = 0;
        $matched = 0;
        $skipped = 0;

        DB::transaction(function () use ($structureByRowId, $editedByRowId, &$personIdByRowId, &$resolvedRowIds, &$created, &$matched, &$skipped) {
            // שלב 1: יצירת/זיהוי דמות לכל שורה, בסדר תלות (הורים לפני ילדים)
            $pending = array_keys($structureByRowId);
            $guard   = 0;
            while (! empty($pending) && $guard++ < 500) {
                foreach ($pending as $i => $rowId) {
                    $structure = $structureByRowId[$rowId];
                    $refId     = $structure['ref_row_id'] ?: null;

                    if ($refId && ! array_key_exists($refId, $resolvedRowIds)) {
                        continue; // מחכים שההורה/בן-הזוג יעובד קודם
                    }

                    $edited   = $editedByRowId[$rowId] ?? $structure;
                    $decision = $edited['decision'] ?? 'new';

                    if ($decision === 'skip') {
                        $skipped++;
                        $resolvedRowIds[$rowId] = true;
                        unset($pending[$i]);
                        continue;
                    }

                    if (str_starts_with($decision, 'match:')) {
                        $personId = (int) substr($decision, 6);
                        $person   = Person::find($personId);
                        if (! $person) {
                            // הדמות שנבחרה לא נמצאה (נמחקה בינתיים) — מדלגים על השורה במקום לקרוס
                            $skipped++;
                            $resolvedRowIds[$rowId] = true;
                            unset($pending[$i]);
                            continue;
                        }
                        // השלמת שדות ריקים בלבד — לא דורסים מידע קיים בדמות
                        $fillIfEmpty = [
                            'phone' => $edited['phone'] ?? null,
                            'city'  => $edited['city'] ?? null,
                            'current_occupation' => $edited['current_occupation'] ?? null,
                            'bio'   => $edited['bio'] ?? null,
                        ];
                        foreach ($fillIfEmpty as $field => $value) {
                            if (empty($person->$field) && ! empty($value)) {
                                $person->$field = $value;
                            }
                        }
                        if ($person->isDirty()) {
                            $person->save();
                        }
                        $matched++;
                    } else {
                        $birthDate = $edited['birth_date_estimate'] ?? null;
                        $person = Person::create([
                            'first_name'           => $edited['first_name'],
                            'last_name'            => $edited['last_name'] ?: '',
                            'maiden_name'          => $edited['maiden_name'] ?: null,
                            'gender'               => $edited['gender'],
                            'birth_date_gregorian' => $birthDate ?: null,
                            'birth_date_hebrew'    => $birthDate ? HebrewDate::format(\Carbon\Carbon::parse($birthDate)) : null,
                            'phone'                => $edited['phone'] ?: null,
                            'city'                 => $edited['city'] ?: null,
                            'current_occupation'   => $edited['current_occupation'] ?: null,
                            'bio'                  => $edited['bio'] ?: null,
                            'created_by'           => Auth::id(),
                        ]);
                        $created++;
                    }

                    $personIdByRowId[$rowId] = $person->id;
                    $resolvedRowIds[$rowId]  = true;
                    unset($pending[$i]);
                }
            }

            // שלב 2: קשרים — spouse ואז parent_child (עם שיוך אוטומטי להורה השני אם יש בן/בת-זוג)
            foreach ($structureByRowId as $rowId => $structure) {
                $personId = $personIdByRowId[$rowId] ?? null;
                $refId    = $structure['ref_row_id'] ?: null;
                $refPersonId = $refId ? ($personIdByRowId[$refId] ?? null) : null;
                if (! $personId || ! $refPersonId) {
                    continue;
                }

                if ($structure['relation'] === 'spouse') {
                    Relationship::firstOrCreate([
                        'person1_id' => min($personId, $refPersonId),
                        'person2_id' => max($personId, $refPersonId),
                        'type'       => 'spouse',
                    ]);
                } elseif ($structure['relation'] === 'child') {
                    Relationship::firstOrCreate([
                        'person1_id' => $refPersonId,
                        'person2_id' => $personId,
                        'type'       => 'parent_child',
                    ]);

                    // ההורה השני, אם יש לו בן/בת-זוג רשום/ה כבר
                    $otherParentId = Relationship::where('type', 'spouse')
                        ->where(fn($q) => $q->where('person1_id', $refPersonId)->orWhere('person2_id', $refPersonId))
                        ->get()
                        ->map(fn($r) => $r->person1_id == $refPersonId ? $r->person2_id : $r->person1_id)
                        ->first();

                    if ($otherParentId && $otherParentId != $personId) {
                        Relationship::firstOrCreate([
                            'person1_id' => $otherParentId,
                            'person2_id' => $personId,
                            'type'       => 'parent_child',
                        ]);
                    }
                }
            }
        });

        session()->forget(self::SESSION_PREFIX . $data['token']);

        return redirect()->route('people.index')
            ->with('success', "הייבוא הושלם: {$created} דמויות נוצרו, {$matched} שויכו לדמויות קיימות, {$skipped} דולגו");
    }

    /** @return array<int, array<string,mixed>> */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $rows = [];
        $header = null;

        while (($line = fgetcsv($handle)) !== false) {
            if ($header === null) {
                // הסרת BOM מהעמודה הראשונה של הכותרת אם קיים
                $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', $line[0]);
                $header = $line;
                continue;
            }

            if (count(array_filter($line, fn($v) => $v !== null && $v !== '')) === 0) {
                continue; // שורה ריקה
            }

            $assoc = [];
            foreach (self::COLUMNS as $i => $col) {
                $assoc[$col] = $line[$i] ?? '';
            }

            if ($assoc['row_id'] === '' || $assoc['first_name'] === '') {
                continue;
            }

            $rows[] = $assoc;
        }

        fclose($handle);

        return $rows;
    }

    /** עוקב אחרי ref_row_id עד לשורש הענף (root_of_branch), לצורך קיבוץ בתצוגה */
    private function findBranchRoot(string $rowId, array $known, int $guard = 0): string
    {
        $row = $known[$rowId] ?? null;
        if (! $row || $guard > 20) {
            return $rowId;
        }
        if (empty($row['ref_row_id']) || ! isset($known[$row['ref_row_id']])) {
            return $rowId;
        }

        return $this->findBranchRoot($row['ref_row_id'], $known, $guard + 1);
    }
}
