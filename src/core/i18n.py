"""Interface and IA strings for the four URL prefixes.

Institute body copy (news, bios, dummy scientific paragraphs) stays Russian
until an official translation. This module covers the site structure: menus,
lab names in the tree, chrome, section headings, empty states, and forms.
"""

from __future__ import annotations

from contextvars import ContextVar
from typing import Any

from src.core.site_model import MenuItem

LOCALE: ContextVar[str] = ContextVar("locale", default="ru")

MENU: dict[str, dict[str, str]] = {
    "en": {
        "about": "About the institute",
        "about-overview": "Overview",
        "leadership": "Leadership",
        "structure": "Structure",
        "hr": "Human resources",
        "labor-protection": "Occupational safety",
        "engineering": "Chief engineer",
        "accounting": "Accounting",
        "union": "Trade union",
        "young-scientists": "Council of Young Scientists",
        "research": "Research",
        "science": "Research areas",
        "developments": "Developments",
        "cooperation": "Cooperation",
        "publications": "Publications",
        "education": "Research-oriented education",
        "aspirantura": "Postgraduate studies",
        "doctorate": "Doctoral studies",
        "defense-council": "Dissertation council",
        "internships": "Internships",
        "courses": "Courses",
        "scientific-council": "Scientific council",
        "facilities": "Research facilities",
        "documents": "Documents",
        "charter": "Charter",
        "anti-corruption": "Anti-corruption",
        "e-appeals": "Electronic appeals",
        "vacancies": "Vacancies",
        "news": "News",
        "events": "Events",
        "aist": "AIST",
        "contacts": "Contacts",
        "feedback": "Feedback",
        "requisites": "Bank details",
        "media_about": "Media about us",
        "search": "Search",
        "sitemap": "Sitemap",
        "home": "Home",
    },
    "be": {
        "about": "Пра інстытут",
        "about-overview": "Звесткі",
        "leadership": "Кіраўніцтва",
        "structure": "Структура",
        "hr": "Аддзел кадраў",
        "labor-protection": "Ахова працы",
        "engineering": "Галоўны інжынер",
        "accounting": "Бухгалтэрыя",
        "union": "Прафсаюз",
        "young-scientists": "Савет маладых вучоных",
        "research": "Навуковая дзейнасць",
        "science": "Напрамкі працы",
        "developments": "Распрацоўкі",
        "cooperation": "Супрацоўніцтва",
        "publications": "Публікацыі",
        "education": "Навукова-арыентаваная адукацыя",
        "aspirantura": "Аспірантура",
        "doctorate": "Дактарантура",
        "defense-council": "Савет па абаронах",
        "internships": "Стажыроўкі",
        "courses": "Курсы",
        "scientific-council": "Вучоны савет",
        "facilities": "Матэрыяльная база",
        "documents": "Дакументы",
        "charter": "Статут",
        "anti-corruption": "Антыкарупцыя",
        "e-appeals": "Электронныя звароты",
        "vacancies": "Вакансіі",
        "news": "Навіны",
        "events": "Мерапрыемствы",
        "aist": "AIST",
        "contacts": "Кантакты",
        "feedback": "Зваротная сувязь",
        "requisites": "Рэквізіты",
        "media_about": "СМІ пра нас",
        "search": "Пошук",
        "sitemap": "Карта сайта",
        "home": "Галоўная",
    },
    "zh": {
        "about": "关于研究所",
        "about-overview": "概况",
        "leadership": "领导班子",
        "structure": "机构设置",
        "hr": "人事处",
        "labor-protection": "劳动保护",
        "engineering": "总工程师",
        "accounting": "财务处",
        "union": "工会",
        "young-scientists": "青年科学家委员会",
        "research": "科研工作",
        "science": "研究方向",
        "developments": "研发成果",
        "cooperation": "合作",
        "publications": "论文",
        "education": "科研导向教育",
        "aspirantura": "研究生培养",
        "doctorate": "博士培养",
        "defense-council": "答辩委员会",
        "internships": "实习",
        "courses": "课程",
        "scientific-council": "学术委员会",
        "facilities": "仪器设备",
        "documents": "文件",
        "charter": "章程",
        "anti-corruption": "反腐败",
        "e-appeals": "电子诉求",
        "vacancies": "招聘",
        "news": "新闻",
        "events": "活动",
        "aist": "AIST",
        "contacts": "联系我们",
        "feedback": "反馈",
        "requisites": "银行信息",
        "media_about": "媒体报道",
        "search": "搜索",
        "sitemap": "网站地图",
        "home": "首页",
    },
}

LABS: dict[str, dict[str, str]] = {
    "en": {
        "lab-nano": "Laboratory of Micro- and Nanostructured Systems",
        "lab-films": "Laboratory of Optical Multifunctional Films",
        "lab-lcd": "Laboratory of Materials and Technologies for LC Devices",
        "lab-composites": "Industry Laboratory of Heat-Resistant Polymer Composites",
        "lab-woodchem": "Laboratory of Forest Chemical Products and Technologies",
    },
    "be": {
        "lab-nano": "Лабараторыя мікра- і нанаструктураваных сістэм",
        "lab-films": "Лабараторыя аптычных шматфункцыянальных плёнак",
        "lab-lcd": "Лабараторыя матэрыялаў і тэхналогій ВК-прылад",
        "lab-composites": "Галіновая лабараторыя тэрмастойкіх палімерных кампазітаў",
        "lab-woodchem": "Лабараторыя лесахімічных прадуктаў і тэхналогій",
    },
    "zh": {
        "lab-nano": "微纳米结构系统实验室",
        "lab-films": "光学多功能薄膜实验室",
        "lab-lcd": "液晶器件材料与技术实验室",
        "lab-composites": "耐热聚合物复合材料行业实验室",
        "lab-woodchem": "林产化工产品与技术实验室",
    },
}

