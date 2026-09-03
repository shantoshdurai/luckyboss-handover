<div x-data="aiChat" class="relative z-50">
    {{-- Floating Action Button (Bottom Right) --}}
    <button 
        @click="toggle()" 
        type="button"
        class="fixed bottom-6 right-6 z-50 bg-gradient-to-r from-navy via-primary-900 to-accent text-white p-3.5 sm:px-5 sm:py-3.5 rounded-full shadow-2xl hover:shadow-accent/40 hover:scale-105 active:scale-95 transition-all duration-300 flex items-center gap-2.5 border border-white/20 group cursor-pointer"
        aria-label="Toggle Lucky AI Chat"
    >
        <div class="relative flex items-center justify-center">
            <template x-if="!open">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-secondary-400 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-secondary-400 rounded-full animate-ping"></span>
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-secondary-400 rounded-full"></span>
                </div>
            </template>
            <template x-if="open">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </template>
        </div>
        <span class="font-bold text-xs sm:text-sm tracking-wide text-white" x-text="open ? 'Close Chat' : 'Ask Lucky AI'"></span>
    </button>

    {{-- Floating eCommerce-Style Popup Box (Bottom Right - NO BACKDROP / NO BLUR) --}}
    <div 
        x-show="open" 
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 scale-95"
        {{-- Positioned with real CSS, not utilities.

             This carried `bottom-22`, `w-[calc(100vw-2rem)]` and
             `sm:w-[380px]` — none of which exist in the prebuilt Tailwind
             bundle this project ships, because arbitrary values and that
             spacing step are generated at build time and there is no build
             step. With no width rule surviving, the panel stretched the full
             width of the window and sat across the bottom of the page like a
             banner instead of beside it like every other assistant. --}}
        class="bg-white flex flex-col overflow-hidden"
        style="position:fixed; right:24px; bottom:92px; width:min(380px, calc(100vw - 32px)); height:min(540px, 72vh); z-index:50; border:1px solid #E4EAF2; border-radius:20px; box-shadow:0 24px 60px -20px rgba(3,31,73,.45);"
        style="display: none;"
    >
        {{-- Popup Header --}}
        <div class="px-5 py-4 bg-gradient-to-r from-navy via-primary-900 to-[#041a3d] text-white flex items-center justify-between shadow-xs shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center border border-white/20 shadow-inner shrink-0">
                    <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-heading font-bold text-sm leading-tight text-white">Lucky AI Copilot</h3>
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-extrabold bg-emerald-500 text-white uppercase tracking-wider">Online</span>
                    </div>
                    <p class="text-[11px] text-blue-200/80">Recruitment & Job Assistant</p>
                </div>
            </div>

            <button @click="open = false" type="button" class="p-1.5 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition-colors cursor-pointer" aria-label="Minimize Chat">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        {{-- Messages Scroll Area --}}
        <div id="ai-chat-messages" class="flex-1 p-4 overflow-y-auto space-y-3.5 bg-[#f8fafc]">
            <template x-for="(msg, index) in messages" :key="index">
                <div>
                    {{-- AI Message --}}
                    <template x-if="msg.sender === 'ai'">
                        <div class="flex items-start gap-2.5 max-w-[92%]">
                            <div class="w-7 h-7 rounded-lg bg-navy text-secondary-400 flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <div class="space-y-2 flex-1">
                                <div class="bg-white p-3 rounded-2xl rounded-tl-xs border border-border text-xs text-text-primary shadow-2xs leading-relaxed" x-text="msg.text"></div>
                                
                                {{-- Action Buttons if any --}}
                                <template x-if="msg.actions && msg.actions.length > 0">
                                    <div class="flex flex-wrap gap-1.5 pt-0.5">
                                        <template x-for="act in msg.actions" :key="act.label">
                                            <a :href="act.url" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-accent text-white text-[11px] font-semibold hover:bg-accent-dark shadow-2xs transition-colors">
                                                <span x-text="act.label"></span>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                            </a>
                                        </template>
                                    </div>
                                </template>

                                {{-- Suggestion Chips on welcome message --}}
                                <template x-if="msg.suggestions && msg.suggestions.length > 0">
                                    <div class="flex flex-col gap-1.5 pt-1">
                                        <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Suggested Questions:</span>
                                        <template x-for="sug in msg.suggestions" :key="sug">
                                            <button @click="sendSuggestion(sug)" class="text-left px-3 py-1.5 rounded-xl bg-white hover:bg-blue-50 border border-border hover:border-accent text-xs text-text-secondary hover:text-accent transition-all shadow-2xs cursor-pointer flex items-center gap-1.5">
                                                <span class="text-accent">✦</span>
                                                <span x-text="sug" class="truncate"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- User Message --}}
                    <template x-if="msg.sender === 'user'">
                        <div class="flex items-end justify-end gap-2 ml-auto max-w-[85%]">
                            <div class="bg-navy text-white p-3 rounded-2xl rounded-tr-xs text-xs shadow-xs leading-relaxed" x-text="msg.text"></div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Typing Indicator --}}
            <div x-show="loading" class="flex items-center gap-2 text-text-muted text-[11px] p-2 bg-white rounded-xl w-fit border border-border shadow-2xs" style="display: none;">
                <div class="flex gap-1">
                    <span class="w-1.5 h-1.5 bg-accent rounded-full animate-bounce"></span>
                    <span class="w-1.5 h-1.5 bg-accent rounded-full animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 bg-accent rounded-full animate-bounce [animation-delay:0.4s]"></span>
                </div>
                <span>Lucky AI is typing...</span>
            </div>
        </div>

        {{-- Popup Bottom Input Bar --}}
        <div class="p-3 bg-white border-t border-border shrink-0">
            <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                <input 
                    type="text" 
                    x-model="input" 
                    placeholder="Ask about jobs, salaries, hiring..." 
                    class="flex-1 bg-[#f8fafc] border border-border text-text-primary text-xs rounded-xl px-3.5 py-2.5 focus:ring-1 focus:ring-accent focus:bg-white transition-all outline-none"
                    :disabled="loading"
                >
                <button 
                    type="submit" 
                    :disabled="!input.trim() || loading"
                    class="p-2.5 bg-accent hover:bg-accent-dark disabled:opacity-40 text-white rounded-xl shadow-xs transition-colors flex items-center justify-center shrink-0 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </form>
            <div class="text-[9px] text-center text-text-muted mt-1.5">
                Powered by Luckyboss Intelligence
            </div>
        </div>
    </div>
</div>
