# AI in Skooline

## Set up (Super Admin → AI setup)
1. Pick a provider with a free tier (Groq, Google Gemini, OpenRouter, Cerebras, Mistral, GitHub Models…), click **Get a key**, paste it, tick **Switched on**, **Save**, then **Test**.
2. Switch on two or three providers. They are tried in **Order** (1 first). When one is out of quota or down, the next one answers.
3. **Edit endpoints & models** unlocks the endpoint and model fields. **Load models** asks the provider for its current model list.
4. **Custom AI** adds any OpenAI-compatible or Anthropic-style API (endpoint + model + key).
5. The **Error help** button explains every test error (wrong key, quota, wrong model, network…).

Keys are saved in `.env` as `AI_<PROVIDER>_API_KEY` and shown masked; **Show** asks for the PIN (Settings → Security).

## Who can use it
- Turn on the **AI Assistant** module in the plan and set **AI questions / month** (Plans). A school can get its own limit when adding/editing it (blank = plan's limit).
- In the school portal: the **principal** gets analysis only; the **school admin** can also ask for changes, which run only after pressing **Confirm**.

## Keeping token use low
- Answers are limited to `config/ai.php` `max_tokens` (700); only the last `history` (6) messages are sent.
- The school summary is short plain text (names and counts, at most 60 rows per list).
- Tests send a one-word prompt with a 16-token limit.

## Teaching the assistant a new module ("training")
The assistant only knows what `app/Services/Ai/SchoolKnowledge.php` tells it, and can only do what `app/Services/Ai/SchoolActions.php` allows. When a module is built (students, attendance, fees…):

1. **Knowledge:** add a method to `SchoolKnowledge` that summarises the module in a few lines (counts, totals, the top items), and add it to `sections()`.
2. **Actions (optional):** add an entry to `SchoolActions::CATALOG` (what it does + example params), validation rules in `prepare()`, and the code in `execute()`. Keep actions small and safe; they always need the admin's Confirm.
3. Add a test in `tests/Feature/AiAssistantTest.php` with a faked provider reply.