HUB: dict[str, dict[str, str]] = {
    "en": {
        "about-overview": "Mission and research areas",
        "leadership": "Director, deputies, scientific secretary, reception",
        "scientific-council": "Meetings and membership",
        "documents": "Charter, anti-corruption, appeals",
        "charter": "Charter text — after the PDF is supplied",
        "anti-corruption": "Policy and plan — after the PDFs are supplied",
        "e-appeals": "Where to write and the statutory deadlines",
        "requisites": "Legal address and bank details",
        "aist": "Conference on feedstock and fuels",
        "hr": "HR office staff and contacts",
        "labor-protection": "Occupational safety specialist",
        "engineering": "Chief engineer of the Institute",
        "accounting": "Chief accountant",
        "education": "Postgraduate and doctoral studies, dissertation council",
        "union": "Primary organisation",
        "young-scientists": "Institute Council of Young Scientists",
        "media_about": "Press and the newspaper Navuka",
        "developments": "Laboratory products and methods",
        "publications": "Institute and laboratory papers",
        "facilities": "Laboratory instruments and setups",
        "vacancies": "Open positions",
        "structure": "Laboratories and units",
        "science": "Institute research areas",
        "cooperation": "Centres, agreements, partner map",
        "search": "People, units, developments",
        "sitemap": "Every public section in one list",
        "aspirantura": "Opened in 1999",
        "doctorate": "Admission rules to be confirmed by the scientific secretary",
        "defense-council": "Specialities and membership — after verification",
        "internships": "Internships for students and early-career researchers",
        "courses": "Continuing professional education",
    },
    "be": {
        "about-overview": "Задачы і напрамкі даследаванняў",
        "leadership": "Дырэктар, намеснікі, вучоны сакратар, прыёмная",
        "scientific-council": "Пасяджэнні і склад савета",
        "documents": "Статут, антыкарупцыя, звароты",
        "charter": "Тэкст статута — пасля перадачы PDF",
        "anti-corruption": "Палажэнне і план — пасля перадачы PDF",
        "e-appeals": "Куды пісаць і якія тэрміны",
        "requisites": "Юрыдычны адрас і банк",
        "aist": "Канферэнцыя па сыравіне і паліве",
        "hr": "Склад і кантакты аддзела кадраў",
        "labor-protection": "Спецыяліст па ахове працы",
        "engineering": "Галоўны інжынер Інстытута",
        "accounting": "Галоўны бухгалтар",
        "education": "Аспірантура, дактарантура, савет па абаронах",
        "union": "Першасная арганізацыя",
        "young-scientists": "Савет маладых вучоных Інстытута",
        "media_about": "Публікацыі ў прэсе і «Навуцы»",
        "developments": "Прадукты і методыкі лабараторый",
        "publications": "Артыкулы Інстытута і лабараторый",
        "facilities": "Прыборы і ўстаноўкі лабараторый",
        "vacancies": "Адкрытыя стаўкі",
        "structure": "Лабараторыі і падраздзяленні",
        "science": "Напрамкі даследаванняў Інстытута",
        "cooperation": "Цэнтры, дагаворы, карта партнёраў",
        "search": "Персаналіі, падраздзяленні, распрацоўкі",
        "sitemap": "Усе раздзелы адным спісам",
        "aspirantura": "Адкрыта ў 1999 годзе",
        "doctorate": "Правілы прыёму ўдакладніць вучоны сакратар",
        "defense-council": "Спецыяльнасці і склад — пасля зверкі",
        "internships": "Стажыроўкі для студэнтаў і маладых даследчыкаў",
        "courses": "Павышэнне кваліфікацыі",
    },
    "zh": {
        "about-overview": "任务与研究方向",
        "leadership": "所长、副所长、学术秘书、接待室",
        "scientific-council": "会议与委员会组成",
        "documents": "章程、反腐败、诉求",
        "charter": "章程文本——待提供 PDF",
        "anti-corruption": "条例与计划——待提供 PDF",
        "e-appeals": "投递地址与法定期限",
        "requisites": "法定地址与银行信息",
        "aist": "原料与燃料会议",
        "hr": "人事处人员与联系方式",
        "labor-protection": "劳动保护专员",
        "engineering": "研究所总工程师",
        "accounting": "总会计师",
        "education": "研究生、博士培养与答辩委员会",
        "union": "基层组织",
        "young-scientists": "研究所青年科学家委员会",
        "media_about": "媒体与《科学》报",
        "developments": "各实验室产品与方法",
        "publications": "研究所与各实验室论文",
        "facilities": "各实验室仪器与装置",
        "vacancies": "在招岗位",
        "structure": "实验室与部门",
        "science": "研究所研究方向",
        "cooperation": "中心、协议、合作伙伴地图",
        "search": "人员、部门、成果",
        "sitemap": "全部栏目一览",
        "aspirantura": "1999 年设立",
        "doctorate": "招生规则由学术秘书确认",
        "defense-council": "专业与组成——待核对",
        "internships": "面向学生与青年研究者的实习",
        "courses": "继续教育",
    },
}

