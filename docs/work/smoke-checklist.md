# Smoke-приёмка WordPress (test URL)

Чеклист перед sign-off на переключение `ichnm.by`. **DNS/домен в этом шаге не трогать.** Старый Java Forever остаётся онлайн до приказа.

Базовый URL локально: `http://localhost:8080`. На PHP-хостинге подставьте test URL (`new.ichnm.by`, `….hoster.by` и т.п.).

Скриптовая проверка (нужен поднятый Docker или доступный BASE_URL):

```bash
./scripts/wp-smoke.sh
# или
BASE_URL=https://new.ichnm.by ./scripts/wp-smoke.sh
```

Ручная колонка — для руководства и визуальной приёмки (меню, карточки, язык).

---

## Обязательные пути

| # | Что смотреть | URL (относительно корня) | OK? | Заметки |
|---|----------------|--------------------------|-----|---------|
| 1 | Главная | `/` | ☐ | Ввод, смешанные новости/СМИ, вход в структуру/разработки, ближайшее событие; без 3D-промо |
| 2 | Lab pack | `/labs/nano/` (или другая лаборатория из Structure) | ☐ | Состав, проекты (действующие/завершённые), приборы/разработки по модели |
| 3 | Admin unit | `/accounting/` (или `/hr/`) | ☐ | Пакет подразделения, не пустая витрина |
| 4 | Person | `/people/agabekov/` (или другая персоналия) | ☐ | Все аффилиации; карточка ведёт сюда без отдельной «биографии» в меню |
| 5 | Новости | `/news/` | ☐ | Институт + СМИ о нас на одной ленте |
| 6 | Поиск | `/search/` | ☐ | People / units / facilities / developments |
| 7 | Sitemap | `/sitemap/` | ☐ | Утилита в подвале/шапке, не пункт верхнего меню |
| 8 | Feedback | `/feedback/` | ☐ | Форма обратной связи |
| 9 | EN shell | `/en/home-en/` и `/en/structure-en/` | ☐ | Структура EN; body института пока RU до официального перевода |
| 10 | Образование | `/education/` | ☐ | Хаб; дети в меню (аспирантура и др.) |
| 11 | Аспирантура | `/aspirantura/` | ☐ | Ключевой ребёнок education |
| 12 | Учёный совет | `/scientific-council/` | ☐ | Витрина + состав |
| 13 | Публикации | `/publications/` | ☐ | Стартовый каталог CPT; DOI + лаборатория |
| 14 | Документы | `/documents/` | ☐ | Устав / антикоррупция / эл. обращения — дети |
| 15 | Вакансии | `/vacancies/` | ☐ | Честный текст или empty-slot |
| 16 | Профсоюз | `/union/` | ☐ | Витрина в Structure |
| 17 | СМУ | `/young-scientists/` | ☐ | Совет молодых учёных |
| 18 | Сотрудничество | `/cooperation/` | ☐ | Карта мира; pop партнёра не клипается за край (hover) |
| 19 | Science detail | `/science/thin-films/` | ☐ | Lead, contacts; без fish; ссылка с хаба `/science/` |
| 20 | Developments detail | `/developments/immuno-spheres/` | ☐ | Lab + staff links; продукт / результат |
| 21 | Facilities detail | `/facilities/vaktime-plasma-lab/` | ☐ | Spec; lab attribution |
| 22 | Конференция AIST | `/conferences/aist-2025/` | ☐ | Материалы; ссылки с `/aist/` и `/events/` |
| 23 | Контакты | `/contacts/` | ☐ | Реквизиты + карта Минска: inline SVG, ~aspect 8/5, **не** full-viewport |

---

## Желательные доп. проверки (тикеты 01–05 + remaining IA + preview parity)

| # | Что | URL / действие | OK? |
|---|-----|----------------|-----|
| A | Каталог разработок | `/developments/` | ☐ |
| B | Матбаза | `/facilities/` | ☐ |
| C | Education children | `/doctorate/`, `/defense-council/`, `/internships/`, `/courses/` | ☐ |
| D | Documents children | `/charter/`, `/anti-corruption/`, `/e-appeals/` | ☐ |
| E | Person metrics | на странице персоналии: ORCID / Scholar и т.п. (плейсхолдеры допустимы) | ☐ |
| F | BE / ZH shell | `/be/home-be/`, `/zh/home-zh/` | ☐ |
| G | Языковой переключатель | на главной и Structure: RU ↔ EN ↔ BE ↔ ZH на эквивалентные страницы | ☐ |
| H | Feed-editor | войти как `ichnm_feed_editor`: ленты да, Appearance / витрины — нет | ☐ см. `docs/work/notes/feed-editor-role.md` |
| I | Главная: dir-grid | `/` | ☐ | Полоса «основные направления» (`dir-grid` / эквивалент) |
| J | Главная: top menu | `/` | ☐ | Верхнее меню: Об институте / Новости / Мероприятия / Контакты |
| V | Visual pass (preview parity) | `/`, `/labs/nano/`, `/contacts/` side-by-side vs `preview/` | ☐ | Chrome + home bands + packs + footer grid/pictograms; not Kadence default strip. Spec: `docs/work/specs/2026-09-17-wp-visual-preview-parity.md` |
| M | Veil + BVI | `/` и любая внутренняя: OS reduced-motion **или** включённый BVI → нет navy wipe; кнопка BVI в шапке открывает панель плагина; меню и язык остаются доступны | ☐ | Ticket 32; veil off when `prefers-reduced-motion` / BVI-active |

### Заметки прогона (ticket 34, 2026-09-17)

- **HTTP smoke local:** `./scripts/wp-smoke.sh` vs `http://localhost:8080` — все обязательные и optional пути **200**. Seed option **32**.
- **Row V:** не закрыт как приёмка. Тикеты 28–32 закрыли chrome/cookie/night/veil на локали; финальный side-by-side vs `preview/` (`/`, `/labs/nano/`, `/contacts/`) — ручной gate на test URL (или локально до sign-off). Публичного PHP URL нет → remote smoke не запускался.
- **Бэкап:** `./scripts/wp-backup.sh` → `exports/wp-backup-…` (gitignored). См. handoff «Test URL — blocked / waiting on».

---

## После smoke

1. Снять бэкап: `./scripts/wp-backup.sh` → каталог в `exports/` (в git не коммитить).
2. Зафиксировать замечания (битые ссылки, пустые пакеты, язык).
3. Handoff и cutover: `docs/work/2026-09-02-hosting-handoff.md` (секция «Текущий контур 2026-09-17»).
4. Sign-off руководства → только тогда смена A/`www` на PHP-тариф.
