<?php

/*
|--------------------------------------------------------------------------
| AI providers
|--------------------------------------------------------------------------
|
| Templates shown in Super Admin → AI setup. The admin only pastes an API key;
| endpoint and model can be changed when "Edit endpoints & models" is on.
| Keys are saved in .env as AI_<KEY>_API_KEY.
|
| style:  "openai"    = OpenAI-compatible  POST {base_url}/chat/completions
|         "anthropic" = Anthropic Messages POST {base_url}/messages
| free:   short note about the free tier (as published by the provider; may change)
| models: suggestions for the model field ("Load models" asks the provider for its live list)
|
*/

return [

    'providers' => [
        'groq' => [
            'name' => 'Groq', 'style' => 'openai', 'base_url' => 'https://api.groq.com/openai/v1',
            'model' => 'llama-3.1-8b-instant', 'models' => ['llama-3.1-8b-instant', 'llama-3.3-70b-versatile'],
            'free' => 'Generous free tier, very fast', 'signup' => 'https://console.groq.com/keys',
        ],
        'gemini' => [
            'name' => 'Google Gemini', 'style' => 'openai', 'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai',
            'model' => 'gemini-2.5-flash', 'models' => ['gemini-2.5-flash', 'gemini-2.5-flash-lite', 'gemini-2.5-pro'],
            'free' => 'Free tier with daily limits', 'signup' => 'https://aistudio.google.com/app/apikey',
        ],
        'openrouter' => [
            'name' => 'OpenRouter', 'style' => 'openai', 'base_url' => 'https://openrouter.ai/api/v1',
            'model' => 'meta-llama/llama-3.3-70b-instruct:free', 'models' => ['meta-llama/llama-3.3-70b-instruct:free', 'deepseek/deepseek-chat-v3-0324:free', 'openrouter/auto'],
            'free' => 'Many ":free" models', 'signup' => 'https://openrouter.ai/keys',
        ],
        'agentrouter' => [
            'name' => 'AgentRouter', 'style' => 'openai', 'base_url' => 'https://agentrouter.org/v1',
            'model' => 'deepseek-v3.1', 'models' => ['deepseek-v3.1', 'glm-4.5', 'gpt-5'],
            'free' => 'Free credits on sign-up', 'signup' => 'https://agentrouter.org',
        ],
        'cerebras' => [
            'name' => 'Cerebras', 'style' => 'openai', 'base_url' => 'https://api.cerebras.ai/v1',
            'model' => 'llama3.1-8b', 'models' => ['llama3.1-8b', 'llama-3.3-70b'],
            'free' => 'Free tier, very fast', 'signup' => 'https://cloud.cerebras.ai',
        ],
        'mistral' => [
            'name' => 'Mistral AI', 'style' => 'openai', 'base_url' => 'https://api.mistral.ai/v1',
            'model' => 'mistral-small-latest', 'models' => ['mistral-small-latest', 'open-mistral-nemo', 'mistral-large-latest'],
            'free' => 'Free "Experiment" plan', 'signup' => 'https://console.mistral.ai/api-keys',
        ],
        'github' => [
            'name' => 'GitHub Models', 'style' => 'openai', 'base_url' => 'https://models.github.ai/inference',
            'model' => 'openai/gpt-4o-mini', 'models' => ['openai/gpt-4o-mini', 'meta/Llama-3.3-70B-Instruct'],
            'free' => 'Free with a GitHub token (daily limits)', 'signup' => 'https://github.com/settings/tokens',
        ],
        'huggingface' => [
            'name' => 'Hugging Face', 'style' => 'openai', 'base_url' => 'https://router.huggingface.co/v1',
            'model' => 'meta-llama/Llama-3.1-8B-Instruct', 'models' => ['meta-llama/Llama-3.1-8B-Instruct', 'Qwen/Qwen2.5-7B-Instruct'],
            'free' => 'Monthly free credits', 'signup' => 'https://huggingface.co/settings/tokens',
        ],
        'nvidia' => [
            'name' => 'NVIDIA NIM', 'style' => 'openai', 'base_url' => 'https://integrate.api.nvidia.com/v1',
            'model' => 'meta/llama-3.1-8b-instruct', 'models' => ['meta/llama-3.1-8b-instruct', 'meta/llama-3.3-70b-instruct'],
            'free' => 'Free credits for developers', 'signup' => 'https://build.nvidia.com',
        ],
        'sambanova' => [
            'name' => 'SambaNova', 'style' => 'openai', 'base_url' => 'https://api.sambanova.ai/v1',
            'model' => 'Meta-Llama-3.1-8B-Instruct', 'models' => ['Meta-Llama-3.1-8B-Instruct', 'Meta-Llama-3.3-70B-Instruct'],
            'free' => 'Free tier', 'signup' => 'https://cloud.sambanova.ai/apis',
        ],
        'together' => [
            'name' => 'Together AI', 'style' => 'openai', 'base_url' => 'https://api.together.xyz/v1',
            'model' => 'meta-llama/Llama-3.3-70B-Instruct-Turbo-Free', 'models' => ['meta-llama/Llama-3.3-70B-Instruct-Turbo-Free'],
            'free' => 'Free models available', 'signup' => 'https://api.together.ai/settings/api-keys',
        ],
        'cloudflare' => [
            'name' => 'Cloudflare Workers AI', 'style' => 'openai', 'base_url' => 'https://api.cloudflare.com/client/v4/accounts/YOUR_ACCOUNT_ID/ai/v1',
            'model' => '@cf/meta/llama-3.1-8b-instruct', 'models' => ['@cf/meta/llama-3.1-8b-instruct', '@cf/meta/llama-3.3-70b-instruct-fp8-fast'],
            'free' => 'Free daily allowance. Put your account ID in the endpoint.', 'signup' => 'https://dash.cloudflare.com/profile/api-tokens',
            'endpoint_required' => true,
        ],
        'cohere' => [
            'name' => 'Cohere', 'style' => 'openai', 'base_url' => 'https://api.cohere.ai/compatibility/v1',
            'model' => 'command-r7b-12-2024', 'models' => ['command-r7b-12-2024', 'command-a-03-2025'],
            'free' => 'Free trial key', 'signup' => 'https://dashboard.cohere.com/api-keys',
        ],
        'deepseek' => [
            'name' => 'DeepSeek', 'style' => 'openai', 'base_url' => 'https://api.deepseek.com/v1',
            'model' => 'deepseek-chat', 'models' => ['deepseek-chat', 'deepseek-reasoner'],
            'free' => 'Very low cost', 'signup' => 'https://platform.deepseek.com/api_keys',
        ],
        'fireworks' => [
            'name' => 'Fireworks AI', 'style' => 'openai', 'base_url' => 'https://api.fireworks.ai/inference/v1',
            'model' => 'accounts/fireworks/models/llama-v3p1-8b-instruct', 'models' => ['accounts/fireworks/models/llama-v3p1-8b-instruct'],
            'free' => 'Starter credits', 'signup' => 'https://fireworks.ai/account/api-keys',
        ],
        'chutes' => [
            'name' => 'Chutes', 'style' => 'openai', 'base_url' => 'https://llm.chutes.ai/v1',
            'model' => 'deepseek-ai/DeepSeek-V3-0324', 'models' => ['deepseek-ai/DeepSeek-V3-0324'],
            'free' => 'Free and low-cost models', 'signup' => 'https://chutes.ai',
        ],
        'novita' => [
            'name' => 'Novita AI', 'style' => 'openai', 'base_url' => 'https://api.novita.ai/v3/openai',
            'model' => 'meta-llama/llama-3.1-8b-instruct', 'models' => ['meta-llama/llama-3.1-8b-instruct'],
            'free' => 'Trial credits', 'signup' => 'https://novita.ai/settings/key-management',
        ],
        'hyperbolic' => [
            'name' => 'Hyperbolic', 'style' => 'openai', 'base_url' => 'https://api.hyperbolic.xyz/v1',
            'model' => 'meta-llama/Meta-Llama-3.1-8B-Instruct', 'models' => ['meta-llama/Meta-Llama-3.1-8B-Instruct'],
            'free' => 'Trial credits', 'signup' => 'https://app.hyperbolic.xyz/settings',
        ],
        'openai' => [
            'name' => 'OpenAI', 'style' => 'openai', 'base_url' => 'https://api.openai.com/v1',
            'model' => 'gpt-4o-mini', 'models' => ['gpt-4o-mini', 'gpt-4.1-mini'],
            'free' => 'Paid (low cost)', 'signup' => 'https://platform.openai.com/api-keys',
        ],
        'anthropic' => [
            'name' => 'Anthropic Claude', 'style' => 'anthropic', 'base_url' => 'https://api.anthropic.com/v1',
            'model' => 'claude-opus-5-5', 'models' => ['claude-opus-5-5', 'claude-sonnet-5-5', 'claude-haiku-5-5'],
            'free' => 'Paid', 'signup' => 'https://console.anthropic.com/settings/keys',
        ],
        'xai' => [
            'name' => 'xAI Grok', 'style' => 'openai', 'base_url' => 'https://api.x.ai/v1',
            'model' => 'grok-3-mini', 'models' => ['grok-3-mini', 'grok-4'],
            'free' => 'Paid', 'signup' => 'https://console.x.ai',
        ],
        'perplexity' => [
            'name' => 'Perplexity', 'style' => 'openai', 'base_url' => 'https://api.perplexity.ai',
            'model' => 'sonar', 'models' => ['sonar', 'sonar-pro'],
            'free' => 'Paid', 'signup' => 'https://www.perplexity.ai/settings/api',
        ],
        'ollama' => [
            'name' => 'Ollama (your own server)', 'style' => 'openai', 'base_url' => 'http://localhost:11434/v1',
            'model' => 'llama3.2', 'models' => ['llama3.2', 'qwen2.5'],
            'free' => 'Free, runs on your computer or VPS. No key needed.', 'signup' => 'https://ollama.com',
            'key_optional' => true,
        ],
    ],

    // Longest answer the assistant may write (tokens). Short answers keep usage and cost low.
    'max_tokens' => 700,

    // How many earlier chat messages are sent with each question (more = more context, more tokens).
    'history' => 6,

    // Seconds to wait for a provider before trying the next one.
    'timeout' => 30,

];
