#!/usr/bin/env python3
"""Build Excel templates for institute staff data collection.

Requires: openpyxl (`pip install openpyxl` in the project venv).

Output: docs/work/colleague-packet/templates/*.xlsx
Regenerate: python scripts/build_colleague_templates.py
"""

from __future__ import annotations

from pathlib import Path

from openpyxl import Workbook
from openpyxl.styles import Alignment, Font, PatternFill
from openpyxl.utils import get_column_letter
from openpyxl.worksheet.datavalidation import DataValidation

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "docs" / "work" / "colleague-packet" / "templates"

LANG_WARN = (
    "Важно: заполняйте колонки RU / EN / BE / ZH. "
    "Если язык не заполнен, текст будет машинно переведён с русского "
    "(или с единственного заполненного языка). Проверьте машинный перевод перед публикацией."
)

HEADER_FILL = PatternFill("solid", fgColor="0F2A43")
HEADER_FONT = Font(color="FFFFFF", bold=True)
NOTE_FILL = PatternFill("solid", fgColor="FFF3CD")
NOTE_FONT = Font(bold=True, color="664D03")


def _style_header(ws, row: int = 1) -> None:
    for cell in ws[row]:
        cell.fill = HEADER_FILL
        cell.font = HEADER_FONT
        cell.alignment = Alignment(wrap_text=True, vertical="top")


def _autosize(ws, min_w: int = 14, max_w: int = 42) -> None:
    for col in ws.columns:
        letter = get_column_letter(col[0].column)
        width = min_w
        for cell in col:
            if cell.value:
                width = max(width, min(max_w, len(str(cell.value)) + 2))
        ws.column_dimensions[letter].width = width


def _memo_sheet(wb: Workbook, title: str, lines: list[str]) -> None:
    ws = wb.create_sheet("Памятка", 0)
    ws["A1"] = title
    ws["A1"].font = Font(bold=True, size=14)
    ws.merge_cells("A1:B1")
    ws["A3"] = LANG_WARN
    ws["A3"].fill = NOTE_FILL
    ws["A3"].font = NOTE_FONT
    ws["A3"].alignment = Alignment(wrap_text=True)
    ws.merge_cells("A3:B3")
    ws.row_dimensions[3].height = 48
    row = 5
    for line in lines:
        ws.cell(row, 1, line)
        ws.cell(row, 1).alignment = Alignment(wrap_text=True)
        ws.merge_cells(start_row=row, start_column=1, end_row=row, end_column=2)
        ws.row_dimensions[row].height = 36
        row += 1
    ws.column_dimensions["A"].width = 90
    ws.column_dimensions["B"].width = 20


def _data_sheet(wb: Workbook, headers: list[str], example: list[str] | None = None) -> None:
    ws = wb.create_sheet("Данные")
    ws.append(headers)
    _style_header(ws)
    if example:
        ws.append(example)
    for _ in range(8):
        ws.append([""] * len(headers))
    _autosize(ws)
    ws.freeze_panes = "A2"
    ws.auto_filter.ref = f"A1:{get_column_letter(len(headers))}1"


def _save(wb: Workbook, name: str) -> Path:
    OUT.mkdir(parents=True, exist_ok=True)
    path = OUT / name
    if "Памятка" in wb.sheetnames and "Данные" in wb.sheetnames:
        # Keep Памятка first, Данные second; drop default empty sheet if present.
        if "Sheet" in wb.sheetnames:
            del wb["Sheet"]
    wb.save(path)
    return path


