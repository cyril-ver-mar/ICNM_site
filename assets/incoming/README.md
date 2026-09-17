# Incoming — слоты пакета коллег

Сюда кладут файлы **до** переноса в постоянные места репозитория. Пока папка пустая (только `.gitkeep`) — так и должно быть.

Чеклист и правила: [`docs/work/notes/colleague-packet.md`](../../docs/work/notes/colleague-packet.md).

## Куда что

| Подкаталог | Содержимое | Не класть |
|------------|------------|-----------|
| `documents/` | PDF устава, антикоррупции, электронных обращений | реквизиты (они в Контактах); пустые PDF |
| `rosters/` | составы лабораторий (xlsx/csv/ods/docx по лаборатории или сводная) | рыба из `preview-filled/` |
| `publications/` | таблица публикаций **csv/json** (или xlsx→конвертировать) | записи без лаборатории; person-URL; пустые таблицы |
| `brand/` | вектор эмблемы НАН (лучше SVG; также PDF/AI) | подмена марки ИХНМ без согласования (`assets/brand/ichnm-mark.*` уже live) |
| `social/` | `urls.txt` со строками `network URL` для ИХНМ | сети без URL; «иконки на потом» |

Рекомендуемые имена документов: `ustav.pdf` / `charter.pdf`, `anticorruption.pdf`, `e-appeals.pdf`.

## После дропа файла (синк)

1. Положить файл в нужный слот (не коммитить нулевые PDF).
2. Запустить синк (любой из вариантов):
   - локально: `docker compose run --rm wpcli eval 'ichnm_sync_content(true);'`
   - или только пакет: `docker compose run --rm wpcli eval 'ichnm_apply_colleague_packet();'`
3. Что делает синк пакета (`ichnm_apply_colleague_packet`):
   - **documents/** — непустой PDF → полка `is-filled` + ссылка на файл на страницах Устав / Антикоррупция / Эл. обращения; нет файла → честный stub «Файл не загружен»
   - **publications/** — csv/json → идемпотентный upsert CPT `publication` (ключ `incoming-pub-…`); xlsx/ods нужно сначала сохранить как csv/json
   - **social/urls.txt** — только сети с URL попадают в подвал (пустые иконки не рисуются)
   - **brand/** — SVG НАН копируется в тему как `nas-emblem.svg` (шапка предпочитает SVG, иначе webp)
   - **rosters/** — файлы детектятся (опция `ichnm_incoming_rosters`); перенос в `people` / вкладки лаб — по шагам в colleague-packet.md
4. Отметить строку в чеклисте `colleague-packet.md`.
5. Исходник можно оставить здесь как архив приёма.

**Запрет:** коммитить пустые файлы «как будто контент есть». Слот = каталог + README; наполнение — только реальные материалы коллег.

Шов (pytest): `src/core/colleague_packet.py` · `tests/test_colleague_packet.py`. WP: `includes/colleague-packet.php`.
