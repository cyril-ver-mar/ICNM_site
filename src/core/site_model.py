from __future__ import annotations

from dataclasses import dataclass
from functools import cached_property
from pathlib import Path
from typing import Any, Iterable
import json


@dataclass(frozen=True)
class Language:
    code: str
    prefix: str
    status: str


@dataclass(frozen=True)
class MenuItem:
    id: str
    title: str
    kind: str
    children: tuple["MenuItem", ...] = ()
    href: str = ""


@dataclass(frozen=True)
class FooterLink:
    title: str
    href: str
    mark: str = ""
    icon: str = ""


@dataclass(frozen=True)
class SocialProfile:
    network: str
    href: str
    label: str = ""


@dataclass(frozen=True)
class Page:
    id: str
    kind: str
    status: str


class SiteModel:
    """Public seam: information architecture for the official ICNM site."""

    def __init__(self, data: dict[str, Any]) -> None:
        self._data = data

    @cached_property
    def languages(self) -> tuple[Language, ...]:
        return tuple(Language(**row) for row in self._data["languages"])

    @cached_property
    def homepage_blocks(self) -> list[str]:
        return list(self._data["homepage"]["blocks"])

    @cached_property
    def homepage_forbidden(self) -> frozenset[str]:
        return frozenset(self._data["homepage"]["forbidden"])

    @cached_property
    def footer_legal_links(self) -> tuple[FooterLink, ...]:
        return tuple(FooterLink(**row) for row in self._data["footer"]["legal_links"])

    @cached_property
    def footer_pictograms(self) -> tuple[FooterLink, ...]:
        rows = self._data["footer"].get("pictograms") or []
        return tuple(FooterLink(**row) for row in rows)

    @cached_property
    def nas_social_profiles(self) -> list[SocialProfile]:
        return [SocialProfile(**row) for row in self._data["footer"]["nas_social"]]

    @cached_property
    def icnm_social_profiles(self) -> list[SocialProfile]:
        return [
            SocialProfile(**row)
            for row in self._data["footer"]["icnm_social"]
            if row.get("href")
        ]

    @cached_property
    def staff_metric_fields(self) -> list[str]:
        return list(self._data["staff"]["metric_fields"])

    @cached_property
    def staff_presentation(self) -> str:
        return str(self._data["staff"]["presentation"])

    @cached_property
    def editor_may_publish(self) -> tuple[str, ...]:
        return tuple(self._data["roles"]["editor_may_publish"])

    @cached_property
    def short_name(self) -> str:
        return str(self._data["identity"].get("short_name", "ИХНМ НАН Беларуси"))

    @cached_property
    def legal_name(self) -> str:
        return str(self._data["identity"]["legal_name"])

    @cached_property
    def nas_portal_href(self) -> str:
        return str(self._data["identity"]["nas_portal_href"])

    def menu_roots(self) -> tuple[MenuItem, ...]:
        return self._menu_roots()

    def top_menu_titles(self) -> list[str]:
        return [item.title for item in self.menu_roots()]

    def menu_item(self, item_id: str) -> MenuItem:
        found = self._find_menu(self._menu_roots(), item_id)
        if found is None:
            raise KeyError(item_id)
        return found

    def page(self, page_id: str) -> Page:
        for row in self._data["pages"]:
            if row["id"] == page_id:
                return Page(id=row["id"], kind=row["kind"], status=row["status"])
        raise KeyError(page_id)

    def post_type_ids(self) -> frozenset[str]:
        return frozenset(self._data["post_types"])

    def editor_may_edit(self, slug: str) -> bool:
        if slug in self._data["roles"]["editor_locked"]:
            return False
        try:
            page = self.page(slug)
        except KeyError:
            return slug in self.editor_may_publish
        return page.kind != "vitrine"

    def _menu_roots(self) -> tuple[MenuItem, ...]:
        return tuple(self._parse_menu(row) for row in self._data["menu"])

    def _parse_menu(self, row: dict[str, Any]) -> MenuItem:
        children = tuple(self._parse_menu(child) for child in row.get("children", []))
        return MenuItem(
            id=row["id"],
            title=row["title"],
            kind=row.get("kind", "vitrine"),
            children=children,
            href=str(row.get("href") or ""),
        )

    def _find_menu(self, items: Iterable[MenuItem], item_id: str) -> MenuItem | None:
        for item in items:
            if item.id == item_id:
                return item
            nested = self._find_menu(item.children, item_id)
            if nested is not None:
                return nested
        return None


def site_model_path() -> Path:
    return Path(__file__).resolve().parent / "site_model.json"


def load_site_model(path: Path | None = None) -> SiteModel:
    target = path or site_model_path()
    data = json.loads(target.read_text(encoding="utf-8"))
    return SiteModel(data)