def build_people() -> Path:
    wb = Workbook()
    _memo_sheet(
        wb,
        "Персоналии (people/{id}/)",
        [
            "Зачем: карточка сотрудника на сайте и в составах лабораторий / подразделений.",
            "Куда уйдёт: CPT «Персоналии», страница people/{id}/; карточки в руководствах, лабораториях, отделе кадров, профсоюзе.",
            "Как выглядит: фото, должность, контакты, подразделения (можно несколько), наукометрия (ORCID и др.), "
            "опциональные блоки (награды, публикации, интересы, проекты) — только если заполнены.",
            "id — короткий латиницей (rogachev, ivanova). affiliations: unit_id|роль; несколько через ; "
            "(lab-nano|Научный сотрудник; leadership|Директор).",
            "Метрики: URL профиля обязателен, если указываете h-индекс / цитирования. Без URL сеть на сайте не показывается.",
            "Фото: имя файла положите рядом в assets/incoming/rosters/photos/ (или приложите к письму).",
        ],
    )
    headers = [
        "id",
        "name_ru",
        "name_en",
        "name_be",
        "name_zh",
        "role_ru",
        "role_en",
        "role_be",
        "role_zh",
        "degree_ru",
        "degree_en",
        "degree_be",
        "degree_zh",
        "phone",
        "email",
        "affiliations",
        "bio_ru",
        "bio_en",
        "bio_be",
        "bio_zh",
        "orcid_url",
        "google_scholar_url",
        "scopus_author_url",
        "elibrary_url",
        "researchgate_url",
        "h_index_google_scholar",
        "citations_google_scholar",
        "h_index_scopus",
        "citations_scopus",
        "awards_ru",
        "awards_en",
        "publications_scientific_ru",
        "publications_methodical_ru",
        "interests_ru",
        "projects_ru",
        "photo_filename",
        "notes",
    ]
    example = [
        "ivanova",
        "Иванова Наталья Александровна",
        "Natalia Ivanova",
        "",
        "",
        "Заведующий лабораторией",
        "Head of laboratory",
        "",
        "",
        "к.х.н.",
        "PhD",
        "",
        "",
        "+375 (17) 000-00-00",
        "ivanova@ichnm.by",
        "lab-films|Заведующий лабораторией",
        "Кратко о научной биографии…",
        "",
        "",
        "",
        "https://orcid.org/0000-0000-0000-0000",
        "",
        "",
        "",
        "",
        "12",
        "340",
        "",
        "",
        "Грамота НАН (по одной в строке внутри ячейки — Alt+Enter)",
        "",
        "Статья 1; Статья 2",
        "",
        "Тонкие плёнки; поляроиды",
        "ГПНИ 2024–2026: …",
        "ivanova.jpg",
        "",
    ]
    _data_sheet(wb, headers, example)
    return _save(wb, "01-personalia.xlsx")


def build_labs() -> Path:
    wb = Workbook()
    _memo_sheet(
        wb,
        "Лаборатории (labs/{slug}/)",
        [
            "Зачем: пакет страницы лаборатории — о лаборатории, направления, проекты, оборудование, разработки, команда, публикации, контакты.",
            "Куда уйдёт: страница labs/{slug}/ и пункт в «Структура». Каталоги направлений / разработок / приборов подтягиваются по lab_slug.",
            "Как выглядит: локальное меню разделов; пустые блоки на сайте скрываются.",
            "slug: nano | films | lcd | composites | woodchem. head_id — id персоналии заведующего.",
            "Проекты — на листе «Проекты» (status: active|completed). Состав — через персоналии с affiliations на lab-*.",
        ],
    )
    _data_sheet(
        wb,
        [
            "slug",
            "title_ru",
            "title_en",
            "title_be",
            "title_zh",
            "kicker_ru",
            "about_ru",
            "about_en",
            "about_be",
            "about_zh",
            "head_id",
            "phone",
            "email",
            "notes",
        ],
        [
            "nano",
            "Лаборатория микро- и наноструктурированных систем",
            "",
            "",
            "",
            "Подразделение Института",
            "Текст «О лаборатории»…",
            "",
            "",
            "",
            "kulikouskaya",
            "+375 (17) …",
            "nano@ichnm.by",
            "",
        ],
    )
    ws = wb.create_sheet("Проекты")
    ws.append(
        [
            "lab_slug",
            "title_ru",
            "title_en",
            "title_be",
            "title_zh",
            "years",
            "status",
            "lead_ru",
            "lead_en",
        ]
    )
    _style_header(ws)
    ws.append(
        [
            "nano",
            "Название проекта (ГПНИ)",
            "",
            "",
            "",
            "2024–2026",
            "active",
            "Краткое описание",
            "",
        ]
    )
    dv = DataValidation(type="list", formula1='"active,completed"', allow_blank=True)
    ws.add_data_validation(dv)
    dv.add("G2:G200")
    _autosize(ws)
    return _save(wb, "02-laboratories.xlsx")


