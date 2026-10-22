<x-layouts.admin heading="Settings" subheading="Currencies for billing and for the prices shown on your website.">
    @include('admin.settings.tabs')
    <form method="POST" action="{{ route('admin.settings.currencies.update') }}" class="max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        <section class="card">
            <div class="card-header"><h2 class="card-title">Billing currency</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.select name="currency" label="Plans and subscriptions are priced in" :value="$base" required
                    :options="collect($currencies)->mapWithKeys(fn ($c, $code) => [$code => $code.' — '.$c[0]])->all()"
                    hint="Changing this does not convert existing plan prices. Update them on the Plans page." />
                <div class="alert alert-info self-start">
                    <x-icon name="info" size="5" class="shrink-0" />
                    <p>Each school also has its own currency for fees. Set it when adding the school, or the school sets it in its profile.</p>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Website currency switcher</h2>
                    <p class="mt-0.5 text-sm text-muted">Visitors can view your prices in these currencies. Enter today's rate for each one.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th class="w-16">Show</th><th>Currency</th><th>Rate</th></tr>
                    </thead>
                    <tbody>
                        @foreach (collect($currencies)->sortBy(fn ($c, $code) => isset($rates[$code]) ? 0 : 1) as $code => [$name])
                            @continue($code === $base)
                            <tr>
                                <td><input type="checkbox" name="enabled[]" value="{{ $code }}" class="checkbox" aria-label="Show {{ $code }}" @checked(isset($rates[$code]))></td>
                                <td><span class="font-semibold text-ink">{{ $code }}</span> <span class="text-muted">{{ $name }}</span></td>
                                <td>
                                    <div class="flex items-center gap-2 whitespace-nowrap">
                                        <span class="text-muted">1 {{ $code }} =</span>
                                        <input type="number" name="website[{{ $code }}]" value="{{ $rates[$code] ?? '' }}" step="any" min="0" class="input w-32 py-1.5" aria-label="Rate for {{ $code }}">
                                        <span class="text-muted">{{ $base }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex justify-end">
            <button class="btn btn-primary">Save settings</button>
        </div>
    </form>
</x-layouts.admin>
