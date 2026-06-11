# AGENTS.md — Duplicate Post agent delta

This file follows the [AGENTS.md](https://agents.md/) convention (supported natively by OpenAI Codex, Cursor, Aider, Zed, Copilot, Gemini CLI, Windsurf, JetBrains Junie, and others; Claude Code reads it via a thin `CLAUDE.md` pointer).

**Read [`.github/CONTRIBUTING.md`](./.github/CONTRIBUTING.md) first.** It is the canonical source of truth for this repository: architecture, repository layout, code placement, build and test commands, coding standards, commits, branches, the PR opening procedure, changelog rules, license, and security. This file does not repeat any of that — it only lists the behaviours specific to working in this repo as an **AI coding agent**.

If a rule appears to conflict between this file and `CONTRIBUTING.md` or [`.github/PULL_REQUEST_TEMPLATE.md`](./.github/PULL_REQUEST_TEMPLATE.md), prefer those. Edit the source, not this delta.

## Behaviour rules for agents

- **Delegate long-running commands.** Do not run `composer` or `grunt` in the main session — the output clutters context. If a build-runner-style agent is available, delegate to it and have it return PASS/FAIL plus failing-case details only. When no such agent is available, summarise the output rather than inlining it.

- **Verify before you recommend.** Before citing a file path, function name, class, flag, or any other code identifier from memory, confirm it still exists — `ls`, `grep`, or a file read. Memory is a snapshot; the tree may have moved.

- **Use the real commands for this repo.** Tests and CS run through `composer` (`test`, `test-wp`, `test-wp-env`, `check-branch-cs`, `fix-cs`, `lint`); the JS bundle builds through `grunt` / `grunt build` (dev) or `grunt release` (production). There is **no** `composer compile-di`, **no** `grunt build:dev`, and **no** npm/yarn run-scripts in this repo — don't invent them. See [CONTRIBUTING.md → "JavaScript workflow"](./.github/CONTRIBUTING.md#javascript-workflow).

- **Prefer editing over creating.** Before adding a new file, abstraction, or directory, search for where similar functionality already lives and extend that instead. New files are the last resort.

- **Don't paper over failures.** If a pre-push check, test, or coding-standard rule fails, fix it or flag it. Do not skip tests, raise the CS error/warning threshold (the `check-cs-thresholds` script), add ignore pragmas, or untick quality-assurance boxes on the PR template without explicit permission.

- **Don't hand-edit generated or vendored files.** `vendor/`, `node_modules/`, `js/dist/`, `artifact/`, `languages/` — regenerate via the appropriate tooling. The full list is in [CONTRIBUTING.md → "Repository layout"](./.github/CONTRIBUTING.md#repository-layout).

- **Run `grunt build:images` only when you change the wp.org store assets.** `grunt build:images` (imagemin) is configured to optimise only the wp.org store assets under `svn-assets/` (banner, icons, screenshots); there is no `images/` source dir, and other image files elsewhere in the repo are not processed by it. If you edit an `svn-assets/` asset, run it and commit the optimised output. It is **not** part of `grunt build` or the release pipeline, so nothing runs it automatically — for any normal code change it is irrelevant.

- **Respect the legacy/`src/` split.** New backend work goes in `src/` under `Yoast\WP\Duplicate_Post\`. The root `*-functions.php` files and `options.php` are maintenance-only legacy code — don't add features there. There is no DI container; wire new services by hand in the `duplicate-post.php` bootstrap. See [CONTRIBUTING.md → "Where to put new code"](./.github/CONTRIBUTING.md#where-to-put-new-code).

- **Keep changelog bullets to one short sentence.** Extra context goes in *Context* or *Relevant technical choices*, not in the bullet. Attach exactly one `changelog:` label and never set the milestone. See [CONTRIBUTING.md → "Changelog entry and label"](./.github/CONTRIBUTING.md#changelog-entry-and-label).

- **Ask, don't guess.** If a change might affect another Yoast repo but the diff does not prove it, ask before adding a cross-repo changelog entry. If the licensing status of reused or AI-generated code is unclear, ask before including it.

- **Default to CONTRIBUTING.md.** Anything not listed in this delta is in `CONTRIBUTING.md` or the PR template. Read those before making assumptions.
