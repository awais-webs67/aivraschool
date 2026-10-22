{{-- Discount code fields. $c = coupon being edited (or null), $p = unique id prefix for this form. --}}
<x-form.input name="code" :id="$p.'-code'" label="Code" required maxlength="40" :value="$c?->code" placeholder="WELCOME20" hint="Letters, numbers, - and _" />
<x-form.select name="type" :id="$p.'-type'" label="Discount" required :options="\App\Models\Coupon::TYPES" :value="$c?->type ?? 'percent'" />
<x-form.input name="value" :id="$p.'-value'" type="number" step="0.01" min="0.01" label="Value" required :value="$c ? (float) $c->value : null" hint="Percent, or amount in {{ \App\Models\Setting::currency() }}." />
<x-form.select name="plan_id" :id="$p.'-plan'" label="Plan" :options="$plans" placeholder="Every plan" :value="$c?->plan_id" />
<x-form.input name="max_uses" :id="$p.'-max'" type="number" min="1" label="Max uses" :value="$c?->max_uses" hint="Blank = unlimited." />
<x-form.input name="expires_at" :id="$p.'-exp'" type="date" label="Expires on" :value="$c?->expires_at?->toDateString()" hint="Blank = never." />
<x-form.input name="description" :id="$p.'-desc'" label="Note (only you see it)" :value="$c?->description" maxlength="255" class="sm:col-span-2 lg:col-span-3" />
<label class="flex items-center gap-2 text-sm text-body sm:col-span-2 lg:col-span-3">
    <input type="checkbox" name="is_active" value="1" class="checkbox" @checked($c?->is_active ?? true)> Active (schools can use it)
</label>
