<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\HandlesApiUploads;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployerJobResource;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployerJobController extends Controller
{
    use HandlesApiUploads;

    /** GET /v1/employer/jobs */
    public function index(Request $request)
    {
        $company = $this->company($request);

        $jobs = Job::with('jobCategory')
            ->where('company_id', $company->id)
            ->withCount('applications')
            ->latest()
            ->paginate(20);

        return EmployerJobResource::collection($jobs);
    }

    /** POST /v1/jobs */
    public function store(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:120'],
            'job_category_id' => ['nullable', 'exists:job_categories,id'],
            'country_code' => ['required', 'string', 'size:2'],
            'location' => ['required', 'string', 'max:200'],
            'work_mode' => ['required', 'string', 'max:40'],
            'job_type' => ['nullable', 'string', 'max:40'],
            'description' => ['required', 'string', 'max:500'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'salary_visible' => ['nullable', 'boolean'],
            'experience_min' => ['nullable', 'integer', 'min:0'],
            'experience_max' => ['nullable', 'integer', 'min:0'],
            'vacancies' => ['nullable', 'integer', 'min:1'],
            'closing_date' => ['nullable', 'date', 'after_or_equal:today'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'publish_now' => ['nullable', 'boolean'],
        ]);

        $publish = $request->boolean('publish_now', true);

        $job = Job::create([
            'company_id' => $company->id,
            'job_category_id' => $data['job_category_id'] ?? $this->resolveCategoryId($data['category'] ?? null),
            'title' => $data['title'],
            'description' => $data['description'],
            'country_code' => $data['country_code'],
            'location' => $data['location'],
            'work_mode' => $data['work_mode'],
            'job_type' => $data['job_type'] ?? 'Full-Time',
            'salary_min' => $data['salary_min'] ?? null,
            'salary_max' => $data['salary_max'] ?? null,
            'currency_code' => $data['currency_code'] ?? null,
            'salary_visible' => $request->boolean('salary_visible', true),
            'experience_min' => $data['experience_min'] ?? null,
            'experience_max' => $data['experience_max'] ?? null,
            'vacancies' => $data['vacancies'] ?? 1,
            'closing_date' => $data['closing_date'] ?? null,
            'image_path' => $this->storePublicUpload($request->file('image'), 'jobs', 'job'),
            'status' => $publish ? 'published' : 'draft',
            'published_at' => $publish ? now() : null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Vacancy published successfully.',
            'data' => new EmployerJobResource($job->load('jobCategory')->loadCount('applications')),
        ], 201);
    }

    /** POST /v1/employer/jobs/{job} */
    public function update(Request $request, Job $job): JsonResponse
    {
        $company = $this->company($request);
        abort_unless($job->company_id === $company->id, 404);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'description' => ['sometimes', 'string', 'max:500'],
            'location' => ['sometimes', 'string', 'max:200'],
            'work_mode' => ['sometimes', 'string', 'max:40'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            $this->deletePublicUpload($job->image_path);
            $data['image_path'] = $this->storePublicUpload($request->file('image'), 'jobs', 'job');
        }
        unset($data['image']);

        // A job image stays mandatory, matching the web portal.
        abort_if(blank($data['image_path'] ?? $job->image_path), 422, 'A job image is required.');

        if (($data['status'] ?? null) === 'published' && ! $job->published_at) {
            $data['published_at'] = now();
        }

        $job->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Vacancy updated successfully.',
            'data' => new EmployerJobResource($job->fresh()->load('jobCategory')->loadCount('applications')),
        ]);
    }

    private function company(Request $request): Company
    {
        abort_unless($request->user()?->hasRole('employer'), 403);

        return $request->user()->companies()->firstOrFail();
    }

    private function resolveCategoryId(?string $name): ?int
    {
        if (blank($name)) {
            return JobCategory::where('is_active', true)->value('id');
        }

        return JobCategory::where('name', $name)->value('id')
            ?? JobCategory::where('slug', str($name)->slug())->value('id')
            ?? JobCategory::where('is_active', true)->value('id');
    }
}
