<x-employer-sidebar :title="$job ? 'Edit Job Opening' : 'Post New Job Opening'">
    <div class="max-w-4xl mx-auto space-y-6">
        {{-- Top Back Link & Header --}}
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('employer.jobs.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-secondary-600 hover:text-navy transition-colors mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    <span>Back to My Jobs</span>
                </a>
                <h2 class="text-2xl font-heading font-extrabold text-navy">
                    {{ $job ? 'Edit Job Vacancy' : 'Create New Job Vacancy' }}
                </h2>
                <p class="text-xs text-text-muted mt-0.5">Publish roles directly to verified candidates across Singapore, Malaysia, and India.</p>
            </div>
        </div>

        {{-- Main Form Card --}}
        <div class="bg-white rounded-3xl border border-border p-8 shadow-xs">
            <form method="POST" enctype="multipart/form-data" action="{{ $job ? route('employer.jobs.update', $job) : route('employer.jobs.store') }}" class="space-y-6">
                @csrf
                @if($job)
                    @method('PUT')
                @endif

                @if($errors->any())
                    <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Job Image <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="image" accept="image/*" {{ $job?->image_path ? '' : 'required' }} class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy">
                        @if($job?->image_path)
                            <img src="{{ asset($job->image_path) }}" alt="Current job image" class="mt-2 h-24 w-40 rounded-xl object-cover">
                        @endif
                    </div>
                    {{-- Job Title --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Job Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" value="{{ old('title', $job?->title) }}" placeholder="e.g. Warehouse Operations Lead, Cloud DevOps Engineer" required class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Job Category <span class="text-red-500">*</span>
                        </label>
                        <select name="job_category_id" required class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('job_category_id', $job?->job_category_id) == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Target Country --}}
                    <div>
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Target Country <span class="text-red-500">*</span>
                        </label>
                        <select name="country_code" required class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->code }}" @selected(old('country_code', $job?->country_code) === $country->code)>
                                    {{ $country->name }} ({{ $country->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- City / Location --}}
                    <div>
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            City / District Location
                        </label>
                        <input type="text" name="location" value="{{ old('location', $job?->location) }}" placeholder="e.g. Singapore, Jurong or India, Bengaluru" class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                    </div>

                    {{-- Work Mode --}}
                    <div>
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Work Mode
                        </label>
                        <select name="work_mode" class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                            @foreach(['on-site' => 'On-site', 'hybrid' => 'Hybrid', 'remote' => 'Remote'] as $modeKey => $modeLabel)
                                <option value="{{ $modeKey }}" @selected(old('work_mode', $job?->work_mode ?? 'on-site') === $modeKey)>
                                    {{ $modeLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Job Type --}}
                    <div>
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Employment Type <span class="text-red-500">*</span>
                        </label>
                        <select name="job_type" required class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                            @foreach(['Full-time', 'Part-time', 'Contract', 'Internship'] as $type)
                                <option value="{{ $type }}" @selected(old('job_type', $job?->job_type ?? 'Full-time') === $type)>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Vacancies --}}
                    <div>
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Open Vacancies <span class="text-red-500">*</span>
                        </label>
                        <input type="number" min="1" name="vacancies" value="{{ old('vacancies', $job?->vacancies ?? 1) }}" required class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                    </div>

                    {{-- Experience Range --}}
                    <div>
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Experience Range (Years)
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <input type="number" min="0" name="experience_min" placeholder="Min (e.g. 2)" value="{{ old('experience_min', $job?->experience_min) }}" class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                            <input type="number" min="0" name="experience_max" placeholder="Max (e.g. 5)" value="{{ old('experience_max', $job?->experience_max) }}" class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                        </div>
                    </div>

                    {{-- Salary Range & Currency --}}
                    <div>
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Monthly Salary Range & Currency <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="number" min="0" name="salary_min" placeholder="Min (e.g. 3500)" value="{{ old('salary_min', $job?->salary_min) }}" class="w-full px-3 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                            <input type="number" min="0" name="salary_max" placeholder="Max (e.g. 5000)" value="{{ old('salary_max', $job?->salary_max) }}" class="w-full px-3 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                            <select name="currency_code" required class="w-full px-2 py-3 rounded-xl border border-border text-sm text-navy font-bold focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                                <option value="SGD" @selected(old('currency_code', $job?->currency_code ?? 'SGD') === 'SGD')>SGD ($)</option>
                                <option value="INR" @selected(old('currency_code', $job?->currency_code) === 'INR')>INR (₹)</option>
                                <option value="MYR" @selected(old('currency_code', $job?->currency_code) === 'MYR')>MYR (RM)</option>
                                <option value="USD" @selected(old('currency_code', $job?->currency_code) === 'USD')>USD ($)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Closing Date --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Application Closing Date
                        </label>
                        <input type="date" name="closing_date" value="{{ old('closing_date', $job?->closing_date?->format('Y-m-d')) }}" class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Job Description & Key Responsibilities <span class="text-red-500">*</span>
                        </label>
                        <textarea name="description" rows="6" maxlength="500" required placeholder="Outline key tasks, deliverables, and role expectations..." class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">{{ old('description', $job?->description) }}</textarea>
                    </div>

                    {{-- Requirements --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Requirements & Qualifications
                        </label>
                        <textarea name="requirements" rows="4" placeholder="List required certifications, degrees, or technical skill proficiencies..." class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">{{ old('requirements', $job?->requirements) }}</textarea>
                    </div>

                    {{-- Benefits & Perks --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2">
                            Benefits & Compensation Perks
                        </label>
                        <input type="text" name="benefits" value="{{ old('benefits', $job?->benefits) }}" placeholder="e.g. AWS 13th Month Bonus, Medical Insurance, Flexible Working, Shift Allowances" class="w-full px-4 py-3 rounded-xl border border-border text-sm text-navy focus:border-navy focus:ring-1 focus:ring-navy transition-all">
                    </div>

                    {{-- Publish Immediately Toggle --}}
                    <div class="md:col-span-2 p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="publish_now" value="1" id="publish_now" @checked(old('publish_now', $job?->status === 'published' || !$job)) class="mt-1 w-5 h-5 rounded text-navy focus:ring-navy">
                        <label for="publish_now" class="cursor-pointer">
                            <span class="font-bold text-navy text-sm block">Publish immediately upon saving</span>
                            <span class="text-xs text-text-secondary block mt-0.5">When checked, this role will appear live on the job board and be instantly eligible for candidate AI matching.</span>
                        </label>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-4 border-t border-border flex items-center justify-end gap-3">
                    <a href="{{ route('employer.jobs.index') }}" class="btn btn-outline btn-md font-bold text-xs">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-md px-8 font-bold text-xs shadow-md hover:shadow-lg">
                        {{ $job ? 'Save Changes' : 'Publish Job Opening' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-employer-sidebar>
