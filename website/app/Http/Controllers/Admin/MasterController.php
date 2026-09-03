<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyGrade;
use App\Models\CompanyType;
use App\Models\Country;
use App\Models\JobCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MasterController extends Controller
{
    private function definition(string $master): array
    {
        return match ($master) {
            'company-types' => ['model' => CompanyType::class, 'label' => 'Company Types', 'fields' => ['name']],
            'company-grades' => ['model' => CompanyGrade::class, 'label' => 'Company Grades', 'fields' => ['name']],
            'countries' => ['model' => Country::class, 'label' => 'Countries', 'fields' => ['name', 'code', 'sort_order']],
            'job-categories', 'categories' => ['model' => JobCategory::class, 'label' => 'Job Categories', 'fields' => ['name', 'description', 'icon', 'icon_image', 'sort_order', 'show_on_home']],
            default => abort(404),
        };
    }

    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(string $master): View
    {
        $this->ensureAdmin(); $definition = $this->definition($master); $model = $definition['model'];
        $records = $model::when(in_array($master, ['job-categories', 'categories'], true), fn ($query) => $query->orderBy('sort_order'))->orderBy('name')->paginate(20);
        return view('admin.masters.index', compact('master', 'definition', 'records'));
    }

    public function create(string $master): View
    {
        $this->ensureAdmin(); $definition = $this->definition($master);
        return view('admin.masters.form', compact('master', 'definition'))->with('record', null);
    }

    public function store(Request $request, string $master): RedirectResponse
    {
        $this->ensureAdmin(); $definition = $this->definition($master); $model = $definition['model'];
        $data = $this->validated($request, $definition);
        if ($master === 'job-categories' && $request->hasFile('icon_image')) $data['icon_image_path'] = $this->storeIcon($request);
        $data['slug'] = Str::slug($data['name']); $data['is_active'] = $request->boolean('is_active');
        if (in_array('show_on_home', $definition['fields'], true)) { $data['show_on_home'] = $request->boolean('show_on_home'); }
        if ($master === 'countries') $data['code'] = strtoupper($data['code']);
        $record = $model::create($data);
        if ($master === 'job-categories') $this->reorderCategories($record, (int) ($data['sort_order'] ?? 0));
        return redirect()->route('admin.masters.index', $master)->with('success', 'Master record created.');
    }

    public function edit(string $master, int $record): View
    {
        $this->ensureAdmin(); $definition = $this->definition($master); $model = $definition['model'];
        return view('admin.masters.form', compact('master', 'definition'))->with('record', $model::findOrFail($record));
    }

    public function update(Request $request, string $master, int $record): RedirectResponse
    {
        $this->ensureAdmin(); $definition = $this->definition($master); $model = $definition['model']; $item = $model::findOrFail($record);
        $data = $this->validated($request, $definition); if ($master === 'countries') $data['code'] = strtoupper($data['code']); $data['slug'] = Str::slug($data['name']); $data['is_active'] = $request->boolean('is_active');
        if ($master === 'job-categories' && $request->hasFile('icon_image')) $data['icon_image_path'] = $this->storeIcon($request);
        if (in_array('show_on_home', $definition['fields'], true)) { $data['show_on_home'] = $request->boolean('show_on_home'); }
        $item->update($data);
        if ($master === 'job-categories') $this->reorderCategories($item, (int) ($data['sort_order'] ?? 0));
        return redirect()->route('admin.masters.index', $master)->with('success', 'Master record updated.');
    }

    public function destroy(string $master, int $record): RedirectResponse
    {
        $this->ensureAdmin(); $definition = $this->definition($master); $model = $definition['model']; $model::findOrFail($record)->delete();
        return redirect()->route('admin.masters.index', $master)->with('success', 'Master record deleted.');
    }

    private function validated(Request $request, array $definition): array
    {
        $rules = ['name' => ['required', 'string', 'max:120'], 'code' => ['required', 'string', 'size:2', 'alpha', 'unique:countries,code,'.($request->route('record') ?? 'NULL')], 'description' => ['nullable', 'string'], 'icon' => ['nullable', 'string', 'max:100'], 'sort_order' => ['nullable', 'integer', 'min:0'], 'icon_image' => ['nullable', 'image', 'max:2048']];
        return $request->validate(array_intersect_key($rules, array_flip($definition['fields'])));
    }

    private function storeIcon(Request $request): string
    {
        $file = $request->file('icon_image'); $directory = public_path('uploads/master-icons');
        if (! is_dir($directory)) mkdir($directory, 0755, true);
        $name = 'category-'.now()->format('YmdHis').'-'.Str::random(6).'.'.$file->extension(); $file->move($directory, $name);
        return 'uploads/master-icons/'.$name;
    }

    private function reorderCategories(JobCategory $selected, int $requestedOrder): void
    {
        $categories = JobCategory::where('id', '!=', $selected->id)->orderBy('sort_order')->orderBy('id')->get()->values();
        $position = max(0, min($requestedOrder - 1, $categories->count()));
        $categories->splice($position, 0, [$selected]);
        foreach ($categories as $index => $category) $category->updateQuietly(['sort_order' => $index + 1]);
    }
}