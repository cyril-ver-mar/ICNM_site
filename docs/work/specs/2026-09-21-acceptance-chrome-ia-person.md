# Приёмка: chrome, IA, персоналии, контраст night

Status: ready-for-agent

Tracker: `docs/work/specs/`, `docs/work/tickets/` (локальный markdown; `setup-engineering-workflow` в репо не запускался — при желании можно прописать `docs/agents/issue-tracker.md`).

Product locks: `docs/DECISIONS.md`. Glossary: `docs/CONTEXT.md`. Целевой стек реализации: WordPress + Kadence child (`ichnm-kadence` / `ichnm-site`). Honest `preview/` — эталон поведения только где нужен шов или регрессия; **паритет preview HTML по этому срезу не обязателен** (v1 ship = WP).

## Problem

После среза 35–51 приёмка всё ещё видит: сломанную кнопку версии для слабовидящих; режим темы с «auto» в цикле (больше не нужен); низкий контраст night у заголовков секций, плиток и подписей к фото; лишние/неверные плитки на Структуре и слабую группировку тонкоплёночного отдела; отсутствие цветовой корреляции лабораторий в каталогах; раздутый блок «направления» на Об институте; жёсткий checkerboard в Документах; сбитые nested dropdown; «Электронное обращение» слишком глубоко в меню; overflow на плитках AIST; двухкликовый путь с лаборатории к разработке; и недооформленные поля персональной страницы (метрики и person-owned разделы).

## Solution

Довести WP-контур до приёмочных предпочтений 2026-09-21: chrome tool-кнопок (в т.ч. BVI), тема **только day|night** с начальным выбором по местному времени, site-wide night contrast, переразметка Структуры (отдел → лаборатории, заведующий, общественные объединения), lab accent colors в структуре / разработках / матбазе, IA-правки (About → Направления работы; электронные обращения на уровень dropdown Об институте; кнопка на Контактах), персональная страница с условными разделами и админ-полями, обычная сетка Документов, flyout nested menus, deep-link разработок, in-site PDF viewer с download.

Где замечание **меняет** прежний lock — в Notes; после approve тикетов обновить `docs/DECISIONS.md` одним блоком (или вместе с первым theme/IA тикетом).

## User stories

1. As посетитель, I want кнопку версии для слабовидящих того же размера/рамки/иконки, что и остальные tool-кнопки шапки, so that chrome не выглядит сломанным.
2. As посетитель, I want при первом визите тему по местному времени (день или ночь), а дальше только переключение day ↔ night без режима «auto» в цикле, so that переключатель простой и предсказуемый.
3. As посетитель night, I want читаемые заголовки секций под breadcrumbs, текст плиток и подписи к фото site-wide, so that тёмная тема не ломает иерархию и контраст.
4. As посетитель Структуры, I want одну плитку на подразделение (без лишней плитки снизу), подпись руководителя как **заведующий** у лабораторий, и корректную группировку «Отдел физико-химии тонкопленочных материалов» под «Лаборатории», so that дерево подразделений читается.
5. As посетитель, I want у профсоюза / СМУ плитку «название → председатель» в том же языке карточек, so that общественные объединения согласованы со структурой.
6. As посетитель каталогов, I want у каждой лаборатории свой цвет акцентной линии на плитке в Структуре, Разработках и Матбазе, контрастный в day и night, so that подразделение узнаётся по цвету.
7. As посетитель «Об институте», I want вместо блока «направления исследований» кнопку/ссылку на «Направления работы», so that нет дубля смысла с научным хабом.
8. As посетитель персональной страницы, I want научные метрики (в т.ч. индекс Хирша) только если заполнены, со ссылкой на соответствующий профиль, so that пустые показатели не показываются.
9. As редактор при добавлении сотрудника, I want поля ввода для метрик и person-owned разделов (награды, публикации по типам, интересы, научные проекты и т.п.), so that контент вносится один раз на персоналии.
10. As посетитель персональной страницы, I want секции awards / publications / interests / projects только если заполнены, и без синхронизации с lab packs или институтскими каталогами, so that персональный контент остаётся person-owned.
11. As посетитель Документов, I want обычную сетку плиток как в других разделах, so that нет checkerboard-раскладки.
12. As посетитель десктоп-меню, I want вложенный flyout (напр. Об институте › Документы) выровненным по уровню родительского пункта и не прыгающим при движении курсора, so that nested dropdown usable.
13. As посетитель, I want «Электронное обращение» пунктом на уровне dropdown «Об институте» (не только внутри Документов), и на Контактах — аккуратную кнопку «Электронные обращения», so that обращение найти легче.
14. As посетитель архива AIST, I want первую строку плитки без overflow, so that «AIST» читается внутри карточки.
15. As посетитель лаборатории, I want клик по плитке разработки сразу открывать страницу этой разработки (deep-link), so that не нужен второй клик через каталог.
16. As посетитель документов/PDF, I want открытие PDF in-site (без принудительного download), с отдельной опцией скачать, so that можно читать и при необходимости сохранить файл.