STRINGS: dict[str, dict[str, str]] = {
    "ru": {
        "home": "Главная",
        "crumbs": "Навигация",
        "section_pages": "Страницы раздела",
        "copy_note": "",
        "nas_emblem_alt": "Эмблема Национальной академии наук Беларуси",
        "icnm_mark_alt": "Эмблема ИХНМ",
        "street": "ул. Ф. Скорины, 36, Минск",
        "address_full": "220084, г. Минск, ул. Ф. Скорины, 36",
        "on_this_page": "На этой странице",
        "institute_news": "Новости Института",
        "news_kicker": "Новость",
        "media_kicker": "СМИ",
        "all_news": "Все новости",
        "back_to_news": "Ко всем новостям",
        "next_event": "Ближайшее мероприятие",
        "aist_section": "Раздел AIST",
        "register_cycle": "Регистрация текущего цикла",
        "register": "Регистрация",
        "aist_archive": "Архив AIST",
        "other_conferences": "Другие конференции Института",
        "other_conferences_lead": (
            "Слоты для мероприятий вне AIST. Новую страницу можно добавить в этот архив, "
            "не вынося конференцию на отдельный домен."
        ),
        "back_to_events": "К архиву мероприятий",
        "conference_placeholder": (
            "Программа, сборник и ключевые даты этой конференции появятся после передачи "
            "материалов. Страница живёт в архиве ichnm.by, не на отдельном домене."
        ),
        "aist_series": "Серия AIST · раздел на ichnm.by",
        "aist_body": (
            "Материалы текущего цикла — программа, тезисы и резолюция — на этой странице. "
            "Регистрация и исторический сайт серии пока на "
        ),
        "aist_no_domain": " — отдельный домен в v1 не поднимаем.",
        "aist_materials": "Материалы конференции",
        "aist_org": "Оргкомитет",
        "aist_secretary": "Секретарь конференции: ",
        "aist_press": "СМИ о конференции",
        "news_source": "Текст и фото с текущего ichnm.by",
        "all_events": "Весь архив мероприятий",
        "feedback_lead": (
            "Форма откроет письмо на приёмную. Электронные обращения по закону — "
        ),
        "separate_page": "отдельная страница",
        "form_name": "Имя",
        "form_email": "Электронная почта",
        "form_message": "Сообщение",
        "form_send": "Отправить",
        "science_institute": "Общеинститутские направления",
        "science_labs": "Направления лабораторий",
        "dev_institute": "Общеинститутские разработки",
        "dev_labs": "Разработки лабораторий",
        "fac_institute": "Общеинститутское оснащение",
        "fac_labs": "Оборудование лабораторий",
        "partners": "Партнёры",
        "back_to_news_smi": "К новостям Института и СМИ",
        "no_announcements": "Объявлений пока нет",
        "no_announcements_text": (
            "Служебные сообщения появятся здесь, отдельно от новостей и мероприятий."
        ),
        "no_vacancies": "Открытых вакансий нет",
        "no_vacancies_text": (
            "Когда появится ставка, её опубликуют в этом разделе. Вопросы в отдел кадров: "
            "+375 (17) 243-67-56."
        ),
        "research_directions": "Направления исследований",
        "awards": "Награды и достижения",
        "list_heading": "Перечень",
        "requisites_link": "Реквизиты",
        "feedback_form_link": "Форма обратной связи",
        "map_aria": "Институт на карте Минска",
        "map_pending": "Карта проезда появится после согласования. Адрес: ",
        "minsk_map": "Институт на карте Минска",
        "minsk_pin": "ИХНМ НАН Беларуси",
        "minsk_city": "Минск",
        "labs_heading": "Лаборатории",
        "admin_heading": "Административные подразделения",
        "community_heading": "Общественные объединения",
        "union_nas_note": (
            "Профсоюз работников НАН Беларуси"
        ),
        "union_nas_aside": "(общеакадемический сайт, не замена этой страницы).",
        "contact_pending": "Контакт появится после передачи состава.",
        "placeholder": (
            "Плейсхолдер. Текст будет перенесён с ichnm.by "
            "или придёт в пакете к переключению."
        ),
        "photo_pending": "Фото появится после передачи файла",
        "photo_slot": "Место для фото: ",
        "official_photo_slot": "Место для официального фото: ",
        "official_photo_pending": "Официальное фото появится после передачи файла Институтом",
        "description_contacts": "Описание и контакты",
        "lab_label": "Лаборатория: ",
        "assigned": "Закреплено: ",
        "spec": "Спецификация",
        "product": "Продукт / результат",
        "units": "Подразделения",
        "biography": "Биография",
        "interests": "Научные интересы",
        "selected_pubs": "Избранные публикации",
        "to_structure": "К структуре Института",
        "h_index": "h-индекс",
        "citations": "цитирований",
        "profile": "профиль",
        "metrics_empty": "Профили и показатели ещё не указаны",
        "metrics_empty_text": (
            "ORCID, Google Scholar, Scopus, eLIBRARY/РИНЦ и ResearchGate появятся "
            "после передачи ссылок. Индекс Хирша и число цитирований вносит "
            "сотрудник или редактор — сайт базы сам не опрашивает."
        ),
        "metrics": "Наукометрия",
        "metrics_note": (
            "Цифры и ссылки вносит сотрудник или редактор. "
            "Сайт не подтягивает базы автоматически."
        ),
        "photo_archive": "Фотоархив",
        "coop_map": "Карта сотрудничества",
        "unit_pending": "Подразделение появится после передачи состава.",
        "back_to_units": "Ко всем подразделениям",
        "unit_phone": "Тел. подразделения: ",
        "tel": "Тел. ",
        "fish_filled": (
            "Наполненный макет. ФИО, приборы, разработки и статьи лабораторий — рыба: "
            "лаборатория наноструктур на букву А, оптических плёнок — Б, ЖК — В, "
            "композитов — Г, лесохимии — Д. Это не официальные сведения."
        ),
        "fish_honest": (
            "Макет. Рыбный текст и пустые слоты показывают структуру. "
            "Замените данными и файлами Института — это не официальные сведения."
        ),
        "mock_file": "Макетный файл",
        "file_missing": "Файл не загружен",
        "file_slots": "Слоты для документов",
        "articles_n": "статей",
        "pub_list_pending": "Список появится из пакетов лабораторий.",
        "articles_by_year": "Статьи по годам",
        "chart_hint": "Наведите на столбец, чтобы увидеть число",
        "pubs_labs": "Публикации лабораторий",
        "lab_kicker_default": "Подразделение Института",
        "lab_about": "О лаборатории",
        "lab_directions": "Направления",
        "lab_projects": "Действующие и завершённые научные проекты",
        "lab_projects_active": "Действующие",
        "lab_projects_done": "Завершённые",
        "lab_projects_pending": "Список проектов появится из пакета лаборатории.",
        "project_active": "Действующий",
        "project_completed": "Завершённый",
        "lab_equipment": "Оборудование",
        "lab_services": "Услуги и разработки",
        "lab_team": "Команда",
        "lab_pubs": "Публикации",
        "lab_contacts": "Контакты",
        "lab_local": "Разделы лаборатории",
        "lab_about_placeholder": (
            "Подразделение Института химии новых материалов НАН Беларуси. "
            "Пакет страницы собран как у лабораторных сайтов (локальное меню, команда, "
            "приборы, контакты), но живёт на ichnm.by — не отдельный домен в v1."
        ),
        "lab_about_fish": (
            "Рыба: лаборатория ведёт работы по направлению, указанному в названии. "
            "Тексты задач, приборов и договоров появятся из пакета подразделения."
        ),
        "directions_pending": "Направления появятся из пакета лаборатории.",
        "equipment_pending": "Список приборов появится из пакета лаборатории.",
        "services_pending": "Карточки разработок появятся из пакета лаборатории.",
        "lab_pubs_pending": "Избранные публикации появятся из пакета лаборатории.",
        "staff_tab_note": (
            "Карточка ведёт на персональную страницу. У человека может быть несколько подразделений. "
            "Индекс Хирша и профили баз — на персональной странице."
        ),
        "our_team": "Наша команда",
        "address_label": "Адрес: ",
        "head_label": "Заведующий: ",
        "read_original": "Читать оригинал",
        "press": "Внешние СМИ",
        "navuka": "Газета «Навука»",
        "navuka_note": (
            "Онлайн-версии материалов академической газеты. Полные PDF-выпуски — "
        ),
        "navuka_archive": "в архиве gazeta-navuka.by",
        "stats_label": "Цифры об институте",
        "years_work": "Опыт работы",
        "years_aria": "лет опыта работы",
        "labs_count": "Лабораторий",
        "labs_aria": "лабораторий",
        "units_link": "Подразделения",
        "main_directions": "Основные направления",
        "home_dir_1": "Тонкоплёночные и наноструктурированные материалы",
        "home_dir_1p": "Органические плёнки и наноструктуры с заданными свойствами.",
        "home_dir_2": "Композиты лесо- и нефтехимии",
        "home_dir_2p": "Материалы на основе продуктов лесо- и нефтехимии и технологии их получения.",
        "home_dir_3": "Синтез новых органических соединений",
        "home_dir_3p": "Потенциальные физиологически активные вещества.",
        "home_news": "Актуальные новости и мероприятия",
        "all_units": "Все подразделения",
        "overview_pill": "Сведения",
        "filled_ribbon": (
            "Наполненный макет: рыба по буквам лабораторий "
            "(А — наноструктуры, Б — плёнки, В — ЖК, Г — композиты, Д — лесохимия). "
            "Не официальная версия сайта."
        ),
        "home_intro": (
            "Институт является ведущим в Республике Беларусь и одним из лидеров в странах СНГ "
            "в фундаментальных и прикладных исследованиях в области создания тонкопленочных "
            "и наноструктурированных органических материалов, новых композиционных материалов "
            "с заданными свойствами, методов синтеза новых органических соединений в качестве "
            "потенциальных физиологически активных веществ."
        ),
        "search_page_lead": (
            "Поиск по персоналиям, подразделениям, приборам и разработкам. "
            "Введите запрос в поле шапки или здесь."
        ),
        "search_query": "Запрос",
        "search_example": "Например: Рогачёв, нано, поляроид",
        "sitemap_lead": (
            "Все публичные разделы, как карта сайта на nasb.gov.by. "
            "Лаборатории стоят внутри структуры."
        ),
        "sitemap_people": "Персоналии руководства",
        "sitemap_search": "Поиск по персоналиям и разработкам",
        "search_js_hint": "Введите не меньше двух букв: персоналии, подразделения, разработки.",
        "search_js_empty": "Ничего не найдено. Попробуйте фамилию, лабораторию, прибор или разработку.",
        "search_kind_person": "Персоналии",
        "search_kind_unit": "Подразделения",
        "search_kind_facility": "Приборы",
        "search_kind_development": "Разработки",
        "search_unit_lab": "Лаборатория",
        "search_unit_admin": "Административное подразделение",
        "search_unit_union": "Первичная организация",
        "search_unit_smu": "СМУ Института",
        "search_unit_lead": "Дирекция",
        "search_unit_council": "Состав совета",
        "search_dev": "Разработка",
        "search_fac": "Прибор",
        "lattice_group_grid": "Сетка и атомы",
        "lattice_hex": "Показывать шестигранную сетку",
        "lattice_title": "Сетка атомов",
        "lattice_lead": (
            "Только случайные кольца. Курсор сетку не трогает. "
            "Цвет общий для всех волн и идёт по кругу, минуя жёлтый через белый."
        ),
        "lattice_save": "Сохранить в браузере",
        "lattice_copy": "Скопировать JSON",
        "lattice_download": "Скачать JSON",
        "lattice_reset": "Сброс",
        "lattice_home": "На главную",
        "lattice_note": (
            "Главная читает сохранённый профиль из этого браузера. "
            "Чтобы зафиксировать вид в коде — пришлите JSON в чат."
        ),
        "picto_president": "Президент Республики Беларусь",
        "picto_gov": "Совет Министров",
        "picto_pravo": "Национальный правовой Интернет-портал",
        "picto_navuka": "Газета «Навука»",
        "picto_union": "Профсоюз работников НАН Беларуси",
        "picto_nas": "Национальная академия наук Беларуси",
        "picto_brsm": "БРСМ",
        "picto_innosfera": "Журнал «Наука и инновации»",
        "picto_pac": "Академия управления при Президенте",
        "picto_rctt": "РЦТТ",
        "picto_forum": "Правовой форум Беларуси",
        "picto_asio": "АСИО НАН Беларуси",
        "picto_belisa": "БелИСА",
        "picto_quality": "Портал рейтинговой оценки",
        "picto_ssf": "ФСЗН: накопительная пенсия",
        "legal_president": "Президент Республики Беларусь",
        "legal_gov": "Совет Министров Республики Беларусь",
        "legal_pravo": "Национальный правовой Интернет-портал",
        "legal_navuka": "Газета «Навука»",
        "legal_union": "Профсоюз работников НАН Беларуси",
        "legal_nas": "Национальная академия наук Беларуси",
    },
    "en": {
        "home": "Home",
        "crumbs": "Breadcrumb",
        "section_pages": "Pages in this section",
        "copy_note": (
            "The site structure is in English. Institute texts (news, biographies, "
            "scientific copy) remain in Russian until an official translation."
        ),
        "nas_emblem_alt": "Emblem of the National Academy of Sciences of Belarus",
        "icnm_mark_alt": "ICNM emblem",
        "street": "36 F. Skaryna Street, Minsk",
        "address_full": "220084, Minsk, 36 F. Skaryna Street",
        "on_this_page": "On this page",
        "institute_news": "Institute news",
        "news_kicker": "News",
        "media_kicker": "Media",
        "all_news": "All news",
        "back_to_news": "All news",
        "next_event": "Next event",
        "aist_section": "AIST section",
        "register_cycle": "Register for the current cycle",
        "register": "Registration",
        "aist_archive": "AIST archive",
        "other_conferences": "Other Institute conferences",
        "other_conferences_lead": (
            "Slots for events outside AIST. A new page can be added to this archive "
            "without putting the conference on a separate domain."
        ),
        "back_to_events": "Event archive",
        "conference_placeholder": (
            "The programme, proceedings and key dates for this conference will appear "
            "after the materials are supplied. The page lives in the ichnm.by archive, "
            "not on a separate domain."
        ),
        "aist_series": "AIST series · a section on ichnm.by",
        "aist_body": (
            "The current cycle’s programme, abstracts and resolution are on this page. "
            "Registration and the historic series site remain at "
        ),
        "aist_no_domain": " — v1 does not raise a separate domain.",
        "aist_materials": "Conference files",
        "aist_org": "Organising committee",
        "aist_secretary": "Conference secretary: ",
        "aist_press": "Press about the conference",
        "news_source": "Text and photos from the current ichnm.by",
        "all_events": "Full event archive",
        "feedback_lead": (
            "The form opens a message to the reception desk. Statutory electronic appeals are on "
        ),
        "separate_page": "a separate page",
        "form_name": "Name",
        "form_email": "Email",
        "form_message": "Message",
        "form_send": "Send",
        "science_institute": "Institute-wide research areas",
        "science_labs": "Laboratory research areas",
        "dev_institute": "Institute-wide developments",
        "dev_labs": "Laboratory developments",
        "fac_institute": "Institute-wide equipment",
        "fac_labs": "Laboratory equipment",
        "partners": "Partners",
        "back_to_news_smi": "Institute news and media",
        "no_announcements": "No announcements yet",
        "no_announcements_text": (
            "Service notes will appear here, separately from news and events."
        ),
        "no_vacancies": "No open vacancies",
        "no_vacancies_text": (
            "When a post opens, it will be published in this section. HR: +375 (17) 243-67-56."
        ),
        "research_directions": "Research areas",
        "awards": "Awards and achievements",
        "list_heading": "List",
        "requisites_link": "Bank details",
        "feedback_form_link": "Feedback form",
        "map_aria": "The institute on the map of Minsk",
        "map_pending": "The travel map will appear after approval. Address: ",
        "minsk_map": "The institute on the map of Minsk",
        "minsk_pin": "ICNM NASB",
        "minsk_city": "Minsk",
        "labs_heading": "Laboratories",
        "admin_heading": "Administrative units",
        "community_heading": "Public associations",
        "union_nas_note": "Trade union of NASB employees",
        "union_nas_aside": "(Academy-wide site, not a replacement for this page).",
        "contact_pending": "The contact will appear after the roster is supplied.",
        "placeholder": (
            "Placeholder. Text will be moved from ichnm.by or arrive in the cutover packet."
        ),
        "photo_pending": "The photo will appear after the file is supplied",
        "photo_slot": "Photo placeholder: ",
        "official_photo_slot": "Official photo placeholder: ",
        "official_photo_pending": "The official photo will appear after the Institute supplies the file",
        "description_contacts": "Description and contacts",
        "lab_label": "Laboratory: ",
        "assigned": "Assigned: ",
        "spec": "Specification",
        "product": "Product / result",
        "units": "Units",
        "biography": "Biography",
        "interests": "Research interests",
        "selected_pubs": "Selected publications",
        "to_structure": "Institute structure",
        "h_index": "h-index",
        "citations": "citations",
        "profile": "profile",
        "metrics_empty": "Profiles and metrics are not listed yet",
        "metrics_empty_text": (
            "ORCID, Google Scholar, Scopus, eLIBRARY/RSCI and ResearchGate will appear "
            "after the links are supplied. The h-index and citation counts are entered "
            "by a staff member or editor — the site does not query the databases."
        ),
        "metrics": "Bibliometrics",
        "metrics_note": (
            "Figures and links are entered by a staff member or editor. "
            "The site does not pull the databases automatically."
        ),
        "photo_archive": "Photo archive",
        "coop_map": "Cooperation map",
        "unit_pending": "The unit will appear after the roster is supplied.",
        "back_to_units": "All units",
        "unit_phone": "Unit phone: ",
        "tel": "Tel. ",
        "fish_filled": (
            "Filled mock. Names, instruments, developments and papers of the laboratories "
            "are dummy copy letter-coded by lab (A nanostructures, B films, C liquid crystals, "
            "D composites, E wood chemistry). Not official information."
        ),
        "fish_honest": (
            "Mock. Dummy text and empty slots show the structure. "
            "Replace with Institute data and files — this is not official information."
        ),
        "mock_file": "Dummy file",
        "file_missing": "File not uploaded",
        "file_slots": "Document slots",
        "articles_n": "papers",
        "pub_list_pending": "The list will appear from the laboratory packets.",
        "articles_by_year": "Papers by year",
        "chart_hint": "Hover a bar to see the count",
        "pubs_labs": "Laboratory publications",
        "lab_kicker_default": "Institute unit",
        "lab_about": "About the laboratory",
        "lab_directions": "Research areas",
        "lab_projects": "Current and completed research projects",
        "lab_projects_active": "Current",
        "lab_projects_done": "Completed",
        "lab_projects_pending": "The project list will appear from the laboratory packet.",
        "project_active": "Current",
        "project_completed": "Completed",
        "lab_equipment": "Equipment",
        "lab_services": "Services and developments",
        "lab_team": "Team",
        "lab_pubs": "Publications",
        "lab_contacts": "Contacts",
        "lab_local": "Laboratory sections",
        "lab_about_placeholder": (
            "A unit of the Institute of Chemistry of New Materials of NASB. "
            "The page pack follows laboratory-site practice (local menu, team, "
            "instruments, contacts) but lives on ichnm.by — not a separate domain in v1."
        ),
        "lab_about_fish": (
            "Dummy copy: the laboratory works in the area named in its title. "
            "Task, instrument and contract texts will come from the unit packet."
        ),
        "directions_pending": "Research areas will appear from the laboratory packet.",
        "equipment_pending": "The instrument list will appear from the laboratory packet.",
        "services_pending": "Development cards will appear from the laboratory packet.",
        "lab_pubs_pending": "Selected publications will appear from the laboratory packet.",
        "staff_tab_note": (
            "The card opens the personal page. A person may belong to several units. "
            "The h-index and database profiles live on the personal page."
        ),
        "our_team": "Our team",
        "address_label": "Address: ",
        "head_label": "Head: ",
        "read_original": "Read the original",
        "press": "External media",
        "navuka": "Newspaper Navuka",
        "navuka_note": (
            "Online versions of the Academy newspaper. Full PDF issues are "
        ),
        "navuka_archive": "in the gazeta-navuka.by archive",
        "stats_label": "Institute figures",
        "years_work": "Years of work",
        "years_aria": "years of work",
        "labs_count": "Laboratories",
        "labs_aria": "laboratories",
        "units_link": "Units",
        "main_directions": "Main research areas",
        "home_dir_1": "Thin-film and nanostructured materials",
        "home_dir_1p": "Organic films and nanostructures with tailored properties.",
        "home_dir_2": "Forest- and petrochemistry composites",
        "home_dir_2p": "Materials from forest- and petrochemical products and the technologies to make them.",
        "home_dir_3": "Synthesis of new organic compounds",
        "home_dir_3p": "Potential physiologically active substances.",
        "home_news": "Latest news and events",
        "all_units": "All units",
        "overview_pill": "Overview",
        "filled_ribbon": (
            "Filled mock: dummy copy letter-coded by laboratory "
            "(A nanostructures, B films, C liquid crystals, D composites, E wood chemistry). "
            "Not the official site."
        ),
        "home_intro": (
            "The Institute is a leader in Belarus and among the CIS countries in fundamental "
            "and applied research on thin-film and nanostructured organic materials, new "
            "composites with tailored properties, and methods for synthesising new organic "
            "compounds as potential physiologically active substances."
        ),
        "search_page_lead": (
            "Search people, units, instruments and developments. "
            "Type in the header field or here."
        ),
        "search_query": "Query",
        "search_example": "For example: Rogachev, nano, polaroid",
        "sitemap_lead": (
            "Every public section, in the same class as the nasb.gov.by sitemap. "
            "Laboratories sit inside Structure."
        ),
        "sitemap_people": "Leadership people",
        "sitemap_search": "Search people and developments",
        "search_js_hint": "Type at least two letters: people, units, developments.",
        "search_js_empty": "Nothing found. Try a surname, laboratory, instrument or development.",
        "search_kind_person": "People",
        "search_kind_unit": "Units",
        "search_kind_facility": "Instruments",
        "search_kind_development": "Developments",
        "search_unit_lab": "Laboratory",
        "search_unit_admin": "Administrative unit",
        "search_unit_union": "Primary organisation",
        "search_unit_smu": "Institute CYS",
        "search_unit_lead": "Directorate",
        "search_unit_council": "Council membership",
        "search_dev": "Development",
        "search_fac": "Instrument",
        "lattice_group_grid": "Grid and atoms",
        "lattice_hex": "Show the hexagonal grid",
        "lattice_title": "Atom lattice",
        "lattice_lead": (
            "Random rings only. The cursor does not touch the grid. "
            "Colour is shared by all waves and cycles through white, skipping yellow."
        ),
        "lattice_save": "Save in this browser",
        "lattice_copy": "Copy JSON",
        "lattice_download": "Download JSON",
        "lattice_reset": "Reset",
        "lattice_home": "Home",
        "lattice_note": (
            "The homepage reads the saved profile from this browser. "
            "To lock the look in code, send the JSON in chat."
        ),
        "picto_president": "President of the Republic of Belarus",
        "picto_gov": "Council of Ministers",
        "picto_pravo": "National Legal Internet Portal",
        "picto_navuka": "Newspaper Navuka",
        "picto_union": "Trade union of NASB employees",
        "picto_nas": "National Academy of Sciences of Belarus",
        "picto_brsm": "BRSM",
        "picto_innosfera": "Journal Science and Innovations",
        "picto_pac": "Academy of Public Administration",
        "picto_rctt": "RCTT",
        "picto_forum": "Legal Forum of Belarus",
        "picto_asio": "NASB ASIO",
        "picto_belisa": "BelISA",
        "picto_quality": "Public service quality portal",
        "picto_ssf": "SSF: funded pension",
        "legal_president": "President of the Republic of Belarus",
        "legal_gov": "Council of Ministers of the Republic of Belarus",
        "legal_pravo": "National Legal Internet Portal",
        "legal_navuka": "Newspaper Navuka",
        "legal_union": "Trade union of NASB employees",
        "legal_nas": "National Academy of Sciences of Belarus",
    },
    "be": {
        "home": "Галоўная",
        "crumbs": "Навігацыя",
        "section_pages": "Старонкі раздзела",
        "copy_note": (
            "Структура сайта па-беларуску. Тэксты інстытута (навіны, біяграфіі, "
            "навуковыя абзацы) застаюцца па-руску да афіцыйнага перакладу."
        ),
        "nas_emblem_alt": "Эмблема Нацыянальнай акадэміі навук Беларусі",
        "icnm_mark_alt": "Эмблема ІХНМ",
        "street": "вул. Ф. Скарыны, 36, Мінск",
        "address_full": "220084, г. Мінск, вул. Ф. Скарыны, 36",
        "on_this_page": "На гэтай старонцы",
        "institute_news": "Навіны Інстытута",
        "news_kicker": "Навіна",
        "media_kicker": "СМІ",
        "all_news": "Усе навіны",
        "back_to_news": "Да ўсіх навін",
        "next_event": "Бліжэйшае мерапрыемства",
        "aist_section": "Раздзел AIST",
        "register_cycle": "Рэгістрацыя бягучага цыкла",
        "register": "Рэгістрацыя",
        "aist_archive": "Архіў AIST",
        "other_conferences": "Іншыя канферэнцыі Інстытута",
        "other_conferences_lead": (
            "Слоты для мерапрыемстваў па-за AIST. Новую старонку можна дадаць у гэты архіў, "
            "не выносячы канферэнцыю на асобны дамен."
        ),
        "back_to_events": "Да архіва мерапрыемстваў",
        "conference_placeholder": (
            "Праграма, зборнік і ключавыя даты гэтай канферэнцыі з’явяцца пасля перадачы "
            "матэрыялаў. Старонка жыве ў архіве ichnm.by, не на асобным дамене."
        ),
        "aist_series": "Серыя AIST · раздзел на ichnm.by",
        "aist_body": (
            "Матэрыялы бягучага цыкла — праграма, тэзісы і рэзалюцыя — на гэтай старонцы. "
            "Рэгістрацыя і гістарычны сайт серыі пакуль на "
        ),
        "aist_no_domain": " — асобны дамен у v1 не падымаем.",
        "aist_materials": "Матэрыялы канферэнцыі",
        "aist_org": "Аргкамітэт",
        "aist_secretary": "Сакратар канферэнцыі: ",
        "aist_press": "СМІ пра канферэнцыю",
        "news_source": "Тэкст і фота з бягучага ichnm.by",
        "all_events": "Увесь архіў мерапрыемстваў",
        "feedback_lead": (
            "Форма адкрые ліст на прыёмную. Электронныя звароты па законе — "
        ),
        "separate_page": "асобная старонка",
        "form_name": "Імя",
        "form_email": "Электронная пошта",
        "form_message": "Паведамленне",
        "form_send": "Адправіць",
        "science_institute": "Агульнаінстытуцкія напрамкі",
        "science_labs": "Напрамкі лабараторый",
        "dev_institute": "Агульнаінстытуцкія распрацоўкі",
        "dev_labs": "Распрацоўкі лабараторый",
        "fac_institute": "Агульнаінстытуцкае абсталяванне",
        "fac_labs": "Абсталяванне лабараторый",
        "partners": "Партнёры",
        "back_to_news_smi": "Да навін Інстытута і СМІ",
        "no_announcements": "Аб’яў пакуль няма",
        "no_announcements_text": (
            "Службовыя паведамленні з’явяцца тут, асобна ад навін і мерапрыемстваў."
        ),
        "no_vacancies": "Адкрытых вакансій няма",
        "no_vacancies_text": (
            "Калі з’явіцца стаўка, яе апублікуюць у гэтым раздзеле. Пытанні ў аддзел кадраў: "
            "+375 (17) 243-67-56."
        ),
        "research_directions": "Напрамкі даследаванняў",
        "awards": "Узнагароды і дасягненні",
        "list_heading": "Пералік",
        "requisites_link": "Рэквізіты",
        "feedback_form_link": "Форма зваротнай сувязі",
        "map_aria": "Інстытут на карце Мінска",
        "map_pending": "Карта праезду з’явіцца пасля ўзгаднення. Адрас: ",
        "minsk_map": "Інстытут на карце Мінска",
        "minsk_pin": "ІХНМ НАН Беларусі",
        "minsk_city": "Мінск",
        "labs_heading": "Лабараторыі",
        "admin_heading": "Адміністрацыйныя падраздзяленні",
        "community_heading": "Грамадскія аб’яднанні",
        "union_nas_note": "Прафсаюз работнікаў НАН Беларусі",
        "union_nas_aside": "(агульнаакадэмічны сайт, не замена гэтай старонкі).",
        "contact_pending": "Кантакт з’явіцца пасля перадачы складу.",
        "placeholder": (
            "Плэйсхолдар. Тэкст будзе перанесены з ichnm.by "
            "або прыйдзе ў пакеце да пераключэння."
        ),
        "photo_pending": "Фота з’явіцца пасля перадачы файла",
        "photo_slot": "Месца для фота: ",
        "official_photo_slot": "Месца для афіцыйнага фота: ",
        "official_photo_pending": "Афіцыйнае фота з’явіцца пасля перадачы файла Інстытутам",
        "description_contacts": "Апісанне і кантакты",
        "lab_label": "Лабараторыя: ",
        "assigned": "Замацавана: ",
        "spec": "Спецыфікацыя",
        "product": "Прадукт / вынік",
        "units": "Падраздзяленні",
        "biography": "Біяграфія",
        "interests": "Навуковыя інтарэсы",
        "selected_pubs": "Выбраныя публікацыі",
        "to_structure": "Да структуры Інстытута",
        "h_index": "h-індэкс",
        "citations": "цытаванняў",
        "profile": "профіль",
        "metrics_empty": "Профілі і паказчыкі яшчэ не пазначаны",
        "metrics_empty_text": (
            "ORCID, Google Scholar, Scopus, eLIBRARY/РІНЦ і ResearchGate з’явяцца "
            "пасля перадачы спасылак. Індэкс Хірша і лік цытаванняў уносіць "
            "супрацоўнік або рэдактар — сайт базы сам не апытвае."
        ),
        "metrics": "Навукаметрыя",
        "metrics_note": (
            "Лічбы і спасылкі ўносіць супрацоўнік або рэдактар. "
            "Сайт не падцягвае базы аўтаматычна."
        ),
        "photo_archive": "Фотаархіў",
        "coop_map": "Карта супрацоўніцтва",
        "unit_pending": "Падраздзяленне з’явіцца пасля перадачы складу.",
        "back_to_units": "Да ўсіх падраздзяленняў",
        "unit_phone": "Тэл. падраздзялення: ",
        "tel": "Тэл. ",
        "fish_filled": (
            "Напоўнены макет. Прозвішчы, прыборы, распрацоўкі і артыкулы лабараторый — рыба: "
            "лабараторыя нанаструктур на літару А, аптычных плёнак — Б, ВК — В, "
            "кампазітаў — Г, лесахіміі — Д. Гэта не афіцыйныя звесткі."
        ),
        "fish_honest": (
            "Макет. Рыбны тэкст і пустыя слоты паказваюць структуру. "
            "Замяніце данымі і файламі Інстытута — гэта не афіцыйныя звесткі."
        ),
        "mock_file": "Макетны файл",
        "file_missing": "Файл не загружаны",
        "file_slots": "Слоты для дакументаў",
        "articles_n": "артыкулаў",
        "pub_list_pending": "Спіс з’явіцца з пакетаў лабараторый.",
        "articles_by_year": "Артыкулы па гадах",
        "chart_hint": "Навядзіце на слупок, каб убачыць лік",
        "pubs_labs": "Публікацыі лабараторый",
        "lab_kicker_default": "Падраздзяленне Інстытута",
        "lab_about": "Пра лабараторыю",
        "lab_directions": "Напрамкі",
        "lab_projects": "Дзеючыя і завершаныя навуковыя праекты",
        "lab_projects_active": "Дзеючыя",
        "lab_projects_done": "Завершаныя",
        "lab_projects_pending": "Спіс праектаў з’явіцца з пакета лабараторыі.",
        "project_active": "Дзеючы",
        "project_completed": "Завершаны",
        "lab_equipment": "Абсталяванне",
        "lab_services": "Паслугі і распрацоўкі",
        "lab_team": "Каманда",
        "lab_pubs": "Публікацыі",
        "lab_contacts": "Кантакты",
        "lab_local": "Раздзелы лабараторыі",
        "lab_about_placeholder": (
            "Падраздзяленне Інстытута хіміі новых матэрыялаў НАН Беларусі. "
            "Пакет старонкі сабраны як у лабараторных сайтаў, але жыве на ichnm.by — "
            "не асобны дамен у v1."
        ),
        "lab_about_fish": (
            "Рыба: лабараторыя вядзе работы па напрамку, указаным у назве. "
            "Тэксты задач, прыбораў і дагавораў з’явяцца з пакета падраздзялення."
        ),
        "directions_pending": "Напрамкі з’явяцца з пакета лабараторыі.",
        "equipment_pending": "Спіс прыбораў з’явіцца з пакета лабараторыі.",
        "services_pending": "Карткі распрацовак з’явяцца з пакета лабараторыі.",
        "lab_pubs_pending": "Выбраныя публікацыі з’явяцца з пакета лабараторыі.",
        "staff_tab_note": (
            "Картка вядзе на персанальную старонку. У чалавека можа быць некалькі падраздзяленняў. "
            "Індэкс Хірша і профілі баз — на персанальнай старонцы."
        ),
        "our_team": "Наша каманда",
        "address_label": "Адрас: ",
        "head_label": "Загадчык: ",
        "read_original": "Чытаць арыгінал",
        "press": "Знешнія СМІ",
        "navuka": "Газета «Навука»",
        "navuka_note": (
            "Анлайн-версіі матэрыялаў акадэмічнай газеты. Поўныя PDF-выпускі — "
        ),
        "navuka_archive": "у архіве gazeta-navuka.by",
        "stats_label": "Лічбы пра інстытут",
        "years_work": "Вопыт працы",
        "years_aria": "гадоў вопыту працы",
        "labs_count": "Лабараторый",
        "labs_aria": "лабараторый",
        "units_link": "Падраздзяленні",
        "main_directions": "Асноўныя напрамкі",
        "home_dir_1": "Тонкаплёнкавыя і нанаструктураваныя матэрыялы",
        "home_dir_1p": "Арганічныя плёнкі і нанаструктуры з зададзенымі ўласцівасцямі.",
        "home_dir_2": "Кампазіты леса- і нафтахіміі",
        "home_dir_2p": "Матэрыялы на аснове прадуктаў леса- і нафтахіміі і тэхналогіі іх атрымання.",
        "home_dir_3": "Сінтэз новых арганічных злучэнняў",
        "home_dir_3p": "Патенцыйныя фізіялагічна актыўныя рэчывы.",
        "home_news": "Актуальныя навіны і мерапрыемствы",
        "all_units": "Усе падраздзяленні",
        "overview_pill": "Звесткі",
        "filled_ribbon": (
            "Напоўнены макет: рыба па літарах лабараторый "
            "(А — нанаструктуры, Б — плёнкі, В — ВК, Г — кампазіты, Д — лесахімія). "
            "Не афіцыйная версія сайта."
        ),
        "home_intro": (
            "Інстытут з’яўляецца вядучым у Рэспубліцы Беларусь і адным з лідараў у краінах СНД "
            "у фундаментальных і прыкладных даследаваннях у галіне стварэння тонкаплёнкавых "
            "і нанаструктураваных арганічных матэрыялаў, новых кампазіцыйных матэрыялаў "
            "з зададзенымі ўласцівасцямі, метадаў сінтэзу новых арганічных злучэнняў як "
            "патэнцыйных фізіялагічна актыўных рэчываў."
        ),
        "search_page_lead": (
            "Пошук па персаналіях, падраздзяленнях, прыборах і распрацоўках. "
            "Увядзіце запыт у поле шапкі або тут."
        ),
        "search_query": "Запыт",
        "search_example": "Напрыклад: Рагачоў, нана, паляроід",
        "sitemap_lead": (
            "Усе публічныя раздзелы, як карта сайта на nasb.gov.by. "
            "Лабараторыі стаяць унутры структуры."
        ),
        "sitemap_people": "Персаналіі кіраўніцтва",
        "sitemap_search": "Пошук па персаналіях і распрацоўках",
        "search_js_hint": "Увядзіце не менш за дзве літары: персаналіі, падраздзяленні, распрацоўкі.",
        "search_js_empty": "Нічога не знойдзена. Паспрабуйце прозвішча, лабараторыю, прыбор або распрацоўку.",
        "search_kind_person": "Персаналіі",
        "search_kind_unit": "Падраздзяленні",
        "search_kind_facility": "Прыборы",
        "search_kind_development": "Распрацоўкі",
        "search_unit_lab": "Лабараторыя",
        "search_unit_admin": "Адміністрацыйнае падраздзяленне",
        "search_unit_union": "Першасная арганізацыя",
        "search_unit_smu": "СМВ Інстытута",
        "search_unit_lead": "Дырэкцыя",
        "search_unit_council": "Склад савета",
        "search_dev": "Распрацоўка",
        "search_fac": "Прыбор",
        "lattice_group_grid": "Сетка і атамы",
        "lattice_hex": "Паказваць шасцігранную сетку",
        "lattice_title": "Сетка атамаў",
        "lattice_lead": (
            "Толькі выпадковыя кольцы. Курсор сетку не чапае. "
            "Колер агульны для ўсіх хваль і ідзе па крузе, мінаючы жоўты праз белы."
        ),
        "lattice_save": "Захаваць у браўзеры",
        "lattice_copy": "Скапіраваць JSON",
        "lattice_download": "Спампаваць JSON",
        "lattice_reset": "Скід",
        "lattice_home": "На галоўную",
        "lattice_note": (
            "Галоўная чытае захаваны профіль з гэтага браўзера. "
            "Каб зафіксаваць выгляд у кодзе — прышліце JSON у чат."
        ),
        "picto_president": "Прэзідэнт Рэспублікі Беларусь",
        "picto_gov": "Савет Міністраў",
        "picto_pravo": "Нацыянальны прававы Інтэрнэт-партал",
        "picto_navuka": "Газета «Навука»",
        "picto_union": "Прафсаюз работнікаў НАН Беларусі",
        "picto_nas": "Нацыянальная акадэмія навук Беларусі",
        "picto_brsm": "БРСМ",
        "picto_innosfera": "Часопіс «Навука і інавацыі»",
        "picto_pac": "Акадэмія кіравання пры Прэзідэнце",
        "picto_rctt": "РЦТТ",
        "picto_forum": "Прававы форум Беларусі",
        "picto_asio": "АСІА НАН Беларусі",
        "picto_belisa": "БелІСА",
        "picto_quality": "Партал рэйтынгавай ацэнкі",
        "picto_ssf": "ФСАН: накапляльная пенсія",
        "legal_president": "Прэзідэнт Рэспублікі Беларусь",
        "legal_gov": "Савет Міністраў Рэспублікі Беларусь",
        "legal_pravo": "Нацыянальны прававы Інтэрнэт-партал",
        "legal_navuka": "Газета «Навука»",
        "legal_union": "Прафсаюз работнікаў НАН Беларусі",
        "legal_nas": "Нацыянальная акадэмія навук Беларусі",
    },
    "zh": {
        "home": "首页",
        "crumbs": "当前位置",
        "section_pages": "本栏目页面",
        "copy_note": (
            "网站结构已提供中文。研究所正文（新闻、简历、科学文稿）"
            "在正式翻译完成前仍为俄文。"
        ),
        "nas_emblem_alt": "白俄罗斯国家科学院院徽",
        "icnm_mark_alt": "新材料化学研究所所徽",
        "street": "明斯克斯卡里纳大街 36 号",
        "address_full": "220084，明斯克，斯卡里纳大街 36 号",
        "on_this_page": "本页",
        "institute_news": "研究所新闻",
        "news_kicker": "新闻",
        "media_kicker": "媒体",
        "all_news": "全部新闻",
        "back_to_news": "全部新闻",
        "next_event": "近期活动",
        "aist_section": "AIST 栏目",
        "register_cycle": "本届注册",
        "register": "注册",
        "aist_archive": "AIST 档案",
        "other_conferences": "研究所其他会议",
        "other_conferences_lead": (
            "AIST 以外活动的预留位置。新页面可加入本档案，无需单独域名。"
        ),
        "back_to_events": "活动档案",
        "conference_placeholder": (
            "本会议的日程、论文集和关键日期将在材料提交后发布。"
            "页面位于 ichnm.by 档案中，不是独立域名。"
        ),
        "aist_series": "AIST 系列 · ichnm.by 栏目",
        "aist_body": (
            "本届日程、摘要和决议在本页。注册和系列历史站点仍在 "
        ),
        "aist_no_domain": " — 第一版不另设独立域名。",
        "aist_materials": "会议材料",
        "aist_org": "组委会",
        "aist_secretary": "会议秘书：",
        "aist_press": "会议媒体报道",
        "news_source": "来自现行 ichnm.by 的正文与照片",
        "all_events": "全部活动档案",
        "feedback_lead": (
            "表单将向接待室发信。法定电子诉求见"
        ),
        "separate_page": "单独页面",
        "form_name": "姓名",
        "form_email": "电子邮箱",
        "form_message": "留言",
        "form_send": "发送",
        "science_institute": "所级研究方向",
        "science_labs": "各实验室研究方向",
        "dev_institute": "所级成果",
        "dev_labs": "各实验室成果",
        "fac_institute": "所级设备",
        "fac_labs": "各实验室设备",
        "partners": "合作伙伴",
        "back_to_news_smi": "研究所新闻与媒体",
        "no_announcements": "暂无公告",
        "no_announcements_text": "公务通知将在此发布，与新闻和活动分开。",
        "no_vacancies": "暂无招聘",
        "no_vacancies_text": (
            "有岗位时将在本栏目公布。人事处电话：+375 (17) 243-67-56。"
        ),
        "research_directions": "研究方向",
        "awards": "奖项与成果",
        "list_heading": "列表",
        "requisites_link": "银行信息",
        "feedback_form_link": "反馈表",
        "map_aria": "明斯克地图上的研究所",
        "map_pending": "交通地图将在核准后发布。地址：",
        "minsk_map": "明斯克地图上的研究所",
        "minsk_pin": "白俄罗斯国家科学院新材料化学研究所",
        "minsk_city": "明斯克",
        "labs_heading": "实验室",
        "admin_heading": "行政部门",
        "community_heading": "社会团体",
        "union_nas_note": "白俄罗斯国家科学院工会",
        "union_nas_aside": "（全院网站，不能替代本页）。",
        "contact_pending": "联系方式将在人员名单提交后公布。",
        "placeholder": "占位。正文将从 ichnm.by 迁入或随切换材料提供。",
        "photo_pending": "照片将在文件提交后出现",
        "photo_slot": "照片占位：",
        "official_photo_slot": "正式照片占位：",
        "official_photo_pending": "正式照片将在研究所提供文件后出现",
        "description_contacts": "说明与联系方式",
        "lab_label": "实验室：",
        "assigned": "负责人：",
        "spec": "规格",
        "product": "产品 / 成果",
        "units": "所属部门",
        "biography": "简历",
        "interests": "科研兴趣",
        "selected_pubs": "代表性论文",
        "to_structure": "返回机构设置",
        "h_index": "h 指数",
        "citations": "被引",
        "profile": "主页",
        "metrics_empty": "尚无主页与指标",
        "metrics_empty_text": (
            "ORCID、Google Scholar、Scopus、eLIBRARY/RSCI 和 ResearchGate "
            "将在链接提供后显示。h 指数和被引次数由工作人员或编辑填写——"
            "网站不自动查询数据库。"
        ),
        "metrics": "文献计量",
        "metrics_note": "数字与链接由工作人员或编辑填写。网站不自动抓取数据库。",
        "photo_archive": "图片档案",
        "coop_map": "合作地图",
        "unit_pending": "该部门将在人员名单提交后出现。",
        "back_to_units": "全部部门",
        "unit_phone": "部门电话：",
        "tel": "电话 ",
        "fish_filled": (
            "填充预览。各实验室姓名、仪器、成果和论文为按字母编码的占位内容"
            "（A 纳米结构、B 薄膜、C 液晶、D 复合材料、E 林产化学）。非正式信息。"
        ),
        "fish_honest": (
            "预览。占位文字和空槽展示结构。请用研究所数据和文件替换——非正式信息。"
        ),
        "mock_file": "占位文件",
        "file_missing": "尚未上传文件",
        "file_slots": "文件槽位",
        "articles_n": "篇",
        "pub_list_pending": "列表将来自各实验室材料包。",
        "articles_by_year": "按年论文数",
        "chart_hint": "将指针移到柱上可查看数量",
        "pubs_labs": "各实验室论文",
        "lab_kicker_default": "研究所部门",
        "lab_about": "实验室简介",
        "lab_directions": "研究方向",
        "lab_projects": "在研与已完成科研项目",
        "lab_projects_active": "在研",
        "lab_projects_done": "已完成",
        "lab_projects_pending": "项目列表将来自实验室材料包。",
        "project_active": "在研",
        "project_completed": "已完成",
        "lab_equipment": "仪器设备",
        "lab_services": "服务与成果",
        "lab_team": "团队",
        "lab_pubs": "论文",
        "lab_contacts": "联系方式",
        "lab_local": "实验室栏目",
        "lab_about_placeholder": (
            "白俄罗斯国家科学院新材料化学研究所下属部门。"
            "页面结构参照实验室站点（本地菜单、团队、仪器、联系方式），"
            "但位于 ichnm.by——第一版不设独立域名。"
        ),
        "lab_about_fish": (
            "占位：实验室工作对应其名称所示方向。"
            "任务、仪器和合同文本将来自部门材料包。"
        ),
        "directions_pending": "研究方向将来自实验室材料包。",
        "equipment_pending": "仪器列表将来自实验室材料包。",
        "services_pending": "成果卡片将来自实验室材料包。",
        "lab_pubs_pending": "代表性论文将来自实验室材料包。",
        "staff_tab_note": (
            "卡片打开个人主页。同一人可隶属多个部门。"
            "h 指数和数据库主页在个人页上。"
        ),
        "our_team": "我们的团队",
        "address_label": "地址：",
        "head_label": "主任：",
        "read_original": "阅读原文",
        "press": "外部媒体",
        "navuka": "《科学》报",
        "navuka_note": "科学院报纸的网络版。完整 PDF 见",
        "navuka_archive": "gazeta-navuka.by 档案",
        "stats_label": "研究所数字",
        "years_work": "工作年限",
        "years_aria": "年工作经验",
        "labs_count": "实验室",
        "labs_aria": "个实验室",
        "units_link": "部门",
        "main_directions": "主要方向",
        "home_dir_1": "薄膜与纳米结构材料",
        "home_dir_1p": "具有指定性能的有机薄膜与纳米结构。",
        "home_dir_2": "林产与石油化工复合材料",
        "home_dir_2p": "基于林产与石油化工产品的材料及其制备技术。",
        "home_dir_3": "新有机化合物合成",
        "home_dir_3p": "潜在生理活性物质。",
        "home_news": "最新新闻与活动",
        "all_units": "全部部门",
        "overview_pill": "概况",
        "filled_ribbon": (
            "填充预览：按实验室字母编码的占位内容"
            "（A 纳米结构、B 薄膜、C 液晶、D 复合材料、E 林产化学）。非正式网站。"
        ),
        "home_intro": (
            "本所是白俄罗斯及独联体国家在薄膜与纳米结构有机材料、"
            "指定性能新型复合材料，以及作为潜在生理活性物质的新有机化合物合成方法方面"
            "基础与应用研究的领先机构之一。"
        ),
        "search_page_lead": "搜索人员、部门、仪器和成果。请在页眉或此处输入。",
        "search_query": "查询",
        "search_example": "例如：Rogachev、纳米、偏振片",
        "sitemap_lead": "全部公开栏目，体例同 nasb.gov.by 网站地图。实验室位于机构设置下。",
        "sitemap_people": "领导人员",
        "sitemap_search": "搜索人员与成果",
        "search_js_hint": "请至少输入两个字符：人员、部门、成果。",
        "search_js_empty": "没有结果。请尝试姓氏、实验室、仪器或成果。",
        "search_kind_person": "人员",
        "search_kind_unit": "部门",
        "search_kind_facility": "仪器",
        "search_kind_development": "成果",
        "search_unit_lab": "实验室",
        "search_unit_admin": "行政部门",
        "search_unit_union": "基层组织",
        "search_unit_smu": "研究所青科委",
        "search_unit_lead": "所领导",
        "search_unit_council": "委员会组成",
        "search_dev": "成果",
        "search_fac": "仪器",
        "lattice_group_grid": "网格与原子",
        "lattice_hex": "显示六边形网格",
        "lattice_title": "原子网格",
        "lattice_lead": "仅随机圆环。光标不作用于网格。所有波共用颜色，循环中经白色跳过黄色。",
        "lattice_save": "保存在本浏览器",
        "lattice_copy": "复制 JSON",
        "lattice_download": "下载 JSON",
        "lattice_reset": "重置",
        "lattice_home": "返回首页",
        "lattice_note": "首页读取本浏览器中保存的配置。若要写入代码，请在对话中发送 JSON。",
        "picto_president": "白俄罗斯共和国总统",
        "picto_gov": "部长会议",
        "picto_pravo": "国家法律互联网门户",
        "picto_navuka": "《科学》报",
        "picto_union": "白俄罗斯国家科学院工会",
        "picto_nas": "白俄罗斯国家科学院",
        "picto_brsm": "白俄罗斯青年团",
        "picto_innosfera": "《科学与创新》杂志",
        "picto_pac": "总统管理学院",
        "picto_rctt": "技术转让中心",
        "picto_forum": "白俄罗斯法律论坛",
        "picto_asio": "国家科学院 ASIO",
        "picto_belisa": "BelISA",
        "picto_quality": "服务质量评价门户",
        "picto_ssf": "社保基金：积累养老金",
        "legal_president": "白俄罗斯共和国总统",
        "legal_gov": "白俄罗斯共和国部长会议",
        "legal_pravo": "国家法律互联网门户",
        "legal_navuka": "《科学》报",
        "legal_union": "白俄罗斯国家科学院工会",
        "legal_nas": "白俄罗斯国家科学院",
    },
}

