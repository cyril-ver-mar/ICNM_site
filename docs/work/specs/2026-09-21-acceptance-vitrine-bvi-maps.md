# Приёмка: витрины, BVI, структура, каталоги, карты

Status: ready-for-agent

Tracker: `docs/work/specs/`, `docs/work/tickets/` (локальный markdown; `setup-engineering-workflow` в репо не запускался — при желании можно прописать `docs/agents/issue-tracker.md`).

Product locks: `docs/DECISIONS.md`. Glossary: `docs/CONTEXT.md`. Целевой стек: WordPress + Kadence child (`ichnm-kadence` / `ichnm-site`). Honest `preview/` / `preview-filled/` — **не** обязательный паритет; ship = WP/PHP. Preview HTML только если нужен общий JS/CSS-контракт или pytest-шов на модели.

Предыдущий срез того же дня: `2026-09-21-acceptance-chrome-ia-person.md` (тикеты 52–66). Этот документ — **новый** пакет замечаний приёмки; нумерация тикетов с **67**.

## Problem

После среза 52–66 приёмка всё ещё видит: бренд-текст без ссылки на главную; лишние пункты образования и плоское меню детей; сломанный/слабый BVI (подпись «BVI», низкий контраст, UI пропадает после toggle без reload); персональную страницу с рыбой в scientometrics и неоптимальной раскладкой метрик; дубли и лишнюю вертикальную линию у тонкоплёночного отдела без буферной страницы отдела; пустые duplicate-плитки на Структуре; акцент лаборатории не доведён до всех плиток lab pack (в т.ч. «Наша команда»); клик по оборудованию уводит в институтский каталог вместо страницы прибора; страницы разработок без узкого фото/секций/сайдбара направления; пустые секции и fish-прозу вместо hide; нет плиток сотрудников профсоюза; клик по папке «Научная деятельность» бесполезен; неочевидный клик по плитке (контакты нельзя скопировать); направления без списка связанных разработок; публикации без цветовой плашки лаборатории и с тесными breadcrumbs; лишняя строка «Об институте» над заголовком матбазы; пустые институтские секции каталогов; дубль реквизитов в Документах; слабый night-контраст ролей и годов; Контакты без Яндекс.Карт и с устаревшим контактом приёмной (Силина); overflow на плитках AIST и локации партнёров. Параллельно нужно поднять кандидатов полки «документы/процедуры для сотрудников» из исследования БГУ/НАН — не полный вузовский каталог.

## Solution

Довести WP-контур до приёмочных предпочтений этого пакета: chrome бренда и BVI (иконка, контраст, persistence); IA образования и клик папки «Научная деятельность» → **Направления работы**; структура (отдел как буферная страница, без лишней полосы у группировки, без duplicate-плиток, плитки профсоюза); hide empty + маркеры fish; персональная страница (метрики под фото, чистый scientometrics, демо «Иванов И. И.»); lab pack accents + dedicated facility pages; composition страницы разработки и двусторонняя связь направление ↔ разработки; tile click с исключением контактной кромки; публикации/crumbs/Документы; night contrast audit; Контакты (Яндекс.Карты + контакт руководства); overflow AIST/cooperation; полка BSU→ГНУ как отдельный тикет с опциями полок (не внедрять весь каталог БГУ).

Где замечание **меняет** прежний lock — в Notes → DECISIONS deltas; после approve тикетов обновить `docs/DECISIONS.md` одним блоком (или вместе с первым затронутым тикетом).

## User stories

