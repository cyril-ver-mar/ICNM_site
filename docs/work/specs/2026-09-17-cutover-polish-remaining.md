# Cutover polish — remaining gaps (без карты Минска)

Status: ready-for-agent

Tracker: `docs/work/specs/`, `docs/work/tickets/` (локальный markdown; `setup-engineering-workflow` в репо не запускался).

IA/content и visual→preview срезы (тикеты 01–27) закрыты. Карта Минска (варианты слоёв / выбор контура) **вне этого среза** — отдельный подбор позже. Product locks: `docs/DECISIONS.md`, glossary: `docs/CONTEXT.md`.

## Problem

Локальный WordPress-контур уже несёт IA, сид, chrome и визуальный паритет с honest preview, но до test URL / sign-off остаются недоработки **оболочки и операций**, из‑за которых приёмка видит «ещё не институтский сайт» или блочит деплой:

- в DOM остаётся хвост Kadence (credit / скрытый `.site-footer`), рядом с институтским подвалом;
- в preview есть cookie-баннер и политика cookie; в WP — страницы политики есть, баннера/согласия как у preview нет;
- форма обратной связи на локали пишет в журнал, путь доставки на PHP-тарифе не зафиксирован;
- слоты пакета коллег готовы, но **приём файлов в витрины** (PDF документов, ростеры, таблица публикаций, вектор НАН, URL соцсетей ИХНМ) ещё не закрыт как рабочий конвейер;
- ночная тема: токены и часть карточек есть, полный night-pass по витринам не принят;
- veil / BVI / smoke row V — не доведены до приёмочной отметки;
- PHP-тариф, test URL, форс-синк seed **31**, push/бэкап — операционный хвост cutover (не Forever Java DNS).

## Solution

Закрыть срез **cutover polish**: добить chrome/legal UX до контура preview (без новой IA), зафиксировать доставку feedback на хостинге, подключить пакет коллег по мере поступления файлов, принять night + motion/BVI и smoke, выложить test URL на PHP-тарифе. Домен `ichnm.by` не переключать без sign-off. Карту Минска не трогать в этом срезе.

## User stories

1. As приёмщик, I want на WP не видеть «Kadence WP тема» и пустой/чужой chrome-хвост, so that сайт читается как институтский контур.
2. As посетитель, I want cookie-баннер и страницы политики как в preview (необходимые cookie: язык, тема, BVI), so that согласие согласовано с политикой.
3. As посетитель формы «Написать нам», I want сообщение уходить на институтский ящик на PHP-хостинге (локально — честный журнал), so that обратная связь рабочая к cutover.
4. As разработчик института, I want положить PDF/ростеры/таблицу в слоты и увидеть их на витринах без рыбы, so that пакет коллег закрывает empty-slot’ы к sign-off.
5. As посетитель night theme, I want карточки и полосы не оставаться «белыми островами», so that ночной режим консистентен.
6. As приёмщик, I want smoke (включая visual row V) и test URL на PHP-тарифе, so that cutover опирается на чеклист, а не на память чата.

## Implementation decisions

- Стек без смены: WordPress + child `ichnm-kadence` + `ichnm-site` + Polylang. Источник правды по IA/тексту — site model / migrated copy; сид `ICHNM_CONTENT_SEED_VERSION` (сейчас **31**).
- Chrome: полностью подавить Kadence footer/credit; институтский footer уже runtime PHP — не дублировать.
- Cookie: порт поведения preview (localStorage / cookie key `ichnm-cookies`, баннер, ссылки на cookies / personal-data). Аналитика v1 выключена.
- Feedback: shortcode уже есть; на хостинге — `wp_mail` на адрес института (или константа/option); локально оставить журнал. Не строить отдельный CRM.
- Пакет коллег: вход через `assets/incoming/` + чеклист; синк/attach в документы и CPT публикаций — идемпотентно, без коммита пустых PDF. Соцсети ИХНМ — только при непустых URL.
- Night: расширить поверхности (карточки, полосы, chrome scrolled) по токенам `html.theme-night`; не пиксельный клон IBOCH.
- Veil / BVI: уважать `prefers-reduced-motion` и активный BVI; плагин BVI остаётся классом IBOCH.
- Cutover ops: Docker → backup → PHP test URL → smoke → sign-off → только потом A/`www`. MX / `aist.ichnm.by` не трогать.

## Testing decisions (seams)

1. **Feedback delivery** — чистая функция/обработчик shortcode: вход POST → валидация → либо journal (local), либо `wp_mail` (host); pytest на валидацию без Docker; smoke POST на `/feedback/`.
2. **Colleague document attach** — seam «путь incoming → attachment/page body» для документов (charter / anti-corruption / e-appeals); тест: файл есть → страница не empty-slot; файла нет → честный stub.
3. **Cookie consent state** — JS-контракт: accept/reject пишет ключ; повторный визит без баннера; не seam: текст политики.
4. **Не шов этого среза:** геометрия карты Минска / выбор MKAD-зелени; DNS Forever; официальные переводы тел (post-v1); Polylang Pro share-slug.

Smoke: checklist row V + feedback + cookies page + documents после incoming; `./scripts/wp-smoke.sh` на test URL.

## Out of scope

- Карта Минска (варианты контура, МКАД, зелень) — отдельный подбор
- Официальные переводы институтских тел EN/BE/ZH (post-v1)
- Регистрация AIST / мини-сайт конференции
- Агрегация новостей НАН
- Смена CMS / Bitrix
- Пиксельный Customizer-клон IBOCH

## Notes

- Grill lock: продукт закрыт; остаются файлы коллег, PHP-доступ и полировка оболочки.
- Seed **32** включает colleague packet ingest + class hooks visual; после деплоя — `ichnm_sync_content(true)` при необходимости. После дропа в `assets/incoming/` — `ichnm_apply_colleague_packet()`.
- Предлагаемые швы (подтвердить перед тикетами): (1) feedback delivery, (2) colleague document attach, (3) cookie consent state.
