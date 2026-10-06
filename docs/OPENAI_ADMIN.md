# Admin composer bio regeneration

Set `OPENAI_API_KEY` in the server environment using an OpenAI API project key with API billing enabled. Keep the key out of source control and browser code. The existing `CHATGPT_TOKEN` secures a separate inbound integration and is not an OpenAI API key.

`OPENAI_MODEL` optionally selects a Responses API model that supports structured JSON outputs. The default is `gpt-4o-mini`. Refresh Laravel's configuration cache through the normal deployment process after changing server settings. Ship the PHP, Blade, JavaScript, and Mix manifest changes together. No migration is needed.

On an existing composer's edit page, click **Regenerate bio**. It rewrites the current text in the biography field. Add source facts first if the field is empty. The request sends only the composer's saved name and current source bio to OpenAI; no account data is included. Requests use `store: false`.

The prompt requires English, simple everyday words, short sentences, no jargon, and only facts from the source. The API receives a structured schema of one to three paragraphs. Its string length and pattern constraints also enforce nonempty paragraphs, at most 600 characters and 60 whitespace-separated words, with no embedded line breaks. Server validation independently checks those limits and rejects incomplete, refused, malformed, or HTML responses. Incomplete responses, refusals and oversized paragraphs have distinct safe error messages. Language simplicity and factual accuracy still require editorial review.

The returned draft fills the text box without changing the database. Review it and use **Save changes** to publish it through the existing update flow. Editing the bio while generation runs prevents the pending response from replacing your newer text. Failures retain your text and restore controls.

Generation requires the admin session, CSRF token, and the existing composer update permission. It is limited to 10 requests per minute per signed-in admin. Upstream calls have a 45-second timeout and are not automatically retried. API billing and model access belong to the configured OpenAI project.

Documentation: [Structured outputs](https://developers.openai.com/api/docs/guides/structured-outputs?api-mode=responses), [GPT-4o mini](https://developers.openai.com/api/docs/models/gpt-4o-mini).