_PICTO_BY_HREF = {
    "https://president.gov.by/": "picto_president",
    "https://www.government.by/": "picto_gov",
    "https://www.pravo.by/": "picto_pravo",
    "https://gazeta-navuka.by/": "picto_navuka",
    "https://profnan.by/": "picto_union",
    "https://nasb.gov.by/rus/index.php": "picto_nas",
    "https://www.brsm.by/": "picto_brsm",
    "https://innosfera.by/": "picto_innosfera",
    "https://www.pac.by/": "picto_pac",
    "https://ictt.by/rus/": "picto_rctt",
    "https://forumpravo.by/": "picto_forum",
    "https://asio.basnet.by/": "picto_asio",
    "https://www.belisa.org.by/izdaniya/magazin/zhurnal-novosti-nauki-i-tekhnologiy/": "picto_belisa",
    "https://качество-услуг.бел/": "picto_quality",
    "https://www.ssf.gov.by/ru/dobrovolnoe-strahovanie-dopolnitelnoj-nakopitelnoj-pensii-ru": "picto_ssf",
}

_LEGAL_BY_HREF = {
    "https://president.gov.by/": "legal_president",
    "https://www.government.by/": "legal_gov",
    "https://www.pravo.by/": "legal_pravo",
    "https://gazeta-navuka.by/": "legal_navuka",
    "https://profnan.by/": "legal_union",
    "https://nasb.gov.by/rus/index.php": "legal_nas",
}


