# Product decisions

Locked in grilling, 2026-08-30. Detail and rationale live here. Agents: follow `.cursor/rules/project-decisions.mdc` and do not contradict this file.

## Mandate

- **v1** — Official replacement of the public institute site on `ichnm.by`, not a prototype or portfolio demo.
- **v1** — Primary audiences: NASB leadership / official visitors, and scientific partners. Secondary: industry, students/job-seekers, institute staff.

## Look and information architecture

- **v1** — Take visual language and IA from [iboch.by](https://iboch.by/), not a pixel clone.
- **v1** — Keep ICNM-specific sections: разработки, материальная база, AIST.
- **v1 (2026-08-31)** — Top-level menu: **Об институте**; Новости; Мероприятия; Контакты. **Обратная связь** and **реквизиты** sit under Контакты. **Об институте** dropdown order: сведения, руководство, **Структура** (labs, then admin units, then union and SMU — the structure *page* remains), folder **Научная деятельность** (no page of its own), учёный совет, материальная база, документы (устав, антикоррупция, электронные обращения), вакансии. That folder’s first child is **Направления работы** (`science.html`, former science hub), then разработки, сотрудничество, публикации, научно-ориентированное образование (аспирантура, докторантура, совет по защитам, стажировки, курсы). **Новости** is one page with two blocks (новости Института + СМИ о нас), not a dropdown. A parent *page* with children is itself a link; the dropdown repeats that page first (same title, no «страница раздела» caption). A `folder` parent (Научная деятельность) is a label only — no duplicate first row. Профсоюз и совет молодых учёных живут в выпадающем списке Структуры (после административных единиц, с визуальным разделителем). No separate announcements feed. Footer also has webmail, URL filled after PHP hosting exists. Search and sitemap stay header/footer utilities, not extra top-menu roots.
- **v1 (2026-08-31)** — Laboratory pages are self-contained packs under `labs/{slug}/` on this site. They are **not** separate domains in v1. Staff, directions, **scientific projects (current and completed)**, equipment, developments, and publications on a lab page feed the institute catalogues — except projects, which stay on the lab pack in v1 (no extra top-menu catalogue). A pack can later be lifted to a subdomain without rewriting the IA.
- **v1 (2026-08-31)** — Personal pages exist at `people/{id}/`. A person may belong to several units at once (director and lab researcher, учёный секретарь and council secretary, etc.). The person page lists every affiliation. Leadership, council, labs, and admin units all link to the same person record. The whole card is the hyperlink; no extra «Биография и публикации» caption. Bibliometrics live on the person page: optional h-index and citation counts per database, plus profile URLs. Locked URL order: ORCID, Google Scholar, Scopus Author, eLIBRARY/РИНЦ, ResearchGate. The site does not pull the databases. Omit a network if both the URL and the numbers are missing.
- **v1 (2026-08-31)** — Education block is titled **Научно-ориентированное образование** and sits under the **Научная деятельность** folder (itself under Об институте). Children: аспирантура, докторантура, совет по защитам, стажировки и курсы.
- **v1** — Staff metric **profile** links, in this order: ORCID, Google Scholar, Scopus Author, eLIBRARY/РИНЦ, ResearchGate. Numbers (h-index, citations) sit next to the matching profile on the person page. Omit a network if the URL and the numbers are both missing.
- **v1** — AIST is a section on `ichnm.by`. For launch: basic facts plus a stub, not a full conference mini-site and not a registration form.
- **fact (2026-09-01)** — Public AIST files (info letters, programmes, abstracts, proceedings, resolutions that still resolve on `aist.ichnm.by`) live in `assets/aist/` and on the AIST section. Registration stays on `aist.ichnm.by`. Skip 404s, dead tut.by hosts, photo albums, and whole-issue newspaper PDFs (link out).
- **fact (2026-09-01)** — Institute news already on the current `ichnm.by` (`c.eco?q=news_item…`) is copied as real text and photos into `migrated_copy.json` / `assets/news/`. Honest preview shows those files, not fish. «СМИ о нас» stays a short retelling plus a link to the publisher.
- **v1** — No 3D-printing promo on the homepage. That material lives only under Разработки.
- **v1** — NAS identity block (emblem, full legal name, link to [nasb.gov.by](https://nasb.gov.by/rus/index.php)).
- **v1** — Footer legal/government set is the **stable** Academy pack, not seasonal banners: President (`president.gov.by`), Council of Ministers (`government.by`), National Legal Internet Portal (`pravo.by`), newspaper «Навука» (`gazeta-navuka.by`), Academy union (`profnan.by`), NAS portal. Plus NAS social accounts and ICNM social accounts. Old [ichnm.by](https://ichnm.by/) has **no** social URLs; show ICNM icons only when the institute supplies them. Do not leave empty icon holes.
- **v1** — Homepage rhythm like IBOCH: short official intro, mixed news + «СМИ о нас», entries to structure and developments, next event. Achievements stay under Об институте.
- **v1 (2026-08-31)** — Documents under Об институте: устав, антикоррупция, электронные обращения. **Реквизиты** live under Контакты (not under Документы).
- **post-v1** — Do not aggregate NASB news/events onto the institute site.

- **v1 (2026-08-31)** — Homepage header on scroll uses the paper background and **ink** text (`--header-fg`), including night theme (night paper, not a white bar with leftover white type). Submenus and the mobile overlay stay navy with white type.

## Languages

- **v1 (2026-09-01)** — Site **structure** (IA, menus, chrome, page shells, laboratory names in the tree) is filled in four languages: Russian `/`, English `/en/`, Belarusian `/be/`, Chinese `/zh/`. Institute **copy** (news, biographies, scientific paragraphs, dummy filled texts) stays Russian until an official translation. The language switcher stays on the equivalent page.
- **post-v1** — Translate remaining institute texts into English, Belarusian, and Chinese.

## Content

- **v1** — Rebuild old [ichnm.by](https://ichnm.by/) pages into the new IA. Keep wording close to the current site; clean, do not rewrite from scratch.
- **v1** — Stubs only where the old site has no text — except AIST, which is allowed to launch as basic info + stub.
- **v1** — Cutover day: all in-menu sections except AIST must have real institute-supplied data (including publications catalogue, lab staff tabs, and the full education block).
- **v1** — Publications: institute supplies a starting list; the same staff role that publishes news also adds publication records in WordPress after launch (cite + DOI + laboratory).
- **v1 (2026-08-31)** — No separate «Объявления» feed. Service notes go into news or the contact form. The same staff role publishes news, events, «СМИ о нас», and publications.
- **v1 (2026-08-31)** — Homepage block «Актуальные новости и мероприятия» mixes institute news and «СМИ о нас» cards. Each news item opens on its own page (`news/{slug}/`).
- **v1 (2026-08-31)** — Contact form is a header action («Написать нам») as well as `/feedback.html` and Contacts.
- **v1 (2026-08-31)** — Science keeps one institute-wide block; lab **направления**, разработки, оборудование and публикации are collected onto the institute pages. Directions and instruments render in three columns on wide screens. Each aggregated science/developments/facilities card names the laboratory and the assigned staff / abbreviated head contacts. Facilities cards say which lab owns the instrument and who is assigned to it. **Publications** are different: one GOST cite line (or authors / title / journal / year), optional DOI (`https://doi.org/…`), and a laboratory — no person hyperlinks. Editors add records in WordPress with that shape after launch.
- **v1 (2026-08-31)** — Scientific council uses the same card pattern as Руководство (photo slot, role, contacts). Cards open the shared personal page.
- **v1 (2026-08-31)** — Structure includes administrative units (отдел кадров, охрана труда, главный инженер, бухгалтерия) plus профсоюз and совет молодых учёных (visual break after admin). Staff lists live on each unit/lab page and on personal pages, not on the structure hub.
- **v1 (2026-08-31)** — Руководство is director, deputies, honorary director, учёный секретарь and сотрудник приёмной (two-column cards). Chief engineer and chief accountant sit under Структура.
- **v1 (2026-08-31)** — Cooperation page: Natural Earth country outlines in theme colors (white land / light ocean by day) with partner pins and hover cards (photo slot + interaction note). Not a watermarked political atlas. Contacts and the footer use the same language for **Minsk**: simplified city outline, Svisloch and reservoirs in the ocean colour, one pin at ул. Ф. Скорины, 36. Not an OSM/Google screenshot.
- **v1 (2026-08-31)** — Publications year chart: bars grow on first view; a moving-average polyline sits on the histogram. Series remains mock until official yearly counts exist.
- **v1** — Cutover content packet: the **institute developer/configurator** (this project’s builder) owns both the WordPress build and the institute-side setup (hosting, admin, roles, migration). Colleagues still supply source files (lab lists, PDFs, vectors, publication spreadsheet); there is no second named counterpart.

## Platform and launch

- **v1** — WordPress with the **Kadence** theme (same constructor as IBOCH). Do not build a second custom theme.
- **v1** — Visually-impaired mode in v1, via a plugin in the same class as IBOCH’s button.
- **v1** — Editors must not be able to break header, footer, or vitrine pages. The same staff role publishes news, events, «СМИ о нас», and publication records.
- **v1** — Domain stays `ichnm.by`.
- **v1** — Plan a **PHP tariff** at Active.by or Hoster.by. Do not wait for the current Java panel; do not serve WordPress from the Forever Java app.
- **v1** — Logos: institute supplies vector files (PDF/SVG/AI) for ICNM mark and NAS emblem. **ICNM mark (2026-08-31):** official Illustrator file `assets/brand/ICHNM_Bew.ai`, web copies `ichnm-mark.svg` / `ichnm-mark.png`. Rebuild with `scripts/export-brand-mark.py`. NAS emblem may still be a crop from [nasb.gov.by](https://nasb.gov.by/rus/index.php) until its vector arrives. Cutover uses the vectors that exist.
- **v1** — Ship on a test URL first; switch the domain only after institute sign-off. Do not take the old site down before that.
- **v1** — Hosting panel / FTP / DNS access is unknown today; resolve before the test deploy.
- **fact (2026-08-30)** — Current `ichnm.by` DNS is Active.by (`ns1/ns2.activeby.net`), A record `178.172.235.199` in Hoster.by space, mail on `g-cloud.by`. The live site issues a `JSESSIONID` cookie (Java app), nginx 1.10.3, built by Forever.
- **fact (2026-08-30)** — [iboch.by](https://iboch.by/) is WordPress 7.1 + Kadence theme, PHP 8.3, DNS `hoster.by`, same IP as `iboch.bas-net.by` (`178.172.163.250`, Hoster.by). Old Joomla hostname still resolves there. BASNET sells PHP hosting, but IBOCH’s current public site is not on a distinct BASNET web farm.

- **v1 (2026-08-31)** — Search is a header/footer utility, not a top-menu root (locked menu list stays). The index covers персоналии, structural units, **приборы**, and developments. Карта сайта is a nested list of public sections, in the same class as [nasb.gov.by/rus/map/](https://nasb.gov.by/rus/map/).
- **v1 (2026-08-31)** — Opening an inner page uses a short navy veil wipe and staggered fade-up (Academy-class motion). The effect is off for `prefers-reduced-motion` and for visually-impaired mode.
- **v1 (2026-08-31)** — The official local contour (`preview/`) keeps empty slots. A separate tree `preview-filled/` is dummy-filled so the layout can be reviewed full: laboratory copy is letter-coded (наноструктуры — А, плёнки — Б, ЖК — В, композиты — Г, лесохимия — Д) with SVG fish images. That tree is not institute-approved content.

## Open (not locked)

Operational, not product forks: ICNM social-profile URLs (none on the old site); PHP-тариф and hosting credentials; NAS emblem vector; document PDFs, lab rosters, union/SMU texts, and the starting publication list from colleagues.

- **v1 slice (2026-08-30)** — Build proceeds **without** colleague files and **without** PHP-тариф. Placeholders in the test contour. Those assets are dropped in later; they do not block the local WordPress scaffold.
