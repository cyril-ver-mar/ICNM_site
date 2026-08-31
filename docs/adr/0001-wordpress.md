# ADR 0001: WordPress as the public CMS

Date: 2026-08-30
Status: accepted

Context:

The new ICNM site must be the official replacement of `ichnm.by`. Staff will publish news and events; vitrine pages stay locked. Four language prefixes are required from day one. The visual and IA reference is [iboch.by](https://iboch.by/).

Alternatives considered: keep a static site with a tiny news admin; Bitrix or another CMS common in Belarus; stay on the current Forever Java stack.

The live ICNM site is a Java application (`JSESSIONID`). IBOCH’s current site is WordPress 7.1 with the Kadence theme on Hoster.by PHP. WordPress matches the reference institute, runs on ordinary Belarus shared hosting (PHP + MySQL), and gives non-programmers an admin they already know.

Decision:

Build the public site as WordPress. Prefer staying in the Active.by / Hoster.by circle so `ichnm.by` DNS and mail can stay put. If the current Java tariff cannot run PHP, order a PHP/WordPress account and switch the domain to that account at cutover — do not try to serve WordPress from the Java app.

Consequences:

Theme, plugins, and multilingual (Polylang or equivalent) become part of v1. The test site needs a PHP 8.x + MySQL tariff in the Active.by / Hoster.by circle; the current Java site stays up until cutover. Editors must be given a narrow role. Kadence is locked in ADR 0002.
