# 38 — Theme auto by clock + veil stagger

Status: done
Blocked by: none
Spec: docs/work/specs/2026-09-18-wp-preview-parity-acceptance.md

## What to build

Режим темы: `day` | `night` | `auto` (auto — по местным часам пользователя; порог зафиксировать константой). Persisted preference. Veil открытия страницы, затем staggered появление блоков как в preview; выключить в BVI и `prefers-reduced-motion`.

## Acceptance

- [ ] Переключатель поддерживает auto; effective theme следует clock при auto
- [ ] Preference переживает reload
- [ ] После veil блоки появляются со stagger (preview-class)
- [ ] BVI / reduced-motion: без veil и stagger
- [ ] Unit/seam на вычисление effective theme от clock
