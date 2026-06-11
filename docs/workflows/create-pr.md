# Creating a pull request

This is the canonical workflow for opening a pull request on this repository. It is written to be followed by both humans and AI coding agents. Tool-specific entry points — the Claude Code skill, the Cursor rule, any future Copilot chat mode — should be thin pointers to this file so every tool follows the same procedure.

Rules and templates live elsewhere — this file is the **procedure**:

- **Changelog grammar, label rules, section structure** → [`.github/PULL_REQUEST_TEMPLATE.md`](../../.github/PULL_REQUEST_TEMPLATE.md)
- **Architecture, repository layout, placement rules, build/test commands, Yoast CS ruleset, testing policy, commit style, branching, pre-push checks** → [`.github/CONTRIBUTING.md`](../../.github/CONTRIBUTING.md)
- **Agent-specific behaviours (delegate long-running commands, verify before recommending, ask don't guess)** → [`AGENTS.md`](../../AGENTS.md)

If anything here contradicts those files, prefer them. Edits to rules belong there, not in this file.

## 0. Creator vs. merger responsibilities

Every merged PR needs a changelog entry and a changelog label. The milestone is the merger's job.

- **Creator provides:** a changelog entry (in the PR body) and a `changelog:` label on the PR.
- **Creator MUST NOT set:** the milestone. Do not add a milestone via `gh pr edit --milestone`.

## 1. Gather context

Run in parallel:

- `git status`
- `git diff`
- `git log <base>..HEAD` and `git diff <base>...HEAD` — inspect **every** commit since the branch diverged, not just the tip.
- Check whether the branch has an upstream and is up to date.

Identify the base branch: usually `trunk`. PRs targeting `release/*` follow stricter label rules — check the PR template.

Read [`.github/PULL_REQUEST_TEMPLATE.md`](../../.github/PULL_REQUEST_TEMPLATE.md) so the PR body matches it section-for-section.

## 2. Verify checks

Follow the pre-push checklist in [`.github/CONTRIBUTING.md` — "Before you push or open/update a PR"](../../.github/CONTRIBUTING.md#before-you-push-or-openupdate-a-pr). All required checks must pass **before** calling `gh pr create`:

- `composer test`
- `composer test-wp-env` — when the change touches code that has or needs WP integration coverage (disclose in the PR body if Docker is unavailable)
- `composer check-branch-cs`
- `composer lint`
- `grunt build` if `js/` changed; `grunt build:images` only if you changed a wp.org asset under `svn-assets/`.

Do not paper over failures by excluding tests, raising CS thresholds, or adding ignore pragmas without explicit permission. If a `build-runner`-style agent is available in your tool, delegate the actual command execution there so the main session stays focused on the workflow.

## 3. Decide how many changelog bullets the PR needs

Default is **one bullet**, describing one logical change. Write more than one bullet only when:

- **Multiple distinct changes in the same repo and same label** — one bullet per change, no brackets.
- **Bullets with different changelog types** — note the differing type where relevant.
- **Impact on another repo** — add a bullet prefixed with `[<repo-name>]`.

Grammar, bracket syntax, and the bugfix template live in the PR template — follow it, do not restate the rules in the PR body.

If you are not certain a change affects another repo, ask the user rather than guessing.

## 4. Fill every PR-template section

Cover every section of `.github/PULL_REQUEST_TEMPLATE.md`:

- **Context** — *why* the change is being made.
- **Summary** — the changelog bullet(s) from step 3.
- **Relevant technical choices** — non-obvious decisions, trade-offs, architectural notes.
- **Test instructions (acceptance)** — step-by-step, aimed at non-technical users.
- **Relevant test scenarios** — tick the boxes that apply (console open, post/page/CPT/taxonomy, editor type, browser, multisite) and explain why for each ticked box.
- **Test instructions for QA in the RC** — tick "same steps as above" when acceptance and QA match, or write separate steps.
- **Impact check** — parts of the plugin that may need regression testing.
- **UI changes** — tick the box and add the `UI change` label only when the PR changes the UI.
- **Documentation / Quality assurance / Innovation** checkboxes — tick only those that are actually true.
- **Fixes #** — link the issue being closed, if any.

Preserve the HTML comments from the template so future editors keep the same structure.

## 5. Create and label the PR

1. Push the branch if it has no upstream: `git push -u origin <branch>`.
2. `gh pr create --base <base> --title "<title>" --body-file <file>`.
   - Pass `--base` with the base branch identified in step 1 (usually `trunk`, or a `release/*` branch). Do not rely on the repository default — be explicit so release PRs target the right branch.
   - Title under 70 characters. Use a Conventional Commits prefix when natural (`fix: …`, `feat(ui): …`).
   - Pass the body via `--body-file`, never an inline heredoc — Markdown fences and `$` sigils mangle through shell escaping.
3. Apply the changelog label immediately: `gh pr edit <number> --add-label "changelog: <type>"`. Add the `UI change` label in the same call only when the PR changes the UI.
4. **Never set the milestone.**
5. Return the PR URL.

## 6. Self-check

Before reporting the PR as created, verify:

- [ ] Pre-push checks (step 2) ran and passed — any skipped check is disclosed in the PR body.
- [ ] The body contains at least one changelog bullet, correctly prefixed for every affected changelog.
- [ ] Exactly one `changelog:` label is attached; the `UI change` label matches the content.
- [ ] Bugfix bullets describe the incorrect behaviour followed by the triggering condition, in past tense, per the PR template's grammar rules.
- [ ] Every PR-template section has content — no empty bullets, no leftover placeholder text.
- [ ] No milestone was set.
- [ ] If the branch changed a wp.org asset under `svn-assets/`, `grunt build:images` was run and the optimised output committed.
- [ ] Each changelog bullet is one short sentence; extra context lives in *Context* or *Relevant technical choices*.

If any item fails, fix it (edit the body or labels with `gh pr edit`) before returning the URL.
