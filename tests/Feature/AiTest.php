<?php

namespace Tests\Feature;

use App\Models\AiUsage;
use App\Models\Plan;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\Ai\AiErrorExplainer;
use App\Services\Ai\AiProviders;
use App\Support\EnvFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiTest extends TestCase
{
    use RefreshDatabase;

    /** Switch providers on with fake keys. */
    private function enable(string ...$keys): void
    {
        foreach ($keys as $i => $key) {
            EnvFile::set([AiProviders::keyEnv($key) => "key-{$key}"]);
            AiProviders::update($key, ['enabled' => true, 'priority' => $i + 1]);
        }
    }

    private function openAiReply(string $content): array
    {
        return ['choices' => [['message' => ['content' => $content]]], 'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 30]];
    }

    public function test_catalog_has_at_least_twenty_providers_including_agentrouter(): void
    {
        $this->assertGreaterThanOrEqual(20, count(config('ai.providers')));
        $this->assertArrayHasKey('agentrouter', config('ai.providers'));

        $this->actingAs($this->superAdmin(), 'web');
        $this->get(route('admin.ai.index'))->assertOk()->assertSee('AgentRouter')->assertSee('Groq')->assertSee('Error help');
    }

    public function test_saving_a_key_writes_env_and_shows_it_masked(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $this->put(route('admin.ai.update', 'groq'), ['api_key' => 'gsk_secret_value_123', 'enabled' => 1, 'priority' => 2, 'model' => 'hacked-model'])->assertRedirect();

        $this->assertSame('gsk_secret_value_123', EnvFile::get('AI_GROQ_API_KEY'));
        $groq = AiProviders::find('groq');
        $this->assertTrue($groq->enabled);
        $this->assertSame('llama-3.1-8b-instant', $groq->model, 'model is locked while edit mode is off');

        $this->get(route('admin.ai.index'))->assertDontSee('gsk_secret_value_123')->assertSee(EnvFile::mask('gsk_secret_value_123'));

        // With edit mode on the model and endpoint can change.
        $this->post(route('admin.ai.edit-mode'), ['edit_mode' => 1]);
        $this->put(route('admin.ai.update', 'groq'), ['enabled' => 1, 'model' => 'llama-3.3-70b-versatile', 'base_url' => 'https://api.groq.com/openai/v1']);
        $this->assertSame('llama-3.3-70b-versatile', AiProviders::find('groq')->model);
        $this->assertSame('gsk_secret_value_123', EnvFile::get('AI_GROQ_API_KEY'), 'blank key keeps the saved one');
    }

    public function test_test_button_reports_success_and_explains_errors(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $this->enable('groq', 'anthropic');

        Http::fake([
            'api.groq.com/*' => Http::sequence()
                ->push($this->openAiReply('OK'))
                ->push(['error' => ['message' => 'Rate limit reached for model']], 429)
                ->push(['error' => ['message' => 'Invalid API Key']], 401),
            'api.anthropic.com/*' => Http::response(['content' => [['type' => 'thinking', 'thinking' => ''], ['type' => 'text', 'text' => 'OK']], 'usage' => ['input_tokens' => 10, 'output_tokens' => 2]]),
        ]);

        $this->postJson(route('admin.ai.test', 'groq'))->assertJson(['ok' => true, 'reply' => 'OK']);
        $this->postJson(route('admin.ai.test', 'groq'))->assertJson(['ok' => false, 'type' => 'quota']);
        $this->postJson(route('admin.ai.test', 'groq'))->assertJson(['ok' => false, 'type' => 'invalid_key']);
        $this->postJson(route('admin.ai.test', 'anthropic'))->assertJson(['ok' => true, 'reply' => 'OK']);

        Http::assertSent(fn ($r) => $r->url() === 'https://api.anthropic.com/v1/messages'
            && $r->hasHeader('x-api-key', 'key-anthropic') && $r->hasHeader('anthropic-version', '2023-06-01')
            && $r['model'] === 'claude-opus-5-5' && ! isset($r['temperature']));
        $this->assertSame('invalid_key', AiProviders::find('groq')->lastTest['type']);
    }

    public function test_error_explainer_maps_common_failures(): void
    {
        $this->assertSame('no_credit', AiErrorExplainer::fromResponse(402, 'Insufficient balance')->type);
        $this->assertSame('model_not_found', AiErrorExplainer::fromResponse(404, 'The model `foo` does not exist')->type);
        $this->assertSame('bad_endpoint', AiErrorExplainer::fromResponse(404, 'Not Found')->type);
        $this->assertSame('overloaded', AiErrorExplainer::fromResponse(529, 'Overloaded')->type);
        $this->assertSame('quota', AiErrorExplainer::fromResponse(400, 'RESOURCE_EXHAUSTED: quota exceeded')->type);
    }

    public function test_custom_provider_can_be_added_and_removed(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $this->post(route('admin.ai.custom.store'), ['name' => 'My AI', 'style' => 'openai', 'base_url' => 'https://ai.example.test/v1', 'model' => 'mini', 'api_key' => 'abc'])->assertRedirect();

        $custom = AiProviders::find('custom-my-ai');
        $this->assertTrue($custom->custom);
        $this->assertSame('abc', EnvFile::get('AI_CUSTOM_MY_AI_API_KEY'));

        Http::fake(['ai.example.test/*' => Http::response(['data' => [['id' => 'mini'], ['id' => 'big']]])]);
        $this->getJson(route('admin.ai.models', 'custom-my-ai'))->assertJson(['ok' => true, 'models' => ['big', 'mini']]);

        $this->delete(route('admin.ai.custom.destroy', 'custom-my-ai'))->assertRedirect();
        $this->assertNull(AiProviders::find('custom-my-ai'));
        $this->assertNull(EnvFile::get('AI_CUSTOM_MY_AI_API_KEY'));
    }

    private function aiSchool(int $limit = 5)
    {
        $plan = Plan::factory()->create(['modules' => ['students', 'ai'], 'ai_requests_monthly' => $limit]);
        $school = $this->createSchool('greenfield', $plan);
        $school->run(fn () => SchoolClass::create(['name' => 'Class 1'])->sections()->create(['name' => 'A']));

        return $school;
    }

    public function test_principal_gets_analysis_and_fails_over_to_next_provider(): void
    {
        $school = $this->aiSchool();
        $this->enable('groq', 'gemini');
        $principal = $school->run(fn () => User::create(['name' => 'Pat', 'email' => 'p@g.test', 'password' => 'x', 'role' => 'principal']));
        $this->actingAs($principal, 'school');

        Http::fake([
            'api.groq.com/*' => Http::response(['error' => ['message' => 'quota exceeded']], 429),
            'generativelanguage.googleapis.com/*' => Http::response($this->openAiReply('{"answer": "You have **1 class** (Class 1).", "action": {"type": "create_class", "params": {"name": "Class 2"}}}')),
        ]);

        $this->get($this->schoolUrl('greenfield', 'ai'))->assertOk()->assertSee('Analysis only');

        $this->postJson($this->schoolUrl('greenfield', 'ai/ask'), ['question' => 'How many classes?'])
            ->assertOk()
            ->assertJson(['action' => null, 'used' => 1, 'limit' => 5])
            ->assertJsonPath('html', "<p>You have <strong>1 class</strong> (Class 1).</p>\n");

        // The school's data went in the prompt, with principal rules.
        Http::assertSent(fn ($r) => str_contains((string) $r['messages'][0]['content'], 'Class 1: sections A') && str_contains((string) $r['messages'][0]['content'], 'PRINCIPAL'));
        $this->assertSame(150, (int) AiUsage::forMonth($school)->input_tokens + (int) AiUsage::forMonth($school)->output_tokens);
    }

    public function test_admin_confirms_a_proposed_change_once(): void
    {
        $school = $this->aiSchool();
        $this->enable('groq');
        $this->actingAs($school->users()->first(), 'school');

        Http::fake(['api.groq.com/*' => Http::response($this->openAiReply("```json\n{\"answer\": \"I can add it.\", \"action\": {\"type\": \"create_class\", \"params\": {\"name\": \"Class 11\", \"sections\": [\"A\", \"B\"]}}}\n```"))]);

        $response = $this->postJson($this->schoolUrl('greenfield', 'ai/ask'), ['question' => 'Add Class 11 with A and B'])->assertOk();
        $action = $response->json('action');
        $this->assertSame('Add class "Class 11" with sections A, B', $action['summary']);
        $this->assertNull($school->run(fn () => SchoolClass::where('name', 'Class 11')->first()), 'nothing changes before Confirm');

        $this->postJson($action['url'])->assertOk()->assertJson(['message' => 'Added Class 11 with 2 section(s).']);
        $this->postJson($action['url'])->assertNotFound(); // cannot run twice

        $this->assertSame(2, $school->run(fn () => SchoolClass::where('name', 'Class 11')->first()->sections()->count()));
    }

    public function test_unknown_actions_are_refused(): void
    {
        $school = $this->aiSchool();
        $this->enable('groq');
        $this->actingAs($school->users()->first(), 'school');
        Http::fake(['api.groq.com/*' => Http::response($this->openAiReply('{"answer": "Done.", "action": {"type": "delete_school", "params": {}}}'))]);

        $this->postJson($this->schoolUrl('greenfield', 'ai/ask'), ['question' => 'Delete everything'])
            ->assertOk()->assertJson(['action' => null]);
    }

    public function test_quota_and_plan_limits(): void
    {
        $school = $this->aiSchool(limit: 1);
        $this->enable('groq');
        $this->actingAs($school->users()->first(), 'school');
        Http::fake(['api.groq.com/*' => Http::response($this->openAiReply('{"answer": "Hi", "action": null}'))]);

        $this->postJson($this->schoolUrl('greenfield', 'ai/ask'), ['question' => 'Hi'])->assertOk();
        $this->postJson($this->schoolUrl('greenfield', 'ai/ask'), ['question' => 'Again'])->assertStatus(422)->assertJsonFragment(['error' => 'You have used all 1 AI questions for this month. The limit resets on the 1st.']);

        // A school's own limit overrides the plan; a plan without the AI module gives no AI.
        $school->update(['ai_requests_limit' => 50]);
        $this->assertSame(50, $school->fresh()->aiMonthlyLimit());
        $other = $this->createSchool('riverside', Plan::factory()->create(['modules' => ['students'], 'ai_requests_monthly' => 100]), adminEmail: 'a@r.test');
        $this->assertSame(0, $other->aiMonthlyLimit());
    }
}
