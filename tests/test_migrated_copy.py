"""Homepage and vitrine copy migrated from public ichnm.by."""

from __future__ import annotations

from src.core.preview import render_site_files
from src.core.site_model import load_site_model


def test_homepage_has_migrated_news_and_aist_not_3d():
    home = render_site_files(load_site_model())["index.html"]
    assert "СИНЮТИЧ" in home
    assert "21-23 октября 2025" in home
    assert "aist.ichnm.by" in home
    assert "3D печати" not in home
    assert "ABS-пластик" not in home


def test_contacts_and_leadership_from_old_site():
    files = render_site_files(load_site_model())
    contacts = files["contacts.html"]
    assert "Скорины," in contacts
    assert "36" in contacts
    assert "263-92-99" in contacts
    lead = files["leadership.html"]
    assert "Рогачёв" in lead or "Рогачев" in lead
    assert "Агабеков" in lead


def test_developments_holds_3d_printing():
    html = render_site_files(load_site_model())["developments.html"]
    assert "3D" in html
    assert "ABS" in html
    assert "поляроид" in html.lower() or "Поляроид" in html


def test_facilities_and_appeals_and_feedback():
    files = render_site_files(load_site_model())
    assert "Хромос" in files["facilities.html"]
    assert "ichnm@ichnm.by" in files["e-appeals.html"]
    assert 'mailto:' in files["feedback.html"]
    assert "lab-nano.html" in files
    assert "Наша команда" in files["lab-nano.html"]
