# Admin text improvement and composer biography rewrites

Every admin textarea also has an **Improve text** button underneath, including fields added dynamically and TinyMCE editors. Click it to open Length (Shorter, Same length, Longer) and Tone (Casual, Same tone, Formal), then click **Rewrite text**. Both options default to Same each time the options open. The rewrite improves readability and natural flow, keeps the source language and facts, and asks for natural, everyday wording without jargon or artificial language, including in Formal mode. Longer expands the wording using only existing information; it does not add facts. Field character limits take precedence over the selected length.

This shared control uses the same server OpenAI configuration documented below, requires the admin session and CSRF token, and is limited to 10 requests per minute per admin. It sends only the current field's text, with `store: false`, and returns an unsaved draft. There are no database writes in the rewrite endpoint. Review the replaced text and save the existing form to persist it. Failed requests, malformed or oversized results, and edits/resets made while rewriting keep the existing text. Saving the form waits for an active rewrite to finish. Disabled and read-only fields retain a disabled button.

Rich text rewrites replace text nodes only, preserving the original markup, links, images and formatting. Generated strings are assigned as text rather than interpreted as HTML. Source text is limited to 50000 characters per request and 1000 text segments; the configured output token budget may limit long rewrites. Writing quality, factual fidelity and approximate length/tone still require editorial review. The shared control replaces the former **Regenerate bio** button on composer edit pages. The bulk biography command retains its existing paragraph rules.

Set `OPENAI_API_KEY` in the server environment using an OpenAI API project key with API billing enabled. Keep the key out of source control and browser code. The existing `CHATGPT_TOKEN` secures a separate inbound integration and is not an OpenAI API key.

`OPENAI_MODEL` optionally selects a Responses API model that supports structured JSON outputs. The default is `gpt-4o-mini`. Refresh Laravel's configuration cache through the normal deployment process after changing server settings. Ship the PHP, Blade, JavaScript, and Mix manifest changes together. No migration is needed.

`OPENAI_MAX_OUTPUT_TOKENS` sets the generation budget (default `4096`, supported project range `256`–`32768`). This budget includes both the visible response and internal reasoning tokens; it does not change the paragraph or word limits. Reasoning models may need a higher budget to finish the same short bio. For example, set `OPENAI_MAX_OUTPUT_TOKENS=12000` and refresh the configuration cache if confirmed token-limit failures persist. This is a maximum allowance, not a requested bio length.

Use **Improve text** on a composer's biography field for an unsaved draft with the selected length and tone. The following composer-specific rules apply to the bulk command and retained legacy composer rewrite endpoint, not the shared control. These send only the composer's saved name and current source bio to OpenAI; no account data is included. Requests use `store: false`.

This is a rewrite only. The current biography field is the sole factual source, including any unsaved edits. Instructions prohibit outside knowledge, research and inferred details; the composer name only identifies the subject. Wording and organization may change, but meaning and qualifications must be preserved. Source fidelity takes priority over length targets, so brief source text produces shorter paragraphs rather than new information.

The prompt requires English, simple everyday words, short sentences, no jargon, and only facts from the source. It requires three paragraphs, with a fourth only when additional useful source details need their own paragraph. All paragraphs should have similar lengths, including the third and any fourth, rather than ending with a short closing summary. It aims for four to six short sentences and about 80–100 words per paragraph when there are enough source facts, with useful detail rather than repetition or filler. Sparse sources still require three paragraphs but may produce shorter ones. The API receives a structured schema enforcing three to four paragraphs. Its string length and pattern constraints also enforce nonempty paragraphs, at most 1000 characters and 100 whitespace-separated words, with no embedded line breaks. Server validation independently checks those limits and rejects incomplete, refused, malformed, or HTML responses. Incomplete responses caused by the token limit or content filter have distinct safe error messages; an unknown incomplete reason retains the generic message. Refusals and oversized paragraphs also have distinct messages. Paragraph balance, language simplicity and factual accuracy still require editorial review.

The shared control fills the text box without changing the database. Review it and use **Save changes** to publish it through the existing update flow. Editing the bio while generation runs prevents the pending response from replacing your newer text. Failures retain your text and restore controls.

The retained composer-specific endpoint requires the admin session, CSRF token, and existing composer update permission. It is limited to 10 requests per minute per signed-in admin. Upstream calls have a 45-second timeout and are not automatically retried. API billing and model access belong to the configured OpenAI project.

To rewrite every composer's saved biography, run this from the deployed application's directory:

```sh
php artisan composers:regenerate-bios
```

This command saves successful drafts immediately using the composer-specific rewrite service and configured `OPENAI_MODEL`. It processes composers sequentially in database batches, makes one API request per nonempty source within its 20000-character input limit, and skips empty biographies. It does not require a queue worker. Each API request uses the project's API billing. Running the full command again rewrites successful composers again.

The command displays progress and a final saved/skipped/failed count. Failures retain the existing bio and processing continues. A name or biography edit made during generation prevents that pending draft from being saved; other composer fields are preserved. The command returns exit code 1 if any composer fails. It prints a targeted retry command so successful composers are not generated again, for example:

```sh
php artisan composers:regenerate-bios --composer=12 --composer=37
```

Before each attempted save, it writes the original text and generated draft to a private JSON Lines file in `storage/app/composer-bio-backups/`. The command prints the exact path. Entries identify composers by ID and include `original_biography` and `generated_biography`; an entry can also describe a failed or conflicted save, so it does not itself prove the database was updated. Backup creation must succeed before API requests begin, and backup write failures stop processing before saving that draft. The printed retry command includes any remaining unprocessed composers when backup storage fails. No automatic schedule is added.

Documentation: [Structured outputs](https://developers.openai.com/api/docs/guides/structured-outputs?api-mode=responses), [Reasoning token budgets and incomplete responses](https://developers.openai.com/api/docs/guides/reasoning#allocating-space-for-reasoning), [GPT-4o mini](https://developers.openai.com/api/docs/models/gpt-4o-mini).
