"""Letter-coded dummy copy so a filled preview is readable by lab."""

from __future__ import annotations

from copy import deepcopy
from hashlib import sha1
import re
from typing import Any

from src.core.copy import disk_copy

LAB_LETTERS = {
    "lab-nano": "А",
    "lab-films": "Б",
    "lab-lcd": "В",
    "lab-composites": "Г",
    "lab-woodchem": "Д",
}

LETTER_COLORS = {
    "А": ("#0f5f8a", "#28a7ea", "#d7f1fb"),
    "Б": ("#1b6b4a", "#3dba86", "#ddf6ea"),
    "В": ("#4a2d7a", "#8b6cc9", "#efe7fb"),
    "Г": ("#7a4a12", "#d4a017", "#f8ecd0"),
    "Д": ("#7a1f2b", "#c45c5c", "#f8dfe2"),
    "И": ("#0f2a43", "#28a7ea", "#e8f4fb"),
}

_PACKS: dict[str, dict[str, Any]] = {
    "А": {
        "names": [
            ("Адамович Алина Андреевна", "АА", "к.х.н."),
            ("Алексеев Артём Алексеевич", "АЛ", "к.х.н."),
            ("Анисимова Анна Александровна", "АН", ""),
            ("Архипов Антон Анатольевич", "АР", "аспирант"),
            ("Афанасьева Алла Артёмовна", "АФ", ""),
        ],
        "head_role": "Заведующий лабораторией",
        "direction": (
            "Адресная доставка активных агентов",
            "Аэрогелевые и альгинатные носители, капсулирование аминокислот.",
        ),
        "developments": [
            (
                "Аэрогелевые адсорбенты «Астра»",
                "Альгинатные аэрогели для адресного высвобождения.",
            ),
            (
                "Антимикробные ампулы",
                "Ампульные формы с амфифильными оболочками.",
            ),
        ],
        "equipment": [
            (
                "Атомно-силовой анализатор «Аврора»",
                "Амплитудная атомная силовая микроскопия поверхностей.",
                "Атомная силовая микроскопия, амплитуда 10–80 нм.",
            ),
            (
                "Автоклав аналитический",
                "Автоклавная обработка альгинатных дисперсий.",
                "Автоклав, 2 л, 180 °C.",
            ),
        ],
        "pubs": [
            "Адамович А. А., Алексеев А. А. Анизотропия аэрогелей // Журнал «Аэрогель». 2024. Т. 1. С. 11–18.",
            "Анисимова А. А. Адресная адсорбция аминокислот // Альманах. 2023. № 4. С. 21–29.",
            "Архипов А. А. Амфифильные агрегаты // Acta. 2022. Т. 8. С. 3–10.",
        ],
        "projects": [
            (
                "Адресные аэрогели (ГПНИ)",
                "2024–2026",
                "active",
                "Альгинатные носители и капсулирование аминокислот.",
            ),
            (
                "Амфифильные агрегаты (БРФФИ)",
                "2020–2023",
                "completed",
                "Завершённый проект по амфифильным оболочкам.",
            ),
        ],
        "about": "Альгинатные и аэрогелевые системы лаборатории. Все ФИО, приборы и статьи этой лаборатории в наполненном макете начинаются на букву А.",
    },
    "Б": {
        "names": [
            ("Борисова Белла Борисовна", "ББ", "к.х.н."),
            ("Белов Борис Борисович", "БВ", "к.х.н."),
            ("Бондаренко Богдан Борисович", "БН", ""),
            ("Булатова Бэла Борисовна", "БЛ", "аспирант"),
            ("Баранов Борис Борисович", "БР", ""),
        ],
        "direction": (
            "Бриллиантовые и поляроидные плёнки",
            "Бесцветные и бронзовые поляроиды, бриллиантовый блеск слоёв.",
        ),
        "developments": [
            (
                "Бронзовые поляроиды",
                "Бромированные плёнки с повышенной термостойкостью.",
            ),
            (
                "Бумажные блистеры с поляроидом",
                "Защита бланков и банкнот.",
            ),
        ],
        "equipment": [
            (
                "Баня водяная «Бриз»",
                "Бережное осаждение мультислоёв.",
                "Баня 5 л, 20–90 °C.",
            ),
            (
                "Блескомер лабораторный",
                "Блеск и мутность плёнок.",
                "Геометрия 20/60/85°.",
            ),
        ],
        "pubs": [
            "Борисова Б. Б. Бриллиантовый блеск поляроидов // Плёнки. 2024. Т. 2. С. 14–22.",
            "Белов Б. Б. Бромированные слои Блоджетт // Бюллетень. 2023. № 6. С. 5–12.",
            "Бондаренко Б. Б. Бумажные блистеры // Банкноты. 2022. С. 9–16.",
        ],
        "projects": [
            (
                "Бронзовые поляроиды (ГПНИ)",
                "2025–2027",
                "active",
                "Бромированные слои с повышенной термостойкостью.",
            ),
            (
                "Бумажные блистеры (ГПНИ)",
                "2019–2022",
                "completed",
                "Завершённый цикл защиты бланков.",
            ),
        ],
        "about": "Поляроидные и бронзовые плёнки. В наполненном макете всё, что относится к этой лаборатории, начинается на букву Б.",
    },
    "В": {
        "names": [
            ("Васильева Вера Васильевна", "ВВ", "к.т.н."),
            ("Волков Виктор Викторович", "ВЛ", "к.т.н."),
            ("Виноградов Владислав Владимирович", "ВН", ""),
            ("Воронова Валерия Васильевна", "ВР", "аспирант"),
            ("Власов Василий Васильевич", "ВС", ""),
        ],
        "direction": (
            "Волноводные жидкокристаллические узлы",
            "Высокочастотные ячейки, волноводные ориентирующие слои.",
        ),
        "developments": [
            (
                "Волноводные ячейки «Вектор»",
                "Высококонтрастные ЖК-узлы для витрин.",
            ),
            (
                "Вязкостные воротники",
                "Вязкость ориентирующих лаков.",
            ),
        ],
        "equipment": [
            (
                "Вольтметр-импеданс «Волна»",
                "Высокочастотные импедансные спектры ЖК.",
                "0,1 Гц — 1 МГц.",
            ),
            (
                "Вискозиметр лабораторный",
                "Вязкость ориентирующих составов.",
                "Шпиндели 1–4.",
            ),
        ],
        "pubs": [
            "Васильева В. В. Волноводные ячейки ЖК // Вестник. 2024. Т. 3. С. 7–15.",
            "Волков В. В. Вязкость ориентирующих лаков // Жидкие кристаллы. 2023. № 2. С. 18–24.",
            "Виноградов В. В. Высокочастотный импеданс // Измерения. 2022. С. 4–11.",
        ],
        "projects": [
            (
                "Волноводные ячейки (ГПНИ)",
                "2024–2026",
                "active",
                "Высококонтрастные ЖК-узлы для витрин.",
            ),
            (
                "Вязкостные воротники (БРФФИ)",
                "2018–2021",
                "completed",
                "Завершённый проект по ориентирующим лакам.",
            ),
        ],
        "about": "Жидкокристаллические волноводные узлы. Рыба этой лаборатории — на букву В.",
    },
    "Г": {
        "names": [
            ("Громова Галина Георгиевна", "ГГ", "к.х.н."),
            ("Гусев Глеб Геннадьевич", "ГС", "к.х.н."),
            ("Гордеева Глафира Георгиевна", "ГР", ""),
            ("Грачёв Григорий Григорьевич", "ГЧ", "аспирант"),
            ("Голубев Герман Германович", "ГЛ", ""),
        ],
        "direction": (
            "Графеновые и гибридные композиты",
            "Горячее прессование, гидравлическая экструзия, графеновые наполнители.",
        ),
        "developments": [
            (
                "Гибрид «Графит-ПАНАНТ»",
                "Горячепрессованные карточки и детали.",
            ),
            (
                "Гранулы для 3D-печати",
                "Гранулированный композит конструкционного назначения.",
            ),
        ],
        "equipment": [
            (
                "Гидравлический пресс «Гранат»",
                "Горячее прессование композитных пластин.",
                "200 кН, 300 °C.",
            ),
            (
                "Галтовочный смеситель",
                "Гомогенизация графеновых паст.",
                "Барабан 3 л.",
            ),
        ],
        "pubs": [
            "Громова Г. Г. Гибридные композиты «Графит-ПАНАНТ» // Композиты. 2024. Т. 5. С. 12–20.",
            "Гусев Г. Г. Горячее прессование гранул // Полимеры. 2023. № 8. С. 6–13.",
            "Гордеева Г. Г. Графеновые наполнители // Гибриды. 2022. С. 2–9.",
        ],
        "projects": [
            (
                "Графеновые гибриды (ГПНИ)",
                "2023–2026",
                "active",
                "Горячее прессование графеновых наполнителей.",
            ),
            (
                "Гранулы «ПАНАНТ» (ГПНИ)",
                "2017–2020",
                "completed",
                "Завершённый цикл гранулированных композитов.",
            ),
        ],
        "about": "Гибридные термостойкие композиты. В наполненном макете лаборатория говорит на букву Г.",
    },
    "Д": {
        "names": [
            ("Дмитриева Дарья Дмитриевна", "ДД", "к.х.н."),
            ("Дорофеев Денис Денисович", "ДР", "к.х.н."),
            ("Данилова Диана Дмитриевна", "ДН", ""),
            ("Дубовик Данила Дмитриевич", "ДБ", "аспирант"),
            ("Демина Дарина Дмитриевна", "ДМ", ""),
        ],
        "direction": (
            "Древесные и дитерпеновые продукты",
            "Дистилляция канифоли, древесные отходы, биологически активные дитерпены.",
        ),
        "developments": [
            (
                "Древесная СОЖ «Дубрава»",
                "Дисперсии канифолемалеинового аддукта.",
            ),
            (
                "Дитерпеновые фунгициды",
                "Биоразлагаемые добавки на основе канифоли.",
            ),
        ],
        "equipment": [
            (
                "Дистиллятор «Двина»",
                "Дробная разгонка лесохимических фракций.",
                "Колонна 1 м, вакуум.",
            ),
            (
                "Детектор газовый",
                "Детектирование органических паров.",
                "ПИД, 400 °C.",
            ),
        ],
        "pubs": [
            "Дмитриева Д. Д. Дитерпены канифоли // Древесина. 2024. Т. 6. С. 8–16.",
            "Дорофеев Д. Д. Древесная СОЖ «Дубрава» // Смазки. 2023. № 3. С. 11–19.",
            "Данилова Д. Д. Дробная дистилляция скипидара // Лесохимия. 2022. С. 1–8.",
        ],
        "projects": [
            (
                "Дитерпены канифоли (ГПНИ)",
                "2024–2026",
                "active",
                "Биологически активные дитерпены из древесных отходов.",
            ),
            (
                "Древесная СОЖ «Дубрава» (ГПНИ)",
                "2018–2022",
                "completed",
                "Завершённый проект канифолемалеиновых дисперсий.",
            ),
        ],
        "about": "Лесохимия и дитерпены. Рыба этой лаборатории — на букву Д.",
    },
}


