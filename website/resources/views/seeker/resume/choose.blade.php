<x-seeker-sidebar title="Your Resume">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 space-y-8">

        <div class="text-center space-y-2">
            <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-navy">Let&rsquo;s find jobs that fit you</h1>
            <p class="text-sm text-text-secondary max-w-xl mx-auto leading-relaxed">
                Start with your resume and we&rsquo;ll read it for you, or fill in your details by hand.
                Either way you&rsquo;ll see your matching jobs at the end.
            </p>
        </div>

        @error('resume')
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800">
                {{ $message }}
            </div>
        @enderror

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-stretch">

            {{-- Door 1, primary. TickBig puts their builder first; we put upload
                 first, because our candidate usually already has a CV. --}}
            <form method="POST"
                  action="{{ route('seeker.resume.upload') }}"
                  enctype="multipart/form-data"
                  class="bg-white rounded-2xl border-2 border-accent p-6 sm:p-8 shadow-sm flex flex-col"
                  x-data="{ fileName: '' }">
                @csrf

                <div class="w-12 h-12 rounded-2xl bg-accent/10 text-accent flex items-center justify-center mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.9A5 5 0 1115.9 6H16a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                </div>

                <h2 class="text-lg font-heading font-bold text-navy">Upload your resume</h2>
                <p class="text-xs text-text-secondary mt-1.5 leading-relaxed flex-1">
                    @if ($autofillAvailable)
                        PDF or Word. We&rsquo;ll read your job title, experience and skills so you don&rsquo;t
                        have to type them &mdash; then you check every field before anything is saved.
                    @else
                        PDF or Word. Your resume is saved and sent with your applications. Automatic
                        reading is switched off right now, so you&rsquo;ll enter your details on the next
                        screen.
                    @endif
                </p>

                <input type="file"
                       name="resume"
                       id="resume-file"
                       accept=".pdf,.doc,.docx,.txt"
                       class="sr-only"
                       x-ref="file"
                       @change="fileName = $event.target.files.length ? $event.target.files[0].name : ''; if (fileName) $el.form.submit()">

                <button type="button"
                        @click="$refs.file.click()"
                        class="btn btn-primary w-full mt-6 cursor-pointer">
                    Choose a file
                </button>

                <p class="text-xs text-emerald-700 font-semibold mt-3 text-center" x-show="fileName" x-cloak>
                    <span x-text="fileName"></span> &mdash; reading it now&hellip;
                </p>
                <p class="text-xs text-text-muted mt-3 text-center" x-show="! fileName">Up to 4 MB</p>

                {{-- Works with no JavaScript at all: the file input above is
                     visually hidden, not disabled, and this submits it. --}}
                <noscript>
                    <div class="mt-4 space-y-2">
                        <label for="resume-file" class="block text-xs font-bold text-navy">Choose your resume file</label>
                        <button type="submit" class="btn btn-primary w-full">Upload</button>
                    </div>
                </noscript>
            </form>

            {{-- Door 2, secondary. --}}
            <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 shadow-xs flex flex-col">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>

                <h2 class="text-lg font-heading font-bold text-navy">Fill it in myself</h2>
                <p class="text-xs text-text-secondary mt-1.5 leading-relaxed flex-1">
                    No resume to hand? Answer a few questions about your trade, experience and where
                    you can work. It takes about two minutes, and you can add your resume later.
                </p>

                <a href="{{ route('seeker.resume.review') }}" class="btn btn-outline w-full mt-6">
                    Enter my details
                </a>
                <p class="text-xs text-text-muted mt-3 text-center">About 2 minutes</p>
            </div>
        </div>

        @if ($profile?->resume_file_name)
            <div class="bg-white rounded-2xl border border-border p-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 grid place-items-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-navy truncate">{{ $profile->resume_file_name }}</p>
                        <p class="text-xs text-text-muted">Already on your profile. Uploading again replaces it.</p>
                    </div>
                </div>
                <a href="{{ route('seeker.resume.matches') }}" class="text-xs font-bold text-accent hover:underline">See my matches &rarr;</a>
            </div>
        @endif

        <p class="text-center">
            <a href="{{ route('seeker.dashboard') }}" class="text-xs font-semibold text-text-muted hover:text-navy">Skip for now</a>
        </p>
    </div>
</x-seeker-sidebar>
