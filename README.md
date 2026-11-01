# SmartCook – integration branch

Fork build of [gabryk91/smartcook](https://github.com/gabryk91/smartcook): upstream `main` + all open PRs + our own patches.
Version = `<upstream>-<patch level>` (`appinfo/info.xml`). Upstream base: **1.0.196**. Current: **1.0.196-6**.

## Patches in this branch

### Upstream PRs (merged here, not ours)

| PR | Since | Keywords |
| --- | --- | --- |
| [#2](https://github.com/gabryk91/smartcook/pull/2) | 1.0.196 | French (fr) translation, fr plural rule |
| [#3](https://github.com/gabryk91/smartcook/pull/3) | 1.0.196 | unit labels (tsp, tbsp, cup, pc, clove, pinch, to_taste) from the translation catalog |
| [#4](https://github.com/gabryk91/smartcook/pull/4) | 1.0.196 | ingredient bulk edit, category + substitutes update |

### Our patches

| Patch (branch / PR) | Since | Keywords |
| --- | --- | --- |
| German (de) translation – `l10n-de`, [#6](https://github.com/gabryk91/smartcook/pull/6) | 1.0.196 | `de.json`/`de.js`, informal "du", Nextcloud terms; plus de texts for #4 (this branch only) |
| Dark theme fix – `fix-dark-mode`, [#8](https://github.com/gabryk91/smartcook/pull/8) | 1.0.196 | tokens on `:root, body` (Nextcloud dark vars live on `body`), hard-coded light colors derived from surface |
| Plural-aware counts – `l10n-plurals` | 1.0.196-1 | `trn()` on `n()`, `_%n x_::_%n xs_` keys for en/it/fr/de, "1 recipe" instead of "1 recipes", l10n test checks plural keys |
| Composed texts – `l10n-composed-texts` | 1.0.196-2 | "Decrease/Increase servings" as full keys, shopping progress `{checked} of %n items completed`, `trn()` vars |
| Hard-coded texts – `l10n-hardcoded-texts` | 1.0.196-3 | backend error messages translated (`localizeApiMessage`, patterns + placeholders), `min` unit, manifest description, `tr(text, vars)`; de "Danger zone" = "Unwiderrufliche Aktionen" |
| AI meal plan prompt – `l10n-ai-prompts` | 1.0.196-4 | prompt in en/it/fr/de via `IL10N`, default instruction not stored (placeholder), no Italian example |
| Default language – `language-setting` | 1.0.196-4 | setting was never used; now default for new recipes, import output, assistant; `auto` = Nextcloud language; datalist autocompletion; validation |
| OCR default language – `ocr-language-default` | 1.0.196-5 | default from Nextcloud language (`deu+eng`, `fra+eng`, …) instead of `ita+eng`; OCR help lists matching packages |
| AI provider errors – `ai-http-errors` | 1.0.196-6 | retry on 429/502/503/504 (Retry-After), readable translated errors instead of raw Guzzle message / "unexpected error", API key no longer in logs or messages |

## Maintaining this list

- Add one row per patch merged into `integration` (keywords, branch/PR, version) and raise the patch level in `appinfo/info.xml`.
- Documentation-only changes (like this file) do not raise the patch level.
- When upstream releases a new version: merge `upstream/main`, reset the patch level to `-1`, keep the rows (drop a row once its PR is part of upstream).
