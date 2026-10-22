<x-layouts.admin heading="AI setup" subheading="Paste an API key, press Test, switch it on. Providers are tried in order, so a backup takes over when one runs out of free quota.">
    <x-slot:actions>
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('ai-help').showModal()" aria-label="What do test errors mean?"><x-icon name="info" size="4" /> Error help</button>
        <button type="button" class="btn btn-secondary" data-ai-test-all><x-icon name="refresh" size="4" /> Test all switched on</button>
        <button type="button" class="btn btn-primary" data-toggle="#custom-ai"><x-icon name="plus" size="4" /> Custom AI</button>
    </x-slot:actions>

    <div class="mb-6 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
        <div class="alert {{ $activeCount ? 'alert-success' : 'alert-warning' }}">
            <x-icon :name="$activeCount ? 'check-circle' : 'alert'" class="mt-px" />
            <p>{{ $activeCount ? $activeCount.' '.\Illuminate\Support\Str::plural('provider', $activeCount).' ready. Schools with AI in their plan can use the assistant.' : 'No provider is switched on yet. Groq, Gemini and OpenRouter have generous free tiers — start with one of them.' }}</p>
        </div>
        <form method="POST" action="{{ route('admin.ai.edit-mode') }}" class="card flex items-center gap-3 px-4 py-3">
            @csrf
            <input type="hidden" name="edit_mode" value="{{ $editMode ? 0 : 1 }}">
            <x-icon name="pencil" size="4" class="text-muted" />
            <span class="text-sm text-ink">Edit endpoints & models</span>
            <button class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition {{ $editMode ? 'bg-brand' : 'bg-line-strong' }}" role="switch" aria-checked="{{ $editMode ? 'true' : 'false' }}" aria-label="Edit endpoints and models">
                <span class="inline-block size-5 rounded-full bg-white shadow transition {{ $editMode ? 'translate-x-5.5' : 'translate-x-0.5' }}"></span>
            </button>
        </form>
    </div>

    {{-- Add a custom provider (any OpenAI-compatible or Anthropic-style API) --}}
    <form id="custom-ai" method="POST" action="{{ route('admin.ai.custom.store') }}" class="card mb-6" @unless ($errors->hasAny(['name', 'style', 'base_url', 'model'])) hidden @endunless>
        @csrf
        <div class="card-header"><h2 class="card-title">Add a custom AI</h2></div>
        <div class="card-body grid gap-5 lg:grid-cols-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="name" id="custom-name" label="Name" required placeholder="e.g. My Company AI" />
                <x-form.select name="style" id="custom-style" label="API type" :options="['openai' => 'OpenAI-compatible (most providers)', 'anthropic' => 'Anthropic Messages API']" />
                <x-form.input name="base_url" id="custom-url" label="Endpoint (base URL)" required placeholder="https://api.example.com/v1" class="sm:col-span-2" />
                <x-form.input name="model" id="custom-model" label="Model" required placeholder="llama-3.1-8b-instruct" />
                <x-secret-input name="AI_CUSTOM_NEW_API_KEY" field="api_key" id="custom-key" label="API key (optional)" />
            </div>
            <div class="rounded-lg bg-ink p-4 text-xs text-white/80">
                <p class="font-semibold text-white">Sample: OpenAI-compatible</p>
                <pre class="mt-2 overflow-x-auto font-mono leading-relaxed whitespace-pre">Endpoint: https://api.groq.com/openai/v1
Model:    llama-3.1-8b-instant

Skooline calls:
POST {endpoint}/chat/completions
Authorization: Bearer YOUR_KEY
{"model": "...", "messages": [...], "max_tokens": 700}</pre>
                <p class="mt-3 font-semibold text-white">Sample: Anthropic-style</p>
                <pre class="mt-2 overflow-x-auto font-mono leading-relaxed whitespace-pre">Endpoint: https://api.anthropic.com/v1
