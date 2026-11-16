<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * For automated tests only — never seed fake members into a real database.
 *
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => 'Executive Member',
            'member_group' => 'executive',
            'photo_path' => null,
            'phone' => null,
            'bio' => null,
            'member_since' => null,
            'is_active' => true,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