def current_locale() -> str:
    return LOCALE.get()


def string(key: str, locale: str | None = None) -> str:
    code = locale or current_locale()
    pack = STRINGS.get(code) or STRINGS["ru"]
    if key in pack:
        return pack[key]
    return STRINGS["ru"].get(key, key)


def menu_title(item_id: str, fallback: str, locale: str | None = None) -> str:
    code = locale or current_locale()
    if code == "ru":
        return fallback
    return MENU.get(code, {}).get(item_id, fallback)


def lab_title(lab_id: str, fallback: str, locale: str | None = None) -> str:
    code = locale or current_locale()
    if code == "ru":
        return fallback
    return LABS.get(code, {}).get(lab_id, fallback)


def hub_blurb(item_id: str, fallback: str, locale: str | None = None) -> str:
    code = locale or current_locale()
    if code == "ru":
        return fallback
    return HUB.get(code, {}).get(item_id, fallback)


def localize_menu_item(item: MenuItem, locale: str) -> MenuItem:
    return MenuItem(
        id=item.id,
        title=menu_title(item.id, item.title, locale),
        kind=item.kind,
        children=tuple(localize_menu_item(child, locale) for child in item.children),
        href=item.href,
    )


def pictogram_title(href: str, fallback: str, locale: str | None = None) -> str:
    code = locale or current_locale()
    if code == "ru":
        return fallback
    key = _PICTO_BY_HREF.get(href)
    return string(key, code) if key else fallback


def legal_link_title(href: str, fallback: str, locale: str | None = None) -> str:
    code = locale or current_locale()
    if code == "ru":
        return fallback
    key = _LEGAL_BY_HREF.get(href)
    return string(key, code) if key else fallback


def lab_from_row(row: dict[str, Any], locale: str | None = None) -> str:
    return lab_title(str(row.get("id") or ""), str(row.get("title") or ""), locale)