POST {endpoint}/messages
x-api-key: YOUR_KEY
anthropic-version: 2023-06-01</pre>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-4">
            <button type="button" class="btn btn-ghost" data-toggle="#custom-ai">Cancel</button>
            <button class="btn btn-primary">Add provider</button>
        </div>
    </form>

    <div class="grid gap-5 xl:grid-cols-2">
        @foreach ($providers as $p)
            @php
                $test = $p->lastTest;
                $status = ! $p->ready() ? ['badge-neutral', $p->apiKey || $p->keyOptional ? 'Set endpoint' : 'No key'] : ($test ? ($test['ok'] ? ['badge-success', 'Working'] : ['badge-danger', $test['title'] ?? 'Error']) : ['badge-info', 'Not tested']);
                $locked = ! $editMode && ! $p->custom;
            @endphp
            <section id="ai-{{ $p->key }}" class="card scroll-mt-24 {{ $p->enabled ? 'border-brand/40' : '' }}" data-ai-card="{{ $p->key }}" @if ($p->enabled) data-ai-enabled @endif>
                <div class="card-header">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="font-display grid size-10 shrink-0 place-items-center rounded-lg {{ $p->enabled ? 'bg-brand text-white' : 'bg-chip text-steel' }}">{{ strtoupper(mb_substr($p->name, 0, 1)) }}</span>
                        <div class="min-w-0">
                            <h2 class="card-title truncate">{{ $p->name }}</h2>
                            <p class="text-xs text-muted">{{ $p->free }}</p>
                            @if ($p->signup)<a href="{{ $p->signup }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs font-medium text-brand-text hover:underline">Get a key <x-icon name="external" size="3" /></a>@endif
                        </div>
                    </div>
                    <span class="badge {{ $status[0] }}" data-ai-badge="{{ $p->key }}">{{ $status[1] }}</span>
                </div>

                <form method="POST" action="{{ route('admin.ai.update', $p->key) }}" class="card-body grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <x-secret-input :name="$p->keyEnv" field="api_key" :id="'key-'.$p->key" label="API key" :value="$p->apiKey" class="sm:col-span-2"
                        :placeholder="$p->keyOptional ? 'Not needed for this provider' : 'Paste the key'" />
                    <div class="sm:col-span-2">
                        <label for="url-{{ $p->key }}" class="label">Endpoint</label>
                        <input id="url-{{ $p->key }}" name="base_url" value="{{ $p->baseUrl }}" class="input font-mono text-xs {{ $locked ? 'bg-canvas text-muted' : '' }}" @readonly($locked) spellcheck="false">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="model-{{ $p->key }}" class="label">Model</label>
                        <div class="flex gap-2">
                            <input id="model-{{ $p->key }}" name="model" value="{{ $p->model }}" list="models-{{ $p->key }}" class="input min-w-0 font-mono text-xs {{ $locked ? 'bg-canvas text-muted' : '' }}" @readonly($locked) spellcheck="false">
                            @unless ($locked)
                                <button type="button" class="btn btn-secondary btn-sm shrink-0" data-ai-models="{{ route('admin.ai.models', $p->key) }}" data-target="models-{{ $p->key }}">Load models</button>
                            @endunless
                        </div>
                        <datalist id="models-{{ $p->key }}">@foreach ($p->models as $m)<option value="{{ $m }}">@endforeach</datalist>
                        <p class="hint" data-ai-models-status="models-{{ $p->key }}">{{ $locked ? 'Turn on "Edit endpoints & models" to change.' : 'Pick from the list or type a model name.' }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2 text-sm font-medium text-ink">
                            <input type="hidden" name="enabled" value="0">
                            <input type="checkbox" name="enabled" value="1" class="checkbox" @checked($p->enabled)> Switched on
                        </label>
                        <label class="flex items-center gap-2 text-sm text-body">Order
                            <input type="number" name="priority" min="1" max="99" value="{{ $p->priority }}" class="input w-20 py-1.5" aria-label="Order {{ $p->name }}">
                        </label>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2 self-end">
                        @if ($p->apiKey)
                            <label class="flex items-center gap-1.5 text-xs text-muted"><input type="checkbox" name="remove_key" value="1" class="checkbox"> Remove key</label>
                        @endif
                        <button class="btn btn-primary btn-sm">Save</button>
                    </div>
                </form>

                <div class="flex flex-wrap items-center gap-3 border-t border-line px-5 py-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-ai-test="{{ route('admin.ai.test', $p->key) }}" data-key="{{ $p->key }}" @disabled(! $p->ready())><x-icon name="bolt" size="4" /> Test</button>
                    <p class="min-w-0 flex-1 text-xs text-muted" data-ai-result="{{ $p->key }}">
                        @if ($test)
                            @if ($test['ok'])
                                <span class="font-semibold text-success">✓ Working</span> · {{ $test['ms'] }} ms · replied "{{ $test['reply'] }}"
                            @else
                                <span class="font-semibold text-danger">✗ {{ $test['title'] }}</span> — {{ $test['explanation'] }}
                                @if (! empty($test['detail']))<span class="mt-1 block font-mono break-words text-muted/80">{{ $test['detail'] }}</span>@endif
                            @endif
                            <span class="block text-muted/70">Tested {{ \Illuminate\Support\Carbon::parse($test['at'])->diffForHumans() }}</span>
                        @else
                            Not tested yet.
                        @endif
                    </p>
                    @if ($p->custom)
                        <form method="POST" action="{{ route('admin.ai.custom.destroy', $p->key) }}" data-confirm="Remove {{ $p->name }}?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" size="4" /> Remove</button>
                        </form>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    {{-- What test errors mean --}}
    <dialog id="ai-help" class="m-auto w-[min(40rem,calc(100vw-2rem))] rounded-xl border border-line p-0 shadow-pop backdrop:bg-ink/50">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 class="card-title">What test errors mean</h2>
            <button type="button" class="drawer-close text-ink hover:bg-chip" onclick="this.closest('dialog').close()" aria-label="Close"><x-icon name="x" /></button>
        </div>
        <dl class="max-h-[70vh] divide-y divide-line overflow-y-auto">
            @foreach ($errorTypes as $type => [$title, $text])
                <div class="px-5 py-3">
                    <dt class="text-sm font-semibold text-ink">{{ $title }}</dt>
                    <dd class="mt-0.5 text-sm text-body">{{ $text }}</dd>
                </div>
            @endforeach
        </dl>
    </dialog>
</x-layouts.admin>
