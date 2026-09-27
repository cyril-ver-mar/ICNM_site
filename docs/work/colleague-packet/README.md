# Шаблоны данных для коллег

Excel-файлы, которые заполняют сотрудники института. Потом разработчик переносит строки в `migrated_copy.json` / WP CPT.

## Файлы

| Файл | Содержание |
|------|------------|
| `templates/01-personalia.xlsx` | Персоналии (`people/{id}/`, метрики, опциональные секции) |
| `templates/02-laboratories.xlsx` | Лаборатории + лист проектов |
| `templates/03-developments.xlsx` | CPT «Разработки» |
| `templates/04-science-directions.xlsx` | CPT «Направления работы» |
| `templates/05-hr.xlsx` | Отдел кадров |
| `templates/06-engineering-labor-protection.xlsx` | Главный инженер + охрана труда |
| `templates/07-union.xlsx` | Профсоюз |

В каждом файле лист **«Памятка»** (зачем / куда на сайте / как выглядит) и таблицы с колонками **RU / EN / BE / ZH**.

**Если язык не заполнен — будет машинный перевод.** Это явно написано жёлтым блоком на листе «Памятка».

## Как заполнять

1. Откройте нужный `.xlsx` в Excel / LibreOffice / Numbers.
2. Прочитайте «Памятку».
3. Заполняйте лист «Данные» (и доп. листы, если есть). Русский — обязательный минимум.
4. Положите заполненный файл в `assets/incoming/rosters/` (или пришлите разработчику).
5. Фото — отдельными файлами с именами из колонки `photo_filename`.

PDF устава / антикоррупции / соцсети / эмблема НАН — по-прежнему через слоты `assets/incoming/` (см. `docs/work/notes/colleague-packet.md`).

## Пересобрать шаблоны

```bash
# из корня репозитория, в venv:
pip install -r requirements-dev.txt   # нужен openpyxl
python scripts/build_colleague_templates.py
```
