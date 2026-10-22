<x-layouts.school heading="AI assistant" :subheading="$canAct ? 'Ask about your school, or ask it to add classes, sections, subjects and sessions for you.' : 'Ask questions about your school and get clear summaries. (Analysis only for principals.)'">
    <div class="grid gap-6 xl:grid-cols-[1fr_18rem]">
        <section class="card flex min-h-[32rem] flex-col" data-ai-chat data-ask-url="{{ route('school.ai.ask') }}">
            <div class="flex-1 space-y-4 overflow-y-auto p-4 sm:p-6" data-ai-messages aria-live="polite">
                <div class="flex gap-3">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand text-white"><x-icon name="sparkles" size="4" /></span>
                    <div class="ai-prose max-w-[42rem] rounded-lg rounded-tl-none border border-line bg-white px-4 py-3 text-sm">
                        <p>Hi {{ auth('school')->user()->name }}! I know your school's setup: sessions, classes, sections, subjects and staff accounts. What would you like to know?</p>
                    </div>
                </div>
            </div>

            @if (! $available || $limit <= 0)
                <div class="alert alert-warning m-4"><x-icon name="alert" class="mt-px" /><p>{{ $limit <= 0 ? 'AI is not included in your plan.' : 'The AI service is not set up yet.' }} Contact AivraSol support.</p></div>
            @else
                <div class="border-t border-line p-3 sm:p-4">
                    <div class="mb-3 flex gap-2 overflow-x-auto pb-1" data-ai-suggestions>
                        @foreach (array_merge(['Summarise our school setup', 'Which classes have no subjects yet?', 'How many sections does each class have?'], $canAct ? ['Add Class 11 with sections A and B'] : []) as $suggestion)
                            <button type="button" class="shrink-0 rounded-full border border-line-strong bg-white px-3 py-1.5 text-xs font-medium text-body transition hover:border-brand hover:text-brand-text" data-ai-suggestion>{{ $suggestion }}</button>
                        @endforeach
                    </div>
                    <form class="flex items-end gap-2" data-ai-form>
                        <label for="ai-question" class="sr-only">Your question</label>
                        <textarea id="ai-question" rows="1" maxlength="1000" required class="input max-h-40 min-h-11 resize-none" placeholder="Ask about your school…" data-ai-input></textarea>
                        <button class="btn btn-primary shrink-0" data-ai-send><x-icon name="send" size="4" /><span class="sr-only sm:not-sr-only">Ask</span></button>
                    </form>
                    <p class="mt-2 text-[11px] text-muted">AI can make mistakes. Check important numbers. Changes only happen when you press Confirm.</p>
                </div>
            @endif
        </section>

        <aside class="space-y-4">
            <section class="card p-5">
                <p class="text-sm text-muted">Questions this month</p>
                <p class="font-display tabular mt-1 text-2xl text-ink"><span data-ai-used>{{ $used }}</span> <span class="text-base font-normal text-muted">/ {{ $limit }}</span></p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-chip"><div class="h-full rounded-full bg-brand transition-all" data-ai-meter style="width: {{ $limit ? min(100, round($used / $limit * 100)) : 0 }}%"></div></div>
                <p class="mt-2 text-xs text-muted">Resets on the 1st of each month.</p>
            </section>
            <section class="card p-5 text-sm">
                <p class="font-semibold text-ink">{{ $canAct ? 'What it can do' : 'Analysis only' }}</p>
                <ul class="mt-2 space-y-1.5 text-body">
                    <li class="flex gap-2"><x-icon name="check" size="4" class="mt-0.5 text-success" /> Explain and summarise your setup</li>
                    <li class="flex gap-2"><x-icon name="check" size="4" class="mt-0.5 text-success" /> Find gaps (e.g. classes without subjects)</li>
                    @if ($canAct)
                        <li class="flex gap-2"><x-icon name="check" size="4" class="mt-0.5 text-success" /> Add classes, sections, subjects and sessions after you confirm</li>
                    @else
                        <li class="flex gap-2"><x-icon name="x" size="4" class="mt-0.5 text-muted" /> Cannot change data (school admin only)</li>
                    @endif
                </ul>
                <p class="mt-3 text-xs text-muted">More modules (students, attendance, fees) are added to the assistant as they are released.</p>
            </section>
        </aside>
    </div>
</x-layouts.school>