## Implementation decisions

### Chrome

- BVI tool: единый размер, padding, border/outline (без двойной рамки), рабочая иконка — тот же visual language, что language/theme tools.
- Theme control: убрать `auto` из UI-цикла и из persisted modes, которые пользователь листает. **Default** при отсутствии сохранённого preference: day или night по локальным часам пользователя. После выбора пользователь переключает только `day` ↔ `night`. (Реверс lock 2026-09-18 «auto in cycle».)

### Night contrast (site-wide)

- Section title под breadcrumbs (пример: «Руководство» на About › Leadership) — токен ink/заголовка, читаемый на night paper/navy.
- Текст плиток / tiles — достаточный контраст (не dark-grey на dark).
- Photo captions (About и вообще) — контрастные в night.
- Правки через theme tokens / surface classes child-темы, не точечные хаки одной страницы.

### Структура и акценты лабораторий

- Убрать лишнюю «плитку под плиткой» у каждого unit.
- У лабораторий: имя подразделения; ниже — FIO заведующего с явной подписью роли **заведующий**.
- Под заголовком «Лаборатории»: с иным отступом — название отдела «Отдел физико-химии тонкопленочных материалов»; внутри — три связанные unit-плитки; остальные лаборатории/отделы — вне этого indent. Восстановить пропавшие units из roster/модели, если исчезли.
- Общественные объединения (профсоюз, СМУ и т.п.): та же схема плитки — название; ниже — председатель.
- Lab accent: каждой лаборатории назначить цвет; тонкая accent-линия над плиткой (как уже для отдела) на Структуре и на плитках той же лаборатории в каталогах Разработки и Матбаза. Цвета контрастны в day и night.

### About и наука

- На витрине «Об институте» заменить блок/текст «направления исследований» на button/link к «Направления работы» (science directions hub).

### Персональная страница и админка

- Метрики (h-index / science-intensive): если значение есть — показать и связать с соответствующей страницей профиля сети; если нет — не рендерить метрику. Порядок сетей как lock: ORCID → Google Scholar → Scopus Author → eLIBRARY/РИНЦ → ResearchGate.
- При создании/редактировании сотрудника в админке — явные input fields для метрик и для person-owned разделов.
- Опциональные секции персональной страницы (появляются только если заполнены): награды; публикации (научные, методические и др. типы по полям админки); исследовательские интересы; научные проекты; иные согласованные person fields.
- **Не синхронизировать** эти списки с lab packs и институтскими каталогами: это person-owned контент. Lab «научные проекты» и институтский каталог публикаций остаются отдельными surfaces (как в glossary/DECISIONS).

### Документы, меню, IA

- Документы: обычный grid плиток (как другие разделы), не checkerboard.
- Nested dropdown: первый child вложенного меню выровнен по вертикали с родительским пунктом (proper flyout); курсор не должен «переключать» подменю чужих siblings при движении к flyout.
- «Электронное обращение» / «Электронные обращения»: пункт на уровне dropdown **Об институте** (рядом с Документами и др., не только ребёнок Документов). На странице Контакты — улучшенная кнопка/CTA на ту же витрину. Страница порядка обращений сохраняется; реквизиты по-прежнему под Контактами.

### AIST, разработки, PDF

- AIST archive tiles: первая строка («AIST») без overflow (truncate / wrap / type scale — в рамках существующих tile tokens).
- Lab pack → development tile: href сразу на detail разработки (тот же deep-link, что из институтского каталога). Каталог Разработки тоже ведёт на detail одним кликом.
- Future PDFs: in-site viewer (browser embed / плагин-класс без forced download), плюс явная download-опция. Не ломать честные empty-slots, пока файла нет.

