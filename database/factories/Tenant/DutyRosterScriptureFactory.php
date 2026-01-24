<?php

namespace Database\Factories\Tenant;

use App\Enums\ScriptureReadingType;
use App\Models\Tenant\DutyRosterScripture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\DutyRosterScripture>
 */
class DutyRosterScriptureFactory extends Factory
{
    protected $model = DutyRosterScripture::class;

    /**
     * Sample scripture references.
     *
     * @var array<string>
     */
    private array $scriptureReferences = [
        'Genesis 1:1-10',
        'Exodus 20:1-17',
        'Psalm 23:1-6',
        'Psalm 91:1-16',
        'Psalm 147:12-14',
        'Isaiah 40:1-11',
        'Isaiah 60:1-6',
        'Jeremiah 31:7-14',
        'Matthew 5:1-12',
        'Matthew 6:25-34',
        'Mark 1:1-8',
        'Luke 2:1-20',
        'John 1:1-18',
        'John 3:16-21',
        'Acts 2:1-21',
        'Romans 8:28-39',
        'Ephesians 1:3-14',
        'Philippians 4:4-9',
        'Colossians 3:12-17',
        'Revelation 21:1-7',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => fake()->randomElement($this->scriptureReferences),
            'reading_type' => fake()->optional(0.8)->randomElement(ScriptureReadingType::cases()),
            'reader_name' => fake()->optional(0.6)->name(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Set as first reading.
     */
    public function firstReading(): static
    {
        return $this->state(fn (array $attributes) => [
            'reading_type' => ScriptureReadingType::FirstReading,
            'sort_order' => 0,
        ]);
    }

    /**
     * Set as second reading.
     */
    public function secondReading(): static
    {
        return $this->state(fn (array $attributes) => [
            'reading_type' => ScriptureReadingType::SecondReading,
            'sort_order' => 1,
        ]);
    }

    /**
     * Set as gospel reading.
     */
    public function gospelReading(): static
    {
        return $this->state(fn (array $attributes) => [
            'reading_type' => ScriptureReadingType::GospelReading,
            'sort_order' => 2,
        ]);
    }

    /**
     * Set as psalm reading.
     */
    public function psalmReading(): static
    {
        return $this->state(fn (array $attributes) => [
            'reading_type' => ScriptureReadingType::PsalmReading,
            'sort_order' => 3,
        ]);
    }

    /**
     * Set a specific scripture reference.
     */
    public function withReference(string $reference): static
    {
        return $this->state(fn (array $attributes) => [
            'reference' => $reference,
        ]);
    }
}