1. As посетитель, I want клик по тексту «ИХНМ НАН Беларуси» в шапке открывал главную, so that бренд ведёт домой как у типичных институтских сайтов.
2. As посетитель, I want в меню образования только аспирантуру, докторантуру и совет по защитам как submenu под «Научно-ориентированное образование», без «повышения квалификации» и «курсов», so that IA совпадает с реальным предложением института.
3. As посетитель со слабым зрением, I want кнопку BVI без текста «BVI», с крупной иконкой очков/глаза, читаемый high-contrast режим (при необходимости без декоративного chrome) и восстановление UI после вкл/выкл без reload, so that версия для слабовидящих usable.
4. As посетитель персональной страницы, I want профили/метрики под фото, только имя ресурса + ссылка (без категории «Профиль») и числа вида «h-индекс: 5», без fish-прозы про внесение цифр, so that scientometrics читается как у готового профиля.
5. As приёмщик, I want демо-персону «Иванов И. И.» с полностью заполненным профилем, so that можно показать полный каркас персональной страницы.
6. As посетитель Структуры, I want у тонкоплёночного отдела буферную страницу (руководитель + лаборатории), без вертикальной светло-голубой линии у этой группировки и без пустой duplicate-плитки под каждым unit, so that дерево чистое.
7. As посетитель профсоюза, I want плитки сотрудников организации, so that состав виден как у других подразделений.
8. As посетитель lab pack / каталогов, I want акцентную полосу цвета лаборатории над плитками (в т.ч. на странице лаборатории: оборудование, разработки, команда и др. секции), so that принадлежность к лаборатории узнаётся везде.
9. As посетитель вкладки оборудования лаборатории, I want клик открывал dedicated страницу прибора (файлы для сотрудников + сведения об инструменте), а не только институтский каталог, so that путь как у разработок.
10. As посетитель страницы разработки, I want узкое фото (~ширина lab hero), секции описание / теххарактеристики / контакты и сайдбар с направлением работы лаборатории, so that карточка разработки структурирована.
11. As посетитель, I want пустые секции на lab/person/и др. полностью скрытыми (без fish-прозы «появится…»), а оставшиеся fish people/equipment — с fish-иконкой на фото для удаления, so that честный контур не шумит, а наполненный макет помечает заглушки.
12. As посетитель, I want клик по папке «Научная деятельность» вёл на «Направления работы» (не scroll top), so that folder-label не даёт пустого действия.
13. As посетитель плитки с контактами, I want кликабельной всю плитку кроме нижней контактной кромки (копирование телефона/email), so that навигация и копирование не конфликтуют — если сложность разумна.
14. As посетитель «Направления работы», I want список связанных разработок направления, so that связь направление ↔ разработка двусторонняя (на странице разработки — направление в сайдбаре).
15. As посетитель публикаций, I want цветовую плашку лаборатории в сайдбаре и нормальные отступы breadcrumbs «Главная / Об институте / Публикации», so that атрибуция и chrome читаемы.
16. As посетитель About-level страниц, I want один заголовок без лишней строки «Об институте» над «Материальная база» (и audit остальных), so that hero/crumbs не дублируют IA.
17. As посетитель институтских каталогов, I want скрытые пустые секции уровня института, when нет объектов, so that нет пустых блоков.
18. As посетитель Документов, I want три осмысленные плитки (устав, антикоррупция, реквизиты-deep-link) без duplicate реквизитов и без caption под «Реквизиты», so that хаб чистый.
19. As посетитель night, I want контрастные роли/meta (напр. «Директор», «Член-корреспондент») и годы публикаций, so that night theme читаема site-wide для таких токенов.
20. As посетитель Контактов, I want плагин Яндекс.Карт слева от схематической карты и актуальный контакт руководства вместо Силиной, so that локация и приёмка соответствуют факту.
21. As посетитель AIST и Сотрудничества, I want заголовки/локации плиток без overflow верхней границы, so that карточки не обрезают текст.
22. As разработчик от института, I want зафиксированный shortlist полок документов/процедур для сотрудников (из BSU/NAS research), so that можно выбрать v1 vs post-v1 без полного вузовского каталога.

## Implementation decisions

### Chrome: бренд и BVI

- Текст «ИХНМ НАН Беларуси» (идентификационный блок / wordmark рядом с маркой) — hyperlink на homepage текущего языкового префикса.
- Кнопка BVI: убрать видимый текст «BVI»; увеличить иконку очков/глаза; сохранить единый размер рамки tool-кнопок шапки (как в срезе 52).
- Режим BVI: пересмотреть токены — убрать или приглушить decorative chrome, который даёт white-on-white / low contrast; сохранить структуру страницы и навигацию. Veil / stagger уже off в BVI (lock).
- Toggle on/off: UI (шапка tools, панель BVI, chrome) **восстанавливается без reload**; persistence preference не ломает DOM/CSS-классы соседних controls. Регрессия: предыдущий баг «пропадает до F5».

### IA: образование и папка науки

- Убрать из меню/витрин «повышения квалификации» и «курсы» (и связанные URL-redirects при наличии).
- Дети научно-ориентированного образования в top menu: **аспирантура, докторантура, совет по защитам** — nested submenu под родителем «Научно-ориентированное образование». Стажировки: либо остаются, либо убираются только если явно в том же замечании — **в этом пакете пользователь назвал три ребёнка**; стажировки уточнить при реализации: если lock ещё держит стажировки — оставить как четвёртого ребёнка, иначе согласовать с approve (см. Notes deltas).
- Папка **Научная деятельность**: клик по label/родителю **навигирует** на «Направления работы» (`science` hub), а не на `#` / scroll-top. Folder остаётся без собственной витрины-страницы; поведение клика = shortcut на first child. (Реверс/уточнение «folder = label only» для pointer activation.)

### Структура: тонкоплёночный отдел, дубли, профсоюз

