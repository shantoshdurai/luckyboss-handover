<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Key-skill type-ahead and related-skill suggestions.
 *
 * The curated taxonomy answers first and the model only fills gaps. That order
 * matters for three reasons: the taxonomy is instant where an API call is not,
 * it is identical for every candidate where model output drifts, and spec §4
 * lets an admin switch platform AI off entirely — at which point an AI-only
 * implementation would simply stop working. Suggestions degrade here, they do
 * not disappear.
 */
class SkillController extends Controller
{
    /** Related-skill responses are stable; caching keeps repeat taps free. */
    private const CACHE_TTL = 3600;

    /**
     * GET /api/v1/skills/search?q=flut
     *
     * Type-ahead. Prefix matches rank above substring matches so typing "java"
     * offers Java before JavaScript Testing.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $limit = min((int) $request->query('limit', 20), 50);

        if (mb_strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $like = str_replace(['%', '_'], ['\%', '\_'], $query);

        $skills = Skill::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$like}%")
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', ["{$like}%"])
            ->orderByDesc('popularity')
            ->limit($limit)
            ->get(['id', 'name', 'category']);

        return response()->json(['data' => $skills]);
    }

    /**
     * GET /api/v1/skills/suggested?category=IT & Software&qualification=Graduate
     *
     * The opening list, before the candidate has picked anything. Naukri frames
     * this as "Suggested skills based on your education", so category is the
     * primary signal and popularity breaks ties.
     */
    public function suggested(Request $request): JsonResponse
    {
        $category = trim((string) $request->query('category', ''));
        $limit = min((int) $request->query('limit', 12), 30);

        $skills = Skill::query()
            ->where('is_active', true)
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->orderByDesc('popularity')
            ->limit($limit)
            ->get(['id', 'name', 'category']);

        // An unrecognised category must still return something useful rather
        // than an empty screen with a required field.
        if ($skills->isEmpty()) {
            $skills = Skill::query()
                ->where('is_active', true)
                ->orderByDesc('popularity')
                ->limit($limit)
                ->get(['id', 'name', 'category']);
        }

        return response()->json(['data' => $skills, 'source' => 'taxonomy']);
    }

