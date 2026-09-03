<x-layouts.app title="{{ $page[0] }} | Luckyboss Portal">
    <section class="bg-gradient-to-b from-[#031533] to-[#041d45] text-white py-14 relative overflow-hidden">
        <div class="container-app max-w-4xl mx-auto text-center">
            <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-white/10 text-secondary-300 border border-white/15 mb-4">
                ✦ OFFICIAL PORTAL POLICY & INFO ✦
            </span>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-serif !text-white font-normal tracking-tight">
                {{ $page[0] }}
            </h1>
            <p class="text-blue-100/80 text-sm sm:text-base mt-2 max-w-xl mx-auto">
                {{ $page[1] }}
            </p>
        </div>
    </section>

    <main class="py-16 bg-[#f8fafc] flex-1">
        <div class="container-app max-w-4xl mx-auto">
            <article class="bg-white rounded-3xl p-8 sm:p-12 border border-border shadow-sm space-y-6 leading-relaxed text-text-secondary text-base">
                <div class="whitespace-pre-line">
                    {{ $page[2] }}
                </div>

                <div class="pt-8 border-t border-border mt-8">
                    <h3 class="text-lg font-heading font-bold text-navy mb-2">Need Immediate Support?</h3>
                    <p class="text-sm text-text-muted mb-4">
                        Contact our dedicated 24/7 client relations and candidate support desk.
                    </p>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('contact.public') }}" class="btn btn-primary btn-sm">Contact Support Desk</a>
                        <a href="{{ route('home') }}" class="btn btn-outline btn-sm">Back to Home</a>
                    </div>
                </div>
            </article>
        </div>
    </main>
</x-layouts.app>