<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DemoRequestController extends Controller
{
    public function index(): View
    {
        return view('admin.demo-requests.index', [
            'requests' => DemoRequest::query()->latest()->paginate(20),
        ]);
    }

    public function update(Request $request, DemoRequest $demoRequest): RedirectResponse
    {
        $demoRequest->update($request->validate([
            'status' => ['required', Rule::in(DemoRequest::STATUSES)],
        ]));

        return back()->with('success', 'Lead status updated.');
    }
}
