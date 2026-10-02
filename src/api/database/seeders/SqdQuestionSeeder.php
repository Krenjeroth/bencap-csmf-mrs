<?php

namespace Database\Seeders;

use App\Models\SqdQuestion;
use Illuminate\Database\Seeder;

/**
 * The ARTA-2022 SQD statements, verbatim from the form (playbook 1.7, using
 * the single-spaced "walang palakasan" from Picture1.jpg). SQD0 (overall
 * satisfaction) is reported on its own and is not part of the overall
 * score (ADR 0005; to confirm under playbook Q14). Safe to re-run: rows are
 * matched by code and form version, and existing rows are left as edited.
 */
class SqdQuestionSeeder extends Seeder
{
    /** @var array<string, string> */
    public const STATEMENTS = [
        'SQD0' => 'I am satisfied with the service that I availed.',
        'SQD1' => 'I spent a reasonable amount of time for my transaction.',
        'SQD2' => 'The office followed the transaction’s requirements and steps based on the information provided.',
        'SQD3' => 'The steps (including payment) I needed to do for my transaction were easy and simple.',
        'SQD4' => 'I easily found information about my transaction from the office or its website.',
        'SQD5' => 'I paid a reasonable amount of fees for my transaction.',
        'SQD6' => 'I feel the office was fair to everyone, or “walang palakasan”, during my transaction.',
        'SQD7' => 'I was treated courteously by the staff, and (if asked for help) the staff was helpful.',
        'SQD8' => 'I got what I needed from the government office, or (if denied) denial of request was sufficiently explained to me.',
    ];

    public function run(): void
    {
        $position = 0;
        foreach (self::STATEMENTS as $code => $statement) {
            SqdQuestion::firstOrCreate(
                ['code' => $code, 'form_version' => SqdQuestion::CURRENT_FORM_VERSION],
                [
                    'statement' => $statement,
                    'included_in_overall' => $code !== 'SQD0',
                    'sort_order' => $position++,
                    'is_active' => true,
                ],
            );
        }
    }
}
