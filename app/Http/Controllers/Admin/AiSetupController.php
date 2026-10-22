<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiErrorExplainer;
use App\Services\Ai\AiException;
use App\Services\Ai\AiProviders;
use App\Support\Activity;
use App\Support\EnvFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

/**
 * Super Admin → AI setup: paste API keys, test each provider, choose the order they are tried.
 */
class AiSetupController extends Controller
{
    public function index(): View
    {
        $providers = AiProviders::all();

        return view('admin.ai.index', [
            'providers' => $providers,
            'editMode' => (bool) AiProviders::settings()['edit_mode'],
            'activeCount' => $providers->filter->usable()->count(),
            'errorTypes' => AiErrorExplainer::TYPES,
        ]);
    }

    public function update(Request $request, string $provider): RedirectResponse
    {
        $p = AiProviders::find($provider) ?? abort(404);
        $editMode = (bool) AiProviders::settings()['edit_mode'];

        $data = $request->validate([
            'api_key' => ['nullable', 'string', 'max:500'],
            'model' => ['nullable', 'string', 'max:150'],
            'base_url' => ['nullable', 'url:http,https', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:99'],
            'enabled' => ['boolean'],
            'remove_key' => ['boolean'],
        ]);

        // Endpoint and model are locked unless "Edit endpoints & models" is on (custom providers can always be edited).
        $values = ['enabled' => $request->boolean('enabled'), 'priority' => $data['priority'] ?? $p->priority];
        if ($editMode || $p->custom) {
            $values['model'] = filled($data['model'] ?? null) ? trim($data['model']) : $p->model;
            $values['base_url'] = filled($data['base_url'] ?? null) ? rtrim(trim($data['base_url']), '/') : $p->baseUrl;
        }

        try {
            if ($request->boolean('remove_key')) {
                EnvFile::set([$p->keyEnv => null]);
            } elseif (filled($data['api_key'] ?? null)) {
                EnvFile::set([$p->keyEnv => trim($data['api_key'])]);
            }
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['api_key' => $e->getMessage()]);
        }

        AiProviders::update($provider, $values);
        Activity::log('settings.ai', "Updated AI provider {$p->name}".($values['enabled'] ? ' (on)' : ' (off)'));

        return redirect()->to(route('admin.ai.index').'#ai-'.$provider)->with('success', "{$p->name} saved.");
    }

    /** Sends a tiny request ("Reply with OK") so testing uses almost no tokens. */
    public function test(string $provider, AiClient $client): JsonResponse
    {
        $p = AiProviders::find($provider) ?? abort(404);

        try {
            $result = $client->chat($p, 'Reply with the single word OK.', [['role' => 'user', 'content' => 'Test']], $p->style === 'anthropic' ? 64 : 16);
            $outcome = ['ok' => true, 'ms' => $result['ms'], 'reply' => mb_substr($result['text'], 0, 60), 'model' => $p->model, 'tokens' => $result['input_tokens'] + $result['output_tokens']];
        } catch (AiException $e) {
            $outcome = ['ok' => false] + $e->toArray();
        }

        AiProviders::update($provider, ['last_test' => $outcome + ['at' => now()->toIso8601String()]]);

        return response()->json($outcome);
    }

    public function models(string $provider, AiClient $client): JsonResponse
    {
        $p = AiProviders::find($provider) ?? abort(404);

        try {
            return response()->json(['ok' => true, 'models' => $client->models($p)]);
        } catch (AiException $e) {
            return response()->json(['ok' => false] + $e->toArray());
        }
    }

    public function editMode(Request $request): RedirectResponse
    {
        $settings = AiProviders::settings();
        $settings['edit_mode'] = $request->boolean('edit_mode');
        AiProviders::save($settings);

        return back()->with('success', $settings['edit_mode'] ? 'Endpoints and models can now be edited.' : 'Endpoints and models are locked.');
    }

    public function storeCustom(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'style' => ['required', Rule::in(['openai', 'anthropic'])],
            'base_url' => ['required', 'url:http,https', 'max:255'],
            'model' => ['required', 'string', 'max:150'],
            'api_key' => ['nullable', 'string', 'max:500'],
        ]);

        $key = 'custom-'.Str::slug($data['name']);
        abort_if(isset(config('ai.providers')[$key]), 422);

        $settings = AiProviders::settings();
        $settings['custom'][$key] = ['name' => $data['name'], 'style' => $data['style'], 'base_url' => rtrim($data['base_url'], '/'), 'model' => $data['model'], 'key_optional' => true];
        $settings['providers'][$key] = ['enabled' => true, 'priority' => 50];
        AiProviders::save($settings);

        if (filled($data['api_key'] ?? null)) {
            EnvFile::set([AiProviders::keyEnv($key) => trim($data['api_key'])]);
        }

        Activity::log('settings.ai', "Added custom AI provider {$data['name']}");

        return redirect()->to(route('admin.ai.index').'#ai-'.$key)->with('success', "{$data['name']} added. Press Test to check it.");
    }

    public function destroyCustom(string $provider): RedirectResponse
    {
        $settings = AiProviders::settings();
        abort_unless(isset($settings['custom'][$provider]), 404);

        $name = $settings['custom'][$provider]['name'];
        unset($settings['custom'][$provider], $settings['providers'][$provider]);
        AiProviders::save($settings);
        EnvFile::set([AiProviders::keyEnv($provider) => null]);

        Activity::log('settings.ai', "Removed custom AI provider {$name}");

        return redirect()->route('admin.ai.index')->with('success', "{$name} removed.");
    }
}