def letter_for_lab(lab_id: str) -> str:
    return LAB_LETTERS.get(lab_id, "И")


def filled_copy() -> dict[str, Any]:
    data = deepcopy(disk_copy())
    data["_filled"] = True
    people = list(data.get("people") or [])
    by_id = {str(row["id"]): row for row in people}

    for lab in data.get("labs") or []:
        letter = letter_for_lab(str(lab["id"]))
        pack = _PACKS[letter]
        lab["about_filled"] = pack["about"]
        keep_ids = [
            pid
            for pid in lab.get("staff_ids") or []
            if pid in by_id and "Фамилия" not in str(by_id[pid].get("name", ""))
        ]
        new_ids = []
        for index, (name, initials, degree) in enumerate(pack["names"]):
            pid = f"{lab['id']}-fish-{index}"
            role = "Научный сотрудник"
            if index == 0:
                role = "Ведущий научный сотрудник"
            if index == 3:
                role = "Аспирант"
            by_id[pid] = {
                "id": pid,
                "name": name,
                "role": role,
                "degree": degree,
                "initials": initials,
                "phone": f"+375 (17) 200-0{ord(letter) % 10}-{10 + index:02d}",
                "email": f"{pid.replace('lab-', '')}@ichnm.by",
                "bio": [
                    f"{name} — макетная персоналия лаборатории на букву {letter}.",
                    pack["about"],
                ],
                "interests": [pack["direction"][0], f"Аналитика на букву {letter}"],
                "publications": pack["pubs"][:2],
                "affiliations": [{"unit_id": lab["id"], "role": role}],
                "profiles": {
                    "orcid": f"https://orcid.org/0000-0002-{ord(letter):04d}-{index:04d}",
                    "google_scholar": (
                        f"https://scholar.google.com/citations?user=ICHNM{letter}{index}"
                    ),
                },
                "bibliometrics": {
                    "google_scholar": {
                        "h_index": 8 + (ord(letter) % 7) + index,
                        "citations": 80 + 35 * index,
                    },
                    "scopus_author": {
                        "h_index": 6 + (ord(letter) % 5) + index,
                        "citations": 40 + 20 * index,
                    },
                },
            }
            new_ids.append(pid)
        dummy_ids = [
            pid
            for pid in lab.get("staff_ids") or []
            if pid in by_id and "Фамилия" in str(by_id[pid].get("name", ""))
        ]
        lab["staff_ids"] = keep_ids + new_ids
        for pid in dummy_ids:
            by_id.pop(pid, None)

        extra_dirs = [
            {
                "slug": f"{lab.get('slug')}-dir-{letter.lower()}",
                "title": pack["direction"][0],
                "lead": pack["direction"][1],
            }
        ]
        lab["directions"] = list(lab.get("directions") or []) + extra_dirs
        for title, lead in pack["developments"]:
            lab.setdefault("developments", []).append(
                {
                    "slug": f"{lab.get('slug')}-{_slug(title)}",
                    "title": title,
                    "lead": lead,
                    "staff_id": new_ids[0] if new_ids else lab.get("head_id"),
                }
            )
        for title, lead, spec in pack["equipment"]:
            lab.setdefault("equipment", []).append(
                {
                    "slug": f"{lab.get('slug')}-{_slug(title)}",
                    "title": title,
                    "lead": lead,
                    "spec": spec,
                    "staff_id": new_ids[1] if len(new_ids) > 1 else lab.get("head_id"),
                }
            )
        for index, cite in enumerate(pack["pubs"], start=1):
            found = re.search(r"\b((?:19|20)\d{2})\b", cite)
            lab.setdefault("publications", []).append(
                {
                    "year": int(found.group(1)) if found else 2025 - index,
                    "cite": cite,
                    "doi": f"10.0000/ichnm.{lab.get('slug')}.{index}",
                }
            )
        for title, years, status, lead in pack.get("projects") or []:
            lab.setdefault("projects", []).append(
                {
                    "title": title,
                    "years": years,
                    "status": status,
                    "lead": lead,
                }
            )

    office_names = {
        "hr-head": ("Ершова Елена Евгеньевна", "ЕЕ", "Начальник отдела кадров"),
        "ot-spec": ("Жукова Жанна Жоржевна", "ЖЖ", "Специалист по охране труда"),
        "smu-chair": ("Ильина Инна Ивановна", "ИИ", "Председатель совета молодых учёных"),
        "smu-deputy": ("Климов Кирилл Кириллович", "КК", "Заместитель председателя"),
        "smu-secretary": ("Лебедева Лилия Львовна", "ЛЛ", "Секретарь"),
        "chair": ("Новиков Николай Николаевич", "НН", "Председатель учёного совета"),
        "member-1": ("Орлова Ольга Олеговна", "ОО", "Член совета"),
        "member-2": ("Петров Павел Павлович", "ПП", "Член совета"),
    }
    for unit in data.get("admin_units") or []:
        for person in unit.get("people") or []:
            _rename_dummy(person, office_names)
            if person.get("id") in by_id:
                _rename_dummy(by_id[str(person["id"])], office_names)
    for person in data.get("council_people") or []:
        _rename_dummy(person, office_names)
        if person.get("id") in by_id:
            _rename_dummy(by_id[str(person["id"])], office_names)
        if person.get("id") in office_names and person["id"] not in by_id:
            by_id[person["id"]] = {
                **person,
                "affiliations": [
                    {"unit_id": "scientific-council", "role": person.get("role") or ""}
                ],
            }
    for pid, (name, initials, role) in office_names.items():
        if pid.startswith("smu-"):
            by_id[pid] = {
                "id": pid,
                "name": name,
                "role": role,
                "initials": initials,
                "bio": [f"{name} — макетная персоналия совета молодых учёных."],
                "affiliations": [{"unit_id": "young-scientists", "role": role}],
            }

    data["people"] = list(by_id.values())
    pages = data.setdefault("pages", {})
    pages.setdefault("vacancies", {})["list"] = [
        "Макет: младший научный сотрудник лаборатории на букву А (альгинатные системы).",
        "Макет: инженер лаборатории на букву Г (гидравлический пресс).",
    ]
    return data


