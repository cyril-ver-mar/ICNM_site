"""Site-model seam: invariants from docs/DECISIONS.md, not WordPress HTML."""

from __future__ import annotations

import pytest

from src.core.copy import load_migrated_copy
from src.core.site_model import load_site_model


@pytest.fixture(scope="module")
def model():
    return load_site_model()


@pytest.fixture(scope="module")
def copy_data():
    return load_migrated_copy()


def test_languages_have_four_prefixes_and_structure_locales(model):
    langs = {item.code: item for item in model.languages}
    assert set(langs) == {"ru", "en", "be", "zh"}
    assert langs["ru"].prefix == "/"
    assert langs["ru"].status == "filled"
    assert langs["en"].prefix == "/en/"
    assert langs["be"].prefix == "/be/"
    assert langs["zh"].prefix == "/zh/"
    assert langs["en"].status == langs["be"].status == langs["zh"].status == "structure"


def test_top_menu_covers_locked_information_architecture(model):
    roots = model.top_menu_titles()
    assert roots == [
        "Об институте",
        "Новости",
        "Мероприятия",
        "Контакты",
    ]
    about_ids = [child.id for child in model.menu_item("about").children]
    assert about_ids == [
        "about-overview",
        "leadership",
        "structure",
        "research",
        "scientific-council",
        "facilities",
        "documents",
        "vacancies",
    ]
    research = model.menu_item("research")
    assert research.kind == "folder"
    assert [child.id for child in research.children] == [
        "science",
        "developments",
        "cooperation",
        "publications",
        "education",
    ]
    assert model.menu_item("science").title == "Направления работы"
    assert model.menu_item("science").children == ()
    assert model.menu_item("education").title == "Научно-ориентированное образование"
    structure_ids = [child.id for child in model.menu_item("structure").children]
    assert structure_ids[-2:] == ["union", "young-scientists"]
    assert model.menu_item("news").children == ()
    assert [child.id for child in model.menu_item("contacts").children] == [
        "feedback",
        "requisites",
    ]


def test_about_and_education_children(model):
    about = model.menu_item("about")
    assert {"Сведения", "Руководство", "Учёный совет", "Документы", "Материальная база", "Вакансии", "Структура"} <= set(
        child.title for child in about.children
    )
    education = model.menu_item("education")
    assert {
        "Аспирантура",
        "Докторантура",
        "Совет по защитам",
        "Стажировки",
        "Курсы",
    } <= set(child.title for child in education.children)


def test_documents_set(model):
    docs = model.menu_item("documents")
    assert {
        "Устав",
        "Антикоррупция",
        "Электронные обращения",
    } <= set(child.title for child in docs.children)
    assert "Реквизиты" not in {child.title for child in docs.children}


def test_homepage_rhythm_forbids_3d_promo(model):
    assert model.homepage_blocks == [
        "official_intro",
        "news",
        "structure_entry",
        "developments_entry",
        "next_event",
    ]
    assert "3d_printing_promo" in model.homepage_forbidden
    assert "achievements_list" in model.homepage_forbidden


def test_footer_stable_legal_pack(model):
    hrefs = [link.href for link in model.footer_legal_links]
    assert hrefs == [
        "https://president.gov.by/",
        "https://www.government.by/",
        "https://www.pravo.by/",
        "https://gazeta-navuka.by/",
        "https://profnan.by/",
        "https://nasb.gov.by/rus/index.php",
    ]


def test_nas_socials_present_icnm_socials_have_no_empty_holes(model):
    assert model.nas_social_profiles
    assert all(profile.href for profile in model.nas_social_profiles)
    assert model.icnm_social_profiles == []
    marks = [link.mark for link in model.footer_pictograms]
    assert "ПР" in marks
    assert "НАН" in marks
    assert len(model.footer_pictograms) >= 12
    assert any(link.icon.endswith("president.gif") for link in model.footer_pictograms)


def test_staff_metrics_order(model):
    assert model.staff_metric_fields == [
        "orcid",
        "google_scholar",
        "scopus_author",
        "elibrary",
        "researchgate",
    ]


def test_editor_publishes_feeds_not_vitrine(model):
    assert set(model.editor_may_publish) == {
        "news",
        "event",
        "media_about",
        "publication",
    }
    for slug in ("header", "footer", "about", "structure", "developments"):
        assert not model.editor_may_edit(slug)