- У группировки тонкоплёночного отдела на хабе Структуры — **нет** вертикальной светло-голубой линии слева.
- Отдельная буферная страница отдела: заголовок отдела, руководитель (head), состав лабораторий в композиции (ссылки/плитки трёх labs: nano, films, lcd). Labs по-прежнему имеют свои `labs/{slug}/`.
- Убрать пустую duplicate-плитку под каждым structure unit (регресс после 55/частичных правок).
- Профсоюз: добавить staff tiles людей организации (как у admin units / labs) → персональные страницы.

### Empty / fish

- Пустые секции на lab pack, person, каталогах и др.: **не рендерить** секцию целиком.
- Убрать empty-slot fish prose («Перечень проектов появится…» и аналоги).
- Оставшиеся fish people / equipment в filled-контуре: fish-icon illustration в photo slot как маркер «удалить при замене реальными файлами». Honest contour остаётся без fish-контента.

### Персональная страница

- Блок профилей/метрик — **под фото** (экономия горизонтали).
- Убрать fish copy про «Цифры и ссылки вносит…».
- Показ: имя ресурса + link (напр. ORCID) **без** категории «Профиль»; числовые метрики только как «h-индекс: 5» (и аналогичные labels), без orphan-категорий.
- Seed демо-персоны **Иванов И. И.** с полным набором заполненных секций/метрик для приёмки (marked as demo/fish или явный sample id — не выдавать за реального сотрудника института).

### Lab packs, facilities, developments

- Accent stripe цвета лаборатории над **каждой** плиткой на странице лаборатории (включая «Наша команда» и прочие секции с плитками), а также подтвердить на каталогах facilities/developments (уже intended).
- Вкладка оборудования: href → **dedicated facility page** (как development detail): сведения об инструменте + files for staff; не только jump на институтский хаб матбазы.
- Страница разработки: photo field уже (~ширина lab hero); секции description, tech specs, contacts; sidebar — lab work direction, к которому относится разработка.
- «Направления работы»: секция со списком связанных developments (в дополнение к направлению на detail разработки).

### Плитки и каталоги

- Whole-tile hyperlink **кроме** нижней контактной кромки (телефоны/email selectable/copyable), если сложность в теме разумна; иначе зафиксировать fallback в тикете (весь tile link + отдельные `user-select` contacts).
- Институтские каталоги (facilities, directions, developments): hide empty institute-level sections when нет объектов.
- About-level: убрать лишнюю строку «Об институте» над «Материальная база»; audit других About children на double title/crumb.

### Публикации и Документы

- Публикации: sidebar color plaque лаборатории-автора статьи (тот же lab accent token).
- Breadcrumbs публикаций: top spacing / indent как у остальных About children.
- Документы: плитки устав, антикоррупция, реквизиты (deep-link contacts); **удалить** duplicate empty requisites tile; у «Реквизиты» — только title, без caption под плиткой.

### Night, Contacts, overflow

- Night: audit role/meta colors (Leadership «Директор», «Член-корреспондент» и аналоги site-wide); publication years — достаточный контраст. Через theme tokens, не one-off.
- Contacts: Яндекс.Карты plugin **слева** от существующей схематической карты Минска; схематика остаётся (lock Natural Earth / theme colours).
- Contacts: заменить контакт приёмной (Силина) на контакт **руководства** (актуальные FIO/телефон/email из roster руководства — не выдумывать; уточнить у источника при сиде).
- AIST archive tiles: overflow top edge (регресс/дожим после 64).
- Cooperation partner tiles: location line не overflow верхней границы.

### Полкa BSU → ГНУ (не полный каталог)

Из research ([BSU staff docs](02823676-10d5-41ad-9838-7af149ea006c); зеркало research.bsu.by; параллели nasb.gov.by):

**Кандидаты для ИХНМ (ГНУ-релевантные):**

| Содержимое | Смысл | Возможные полки |
|---|---|---|
| ПВТР | локальный трудовой акт | расширить **Документы** |
| Коллективный договор | рядом с профсоюзом | **Документы** и/или пакет **Профсоюза** |
| Этика | лучше кодекс НАН / научная этика, не вузовский «студенческий» | **Документы** или ссылка на НАН |
| ПДн | уже есть политика | оставить; при необходимости связать с Документами |
| Видеонаблюдение | опционально при наличии положения | **Документы** |
| Адм. процедуры «по месту работы» | кадровые/зарплатные справки, пособия, кто выдаёт, сроки — не студенческое «одно окно» | **Кадры** / **Бухгалтерия** или тонкая страница под Документами/Контактами |
| График приёма | руководство / секретарь | **Контакты** / приёмная |
| Командировки | в т.ч. зарубежные для науки | **Контакты** / учёный секретарь / внутренняя страница |
| Научный слой | положение учёного совета; IP у разработок; ОТ/химбезопасность; статус аккредитации научной организации (ссылка) | существующие витрины |

**Не тащить:** студенческие справки, стоимость обучения, полный жилищный/лесной каталог Академии, отдельный корневой «Сотрудникам» в v1.