def _rename_dummy(person: dict[str, Any], mapping: dict[str, tuple[str, str, str]]) -> None:
    pid = str(person.get("id") or "")
    if pid in mapping and "Фамилия" in str(person.get("name", "")):
        name, initials, role = mapping[pid]
        person["name"] = name
        person["initials"] = initials
        person["role"] = role


def _slug(title: str) -> str:
    return sha1(title.encode("utf-8")).hexdigest()[:10]


def fish_svg(title: str, letter: str = "И", kind: str = "cover") -> str:
    ink, accent, paper = LETTER_COLORS.get(letter, LETTER_COLORS["И"])
    digest = sha1(f"{kind}:{title}".encode("utf-8")).hexdigest()
    c1 = int(digest[0:2], 16) % 80 + 10
    c2 = int(digest[2:4], 16) % 60 + 20
    c3 = int(digest[4:6], 16) % 50 + 25
    label = (title[:42] + "…") if len(title) > 42 else title
    if kind == "person":
        body = (
            f'<circle cx="80" cy="58" r="28" fill="{accent}"/>'
            f'<rect x="36" y="92" width="88" height="70" rx="36" fill="{ink}"/>'
            f'<text x="80" y="64" text-anchor="middle" fill="#fff" font-size="22" '
            f'font-family="Montserrat, sans-serif" font-weight="700">{letter}</text>'
        )
    elif kind == "equipment":
        body = (
            f'<rect x="28" y="36" width="104" height="72" rx="8" fill="{ink}"/>'
            f'<rect x="44" y="48" width="72" height="36" rx="4" fill="{paper}"/>'
            f'<circle cx="{40 + c1}" cy="128" r="10" fill="{accent}"/>'
            f'<circle cx="{70 + c2 // 2}" cy="128" r="10" fill="{accent}"/>'
        )
    elif kind == "document":
        body = (
            f'<rect x="42" y="24" width="76" height="100" rx="4" fill="#fff" stroke="{ink}" stroke-width="2"/>'
            f'<polygon points="118,24 118,48 94,24" fill="{accent}"/>'
            f'<rect x="54" y="64" width="52" height="6" fill="{ink}" opacity="0.35"/>'
            f'<rect x="54" y="78" width="40" height="6" fill="{ink}" opacity="0.25"/>'
            f'<text x="80" y="112" text-anchor="middle" fill="{accent}" font-size="18" '
            f'font-family="Montserrat, sans-serif" font-weight="700">{letter}</text>'
        )
    else:
        body = (
            f'<polygon points="{c1},20 150,{c2} 20,{100 + c3}" fill="{accent}" opacity="0.85"/>'
            f'<rect x="18" y="88" width="124" height="48" rx="6" fill="{ink}" opacity="0.88"/>'
        )
    safe = (
        label.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
    )
    return (
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 160" width="640" height="640">'
        f'<rect width="160" height="160" fill="{paper}"/>'
        f"{body}"
        f'<text x="8" y="152" fill="{ink}" font-size="7" font-family="Roboto, sans-serif">{safe}</text>'
        "</svg>\n"
    )