def test_aist_is_stub_vitrine_not_a_feed(model):
    aist = model.page("aist")
    assert aist.kind == "vitrine"
    assert aist.status == "stub"
    assert "aist" not in model.editor_may_publish


def test_personal_pages_with_multiple_affiliations(model):
    assert "person" in model.post_type_ids()
    assert model.staff_presentation == "person_page"


def test_identity_block(model):
    assert model.legal_name.startswith("Государственное научное учреждение")
    assert "Институт химии новых материалов" in model.legal_name
    assert model.nas_portal_href == "https://nasb.gov.by/rus/index.php"


def test_no_separate_announcements_feed(model):
    """DECISIONS 2026-08-31: announcements dropped; service notes go to news/feedback."""
    root_ids = [item.id for item in model.menu_roots()]
    assert "announcement" not in root_ids
    assert "Объявления" not in model.top_menu_titles()
    assert "announcement" not in model.post_type_ids()
    assert "announcement" not in model.editor_may_publish
    assert "announcement" in model.forbidden_feeds


def test_labs_admin_units_people_consistent_with_decisions(model, copy_data):
    """Labs under labs/{slug}/; admin units on structure; people may have many affiliations."""
    from src.core import roster

    labs = copy_data.get("labs") or []
    admin_units = copy_data.get("admin_units") or []
    people = copy_data.get("people") or []
    assert labs, "labs catalogue required"
    assert admin_units, "admin units required"
    assert people, "people catalogue required"

    structure_ids = [child.id for child in model.menu_item("structure").children]
    admin_ids = [str(unit["id"]) for unit in admin_units]
    assert structure_ids[: len(admin_ids)] == admin_ids
    assert structure_ids[-2:] == ["union", "young-scientists"]

    for lab in labs:
        assert lab.get("id")
        assert lab.get("slug"), f"lab {lab.get('id')} needs slug for labs/{{slug}}/"
        assert lab.get("title")

    catalogue = roster.all_people()
    multi = 0
    for person in catalogue.values():
        affs = person.get("affiliations") or []
        assert isinstance(affs, list)
        if len(affs) > 1:
            multi += 1
        for aff in affs:
            assert aff.get("unit_id"), f"{person.get('id')} affiliation missing unit_id"
    assert multi >= 1, "at least one person must belong to several units"

    for lab in labs:
        head = lab.get("head_id")
        if head:
            assert str(head) in catalogue, f"lab head {head} missing from people catalogue"
        for sid in lab.get("staff_ids") or []:
            assert str(sid) in catalogue, f"lab staff {sid} missing from people catalogue"

    assert model.staff_presentation == "person_page"
    assert "person" in model.post_type_ids()
    assert "department" in model.post_type_ids()


def test_catalogue_cards_keep_lab_and_staff_attribution():
    """Science / developments / facilities cards name lab + assigned staff (DECISIONS)."""
    from src.core import roster

    lab_science = [row for row in roster.science_catalog() if row.get("scope") == "lab"]
    assert lab_science, "lab directions feed the science catalogue"
    for row in lab_science:
        assert row.get("lab_id"), f"science row {row.get('slug')} missing lab_id"
        assert row.get("lab_title"), f"science row {row.get('slug')} missing lab_title"
        meta = roster.catalog_meta(row)
        assert str(row["lab_title"]) in meta

    lab_devs = [row for row in roster.developments_catalog() if row.get("scope") == "lab"]
    assert lab_devs, "lab developments feed the developments catalogue"
    attributed = 0
    for row in lab_devs:
        assert row.get("lab_id")
        meta = roster.catalog_meta(row)
        assert str(row.get("lab_title") or "") in meta
        staff_id = str(row.get("staff_id") or row.get("head_id") or "")
        if staff_id and roster.person_contact_line(staff_id):
            attributed += 1
            assert roster.person_contact_line(staff_id).split(" · ")[0] in meta
    assert attributed >= 1, "at least one development names assigned staff"

    lab_fac = [row for row in roster.facilities_catalog() if row.get("scope") == "lab"]
    assert lab_fac, "lab equipment feeds the facilities catalogue"
    for row in lab_fac:
        assert row.get("lab_id")
        meta = roster.catalog_meta(row)
        assert str(row.get("lab_title") or "") in meta

    # Honest institute slots may omit lab_id; must not invent fish lab titles.
    for row in roster.facilities_catalog():
        if row.get("scope") == "institute" and not row.get("lab_id"):
            assert "lab_title" not in row or not row.get("lab_title")
