# Product decisions

Locked in grilling, 2026-08-30. Detail and rationale live here. Agents: follow `.cursor/rules/project-decisions.mdc` and do not contradict this file.

## Mandate

- **v1** — Official replacement of the public institute site on `ichnm.by`, not a prototype or portfolio demo.
- **v1** — Primary audiences: NASB leadership / official visitors, and scientific partners. Secondary: industry, students/job-seekers, institute staff.

## Look and information architecture

- **v1** — Take visual language and IA from [iboch.by](https://iboch.by/), not a pixel clone.
- **v1** — Keep ICNM-specific sections: разработки, материальная база, AIST.
- **v1** — Top-level menu: Об институте (сведения, руководство, учёный совет, документы), Структура (лаборатории + административные подразделения), Научная деятельность, Разработки, Материальная база, Сотрудничество, Новости, Мероприятия, Вакансии, Контакты, база публикаций, образование/аспирантура (полный блок), профсоюз, совет молодых учёных, «СМИ о нас», форма обратной связи. No separate announcements feed. Footer also has webmail, URL filled after PHP hosting exists.
- **v1** — Laboratory pages are self-contained packs under `labs/{slug}/` on this site (own intro, staff tab, publications, contacts). They are **not** separate domains in v1. Staff stay on the lab page; no personal mini-sites. A pack can later be lifted to a subdomain without rewriting the IA.
- **v1** — Official pages of the **администрация** (director, deputies, honorary director) live under Об институте / Руководство: photo slot, biography, scientific interests, selected publications, metric links. These are institute vitrine pages, not personal mini-sites for laboratory staff.
- **v1** — Staff-tab metric links, in this order: ORCID, Google Scholar, Scopus Author, eLIBRARY/РИНЦ, ResearchGate. Omit a network if the URL is missing.
- **v1** — Education block includes аспирантура, докторантура, совет по защитам, стажировки и курсы.
- **v1** — AIST is a section on `ichnm.by`. For launch: basic facts plus a stub, not a full conference mini-site and not a registration form.
- **v1** — No 3D-printing promo on the homepage. That material lives only under Разработки.
- **v1** — NAS identity block (emblem, full legal name, link to [nasb.gov.by](https://nasb.gov.by/rus/index.php)).
- **v1** — Footer legal/government set is the **stable** Academy pack, not seasonal banners: President (`president.gov.by`), Council of Ministers (`government.by`), National Legal Internet Portal (`pravo.by`), newspaper «Навука» (`gazeta-navuka.by`), Academy union (`profnan.by`), NAS portal. Plus NAS social accounts and ICNM social accounts. Old [ichnm.by](https://ichnm.by/) has **no** social URLs; show ICNM icons only when the institute supplies them. Do not leave empty icon holes.
- **v1** — Homepage rhythm like IBOCH: short official intro, mixed news + «СМИ о нас», entries to structure and developments, next event. Achievements stay under Об институте.
- **v1** — Documents under Об институте: устав, антикоррупция, электронные обращения, реквизиты. Requisites also appear under Контакты.
- **post-v1** — Do not aggregate NASB news/events onto the institute site.

## Languages

- **v1** — Public content in Russian only.
- **v1** — URL prefixes exist from day one: `/`, `/en/`, `/be/`, `/zh/`. Header language switcher is visible. Non-Russian locales are short stubs.
- **post-v1** — Fill English, Belarusian, and Chinese.

## Content

- **v1** — Rebuild old [ichnm.by](https://ichnm.by/) pages into the new IA. Keep wording close to the current site; clean, do not rewrite from scratch.
- **v1** — Stubs only where the old site has no text — except AIST, which is allowed to launch as basic info + stub.
- **v1** — Cutover day: all in-menu sections except AIST must have real institute-supplied data (including publications catalogue, lab staff tabs, and the full education block).
- **v1** — Publications: institute supplies a starting list; staff keep adding records in WordPress after launch.
- **v1 (2026-08-31)** — No separate «Объявления» feed. Service notes go into news or the contact form. The same staff role publishes news, events, and «СМИ о нас».
- **v1 (2026-08-31)** — Homepage block «Актуальные новости и мероприятия» mixes institute news and «СМИ о нас» cards. Each news item opens on its own page (`news/{slug}/`).
- **v1 (2026-08-31)** — Contact form is a header action («Написать нам») as well as `/feedback.html` and Contacts.
- **v1 (2026-08-31)** — Science directions, developments, and facilities are card grids with a photo slot; click opens a detail page (description, contacts, product/spec). Dummy copy is labeled макет.
- **v1 (2026-08-31)** — Scientific council uses the same card pattern as Руководство (photo slot, role, contacts). Photo slots exist for all listed people (council, labs, admin units). Still no personal mini-sites for laboratory staff.
- **v1 (2026-08-31)** — Structure includes administrative units (отдел кадров, охрана труда, главный инженер, бухгалтерия) with named placeholders until rosters arrive. The structure dropdown lists laboratories above those units, visually separated. Staff lists live on each unit/lab page, not on the structure hub.
- **v1 (2026-08-31)** — Руководство is director, deputies, honorary director only (two-column cards). Reception is not a leadership block. Chief engineer and chief accountant sit under Структура.
- **v1 (2026-08-31)** — Cooperation page: schematic world map with partner pins and hover cards (photo slot + interaction note).
- **v1 (2026-08-31)** — Publications year chart: bars grow on first view; a moving-average polyline sits on the histogram. Series remains mock until official yearly counts exist.
- **v1** — Cutover content packet: the **institute developer/configurator** (this project’s builder) owns both the WordPress build and the institute-side setup (hosting, admin, roles, migration). Colleagues still supply source files (lab lists, PDFs, vectors, publication spreadsheet); there is no second named counterpart.

## Platform and launch

- **v1** — WordPress with the **Kadence** theme (same constructor as IBOCH). Do not build a second custom theme.
- **v1** — Visually-impaired mode in v1, via a plugin in the same class as IBOCH’s button.
- **v1** — Editors must not be able to break header, footer, or vitrine pages. The same staff role publishes news, events, and «СМИ о нас».
- **v1** — Domain stays `ichnm.by`.
- **v1** — Plan a **PHP tariff** at Active.by or Hoster.by. Do not wait for the current Java panel; do not serve WordPress from the Forever Java app.
- **v1** — Logos: institute will supply vector files (PDF/SVG/AI) for ICNM mark and NAS emblem. Test builds may crop from [ichnm.by](https://ichnm.by/) and [nasb.gov.by](https://nasb.gov.by/rus/index.php) until vectors arrive. Cutover should use the vectors.
- **v1** — Ship on a test URL first; switch the domain only after institute sign-off. Do not take the old site down before that.
- **v1** — Hosting panel / FTP / DNS access is unknown today; resolve before the test deploy.
- **fact (2026-08-30)** — Current `ichnm.by` DNS is Active.by (`ns1/ns2.activeby.net`), A record `178.172.235.199` in Hoster.by space, mail on `g-cloud.by`. The live site issues a `JSESSIONID` cookie (Java app), nginx 1.10.3, built by Forever.
- **fact (2026-08-30)** — [iboch.by](https://iboch.by/) is WordPress 7.1 + Kadence theme, PHP 8.3, DNS `hoster.by`, same IP as `iboch.bas-net.by` (`178.172.163.250`, Hoster.by). Old Joomla hostname still resolves there. BASNET sells PHP hosting, but IBOCH’s current public site is not on a distinct BASNET web farm.

## Open (not locked)

Operational, not product forks: ICNM social-profile URLs (none on the old site); PHP-тариф and hosting credentials; arrival of vector logos, document PDFs, lab rosters, union/SMU texts, and the starting publication list from colleagues.

- **v1 slice (2026-08-30)** — Build proceeds **without** colleague files and **without** PHP-тариф. Placeholders in the test contour. Those assets are dropped in later; they do not block the local WordPress scaffold.
