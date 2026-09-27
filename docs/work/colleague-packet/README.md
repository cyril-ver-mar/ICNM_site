# Шаблоны данных для коллег

Word-файлы (`.docx`), которые заполняют сотрудники института. Потом разработчик переносит строки в `migrated_copy.json` / WP CPT.

**Живой макет (рыба):** https://cyril-ver-mar.github.io/ICNM_site/preview-filled/

**Письмо-рассылка (копия, исходник `docs/Mail.docx` не трогаем):** [`Mail-rasylka.docx`](Mail-rasylka.docx)

## Файлы

| Файл | Содержание |
|------|------------|
| `templates/01-personalia.docx` | Персоналии (`people/{id}/`, метрики, опциональные секции, **все** affiliations) |
| `templates/02-laboratories.docx` | Лаборатории + проекты |
| `templates/03-developments.docx` | CPT «Разработки» |
| `templates/04-science-directions.docx` | CPT «Направления работы» |
| `templates/05-hr.docx` | Отдел кадров |
| `templates/06-engineering-labor-protection.docx` | Главный инженер + охрана труда |
| `templates/07-union.docx` | Профсоюз |

В начале каждой **памятки**: скриншоты из `preview-filled` (`templates/_screens/`), предупреждение про **RU / EN / BE / ZH** (пустой язык → машинный перевод) и про **несколько ролей** одного человека. Дальше — справочник полей с русскими подписями и пустые блоки для заполнения.

Старые `.xlsx` больше не используются (Word — основной формат).

## Как заполнять

1. Откройте нужный `.docx` в Microsoft Word / LibreOffice / Pages.
2. Прочитайте памятку и посмотрите скриншоты.
3. Заполните блоки «Данные…». Русский — обязательный минимум.
4. Верните файл разработчику письмом **или** положите в `assets/incoming/rosters/`.
5. Фото — отдельными файлами; имя = поле «Имя файла фото» (латиницей).

PDF устава / антикоррупции / соцсети / эмблема НАН — по-прежнему через слоты `assets/incoming/` (см. `docs/work/notes/colleague-packet.md`).

## Пересобрать шаблоны и скриншоты

```bash
# из корня репозитория, в venv:
pip install -r requirements-dev.txt   # python-docx
python scripts/capture_colleague_screens.py   # Chrome headless → _screens/*.png
python scripts/build_colleague_templates.py   # → templates/*.docx
python scripts/build_colleague_mail.py        # → Mail-rasylka.docx (копия письма)
```
