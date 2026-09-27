"""Person-page affiliations and metrics contracts (tickets 03 / 59 / 60)."""

from __future__ import annotations

from src.core import roster
from src.core.person_page import (
    METRIC_FIELDS,
    METRIC_LABELS,
    affiliations_html,
    affiliations_items,
    filled_optional_sections,
    metric_fields,
    metrics_html,
    optional_sections_html,
    person_list_card_html,
    profile_href,
    visible_metric_networks,
)
from src.core.site_model import load_site_model


def _resolve(unit_id: str) -> tuple[str, str]:
    return roster.unit_link(unit_id, depth=0)


def test_metric_fields_locked_order_matches_site_model():
    model = load_site_model()
    assert metric_fields() == METRIC_FIELDS
    assert list(METRIC_FIELDS) == model.staff_metric_fields
    assert list(METRIC_LABELS) == list(METRIC_FIELDS)


def test_orcid_bare_id_becomes_profile_url():
    assert profile_href("orcid", "0000-0001-6505-3929") == (
        "https://orcid.org/0000-0001-6505-3929"
    )
    assert profile_href("google_scholar", "https://scholar.google.com/x") == (
        "https://scholar.google.com/x"
    )
    assert profile_href("google_scholar", "bare-id") == ""


def test_empty_networks_and_orphan_numbers_are_skipped():
    person = {
        "profiles": {
            "researchgate": "https://www.researchgate.net/profile/Example",
            "orcid": "https://orcid.org/0000-0001-6505-3929",
        },
        "bibliometrics": {
            # Orphan numbers without a profile URL must not render (ticket 59).
            "scopus_author": {"h_index": 12, "citations": 400},
        },
    }
    rows = visible_metric_networks(person)
    assert [r["field"] for r in rows] == ["orcid", "researchgate"]
    assert "scopus_author" not in [r["field"] for r in rows]


def test_metrics_html_links_label_and_omits_empty():
    person = {
        "orcid": "0000-0001-6505-3929",
        "profiles": {"elibrary": "https://elibrary.ru/author_profile.asp?id=1"},
        "bibliometrics": {"elibrary": {"h_index": 3}},
    }
    html = metrics_html(person)
    assert "Наукометрия" in html
    assert "ORCID" in html
    assert "orcid.org/0000-0001-6505-3929" in html
    assert "eLIBRARY / РИНЦ" in html
    assert "h-индекс: 3" in html
    assert "metrics-note" not in html
    assert "Профиль" not in html
    assert ">профиль<" not in html.lower()
    assert "Google Scholar" not in html
    assert "Scopus Author" not in html
    assert "ResearchGate" not in html
    pos_orcid = html.index("ORCID")
    pos_elib = html.index("eLIBRARY / РИНЦ")
    assert pos_orcid < pos_elib


def test_metrics_html_hides_when_empty_by_default():
    assert metrics_html({}) == ""
    assert "Профили и показатели ещё не указаны" in metrics_html(
        {}, include_empty_state=True
    )


def test_optional_sections_only_if_filled_no_lab_sync():
    person = {
        "awards": ["Медаль НАН"],
        "person_sections": {"interests": ["LbL", "биополимеры"]},
        "projects": [],
    }
    filled = filled_optional_sections(person)
    assert [s["key"] for s in filled] == ["awards", "interests"]
    html = optional_sections_html(person)
    assert "Награды" in html
    assert "Медаль НАН" in html
    assert "Исследовательские интересы" in html
    assert "Научные проекты" not in html


def test_multi_affiliations_link_to_units_and_labs():
    person = roster.person("rogachev")
    assert person is not None
    affs = person.get("affiliations") or []
    assert len(affs) >= 2
    items = affiliations_items(affs, _resolve)
    assert len(items) == len(affs)
    hrefs = [h for h, _ in items]
    assert any("/labs/nano/" in h or "labs/nano" in h for h in hrefs)
    assert any("leadership" in h for h in hrefs)
    html = affiliations_html(affs, _resolve)
    assert "Подразделения" in html
    assert "labs/nano" in html
    assert "leadership" in html
    assert "—" in html  # role separator


def test_person_list_card_is_whole_link_without_biography_caption():
    html = person_list_card_html(
        name="Рогачёв Александр Александрович",
        role="Директор",
        href="/people/rogachev/",
        degree="д.х.н.",
    )
    assert html.startswith('<a class="ichnm-person-card"')
    assert 'href="/people/rogachev/"' in html
    assert "Рогачёв" in html
    assert "Биография и публикации" not in html
    assert "биография" not in html.lower()
