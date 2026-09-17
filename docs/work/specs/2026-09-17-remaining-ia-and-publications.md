# Оставшиеся витрины IA + стартовые публикации

Status: done

Tracker is local markdown (`docs/work/specs/`, `docs/work/tickets/`).

Follows `docs/work/specs/2026-09-17-wordpress-cutover-readiness.md` (done). Product locks: `docs/DECISIONS.md` / `docs/CONTEXT.md`.

## Problem

Контур WordPress уже закрывает cutover readiness (пакеты лабораторий/подразделений, каталоги наука/разработки/приборы, person metrics, i18n-хабы, роль редактора, smoke/handoff). До приёмки test URL всё ещё «тонкие» или stub-only разделы меню: **научно-ориентированное образование** и дети, учёный совет, документы/вакансии, профсоюз и СМУ, карта сотрудничества; каталог **публикаций** живёт на mock/lab rows и пустом хабе. Пакет коллег (PDF, полные составы, вектор НАН, соцсети ИХНМ) ещё не пришёл — нужны готовые слоты приёма. DNS и Forever в этом срезе не трогаем.

## Solution

Закрыть срез **remaining IA + publications bootstrap**:

1. Довести оставшиеся витрины IA до honest contour из `migrated_copy` и того, что реально есть на старом `ichnm.by` (без рыбного preview-filled).
2. Вытащить **стартовый список публикаций** со старого сайта (где страницы/списки ещё открываются), привести к locked shape (GOST-строка или authors/title/journal/year + optional DOI + laboratory), засеять CPT `publication` и хаб.
3. Подготовить **тонкий приём пакета коллег**: чеклист + файловые слоты (PDF документов, ростеры, таблица публикаций, вектор НАН, URL соцсетей) без блокировки на отсутствие файлов.
4. **Не** заказывать PHP-тариф, не менять DNS, не выключать Java Forever.

## User stories

1. As посетитель, I want открыть образование / учёный совет / документы / вакансии / профсоюз / СМУ / сотрудничество и увидеть честный институтский текст или честный пустой слот, so that меню не ведёт в пустые оболочки.
2. As научный партнёр, I want на `/publications/` видеть стартовый каталог с DOI и лабораторией (без ссылок на людей), so that публикации читаются по правилам v1.
3. As разработчик от института, I want чеклист и слоты под PDF/составы/вектор/соцсети, so that пакет коллег кладётся без новой спеки.
4. As приёмщик, I want эти разделы в smoke на localhost / будущем test URL, so that cutover checklist покрывает весь top-menu контур.

## Implementation decisions

- Источник правды по тексту: `migrated_copy` + точечный перенос со старого `ichnm.by`, wording близко к оригиналу (DECISIONS).
- Публикации: shape из DECISIONS — cite (GOST или authors/title/journal/year), optional `https://doi.org/…`, laboratory; **без** person hyperlinks. Стартовый список — то, что удаётся снять со старого сайта; дыры остаются честными; импортёр готов и к будущей таблице коллег.
- График годов публикаций: остаётся mock, пока нет официальной ежегодной статистики (уже в copy).
- Слоты коллег: каталог вроде `assets/incoming/` (или расширение существующих `assets/`) + короткий `docs/work/notes/colleague-packet.md`; пустые файлы не коммитить как «контент».
- Стек без смены: WordPress + Kadence child + `ichnm-site` + Polylang. Seed-version bump при изменении сида.
- DNS / домен / Forever / PHP-тариф — **out of this slice** (handoff уже есть).

## Testing decisions (seams)

1. **Publication record** (`src/core`): нормализация записи (обязательные поля cite/lab, optional DOI URL) + парсер/фикстура «сырой фрагмент со старого сайта → записи»; pytest без Docker.
2. **IA completeness** (`src/core` site model / copy): наличие страниц education-children, documents, vacancies, council, union, young-scientists, cooperation в модели и непустые paragraphs *или* явный empty-slot маркер; запрет рыбы из preview-filled.
3. **Не шов:** живой DNS, заказ хостинга, полный пакет коллег на диске, пиксельный IBOCH, официальные переводы тел.

Smoke: расширить чеклист путями образования, council, publications, documents, vacancies, union, cooperation.

## Out of scope

- Переключение `ichnm.by`, PHP-тариф, разговор с Forever
- Официальные переводы EN/BE/ZH тел
- Регистрация AIST / мини-сайт конференции
- Обязательное наличие всех PDF до test URL (слоты да; файлы — когда пришлют)
- Polylang Pro share-slug

## Notes

- Grill lock 2026-09-17: цель = витрины IA (**A**) + тонкий приём пакета (**B**); публикации = вытащить со старого ichnm.by (**2**); DNS не трогаем.
- Glossary: витринная страница, пакет лаборатории, персональная страница, официальная замена.
- После approve тикетов — работать frontier с `implement`.
