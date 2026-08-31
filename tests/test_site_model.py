"""Site-model seam: invariants from docs/DECISIONS.md, not WordPress HTML."""

from __future__ import annotations

import pytest

from src.core.site_model import load_site_model


@pytest.fixture(scope="module")
def model():
    return load_site_model()


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
