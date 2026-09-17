# WordPress ↔ honest preview: паритет витрин и chrome

Status: done

Tracker is local markdown (`docs/work/specs/`, `docs/work/tickets/`).

Follows done slices: cutover readiness (`2026-09-17-wordpress-cutover-readiness.md`) and remaining IA + publications (`2026-09-17-remaining-ia-and-publications.md`). Product locks: `docs/DECISIONS.md` / `docs/CONTEXT.md`.

## Problem

Локальный WordPress уже закрывает IA-контур и сид контента, но **honest preview** (`src/core/preview.py` → `preview/`) всё ещё богаче по ряду витрин и поведению chrome. Аудит 2026-09-17 на `:8080` показал крупные дыры (отдельные страницы каталогов, блок направлений на главной, связность архива конференций) и расхождения chrome/карт. Часть chrome уже чинили в той же сессии (меню по имени, lattice, overflow карточек на карте мира); **карта Минска после правки регрессировала** (по центру, ломает вёрстку, занимает всю ширину/высоту) — это входит в срез как обязательный fix, без пиксельного клона IBOCH.

## Solution

Закрыть срез **preview parity (vitrines + chrome maps)**:

1. Отдельные страницы элементов каталогов science / developments / facilities (как в preview), со ссылками с хабов.
2. На главной — блок «основные направления» (`dir-grid` / эквивалент) и устойчивый hero lattice (атомы), не ломая BVI / reduced-motion.
3. Связать `/aist/` и `/events/` с уже существующими `/conferences/{slug}/`.
4. Починить chrome/карты: шапка (меню + выравнивание tool-кнопок), карта Минска (inline SVG, нормальный размер как в preview, не full-bleed), карточки на карте мира без клипа за край.
5. Опционально в том же срезе или хвостом: редиректы legacy-алиасов preview; отдельная `media_about` не обязательна (СМИ остаются в news hub по DECISIONS).

DNS / Forever / PHP-тариф — **out of scope**.

## User stories

1. As посетитель каталогов, I want открыть карточку направления / разработки / прибора на своей странице с контактами и ссылкой на lab/person, so that хаб не единственная точка входа (как в preview).
2. As посетитель главной, I want видеть полосу основных направлений и живую lattice в hero, so that ритм ближе к honest preview.
3. As участник AIST, I want с раздела AIST и мероприятий переходить на страницы конкретных конференций с файлами, so that архив не только inline-список.
4. As приёмщик, I want шапку с разделами Об институте / Новости / Мероприятия / Контакты и кнопками в ритме preview, so that навигация читается сразу.
5. As посетитель контактов и сотрудничества, I want карту Минска нормального размера и hover-карточки партнёров на карте мира в пределах экрана, so that карты usable.

## Implementation decisions

- Источник правды по контуру страниц: honest preview builders + `migrated_copy` (не preview-filled).
- Detail pages каталогов: URL вида `/science/{slug}/`, `/developments/{slug}/`, `/facilities/{slug}/`; контент — lead, фото-слот (честный empty), contacts / lab / staff links, spec или product; хаб-карточки — ссылки на detail (не только `id=` якоря).
- Главная: сохранить текущие блоки новостей / structure / developments / next event; **добавить** полосу направлений как в preview; lattice — canvas в hero, скрипт темы, off при BVI и `prefers-reduced-motion`.
- Конференции: страницы CPT/page уже могут существовать; обеспечить ссылки с AIST hub и events archive; не дублировать регистрацию (остаётся aist.ichnm.by).
- Меню: рендер по имени «Главное меню ИХНМ» (не полагаться только на `has_nav_menu` / Polylang locations).
- Карта Минска: shortcode / inline SVG с CSS-заливками (ocean/land/water); геометрия и max-width как city-map в preview (~aspect 8/5, ограниченная ширина); **не** растягивать на всю страницу. Регрессия «по центру / на всё место» — acceptance blocker.
- Карта мира: `overflow` не клипает pop; пины в верхней половине открывают карточку вниз (`data-pop=below` или эквивалент).
- Стек без смены. Seed bump при изменении сида страниц.

## Testing decisions (seams)

1. **Catalogue detail contract** (`src/core`): slug → обязательные поля/якоря detail (title, lab link when known, no fish); pytest без Docker.
2. **Chrome / maps smoke**: ручной или `wp-smoke` пути `/`, `/contacts/`, `/cooperation/`, один science detail, один conference; чеклист: меню top-level виден, minsk figure не full-viewport, coop pop visible on hover.
3. **Не шов:** пиксельный IBOCH, DNS, пакет коллег PDF, полные EN/BE/ZH тела detail.

## Out of scope

- Переключение домена, Forever, заказ PHP-тарифа
- Официальные переводы тел
- Обязательные PDF документов
- `lattice-demo.html` как публичная страница
- Пиксельный клон iboch.by Customizer

## Notes

- Аудит-якорь (2026-09-17): крупные дыры — detail каталогов (~53 в preview), отсутствие `dir-grid` на главной, отсутствие lattice, AIST/events без ссылок на `/conferences/{slug}/` при том что страницы конференций на WP уже отвечают 200.
- Закрыто тикетами 15–22. Авторитетный сид: `ICHNM_CONTENT_SEED_VERSION = 30`. Карта Минска починена в тикете 20 (city-map frame, не full-bleed).
- Glossary: витринная страница, пакет лаборатории, официальная замена.