Тикет фиксирует shortlist + shelf options → пользователь выбирает v1 vs post-v1 → затем DECISIONS. Реализация PDF только после файлов института.

### Стек

- WordPress/PHP. Preview HTML optional/deferred. Сид/модель — `src/core` seams где применимо.

## Testing decisions (seams)

Предлагаемые швы (подтвердить при старте тикетов):

1. **BVI preference restore** — set/clear BVI не удаляет header tool nodes; class/`aria` восстанавливаются; unit или smoke DOM contract (без reload).
2. **Empty section filter** — секция в выводе iff non-empty collection; pytest на lab/person fixtures.
3. **Person metrics render** — resource name+href; metric label `h-индекс: N`; no «Профиль» category; no fish prose string.
4. **Education menu tree** — children ⊆ {aspirantura, doctorate, defense-council, ±internships}; no courses / advanced-training ids.
5. **Science folder href** — menu parent «Научная деятельность» → science directions URL.
6. **Thin-film department page** — model: department entity with head + three lab ids; structure hub без vertical stripe class для этой группы; no duplicate empty tile per unit.
7. **Facility deep-link from lab** — lab equipment tile href == facility detail URL (как development deep-link).
8. **Direction ↔ developments** — direction page lists development ids tagged with that direction; development detail sidebar points to same direction.
9. **Documents hub tiles** — ровно ожидаемый набор (charter, anti-corruption, requisites link); no duplicate requisites; requisites tile title-only.
10. **Lab accent on lab-pack tiles** — same token map as structure/catalogues applied to team/equipment/developments sections on lab page.

Не шов: точный px Яндекс.Карт; пиксельный размер BVI icon; точный copy FIO замены Силиной (данные из roster).

Smoke: homepage brand click; BVI on/off without reload; education submenu; science folder → directions; structure dept page; union staff; person Иванов; lab accents + facility detail; development sidebar; publications plaque+crumbs; documents 3 tiles; night leadership roles; contacts Yandex+leadership; AIST/cooperation overflow.

## Out of scope

- Паритет honest `preview/` / `preview-filled/` HTML ради этого среза (deferred).
- PHP test URL / DNS cutover.
- Официальные переводы тел EN/BE/ZH.
- Автоопрос ORCID/Scopus и т.д.
- Полный вузовский каталог БГУ / отдельный корневой «Сотрудникам».
- Пиксельный клон IBOCH; forced download-only PDF.
- Замена схематической карты Минска Яндексом (Яндекс — **дополнение** слева, не вместо).

## Notes

### Approved locks (2026-09-21) — ship into DECISIONS

1. **Education children:** убрать «курсы» и «повышения квалификации». Nested submenu: аспирантура, докторантура, совет по защитам, **стажировки** (оставлены — явного отказа не было).
2. **Научная деятельность folder click:** pointer → **Направления работы**. Folder без собственной page.
3. **Science folder children (new):** **Научно-исследовательская работа студентов**; **Трудоустройство выпускников** (тикет 80). Contact + links to directions/developments; honest placeholders OK.
4. **Thin-film department:** буферная страница (head + labs); хаб Структуры без вертикальной accent-линии у группы.
5. **Facility pages:** dedicated instrument pages; lab equipment → detail.
6. **Development detail:** узкое фото; description / tech specs / contacts; sidebar = direction; reverse list on directions.
7. **Empty sections:** hide entirely; no empty-slot fish prose.
8. **Documents hub:** charter + anti-corruption + requisites deep-link; no dup requisites; requisites title-only.
9. **Documents «Для сотрудника»:** child subsection for workplace certificates / адм. процедуры (справки с места работы / трудовой / зарплаты / пособия — who issues, contacts/process; empty PDF slots OK). Also shelves: ПВТР, этика (NAS code link OK), ПДн, видеонаблюдение slot, коллективный договор (Documents и/или Union). График приёма / командировки — Contacts / reception / Documents. **Нет** корневого «Сотрудникам».
10. **Contacts:** Yandex Maps left of schematic; reception → leadership contact (Силина out).
11. **BVI:** icon-only larger; contrast rethink; restore without reload.
12. **Brand wordmark:** «ИХНМ НАН Беларуси» → homepage.
13. **Tile click:** whole tile except contact edge.

### Implementation note

Тикеты: `67`–`80` under `docs/work/tickets/`. Ship = WP/PHP (`ichnm-kadence` / `ichnm-site`); preview HTML only if seam requires. Seed bump if model/menu/pages change.

### Прочее

- Tracker локальный; тикеты с **67** (после 66); **80** = science NIR + employment.
- Исследование BSU: [BSU staff docs research](02823676-10d5-41ad-9838-7af149ea006c).
- «Направления деятельности» → **Направления работы** per DECISIONS.
