<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;

/**
 * Association members as published on the reference website
 * (F:\projects\website\h2cabs_new\about.html, "Helping Hands Driver's
 * Association – Puducherry" section). Names and roles are copied exactly;
 * no photos, phone numbers or other details were published there.
 *
 * Idempotent: records are matched on `source_key` (including soft-deleted
 * ones) and only created when missing, so reseeding never duplicates,
 * overwrites admin edits, or restores members an admin deleted.
 */
class ReferenceMembersSeeder extends Seeder
{
    /** @var list<array{0: string, 1: ?string, 2: string}> name, role, group */
    public const MEMBERS = [
        ['G. Prabaharan', 'President', 'office_bearer'],
        ['H. Ashraff Ali', 'Vice-President', 'office_bearer'],
        ['K. Yuvaraj', 'Secretary', 'office_bearer'],
        ['M. Venkadesan', 'Vice-Secretary', 'office_bearer'],
        ['S. Ramesh', 'Joint-Secretary', 'office_bearer'],
        ['K. Raguraman', 'Treasurer', 'office_bearer'],
        ['K. Radhakrishnan', 'Joint-Treasurer', 'office_bearer'],
        ['P. Kumar', 'Executive Member', 'executive'],
        ['E. Pichande', 'Executive Member', 'executive'],
        ['P. Senthil', 'Executive Member', 'executive'],
        ['T. Suresh', 'Executive Member', 'executive'],
        ['J. Imran Basha', 'Executive Member', 'executive'],
        ['G. Manikandan', 'Executive Member', 'executive'],
        ['K. Abdul Samud', 'Executive Member', 'executive'],
        ['S. Arun Kumar', 'Executive Member', 'executive'],
    ];

    public function run(): void
    {
        foreach (self::MEMBERS as $index => [$name, $role, $group]) {
            Member::withTrashed()->firstOrCreate(
                ['source_key' => self::keyFor($name)],
                [
                    'name' => $name,
                    'role' => $role,
                    'member_group' => $group,
                    'is_active' => true,
                    'is_published' => true,
                    'sort_order' => ($index + 1) * 10,
                ],
            );
        }
    }

    public static function keyFor(string $name): string
    {
        return 'reference-about:'.strtolower(preg_replace('/[^A-Za-z]+/', '-', trim($name)));
    }
}