    /**
     * POST /api/v1/skills/related  { "skills": ["Flutter", "Firebase"] }
     *
     * The heart of the feature: given what the candidate has picked, what else
     * belongs? Already-selected skills are excluded — re-offering a chip the
     * user just tapped is the most obvious way to look broken.
     */
    public function related(Request $request): JsonResponse
    {
        $data = $request->validate([
            'skills' => ['required', 'array', 'min:1', 'max:40'],
            'skills.*' => ['required', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $limit = $data['limit'] ?? 12;
        $selectedSlugs = array_map([Skill::class, 'slugFor'], $data['skills']);

        $selected = Skill::whereIn('slug', $selectedSlugs)->get();

        $taxonomy = collect();
        if ($selected->isNotEmpty()) {
            $taxonomy = $this->fromTaxonomy($selected->pluck('id')->all(), $selectedSlugs, $limit);
        }

        if ($taxonomy->count() >= $limit) {
            return response()->json([
                'data' => $taxonomy->values(),
                'source' => 'taxonomy',
            ]);
        }

        // The taxonomy came up short — either a skill outside the curated set,
        // or a thin corner of it. Ask the model for the remainder.
        $aiSkills = $this->fromAi(
            $data['skills'],
            $taxonomy->pluck('name')->all(),
            $limit - $taxonomy->count()
        );

        return response()->json([
            'data' => $taxonomy->concat($aiSkills)->values(),
            'source' => $aiSkills->isEmpty() ? 'taxonomy' : 'taxonomy+ai',
        ]);
    }

    /**
     * Co-occurring skills, ranked by summed relation weight.
     *
     * Summing means a skill related to several of the candidate's selections
     * outranks one related strongly to a single selection — which is the
     * behaviour that makes a list of picks converge on a coherent role.
     */
    private function fromTaxonomy(array $skillIds, array $excludeSlugs, int $limit)
    {
        $cacheKey = 'skills.related.'.md5(implode(',', $skillIds).'|'.$limit);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($skillIds, $excludeSlugs, $limit) {
            return DB::table('skill_relations')
                ->join('skills', 'skills.id', '=', 'skill_relations.related_skill_id')
                ->whereIn('skill_relations.skill_id', $skillIds)
                ->whereNotIn('skills.slug', $excludeSlugs)
                ->where('skills.is_active', true)
                ->groupBy('skills.id', 'skills.name', 'skills.category')
                ->orderByRaw('SUM(skill_relations.weight) DESC')
                ->orderByDesc('skills.popularity')
                ->limit($limit)
                ->get(['skills.id', 'skills.name', 'skills.category']);
        });
    }

    /**
     * Model-generated suggestions for skills the taxonomy does not cover.
     *
     * Anything returned is persisted as an uncurated skill, so the taxonomy
     * grows from real usage and the same question is answered from the database
     * next time. Failure is silent by design — a candidate does not need to
     * know the suggestion engine had a bad minute, and the curated results are
     * already on screen.
     */
    private function fromAi(array $selected, array $alreadySuggested, int $limit)
    {
        $empty = collect();

        $aiEnabled = FeatureFlag::where('key', 'platform_ai_enabled')->value('is_enabled') ?? true;
        $key = config('services.gemini.api_key', env('GEMINI_API_KEY'));

        if (! $aiEnabled || ! $key || $limit < 1) {
            return $empty;
        }

        $model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-2.5-flash'));
        $exclude = implode(', ', array_merge($selected, $alreadySuggested));

        $prompt = "A job candidate has these skills: ".implode(', ', $selected).".\n"
            ."List exactly {$limit} additional professional skills they most likely also have or should add.\n"
            ."Rules: return ONLY a JSON array of strings, no prose, no markdown fence. "
            ."Each entry is a concrete, searchable skill name of 1-4 words. "
            ."Do NOT include soft-skill filler such as hard working, honesty, team player. "
            ."Do NOT repeat any of: {$exclude}.";

        try {
            $response = Http::withoutVerifying()
                ->timeout(8)
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".urlencode($key),
                    [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        // Generous budget: gemini-2.5-flash spends part of its
                        // allowance on internal reasoning before emitting a
                        // token of the answer, so a tight cap truncates the
                        // JSON array mid-string and the whole reply is lost.
                        'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 1200],
                    ]
                );

            if (! $response->successful()) {
                Log::warning('[SkillController] AI expansion HTTP '.$response->status().': '.mb_substr($response->body(), 0, 400));

                return $empty;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
            $names = $this->parseSkillList($text);

            if ($names === []) {
                // Reaching here means the model replied but not in the shape
                // asked for. Logged rather than swallowed: a silent empty list
                // is indistinguishable from "no suggestions exist", which sent
                // an earlier debugging session down the wrong path entirely.
                Log::warning('[SkillController] AI expansion unparseable: '.mb_substr($text, 0, 400));

                return $empty;
            }

            return collect($names)
                ->take($limit)
                ->map(function (string $name) use ($selected) {
                    // Persisted uncurated: the taxonomy learns, and an admin can
                    // review what the model added before trusting it.
                    $skill = Skill::resolve($name, null, curated: false);
                    $this->linkToSelection($skill, $selected);

                    return (object) [
                        'id' => $skill->id,
                        'name' => $skill->name,
                        'category' => $skill->category,
                    ];
                });
        } catch (\Throwable $e) {
            Log::warning('[SkillController] AI expansion failed: '.$e->getMessage());

            return $empty;
        }
    }

    /**
     * Records the AI's association at a low weight.
     *
     * Lower than any curated link, so a model guess never outranks a
     * hand-verified relationship in the ordering.
     */
    private function linkToSelection(Skill $skill, array $selected): void
    {
        foreach (Skill::whereIn('slug', array_map([Skill::class, 'slugFor'], $selected))->get() as $source) {
            Skill::relate($source, $skill, 40);
        }
    }

    /**
     * Extracts a string array from the model's reply.
     *
     * Models wrap JSON in ```json fences more often than not, so the fence is
     * stripped before decoding rather than trusted to be absent.
     */
    private function parseSkillList(string $text): array
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?|```$/m', '', $clean) ?? $clean;
        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        if (! is_array($decoded)) {
            // Salvage a truncated array. The model streams entries in order, so
            // a reply cut off mid-list still contains a run of complete quoted
            // strings — dropping fifteen good suggestions because the sixteenth
            // was clipped serves nobody.
            $decoded = $this->salvageStrings($clean);
        }

        if ($decoded === []) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->map(fn (string $v) => trim($v))
            ->filter(fn (string $v) => mb_strlen($v) <= 60)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Extracts complete double-quoted strings from a partial JSON array.
     *
     * Only used when a strict decode has already failed, so there is nothing to
     * lose by being lenient. An unterminated trailing string is ignored because
     * the regex requires a closing quote.
     */
    private function salvageStrings(string $text): array
    {
        if (! str_starts_with($text, '[')) {
            return [];
        }

        preg_match_all('/"((?:[^"\\]|\\.)*)"/', $text, $matches);

        return $matches[1] ?? [];
    }
}
