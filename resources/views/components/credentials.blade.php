{{-- One-time display of freshly generated school login credentials --}}
@if ($credentials = session('credentials'))
    <div class="card border-brand/40 bg-brand-tint/40 p-5" role="status">
        <div class="flex items-start gap-3">
            <span class="grid size-9 shrink-0 place-items-center rounded-md bg-brand text-white"><x-icon name="key" /></span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-ink">School login credentials</p>
                <p class="mt-0.5 text-sm text-body">Share these with the school. The password is shown only once.</p>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                    @foreach (['Login URL' => $credentials['url'], 'Email' => $credentials['email'], 'Password' => $credentials['password']] as $label => $value)
                        <div class="min-w-0 rounded border border-line bg-white px-3 py-2">
                            <dt class="text-xs text-muted">{{ $label }}</dt>
                            <dd class="mt-0.5 flex items-center justify-between gap-2">
                                <span class="truncate font-mono text-[13px] text-ink">{{ $value }}</span>
                                <button type="button" class="btn-ghost shrink-0 rounded p-1" data-copy="{{ $value }}" aria-label="Copy {{ $label }}"><x-icon name="copy" size="4" /></button>
                            </dd>
                        </div>
                    @endforeach
                </dl>
                <button type="button" class="btn btn-secondary btn-sm mt-4"
                    data-copy="Skooline login&#10;URL: {{ $credentials['url'] }}&#10;Email: {{ $credentials['email'] }}&#10;Password: {{ $credentials['password'] }}">
                    <x-icon name="copy" size="4" /> Copy all
                </button>
            </div>
        </div>
    </div>
@endif
