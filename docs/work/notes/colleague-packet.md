# Пакет коллег — приём и чеклист

Тонкий приём файлов к cutover / test URL. Слоты: `assets/incoming/` (см. README там). Пока файла нет — витрина остаётся честным empty-slot; **пустые PDF и пустые иконки не коммитим как контент**.

Источник правил: `docs/DECISIONS.md` (документы, публикации, эмблема НАН, соцсети ИХНМ).

Конвейер (ticket 33): `ichnm_apply_colleague_packet()` / полный `ichnm_sync_content(true)` читает слоты и обновляет витрины. Python-шов: `src/core/colleague_packet.py`.

## Чеклист

| # | Что нужно | Куда класть | Куда уедет после приёмки | Статус |
|---|-----------|-------------|--------------------------|--------|
| 1 | PDF **устава** | `assets/incoming/documents/` (`ustav.pdf` / `charter.pdf`) | Документы → Устав (полка `is-filled`) | ☐ ждём файл → sync |
| 2 | PDF **антикоррупции** | `assets/incoming/documents/` (`anticorruption.pdf`) | Документы → Антикоррупция | ☐ ждём файл → sync |
| 3 | PDF / текст **электронных обращений** | `assets/incoming/documents/` (`e-appeals.pdf`) | Документы → Электронные обращения | ☐ ждём файл → sync |
| 4 | **Ростеры** лабораторий (полные составы) | `assets/incoming/rosters/` | люди / `people/{id}/`, вкладки лабораторий | ☐ ждём файл → см. путь ниже |
| 5 | **Таблица публикаций** (csv/json; xlsx→csv) | `assets/incoming/publications/` | CPT `publication`, хаб `/publications/` | ☐ ждём файл → sync |
| 6 | **Вектор эмблемы НАН** (SVG предпочтительно) | `assets/incoming/brand/` | тема `assets/nas-emblem.svg` + шапка | ☐ ждём файл → sync |
| 7 | **URL соцсетей ИХНМ** | `assets/incoming/social/urls.txt` *или* письмом | footer (`ichnm_effective_icnm_social`) | ☐ ждём файл → sync |

Реквизиты — под Контакты, не в Документы. Марка ИХНМ уже в `assets/brand/` (`ichnm-mark.svg`); новый AI/SVG класть сюда только если замена.

## После кладём файл

```bash
docker compose run --rm wpcli eval 'ichnm_apply_colleague_packet();'
# или полный ресид:
docker compose run --rm wpcli eval 'ichnm_sync_content(true);'
```

Затем отметить строку выше ☑ и проверить витрину (документы / публикации / подвал / шапка).

### Ростеры → people / labs (когда данные есть)

Автоимпорт xlsx в CPT людей v1 не делается (разные форматы коллег). Путь:

1. Положить `roster-nano.csv` (или сводную) в `assets/incoming/rosters/`.
2. Sync зафиксирует имена в опции `ichnm_incoming_rosters`.
3. Перенести строки в `src/core/migrated_copy.json`: `people[]` + `labs[].staff` (id, name, role, affiliations).
4. `ichnm_sync_content(true)` — люди и вкладки лабораторий обновятся штатным импортом.
5. Опционально оставить csv в incoming как архив приёма.

### Публикации

Колонки csv/json: `cite` **или** `authors` / `title` / `journal` / `year`; `laboratory` (или `lab_id`); `doi` опционально. Без person-URL. Повторный sync идемпотентен (`_ichnm_source_slug` = `incoming-pub-…`). Стартовый scraped каталог по-прежнему из `publications_items` в migrated_copy.

## Имена файлов (рекомендация)

Документы:

- `ustav.pdf` / `charter.pdf`
- `anticorruption.pdf`
- `e-appeals.pdf` (или согласованный HTML/текст вместо PDF)

Ростеры — один файл на лабораторию или одна сводная таблица:

- `roster-nano.*` — микро- и наноструктурированные системы  
- `roster-films.*` — оптические многофункциональные плёнки  
- `roster-lcd.*` — материалы и технологии ЖК-устройств  
- `roster-composites.*` — термостойкие полимерные композиты  
- `roster-woodchem.*` — лесохимия  

Соцсети ИХНМ — по строке `network URL`, например:

```
facebook https://…
vk https://…
telegram https://…
instagram https://…
youtube https://…
```

Только сети с реальным URL. Пустые строки и сети без URL не попадают в подвал (DECISIONS: нет пустых иконок).

## Правила приёмки

1. Не коммитить нулевые/заглушечные PDF «чтобы слот выглядел заполненным».
2. Не рисовать иконки соцсетей ИХНМ без `href` (фильтруется в модели, incoming-парсере и теме).
3. После дропа: sync → отметить строку в таблице выше.
4. Пакет не блокирует local / test URL; блокирует только день переключения домена, если институт так решит.
