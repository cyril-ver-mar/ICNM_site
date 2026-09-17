"""Person-page affiliations and metrics contracts (ticket 03)."""

from __future__ import annotations

from src.core import roster
from src.core.person_page import (
    METRIC_FIELDS,
    METRIC_LABELS,
    affiliations_html,
    affiliations_items,
    metric_fields,
    metrics_html,
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


def test_empty_networks_are_skipped_order_preserved():
    person = {
        "profiles": {
            "researchgate": "https://www.researchgate.net/profile/Example",
            "orcid": "https://orcid.org/0000-0001-6505-3929",
        },
        "bibliometrics": {
            "scopus_author": {"h_index": 12, "citations": 400},
        },
    }
    rows = visible_metric_networks(person)
    assert [r["field"] for r in rows] == ["orcid", "scopus_author", "researchgate"]
    assert "google_scholar" not in [r["field"] for r in rows]
    assert "elibrary" not in [r["field"] for r in rows]


def test_metrics_html_omits_empty_and_shows_labels_in_order():
    person = {
        "orcid": "0000-0001-6505-3929",
        "bibliometrics": {"elibrary": {"h_index": 3}},
    }
    html = metrics_html(person)
    assert "Наукометрия" in html
    assert "ORCID" in html
    assert "orcid.org/0000-0001-6505-3929" in html
    assert "eLIBRARY / РИНЦ" in html
    assert "h-индекс 3" in html
    assert "Google Scholar" not in html
    assert "Scopus Author" not in html
    assert "ResearchGate" not in html
    pos_orcid = html.index("ORCID")
    pos_elib = html.index("eLIBRARY / РИНЦ")
    assert pos_orcid < pos_elib


def test_metrics_html_empty_state_when_no_data():
    html = metrics_html({})
    assert "Профили и показатели ещё не указаны" in html
    assert metrics_html({}, include_empty_state=False) == ""


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
