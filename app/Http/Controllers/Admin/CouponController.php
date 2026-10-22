<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Plan;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Discount codes schools can enter when registering or renewing. */
class CouponController extends Controller
{
    public function index(): View
    {
        return view('admin.coupons.index', [
            'coupons' => Coupon::with('plan')->latest('id')->paginate(30),
            'plans' => Plan::query()->ordered()->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $coupon = Coupon::create($this->validated($request));
        Activity::log('coupon.created', "Created discount code {$coupon->code} ({$coupon->label()})", $coupon);

        return back()->with('success', "Discount code {$coupon->code} created.");
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->validated($request, $coupon));
        Activity::log('coupon.updated', "Updated discount code {$coupon->code}", $coupon);

        return back()->with('success', "Discount code {$coupon->code} saved.");
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();
        Activity::log('coupon.deleted', "Deleted discount code {$coupon->code}");

        return back()->with('success', "Discount code {$coupon->code} deleted.");
    }

    protected function validated(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($coupon)],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Coupon::TYPES))],
            'value' => ['required', 'numeric', 'gt:0', $request->input('type') === 'percent' ? 'max:100' : 'max:100000000'],
            'plan_id' => ['nullable', Rule::exists('plans', 'id')],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ], ['code.regex' => 'Use letters, numbers, "-" or "_" only (e.g. WELCOME20).']);

        return $data + ['is_active' => $request->boolean('is_active')];
    }
}
