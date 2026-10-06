# Admin composer bio regeneration

Set `OPENAI_API_KEY` in the server environment using an OpenAI API project key with API billing enabled. Keep the key out of source control and browser code. The existing `CHATGPT_TOKEN` secures a separate inbound integration and is not an OpenAI API key.

`OPENAI_MODEL` optionally selects a Responses API model that supports structured JSON outputs. The default is `gpt-4o-mini`. Refresh Laravel's configuration cache through the normal deployment process after changing server settings. Ship the PHP, Blade, JavaScript, and Mix manifest changes together. No migration is needed.

On an existing composer's edit page, click **Regenerate bio**. It rewrites the current text in the biography field. Add source facts first if the field is empty. The request sends only the composer's saved name and current source bio to OpenAI; no account data is included. Requests use `store: false`.

This is a rewrite only. The current biography field is the sole factual source, including any unsaved edits. Instructions prohibit outside knowledge, research and inferred details; the composer name only identifies the subject. Wording and organization may change, but meaning and qualifications must be preserved. Source fidelity takes priority over length targets, so brief source text produces shorter paragraphs rather than new information.

The prompt requires English, simple everyday words, short sentences, no jargon, and only facts from the source. It requires three paragraphs, with a fourth only when additional useful source details need their own paragraph. All paragraphs should have similar lengths, including the third and any fourth, rather than ending with a short closing summary. It aims for four to six short sentences and about 80–100 words per paragraph when there are enough source facts, with useful detail rather than repetition or filler. Sparse sources still require three paragraphs but may produce shorter ones. The API receives a structured schema enforcing three to four paragraphs. Its string length and pattern constraints also enforce nonempty paragraphs, at most 1000 characters and 100 whitespace-separated words, with no embedded line breaks. The output budget is 1600 tokens. Server validation independently checks those limits and rejects incomplete, refused, malformed, or HTML responses. Incomplete responses, refusals and oversized paragraphs have distinct safe error messages. Paragraph balance, language simplicity and factual accuracy still require editorial review.

The returned draft fills the text box without changing the database. Review it and use **Save changes** to publish it through the existing update flow. Editing the bio while generation runs prevents the pending response from replacing your newer text. Failures retain your text and restore controls.

Generation requires the admin session, CSRF token, and the existing composer update permission. It is limited to 10 requests per minute per signed-in admin. Upstream calls have a 45-second timeout and are not automatically retried. API billing and model access belong to the configured OpenAI project.

To rewrite every composer's saved biography, run this from the deployed application's directory:

```sh
php artisan composers:regenerate-bios
```

This command saves successful drafts immediately using the same rewrite service and configured `OPENAI_MODEL` as the button. It processes composers sequentially in database batches, makes one API request per nonempty source within the editor's 20000-character input limit, and skips empty biographies. It does not require a queue worker. Each API request uses the project's API billing. Running the full command again rewrites successful composers again.

The command displays progress and a final saved/skipped/failed count. Failures retain the existing bio and processing continues. A name or biography edit made during generation prevents that pending draft from being saved; other composer fields are preserved. The command returns exit code 1 if any composer fails. It prints a targeted retry command so successful composers are not generated again, for example:

```sh
php artisan composers:regenerate-bios --composer=12 --composer=37
```

Before each attempted save, it writes the original text and generated draft to a private JSON Lines file in `storage/app/composer-bio-backups/`. The command prints the exact path. Entries identify composers by ID and include `original_biography` and `generated_biography`; an entry can also describe a failed or conflicted save, so it does not itself prove the database was updated. Backup creation must succeed before API requests begin, and backup write failures stop processing before saving that draft. The printed retry command includes any remaining unprocessed composers when backup storage fails. No automatic schedule is added.

Documentation: [Structured outputs](https://developers.openai.com/api/docs/guides/structured-outputs?api-mode=responses), [GPT-4o mini](https://developers.openai.com/api/docs/models/gpt-4o-mini).
