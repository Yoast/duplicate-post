# Contribution Guidelines

Thanks for taking the time to contribute to Yoast Duplicate Post! Before filing a bug report, feature request, or pull request, please read the guidelines below.

This file is the canonical contributor guide for this repository. It is written for both humans and AI coding tools. The repo-root [`AGENTS.md`](../AGENTS.md) adds a small set of behaviours specific to AI agents on top of the rules here; the [`PULL_REQUEST_TEMPLATE.md`](./PULL_REQUEST_TEMPLATE.md) carries the detailed changelog and label rules.

## Contents

- [How to use GitHub](#how-to-use-github)
- [Security issues](#security-issues)
- [I have found a bug](#i-have-found-a-bug)
- [I have a feature request](#i-have-a-feature-request)
- [I want to create a patch](#i-want-to-create-a-patch)
  - [License and copyright](#license-and-copyright)
  - [Supported environment](#supported-environment)
  - [Repository layout](#repository-layout)
  - [Where to put new code](#where-to-put-new-code)
    - [Legacy procedural files](#legacy-procedural-files)
  - [No dependency-injection container](#no-dependency-injection-container)
  - [PHP workflow](#php-workflow)
  - [JavaScript workflow](#javascript-workflow)
  - [Testing](#testing)
  - [Code style](#code-style)
  - [Opening a pull request](#opening-a-pull-request)
    - [Before you push or open/update a PR](#before-you-push-or-openupdate-a-pr)
  - [Changelog entry and label](#changelog-entry-and-label)
  - [Submitting an issue you have found](#submitting-an-issue-you-have-found)
- [Additional resources](#additional-resources)

## How to use GitHub

We use GitHub exclusively for well-documented bugs, feature requests, and code contributions. Communication is always done in English.

For support with Duplicate Post, use the [support forum](https://wordpress.org/support/plugin/duplicate-post) on WordPress.org.

## Security issues

Please do **not** report security issues on GitHub. Follow our [security program](https://yoast.com/security-program/) instead — see [`yoast.com/security.txt`](https://yoast.com/security.txt) for the canonical contact details — so we can handle them quickly and responsibly.

## I have found a bug

Before opening a new issue, please:

* update to the latest versions of WordPress and Duplicate Post.
* search for duplicate issues to avoid filing the same report twice. If an open issue already exists, please comment on it.
* check for plugin and theme conflicts, and include your findings.
* check for JavaScript errors in your browser's console and include any output.
* pick the matching GitHub issue form (Bug report, Feature request, Task) and fill in every section.
* include everything needed to understand and reproduce the problem — screenshots, clear reproduction steps, plugin and theme versions, and any relevant logs — but stay focused. A tight, reproducible report is easier to triage than a long narrative with unrelated context.

## I have a feature request

Before opening a new issue:

* search for duplicate issues to avoid filing the same request twice. If an open request already exists, please add your thoughts there.
* pick the Feature request issue form and explain *why* you think this feature is worth considering.

## I want to create a patch

Community patches, localizations, bug reports, and contributions are very welcome.

### License and copyright

Duplicate Post is licensed under [GPL-2.0-or-later](../LICENSE). By opening a pull request you confirm that your contribution is offered under the same license. Before contributing code, make sure that:

- You wrote the code yourself, or you have the right to relicense it under GPL-2.0-or-later.
- You have not copied code from sources whose license is incompatible with GPL-2.0-or-later (for example proprietary code, CC-licensed snippets that restrict commercial use, or GPL-3.0-only code).
- If you have reused code from a GPL-2.0-compatible source (MIT, BSD, public domain, etc.), you have preserved the original copyright notice and license header, and noted the provenance in the commit message.
- You have not included code whose licensing status is unclear — including AI-generated code whose training or output terms you have not verified as compatible.

If you are unsure whether a piece of code is safe to include, ask in the issue or PR before opening it for review.

### Supported environment

* PHP: the minimum version is the `Requires PHP` header in [`readme.txt`](../readme.txt) (also pinned in [`composer.json`](../composer.json)).
* WordPress: the supported range is the `Requires at least` and `Tested up to` headers in [`readme.txt`](../readme.txt).
* The plugin is a single PHP plugin with a small JavaScript bundle (block-editor integration) built through webpack and Grunt.

### Repository layout

The top-level paths you will touch (or explicitly avoid):

| Path | Purpose | Editable? |
| --- | --- | --- |
| `src/` | Namespaced PHP (`Yoast\WP\Duplicate_Post\`). All new backend work lives here. | Yes |
| `js/src/` | Source for the block-editor JavaScript. | Yes |
| `tests/` | PHPUnit tests (`tests/Unit`, `tests/WP`). | Yes |
| `config/` | Grunt, webpack, wp-env, composer actions, build scripts. | Yes, with care |
| `compat/` | Compatibility shims for third-party plugins. | Yes, with care |
| `admin-functions.php`, `common-functions.php`, `options.php` | Legacy procedural PHP. | **Maintenance only** — see below |
| `duplicate-post.php` | Plugin bootstrap and manual service wiring. | With care |
| `vendor/`, `node_modules/` | Composer / Yarn dependencies. | Never hand-edit |
| `js/dist/`, `artifact/`, `languages/` | Generated or distribution artifacts. | Never hand-edit — regenerate via Grunt |
| `readme.txt`, `changelog.md` | wordpress.org readme and the generated changelog. | See [Changelog entry and label](#changelog-entry-and-label) |

### Where to put new code

`src/` is classmap-autoloaded under the `Yoast\WP\Duplicate_Post\` namespace. Code is grouped by its role in the plugin rather than by onion layers:

```
src/
├── admin/        Admin screens, settings, and their views.
├── handlers/     Request handlers (bulk, REST, save-post, links, …).
├── ui/           User-facing surfaces (metabox, block editor, columns, row actions, …).
├── watchers/     Hook listeners that react to post lifecycle events.
└── *.php         Shared services (post-duplicator, post-republisher, permissions-helper, utils, …).
```

When you extend an existing feature, keep the new code in the matching folder and follow the surrounding patterns. New files are a last resort — prefer extending an existing class. PHP files are kebab-case (`link-handler.php`); class names follow Yoast's convention of snake_case with underscores (e.g. `Link_Handler`).

#### Legacy procedural files

`admin-functions.php`, `common-functions.php`, and `options.php` contain pre-namespace procedural code. Treat them as **maintenance-only**: fix bugs and keep them compatible, but do not add new features there. When a legacy function needs significant changes, consider extracting the affected responsibility into a class under `src/`.

### No dependency-injection container

Unlike Yoast SEO, Duplicate Post does **not** use a compiled DI container. Services are instantiated and wired by hand in the `duplicate-post.php` bootstrap. There is no `compile-di` step — when you add a service, wire it explicitly in the bootstrap and pass its dependencies through the constructor.

### PHP workflow

All commands are run from the repo root.

| Command | What it does |
| --- | --- |
| `composer update` | Install PHP dependencies. `composer.lock` is not committed in this repo, so use `update` rather than `install`. |
| `composer lint` | PHP parse-error check across the repo. |
| `composer check-cs` | Run phpcs with the Yoast ruleset (errors only, no warnings). |
| `composer check-branch-cs` | Run phpcs against the files changed on the current branch. |
| `composer check-staged-cs` | Run phpcs against staged files. |
| `composer fix-cs` | Auto-fix fixable phpcs violations. |
| `composer test` | Run PHPUnit unit tests (no WP, no coverage). |
| `composer test-wp` | Run the WP integration tests against a local WP test install. |
| `composer test-wp-env` | Run the WP integration tests inside the `wp-env` Docker environment (preferred locally). |
| `composer coverage` / `coverage-wp-env` | The test commands above, with coverage. |

Coding standards are enforced by the Yoast Coding Standard (`yoast/yoastcs`) — a superset of the WordPress Coding Standards — plus parallel-lint for syntax. Run `composer check-branch-cs` before opening a PR.

### JavaScript workflow

The block-editor JavaScript is built through Grunt (which drives webpack). `package.json` has no npm scripts; use Grunt directly.

| Command | What it does |
| --- | --- |
| `yarn install` | Install JS build dependencies. |
| `grunt` / `grunt build` | Build the block-editor JS bundle (webpack development build). |
| `grunt release` | Production JS build (used by the release/artifact pipeline). |
| `grunt build:images` | Optimise the wp.org store assets in `svn-assets/` (banner, icons, screenshots) via imagemin. Run manually only when you change those assets — it is **not** part of `grunt build` or the release pipeline. |

### Testing

- **Every PR that changes PHP behaviour should ship unit tests.** Place them under `tests/Unit/…`, mirroring the path of the class under test.
- **Integration tests** (those that boot WordPress) live under `tests/WP/…` and run via `composer test-wp-env`, which starts an isolated WP in Docker through `@wordpress/env`.
- Test one method per test class where practical; share setup via abstract base classes or traits (examples already exist under `tests/Unit`).
- If you cannot run the integration tests in your environment, say so explicitly in the PR description rather than skipping them silently.

### Code style

- Follow the existing code. When two styles look plausible, match the file you are editing.
- PHP: Yoast CS (`yoast/yoastcs`), configured in [`.phpcs.xml.dist`](../.phpcs.xml.dist). Namespaces live under `Yoast\WP\Duplicate_Post\…`.
- The CS check enforces an error/warning **threshold** (see the `check-cs-thresholds` composer script). Do not raise the threshold to make a violation pass — fix the violation.
- Comments: document **why**, not **what**. End every inline comment with a full stop.
- Don't add features, scaffolding, or abstractions the task doesn't need.

### Opening a pull request

1. Create your branch from `trunk`. `trunk` is the active development branch and the default branch on GitHub — every PR should target it unless a maintainer asks otherwise. When the work tracks a GitHub issue, name your branch `<issue-number>-<short-description>` (e.g. `210-scheduled-republish`).
2. Make your changes.
3. Follow the [Yoast Coding Standards](https://github.com/Yoast/yoastcs).
4. Document any new functions, actions, and filters following the [PHP inline-documentation standards](https://make.wordpress.org/core/handbook/best-practices/inline-documentation-standards/php/).
5. Write tests. We expect every PR that changes PHP behaviour to ship unit tests.
6. Use the [Conventional Commits](https://www.conventionalcommits.org/) format for commit messages (e.g. `fix: …`, `feat(ui): …`). **Prefer atomic commits** — each commit should represent a single logical change. If your PR mixes unrelated changes, split them into separate commits or ideally separate PRs.
7. Push your branch and open a pull request against `trunk`. **Use the pull request template** at [`.github/PULL_REQUEST_TEMPLATE.md`](./PULL_REQUEST_TEMPLATE.md) and fill in every section — even for small changes.
8. **Keep the PR description focused.** Fill every required section of the template with what the reviewer actually needs. *Test instructions* should be concrete steps, not essays. Link to the issue rather than restating it.

#### Before you push or open/update a PR

Run these checks locally and make sure each one is clean. CI runs the same checks.

* `composer test` — the unit test suite must pass.
* `composer test-wp-env` — the WordPress integration tests (Docker via `@wordpress/env`) must pass if your change touches code that has or needs WP integration coverage.
* `composer check-branch-cs` — must report **no new errors or warnings** introduced by your branch. Use `composer fix-cs` to auto-fix what it can, and address the rest by hand.
* `composer lint` — PHP parse-error check.
* For changes under `js/`: run `grunt build` and confirm the bundle builds.
* Only if you changed a wp.org store asset under `svn-assets/`: run `grunt build:images` and commit the optimised output. (It is not run by `grunt build` or the release pipeline, so it will not happen automatically.)

If a check fails or you need to skip one (e.g. you can't run Docker locally for `test-wp-env`), say so explicitly in the PR description so reviewers know what still needs validating.

### Changelog entry and label

Every PR needs a **changelog entry** in the Summary section of the PR body and a **changelog label** on the PR itself:

* Write one bullet describing the change in present tense, 3rd person singular, ending with a full stop. For bugfixes, describe the incorrect behaviour followed by the condition that triggered it, in clear past tense (e.g. `Fixes a bug where X happened when Y`). See [`PULL_REQUEST_TEMPLATE.md`](./PULL_REQUEST_TEMPLATE.md) for the full grammar.
* Keep each bullet to one short sentence. Extra context belongs in *Context* or *Relevant technical choices*, not in the bullet.
* Attach one of: `changelog: bugfix`, `changelog: enhancement`, `changelog: other`, `changelog: non-user-facing`.
* If the change also affects another Yoast repo, add an extra bullet prefixed with `[<repo-name>]`.
* The release `changelog.md` is generated from merged PRs by [`.github/scripts/generate-changelog.sh`](./scripts/generate-changelog.sh) — do not hand-write release sections into it; the PR body's bullet and label are the source.

Milestones are set by the maintainer who merges your PR.

### Submitting an issue you have found

Make sure your problem doesn't already have a ticket by searching [the existing issues](https://github.com/Yoast/duplicate-post/issues). If you can't find anything matching, please [open a new issue](https://github.com/Yoast/duplicate-post/issues/new/choose).

## Additional resources

* [Yoast developer portal](https://developer.yoast.com/)
* [General GitHub documentation](https://docs.github.com/)
* [GitHub Pull Request documentation](https://docs.github.com/pull-requests/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/creating-a-pull-request)