### Стек

- Реализация в WordPress/PHP theme stack. Preview HTML **не** обновлять ради этого среза, кроме случая, когда нужен общий JS/CSS контракт или pytest-шов на модели. Сид/модель — через существующие seams `src/core` где применимо.

## Testing decisions (seams)

Предлагаемые швы (подтвердить в Notes / при старте тикетов):

1. **Theme preference** — `get/set` только `day|night`; `defaultFromLocalClock(now)` → day|night; нет `auto` в cycle API; unit без DOM; smoke: первый визит без storage получает clock-based class; toggle листает два режима.
2. **Person metrics visibility** — чистая функция: сеть/метрика рендерится iff заполнены value и/или URL по правилам; h-index без профиля не orphan-показывается без link target (или скрыта — зафиксировать в тикете); pytest на фикстурах person record.
3. **Person optional sections** — модель: секция в выводе только если non-empty; **нет** join/sync с lab projects / institute publications catalogue; unit на filter empty sections.
4. **Structure grouping** — site model / roster: thin-film department parent + три child lab ids; все ожидаемые units присутствуют; pytest инвариант дерева.
5. **Lab accent token** — lab_id → accent color token; одна карта для structure / developments / facilities tile chrome; unit: known lab → same token; contrast smoke day+night (checklist).
6. **Development deep-link** — lab pack tile href == catalogue detail URL для того же development id/slug; unit или seed assert.
7. **Menu IA** — e-appeals на уровне About children (не только documents); model/menu test.
8. **PDF viewer contract** — attachment/page: view URL vs download URL; smoke WP (не unit, если нет PHP harness) — отметить в тикете.

Не шов: пиксельные отступы flyout; точные HEX lab colors (константы в тикете); точный порог часов default theme.

Smoke: header tools (BVI), theme toggle, about/leadership/structure/lab/developments/facilities/documents/aist/contacts/people, night captions/titles/tiles, nested Documents flyout, lab→development one click.

## Out of scope

- Обновление honest `preview/` / `preview-filled/` HTML ради визуального паритета этого среза (deferred; WP = ship target).
- PHP test URL / DNS cutover (ticket 34).
- Официальные переводы тел EN/BE/ZH.
- Автоопрос внешних баз (ORCID/Scopus и т.д.) — по-прежнему ручной ввод.
- Отдельный институтский каталог научных проектов в верхнем меню (остаётся на lab pack).
- Синхронизация person publications/projects с lab packs или каталогом публикаций.
- Пиксельный клон IBOCH Customizer.
- Forced download-only PDF policy; photo albums AIST.

## Notes

### DECISIONS deltas (user locking new prefs — обновить после approve)

1. **Theme (реверс 2026-09-18):** убрать `auto` из пользовательского цикла. Default = day|night от local clock; persisted preference только `day`|`night`.
2. **Электронные обращения:** пункт меню на уровне dropdown **Об институте** (не только ребёнок Документов). Документы: устав, антикоррупция (+ прочие PDF-плитки); e-appeals — sibling под Об институте. Glossary «Документы» уточнить: e-appeals больше не обязан быть ребёнком Documents hub.
3. **Структура:** lab tile показывает **заведующий** (роль + FIO); thin-film department = grouped under «Лаборатории» with indent; public associations = name + chairperson. Расширяет lock «structure = tile grid only».
4. **Lab accent colors:** корреляция цвета плиток лаборатории across Структура / Разработки / Матбаза (новое).
5. **Person page:** опциональные person-owned секции (awards, typed publications, interests, projects, …) only-if-filled; **no sync** with lab packs / institute catalogues. Метрики: show+link when present; admin fields required for entry. Уточняет/расширяет lock библиометрики.
6. **About:** CTA/link to «Направления работы» вместо inline «направления исследований».
7. **PDF:** in-site view + download (новое предпочтение для future PDFs).

### Прочее

- Tracker локальный; нумерация тикетов продолжается с **52** (после 35–51).
- Предложенный план тикетов — quiz в ответе агента; файлы `docs/work/tickets/NN-*.md` писать **после** approve плана пользователем.
- Seams выше — предложить; при старте реализации подтвердить или поправить в Notes тикета / follow-up.
- Thin-film children: три unit из roster/модели (не выдумывать); restore missing из той же модели.
