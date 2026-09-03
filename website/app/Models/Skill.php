<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Skill extends Model
{
    protected $fillable = ['name', 'slug', 'category', 'popularity', 'is_curated', 'is_active'];

    protected function casts(): array
    {
        return ['is_curated' => 'boolean', 'is_active' => 'boolean'];
    }

    /**
     * Normalises a skill name to its lookup key.
     *
     * 'Node.js', 'node js', 'NodeJS' and 'node-js' all have to collapse to one
     * skill. If they do not, three candidates with the same ability end up
     * un-matchable against the same vacancy, and the whole match score becomes
     * a measure of how someone happened to type.
     */
    public static function slugFor(string $name): string
    {
        $normalised = Str::lower(trim($name));
        $normalised = preg_replace('/[^a-z0-9]+/', '', $normalised) ?? '';

        return $normalised;
    }

    public function related(): BelongsToMany
    {
        return $this->belongsToMany(
            Skill::class,
            'skill_relations',
            'skill_id',
            'related_skill_id'
        )->withPivot('weight');
    }

    /**
     * Finds an existing skill by its normalised form, or creates one.
     *
     * [$curated] marks seeded entries; anything arriving from AI expansion or a
     * candidate's free text stays uncurated so it can be reviewed later.
     */
    public static function resolve(string $name, ?string $category = null, bool $curated = false): self
    {
        $slug = self::slugFor($name);

        return self::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => trim($name),
                'category' => $category,
                'is_curated' => $curated,
            ]
        );
    }

    /**
     * Records that two skills go together, in both directions.
     *
     * Bidirectional writes keep the read query a single indexed lookup — see
     * the migration's note on why the OR-across-two-columns alternative does
     * not scale.
     */
    public static function relate(self $a, self $b, int $weight = 50): void
    {
        if ($a->id === $b->id) {
            return;
        }

        $a->related()->syncWithoutDetaching([$b->id => ['weight' => $weight]]);
        $b->related()->syncWithoutDetaching([$a->id => ['weight' => $weight]]);
    }
}