def build_developments() -> Path:
    wb = Workbook()
    _memo_sheet(
        wb,
        "Разработки (CPT development)",
        [
            "Зачем: карточка разработки в институтском каталоге и в пакете лаборатории («Услуги и разработки»).",
            "Куда уйдёт: CPT «Разработки», страница developments/{slug}/, плитки на /developments/ и в labs/{slug}/.",
            "Как выглядит: узкое фото, описание, теххарактеристики / продукт, лаборатория и закреплённый сотрудник, связь с направлением.",
            "lab_slug обязателен (nano|films|lcd|composites|woodchem). staff_id — id персоналии. direction_slug — опционально.",
        ],
    )
    _data_sheet(
        wb,
        [
            "slug",
            "title_ru",
            "title_en",
            "title_be",
            "title_zh",
            "lead_ru",
            "lead_en",
            "lead_be",
            "lead_zh",
            "description_ru",
            "product_or_spec_ru",
            "lab_slug",
            "staff_id",
            "direction_slug",
            "photo_filename",
            "notes",
        ],
        [
            "polaroid-films",
            "Поляроидные плёнки",
            "",
            "",
            "",
            "Краткий лид для плитки",
            "",
            "",
            "",
            "Развёрнутое описание…",
            "Технические характеристики / форма поставки…",
            "films",
            "ivanova",
            "thin-films",
            "polaroid.jpg",
            "",
        ],
    )
    return _save(wb, "03-developments.xlsx")


def build_directions() -> Path:
    wb = Workbook()
    _memo_sheet(
        wb,
        "Направления работы (CPT direction)",
        [
            "Зачем: позиции каталога «Направления работы» и блоки направлений в пакете лаборатории.",
            "Куда уйдёт: CPT «Направления», science/{slug}/, плитки на /science/, раздел #directions лаборатории.",
            "Как выглядит: карточка с атрибуцией лаборатории; на детальной странице — связанные разработки.",
            "scope: institute (общеинститутское) или lab. Для lab укажите lab_slug.",
        ],
    )
    _data_sheet(
        wb,
        [
            "slug",
            "title_ru",
            "title_en",
            "title_be",
            "title_zh",
            "lead_ru",
            "lead_en",
            "lead_be",
            "lead_zh",
            "description_ru",
            "scope",
            "lab_slug",
            "staff_id",
            "notes",
        ],
        [
            "thin-films",
            "Тонкоплёночные материалы",
            "",
            "",
            "",
            "Краткий лид",
            "",
            "",
            "",
            "Описание направления…",
            "lab",
            "films",
            "ivanova",
            "",
        ],
    )
    ws = wb["Данные"]
    dv = DataValidation(type="list", formula1='"institute,lab"', allow_blank=True)
    ws.add_data_validation(dv)
    dv.add("K2:K200")
    return _save(wb, "04-science-directions.xlsx")


def build_hr() -> Path:
    wb = Workbook()
    _memo_sheet(
        wb,
        "Отдел кадров (admin unit hr)",
        [
            "Зачем: страница подразделения «Отдел кадров» в Структуре и контакты для справок «Для сотрудника».",
            "Куда уйдёт: витрина /hr/, плитки сотрудников → people/{id}/.",
            "Как выглядит: телефон подразделения + карточки людей (фото, роль, контакты).",
            "Сначала заведите людей в 01-personalia.xlsx (affiliations: hr|…), здесь — состав и телефон отдела.",
        ],
    )
    ws = wb.create_sheet("Подразделение", 1)
    ws.append(["field", "value_ru", "value_en", "value_be", "value_zh"])
    _style_header(ws)
    for field, val in (
        ("unit_id", "hr"),
        ("title", "Отдел кадров"),
        ("phone", "+375 (17) 243-67-56"),
        ("email", ""),
        ("about", "Кратко о функциях отдела…"),
    ):
        ws.append([field, val, "", "", ""])
    _autosize(ws)
    _data_sheet(
        wb,
        [
            "person_id",
            "role_ru",
            "role_en",
            "role_be",
            "role_zh",
            "phone",
            "email",
            "notes",
        ],
        ["hr-head", "Начальник отдела кадров", "", "", "", "+375 (17) …", "", ""],
    )
    # Rename default Данные from helper — already created as sheet 2 via create order.
    # _data_sheet creates "Данные"; reorder if needed.
    if "Sheet" in wb.sheetnames:
        del wb["Sheet"]
    return _save(wb, "05-hr.xlsx")


