<x-seeker-sidebar title="Candidate Profile & Resume">
    <script>
        const initialProfileData = {!! $initialDataJson !!};

        function candidateProfileEditor() {
            return {
                entryMode: 'ai',
                fileName: '',
                isParsing: false,
                parseMessage: '',
                currentTitle: initialProfileData.currentTitle || 'Warehouse Supervisor',
                yearsExperience: initialProfileData.yearsExperience || 4,
                professionalSummary: initialProfileData.professionalSummary || '',
                currentLocation: initialProfileData.currentLocation || 'Singapore',
                expectedSalary: initialProfileData.expectedSalary || 3500,
                noticePeriod: initialProfileData.noticePeriod || 'Immediate / 1 Month',
                
                masterCatalog: [
                    'Python', 'Flutter', 'React', 'React Native', 'Node.js', 'JavaScript', 'TypeScript', 'PHP', 'Laravel', 'Java', 'C++', 'C#', '.NET', 'Go', 'Rust', 'Ruby', 'Swift', 'Kotlin', 'SQL', 'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Docker', 'Kubernetes', 'AWS', 'Azure', 'Google Cloud (GCP)', 'Git', 'GitHub', 'CI/CD', 'Linux', 'REST APIs', 'GraphQL', 'HTML5', 'CSS3', 'Tailwind CSS', 'Vue.js', 'Next.js', 'Angular', 'Machine Learning', 'TensorFlow', 'PyTorch', 'Data Analysis', 'Cybersecurity', 'Figma', 'UI/UX Design',
                    'Warehouse Operations', 'Inventory Management', 'Logistics Management', 'Supply Chain Logistics', 'SAP ERP', 'WMS Software', 'Forklift Operation', 'Safety Compliance', 'Order Fulfillment', 'Material Handling', 'Freight Forwarding', 'Customs Clearance', 'Stock Auditing', 'Procurement', 'Fleet Management', 'Supply Chain Optimization', 'ISO 9001 Standards',
                    'Construction Site Supervision', 'AutoCAD', 'BIM Modeling', 'Structural Engineering', 'Civil Engineering', 'Electrical Engineering', 'Mechanical Engineering', 'HVAC Systems', 'Project Management', 'Contract Administration', 'Quantity Surveying', 'Welding & Fabrication', 'CNC Machining', 'Quality Assurance (QA/QC)', 'Lean Manufacturing', 'Six Sigma',
                    'Patient Care', 'Clinical Nursing', 'First Aid & CPR', 'Medical Records Management', 'Phlebotomy', 'Geriatric Care', 'Infection Control', 'Medication Administration', 'Healthcare Administration',
                    'Business Development', 'B2B Sales', 'CRM (Customer Relationship Management)', 'Salesforce', 'Account Management', 'Financial Modeling', 'Corporate Accounting', 'Taxation & Auditing', 'QuickBooks', 'Excel / Advanced Spreadsheets', 'Digital Marketing', 'SEO', 'Content Marketing', 'Social Media Management', 'Human Resources (HR)', 'Talent Acquisition', 'Payroll Management', 'Customer Support', 'Office Administration',
                    'Hotel Front Desk', 'Food & Beverage Service', 'Culinary Operations', 'Food Hygiene (HACCP)', 'Retail Store Operations', 'Point of Sale (POS)', 'Visual Merchandising', 'Event Coordination'
                ],

                skillsList: Array.isArray(initialProfileData.skills) ? Array.from(initialProfileData.skills) : ['Warehouse Operations', 'Inventory Management', 'SAP ERP', 'Safety Compliance', 'Logistics Management'],
                searchQuery: '',
                showDropdown: false,

                get filteredSuggestions() {
                    if (!this.searchQuery || this.searchQuery.trim().length === 0) return [];
                    const q = this.searchQuery.toLowerCase().trim();
                    return this.masterCatalog
                        .filter(item => item.toLowerCase().includes(q) && !this.skillsList.includes(item))
                        .slice(0, 10);
                },

                addSkill(skillName) {
                    const val = (skillName || this.searchQuery).trim();
                    if (val && !this.skillsList.includes(val)) {
                        this.skillsList.push(val);
                    }
                    this.searchQuery = '';
                    this.showDropdown = false;
                },

                removeSkill(index) {
                    this.skillsList.splice(index, 1);
                },

                async autoParseResume(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    this.fileName = file.name;
                    this.isParsing = true;
                    this.parseMessage = 'AI is extracting skills, experience, and profile details...';

                    const formData = new FormData();
                    formData.append('resume_file', file);

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch('{{ route('seeker.resume.parse') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token || '',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const res = await response.json();
                        if (res.status === 'success' && res.data) {
                            const d = res.data;
                            if (d.title) this.currentTitle = d.title;
                            if (d.years_experience) this.yearsExperience = d.years_experience;
                            if (d.summary) this.professionalSummary = d.summary;
                            if (d.current_location) this.currentLocation = d.current_location;
                            if (d.expected_salary) this.expectedSalary = d.expected_salary;
                            if (d.notice_period) this.noticePeriod = d.notice_period;
                            if (d.skills && Array.isArray(d.skills)) {
                                d.skills.forEach(s => {
                                    if (!this.skillsList.includes(s)) this.skillsList.push(s);
                                });
                            }
                            this.parseMessage = '✓ Successfully extracted & auto-filled from resume!';
                        } else {
                            this.parseMessage = 'Resume file attached. Review details below.';
                        }
                    } catch (e) {
                        console.warn('Auto-parse notice:', e);
                        this.parseMessage = 'Resume file selected.';
                    } finally {
                        this.isParsing = false;
                    }
                }
            };
        }
    </script>

    <div class="max-w-4xl mx-auto space-y-6" x-data="candidateProfileEditor()">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-heading font-extrabold text-navy">Candidate Profile & Resume</h2>
                <span class="sr-only">Manual Resume Profile</span>
                <p class="text-xs text-text-muted mt-1">Upload your CV to auto-fill your profile with AI, or edit details manually.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('seeker.dashboard') }}" class="btn btn-outline btn-sm text-xs font-bold">
                    &larr; Back to Dashboard
                </a>
            </div>
        </div>

        {{-- Mode Selector: AI Auto-Extraction vs Manual Typing --}}
        <div class="bg-white rounded-2xl border border-border p-2 shadow-xs flex items-center gap-2 max-w-md">
            <button type="button" 
                    @click="entryMode = 'ai'" 
                    :class="entryMode === 'ai' ? 'bg-accent text-white shadow-xs' : 'text-slate-600 hover:text-navy hover:bg-slate-100'"
                    class="flex-1 py-2 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                <span>AI Resume Auto-Fill</span>
            </button>
            <button type="button" 
                    @click="entryMode = 'manual'" 
                    :class="entryMode === 'manual' ? 'bg-accent text-white shadow-xs' : 'text-slate-600 hover:text-navy hover:bg-slate-100'"
                    class="flex-1 py-2 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                <span>Manual Typing Mode</span>
            </button>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2 shadow-xs">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data" action="{{ route('seeker.profile.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Hidden File Input (Clean trigger with no browser native overlay / blue hover circle) --}}
            <input type="file" 
                   x-ref="resumeFileInput" 
                   name="resume_file" 
                   id="resume_file_input_element"
                   accept=".pdf,.doc,.docx,.txt" 
                   @change="autoParseResume($event)"
                   class="hidden">

            <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    @if($profile?->profile_photo_path)
                        <img src="{{ asset($profile->profile_photo_path) }}" alt="{{ $user->name }} profile picture" class="w-16 h-16 rounded-full object-cover border-2 border-accent">
                    @else
                        <div class="w-16 h-16 rounded-full bg-blue-100 text-accent flex items-center justify-center text-lg font-bold border-2 border-blue-200">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1">
                        <label class="block text-sm font-bold text-navy mb-1.5">Profile Picture</label>
                        <input type="file" name="profile_photo" accept="image/*" class="input w-full text-xs">
                        <p class="text-[11px] text-text-muted mt-1">Use a clear profile photo so employers can recognize your application.</p>
                    </div>
                </div>
            </div>

            {{-- 1. AI Resume Extraction & Document Upload Box --}}
            <div id="resume-section" class="bg-white rounded-2xl border border-border p-6 sm:p-8 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <div>
                        <h3 class="text-base font-bold text-navy flex items-center gap-2">
                            <span>Resume Upload & Auto-Extractor</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">AI Enabled</span>
                        </h3>
                        <p class="text-xs text-text-muted mt-0.5">Select a PDF or Word CV to automatically extract your skills, experience, and profile details.</p>
                    </div>
                </div>

                {{-- Clean Upload Box without any overlay --}}
                <div class="border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center bg-slate-50/50 space-y-4">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-blue-50 text-accent flex items-center justify-center shadow-xs">
                        <svg x-show="!isParsing" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        <svg x-show="isParsing" x-cloak class="w-7 h-7 animate-spin text-accent" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>

                    <div>
                        <p class="text-sm font-bold text-navy" x-show="!fileName">
                            Upload PDF or Word Document (Max 10MB)
                        </p>
                        <p class="text-sm font-bold text-emerald-600 flex items-center justify-center gap-1.5" x-show="fileName" x-cloak>
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span x-text="'Selected: ' + fileName"></span>
                        </p>
                        <p class="text-xs text-emerald-700 font-semibold mt-1" x-show="parseMessage" x-text="parseMessage"></p>
                        <p class="text-xs text-text-muted mt-1" x-show="!parseMessage">AI engine will extract skills and auto-populate all profile fields below</p>
                    </div>

                    <div class="pt-2">
                        <button type="button" 
                                @click="$refs.resumeFileInput.click()" 
                                class="btn btn-primary py-2.5 px-6 text-xs font-bold shadow-md cursor-pointer inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            <span>Browse & Extract Resume</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 2. Skills & Core Competencies --}}
            <div id="skills-section" class="bg-white rounded-2xl border border-border p-6 sm:p-8 shadow-xs space-y-4" @click.away="showDropdown = false">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <div>
                        <h3 class="text-base font-bold text-navy">Skills & Core Competencies</h3>
                        <p class="text-xs text-text-muted mt-0.5">Search our verified catalog (Python, Flutter, React, SAP, Forklift, etc.) or type any custom skill.</p>
                    </div>
                    <span class="text-xs font-bold text-slate-600" x-text="skillsList.length + ' Skills added'"></span>
                </div>

                {{-- Selected Skill Badges (Visible list of all added skills) --}}
                <div class="flex flex-wrap items-center gap-2 min-h-[44px] p-3 bg-slate-50/80 rounded-xl border border-slate-200">
                    <template x-for="(skill, index) in skillsList" :key="index">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-white text-navy border border-slate-300 shadow-2xs">
                            <span x-text="skill"></span>
                            <button type="button" 
                                    @click="removeSkill(index)" 
                                    class="text-slate-400 hover:text-rose-600 cursor-pointer ml-1 text-sm font-bold leading-none p-0.5 rounded hover:bg-rose-50" 
                                    title="Remove skill">&times;</button>
                        </span>
                    </template>
                    <span x-show="skillsList.length === 0" class="text-xs text-slate-400 italic">No skills selected yet. Search below to add skills.</span>
                </div>

                {{-- Skill Search & Autocomplete Input --}}
                <div class="relative max-w-lg">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <input type="text" 
                                   x-model="searchQuery" 
                                   @focus="showDropdown = true"
                                   @input="showDropdown = true"
                                   @keydown.enter.prevent="addSkill()" 
                                   placeholder="Search or type a skill (e.g. Python, Flutter, React, SAP)..." 
                                   class="input w-full text-xs pr-8">
                            <div class="absolute right-2.5 top-2.5 text-slate-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>

                        <button type="button" 
                                @click="addSkill()" 
                                class="btn btn-outline btn-sm text-xs font-bold shrink-0 cursor-pointer">
                            + Add Skill
                        </button>
                    </div>

                    {{-- Floating Suggestions Dropdown --}}
                    <div x-show="showDropdown && searchQuery.trim().length > 0" 
                         x-cloak
                         class="absolute left-0 right-0 mt-1 bg-white rounded-xl shadow-xl border border-border z-30 max-h-56 overflow-y-auto divide-y divide-border">
                        
                        {{-- Matching Catalog Suggestions --}}
                        <template x-for="item in filteredSuggestions" :key="item">
                            <div @click="addSkill(item)" 
                                 class="p-2.5 text-xs font-semibold text-navy hover:bg-blue-50 hover:text-accent cursor-pointer flex items-center justify-between">
                                <span x-text="item"></span>
                                <span class="text-[10px] font-normal text-slate-400">Add &rarr;</span>
                            </div>
                        </template>

                        {{-- Custom Skill Option --}}
                        <div @click="addSkill()" 
                             class="p-2.5 text-xs font-bold text-accent hover:bg-slate-50 cursor-pointer flex items-center gap-1.5 bg-slate-50/50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>Add "<span x-text="searchQuery"></span>" as custom skill</span>
                        </div>
                    </div>
                </div>

                {{-- Hidden input to persist skills in form submission --}}
                <input type="hidden" name="skills" :value="JSON.stringify(skillsList)">
            </div>

            {{-- 3. Personal & Contact Details --}}
            <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 shadow-xs space-y-6">
                <h3 class="text-base font-bold text-navy border-b border-border pb-3">Personal & Contact Details</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="input w-full" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Professional Title *</label>
                        <input type="text" name="current_title" x-model="currentTitle" placeholder="e.g. Warehouse Supervisor / Full Stack Developer" class="input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Email Address *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input w-full" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Phone Number *</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="input w-full" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">WhatsApp Number</label>
                        <input type="text" name="whatsapp_number" value="{{ data_get($profile?->resume_data, 'personal.whatsapp_number') }}" class="input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Current Location</label>
                        <input type="text" name="current_location" x-model="currentLocation" placeholder="e.g. Singapore, Woodlands" class="input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $profile?->date_of_birth?->format('Y-m-d')) }}" class="input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Gender</label>
                        <select name="gender" class="input w-full">
                            <option value="">Select gender</option>
                            <option value="Male" @selected(old('gender', $profile?->gender) === 'Male')>Male</option>
                            <option value="Female" @selected(old('gender', $profile?->gender) === 'Female')>Female</option>
                            <option value="Other" @selected(old('gender', $profile?->gender) === 'Other')>Other</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- 4. Professional Summary --}}
            <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 shadow-xs space-y-6">
                <h3 class="text-base font-bold text-navy border-b border-border pb-3">Professional Summary & Bio</h3>

                <div>
                    <label class="block text-xs font-bold text-navy mb-1.5">Executive Summary</label>
                    <textarea name="professional_summary" x-model="professionalSummary" rows="4" placeholder="Briefly describe your career background and key operational strengths..." class="input w-full"></textarea>
                </div>
            </div>

            {{-- 5. Experience & Compensation Preferences --}}
            <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 shadow-xs space-y-6">
                <h3 class="text-base font-bold text-navy border-b border-border pb-3">Experience & Salary Expectations</h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Total Experience (Years)</label>
                        <input type="number" name="years_experience" x-model="yearsExperience" min="0" max="40" class="input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Expected Salary</label>
                        <input type="number" step="100" name="expected_salary" x-model="expectedSalary" placeholder="e.g. 3500" class="input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Currency</label>
                        <input type="text" name="preferred_currency" value="{{ old('preferred_currency', $profile?->preferred_currency ?: 'SGD') }}" class="input w-full" maxlength="3">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Notice Period</label>
                        <input type="text" name="notice_period" x-model="noticePeriod" placeholder="e.g. Immediate / 1 Month" class="input w-full">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-navy mb-1.5">Preferred Work Locations</label>
                        <input type="text" name="preferred_location" value="{{ old('preferred_location', $profile?->preferred_location) }}" placeholder="e.g. Singapore, Tuas, Jurong" class="input w-full">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('seeker.dashboard') }}" class="btn btn-outline text-xs font-bold">Cancel</a>
                <button type="submit" class="btn btn-primary py-3 px-8 text-xs font-bold shadow-md cursor-pointer flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Save Profile & Resume</span>
                </button>
            </div>
        </form>
    </div>
</x-seeker-sidebar>
