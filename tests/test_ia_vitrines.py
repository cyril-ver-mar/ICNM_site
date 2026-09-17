"""IA completeness for education / documents / vacancies (ticket 10)."""

from __future__ import annotations

from src.core.copy import disk_copy
from src.core.filled import filled_copy
from src.core.ia_vitrines import (
    DOCUMENTS_CHILD_IDS,
    EDUCATION_CHILD_IDS,
    EDUCATION_DOCUMENTS_VACANCIES_IDS,
    PAGE_FILE_SLOTS,
    VACANCIES_ID,
    honest_file_slots_html,
    page_has_honest_body,
    vitrine_body_is_fish_free,
)
from src.core.site_model import load_site_model


def test_model_exposes_education_documents_vacancies_tree():
    model = load_site_model()
    education = model.menu_item("education")
    assert [child.id for child in education.children] == list(EDUCATION_CHILD_IDS)
    documents = model.menu_item("documents")
    assert [child.id for child in documents.children] == list(DOCUMENTS_CHILD_IDS)
    assert "Реквизиты" not in {child.title for child in documents.children}
    assert model.menu_item(VACANCIES_ID).id == VACANCIES_ID
    assert model.menu_item("requisites").id == "requisites"
    contacts = model.menu_item("contacts")
    assert "requisites" in {child.id for child in contacts.children}


def test_honest_copy_has_body_or_empty_slot_for_education_docs_vacancies():
    pages = disk_copy().get("pages") or {}
    for page_id in EDUCATION_DOCUMENTS_VACANCIES_IDS:
        block = pages.get(page_id) or {}
        assert page_has_honest_body(block), f"{page_id} needs paragraphs or empty_slot"
        assert vitrine_body_is_fish_free(block), f"{page_id} must not carry preview-filled fish"


def test_pdf_slots_are_declared_for_document_and_education_pdf_pages():
    pages = disk_copy().get("pages") or {}
    for page_id, labels in PAGE_FILE_SLOTS.items():
        assert labels, f"{page_id} must declare at least one PDF slot label"
        block = pages.get(page_id) or {}
        assert list(block.get("file_slots") or []) == list(labels), (
            f"{page_id} file_slots in migrated_copy must match PAGE_FILE_SLOTS"
        )


def test_honest_file_slots_html_never_marks_filled_or_mock():
    html = honest_file_slots_html(["Устав ГНУ «ИХНМ НАН Беларуси».pdf"])
    assert "file-shelf" in html
    assert "file-slot" in html
    assert "Устав ГНУ" in html
    assert "Файл не загружен" in html
    assert "is-filled" not in html
    assert "Макет" not in html
    assert "mock" not in html.lower()


def test_vacancies_honest_copy_has_no_open_list_fish():
    honest = disk_copy()["pages"]["vacancies"]
    assert not (honest.get("list") or []), "honest vacancies must not invent open posts"
    assert page_has_honest_body(honest)
    filled = filled_copy()
    fish_list = filled["pages"]["vacancies"].get("list") or []
    assert fish_list and any("Макет" in str(item) for item in fish_list)
    assert not vitrine_body_is_fish_free(filled["pages"]["vacancies"])