def build_engineering_ot() -> Path:
    wb = Workbook()
    _memo_sheet(
        wb,
        "Главный инженер и охрана труда",
        [
            "Зачем: две admin-unit страницы в Структуре: «Главный инженер» (engineering) и «Охрана труда» (labor-protection).",
            "Куда уйдёт: /engineering/, /labor-protection/, карточки → people/{id}/.",
            "Как выглядит: как у отдела кадров — телефон + плитки сотрудников.",
            "Лист «Инженер» и «Охрана труда» — составы. Персоналии заведите в 01-personalia.xlsx.",
        ],
    )
    if "Sheet" in wb.sheetnames:
        del wb["Sheet"]
    for sheet_name, unit_id, title, example_id, example_role in (
        ("Инженер", "engineering", "Главный инженер", "tikhonov", "Главный инженер"),
        (
            "Охрана труда",
            "labor-protection",
            "Охрана труда",
            "ot-spec",
            "Специалист по охране труда",
        ),
    ):
        ws = wb.create_sheet(sheet_name)
        ws.append(
            [
                "unit_id",
                "unit_title_ru",
                "phone",
                "person_id",
                "role_ru",
                "role_en",
                "role_be",
                "role_zh",
                "phone_person",
                "email",
                "notes",
            ]
        )
        _style_header(ws)
        ws.append(
            [
                unit_id,
                title,
                "+375 (17) …",
                example_id,
                example_role,
                "",
                "",
                "",
                "",
                "",
                "",
            ]
        )
        for _ in range(5):
            ws.append([unit_id, title, "", "", "", "", "", "", "", "", ""])
        _autosize(ws)
    return _save(wb, "06-engineering-labor-protection.xlsx")


def build_union() -> Path:
    wb = Workbook()
    _memo_sheet(
        wb,
        "Профсоюз (первичная организация)",
        [
            "Зачем: страница первичной профсоюзной организации Института (не замена profnan.by).",
            "Куда уйдёт: /union/, плитки сотрудников профкома → people/{id}/; ссылка на коллективный договор в Документах.",
            "Как выглядит: краткий текст + карточки (председатель и члены).",
            "affiliations в персоналиях: union|Председатель профсоюза и т.п.",
        ],
    )
    _data_sheet(
        wb,
        [
            "person_id",
            "name_ru",
            "name_en",
            "name_be",
            "name_zh",
            "role_ru",
            "role_en",
            "role_be",
            "role_zh",
            "phone",
            "email",
            "photo_filename",
            "notes",
        ],
        [
            "union-chair",
            "Южик Любовь Ивановна",
            "",
            "",
            "",
            "Председатель профсоюза",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
        ],
    )
    ws = wb.create_sheet("Текст страницы")
    ws.append(["field", "value_ru", "value_en", "value_be", "value_zh"])
    _style_header(ws)
    ws.append(
        [
            "intro",
            "Первичная профсоюзная организация Института…",
            "",
            "",
            "",
        ]
    )
    ws.append(["plan_note", "План работы / контакты бюро…", "", "", ""])
    _autosize(ws)
    return _save(wb, "07-union.xlsx")


def main() -> None:
    paths = [
        build_people(),
        build_labs(),
        build_developments(),
        build_directions(),
        build_hr(),
        build_engineering_ot(),
        build_union(),
    ]
    for path in paths:
        print(f"Wrote {path.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
