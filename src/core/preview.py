from __future__ import annotations

import json
import re
from contextvars import ContextVar
from datetime import date
from hashlib import sha1
from html import escape
from pathlib import Path

from src.core import i18n, roster
from src.core.copy import is_filled_copy, load_migrated_copy, page_copy
from src.core.filled import fish_svg, letter_for_lab
from src.core.search_index import build_search_index
from src.core.site_model import MenuItem, SiteModel

_HERE = Path(__file__).resolve().parent

_ASSET_PREFIX: ContextVar[str] = ContextVar("asset_prefix", default="")
_CURRENT_LETTER: ContextVar[str] = ContextVar("current_letter", default="И")
_LOCALE = i18n.LOCALE
_NEST: ContextVar[int] = ContextVar("nest", default=0)
_REL_PATH: ContextVar[str] = ContextVar("rel_path", default="index.html")
_FISH_FILES: dict[str, str] = {}

_VISIBLE_MENU = frozenset(
    {
        "about",
        "news",
        "events",
        "contacts",
    }
)

_METRIC_LABELS = {
    "orcid": "ORCID",
    "google_scholar": "Google Scholar",
    "scopus_author": "Scopus Author",
    "elibrary": "eLIBRARY / РИНЦ",
    "researchgate": "ResearchGate",
}

_LOCALE_SECTIONS = (
    ("about", "nav_about"),
    ("news", "nav_news"),
    ("events", "nav_events"),
    ("contacts", "nav_contacts"),
)

_CHROME: dict[str, dict[str, str]] = {
    "ru": {
        "skip": "К содержанию",
        "search": "Поиск",
        "sitemap": "Карта сайта",
        "night": "Ночная тема",
        "bvi": "Версия для слабовидящих",
        "write": "Написать нам",
        "menu": "Меню",
        "lang": "Язык",
        "submenu": "Подменю",
        "nav_label": "Разделы",
        "kicker": "Национальная академия наук Беларуси",
        "brand": "Институт химии новых материалов",
        "brand_short": "ИХНМ",
        "title_suffix": "ИХНМ НАН Беларуси",
        "nav_about": "Об институте",
        "nav_news": "Новости",
        "nav_events": "Мероприятия",
        "nav_contacts": "Контакты",
        "cookie_title": "Файлы cookie",
        "cookie_body": (
            "Сайт запоминает язык, тему и версию для слабовидящих. Это необходимые cookie. "
            "Аналитические cookie в макете выключены. Подробнее — "
        ),
        "cookie_policy": "политика cookie",
        "cookie_personal": "персональные данные",
        "cookie_accept": "Принять",
        "cookie_reject": "Отклонить необязательные",
        "cookie_settings": "Настроить",
        "cookie_necessary": "Необходимые (всегда)",
        "cookie_analytics": "Аналитические",
        "cookie_save": "Сохранить",
        "day": "Дневная тема",
        "year_unknown": "Год не указан",
        "to_ru": "Русская версия",
        "home": "Главная",
    },
    "en": {
        "skip": "Skip to content",
        "search": "Search",
        "sitemap": "Sitemap",
        "night": "Dark mode",
        "bvi": "Visually impaired version",
        "write": "Write to us",
        "menu": "Menu",
        "lang": "Language",
        "submenu": "Submenu",
        "nav_label": "Sections",
        "kicker": "National Academy of Sciences of Belarus",
        "brand": "Institute of Chemistry of New Materials",
        "brand_short": "ICNM",
        "title_suffix": "ICNM NASB",
        "nav_about": "About",
        "nav_news": "News",
        "nav_events": "Events",
        "nav_contacts": "Contacts",
        "cookie_title": "Cookies",
        "cookie_body": (
            "The site remembers language, theme, and the visually impaired mode. "
            "These are necessary cookies. Analytics cookies are off in this mock. See "
        ),
        "cookie_policy": "cookie policy",
        "cookie_personal": "personal data",
        "cookie_accept": "Accept",
        "cookie_reject": "Reject optional",
        "cookie_settings": "Settings",
        "cookie_necessary": "Necessary (always on)",
        "cookie_analytics": "Analytics",
        "cookie_save": "Save",
        "day": "Light mode",
        "year_unknown": "Year not stated",
        "to_ru": "Russian version",
        "stub_title": "English version in preparation",
        "stub_lead": (
            "The public site launches in Russian. English pages keep the same "
            "addresses from day one; institute texts will be translated after v1."
        ),
        "stub_note": "Until then, the Russian pages remain the official source.",
        "home": "home",
    },
    "be": {
        "skip": "Да зместу",
        "search": "Пошук",
        "sitemap": "Карта сайта",
        "night": "Начны рэжым",
        "bvi": "Версія для слабавідушчых",
        "write": "Напісаць нам",
        "menu": "Меню",
        "lang": "Мова",
        "submenu": "Падменю",
        "nav_label": "Раздзелы",
        "kicker": "Нацыянальная акадэмія навук Беларусі",
        "brand": "Інстытут хіміі новых матэрыялаў",
        "brand_short": "ІХНМ",
        "title_suffix": "ІХНМ НАН Беларусі",
        "nav_about": "Пра інстытут",
        "nav_news": "Навіны",
        "nav_events": "Мерапрыемствы",
        "nav_contacts": "Кантакты",
        "cookie_title": "Файлы cookie",
        "cookie_body": (
            "Сайт запамінае мову, тэму і версію для слабавідушчых. Гэта неабходныя cookie. "
            "Аналітычныя cookie ў макеце выключаны. Падрабязней — "
        ),
        "cookie_policy": "палітыка cookie",
        "cookie_personal": "персанальныя даныя",
        "cookie_accept": "Прыняць",
        "cookie_reject": "Адхіліць неабавязковыя",
        "cookie_settings": "Наладзіць",
        "cookie_necessary": "Неабходныя (заўсёды)",
        "cookie_analytics": "Аналітычныя",
        "cookie_save": "Захаваць",
        "day": "Дзённы рэжым",
        "year_unknown": "Год не пазначаны",
        "to_ru": "Руская версія",
        "stub_title": "Беларуская версія рыхтуецца",
        "stub_lead": (
            "Публічны сайт запускаецца па-руску. Адрасы /be/ закладзены з першага дня; "
            "тэксты інстытута з’явяцца пасля перакладу."
        ),
        "stub_note": "Да перакладу афіцыйнай застаецца руская версія.",
        "home": "галоўная",
    },
    "zh": {
        "skip": "跳到正文",
        "search": "搜索",
        "sitemap": "网站地图",
        "night": "深色模式",
        "bvi": "无障碍版本",
        "write": "联系我们",
        "menu": "菜单",
        "lang": "语言",
        "submenu": "子菜单",
        "nav_label": "栏目",
        "kicker": "白俄罗斯国家科学院",
        "brand": "新材料化学研究所",
        "brand_short": "新材料所",
        "title_suffix": "白俄罗斯国家科学院新材料化学研究所",
        "nav_about": "关于研究所",
        "nav_news": "新闻",
        "nav_events": "活动",
        "nav_contacts": "联系我们",
        "cookie_title": "Cookie",
        "cookie_body": (
            "网站会记住语言、主题和无障碍模式。这些是必要 Cookie。"
            "本预览中分析 Cookie 已关闭。详见 "
        ),
        "cookie_policy": "Cookie 政策",
        "cookie_personal": "个人数据",
        "cookie_accept": "接受",
        "cookie_reject": "拒绝非必要项",
        "cookie_settings": "设置",
        "cookie_necessary": "必要（始终开启）",
        "cookie_analytics": "分析",
        "cookie_save": "保存",
        "day": "浅色模式",
        "year_unknown": "年份未注明",
        "to_ru": "俄文版",
        "stub_title": "中文版正在准备中",
        "stub_lead": "网站以俄文发布。/zh/ 路径从第一天起保留，研究所文稿将在翻译后填入。",
        "stub_note": "在此之前，俄文页面为正式文本。",
        "home": "首页",
    },
}

_CHROME_MORE: dict[str, dict[str, str]] = {
    "ru": {
        "bvi_font": "Шрифт",
        "bvi_colors": "Цвета",
        "bvi_images": "Изображения",
        "bvi_spacing": "Интервал",
        "bvi_off": "Обычная версия",
        "footer_institute": "Институт",
        "footer_on_site": "На сайте",
        "footer_nas_socials": "Соцсети НАН",
        "footer_webmail": "Веб-почта института — адрес появится после PHP-тарифа.",
        "footer_find_us": "Как нас найти",
        "footer_map_alt": "Институт на карте Минска, ул. Ф. Скорины, 36",
        "footer_pictograms": "Ресурсы Академии и государственные порталы",
        "footer_media": "СМИ о нас",
        "footer_education": "Научно-ориентированное образование",
        "footer_union": "Профсоюз",
        "footer_publications": "Публикации",
        "footer_feedback": "Обратная связь",
        "footer_lattice": "Демо сетки атомов",
        "search_label": "Поиск по сайту",
        "search_placeholder": "Персоналии, подразделения, приборы, разработки",
        "search_submit": "Найти",
        "search_close": "Закрыть",
        "search_hint": "Как на портале НАН: живой список и отдельная страница результатов.",
        "cookies_page_title": "Политика cookie",
        "cookies_p1": (
            "Институт использует файлы cookie, чтобы сайт работал и чтобы запомнить "
            "ваш выбор оформления. Это тот же класс юридических элементов, что на "
            "bsu.by (баннер, политика, настройка категорий)."
        ),
        "cookies_h_needed": "Необходимые",
        "cookies_p_needed": (
            "Язык интерфейса, дневная/ночная тема, панель для слабовидящих, решение по cookie. "
            "Без них страница каждый раз сбрасывается к виду по умолчанию."
        ),
        "cookies_h_analytics": "Аналитические",
        "cookies_p_analytics": (
            "В макете выключены. Если институт позже подключит счётчик, категория появится "
            "в настройках баннера. Сейчас отказ и согласие только сохраняют ваш выбор."
        ),
        "cookies_personal_link": "Обработка персональных данных",
        "cookies_appeals_link": "Электронные обращения",
        "personal_page_title": "Персональные данные",
        "personal_operator": (
            "Оператор: Государственное научное учреждение «Институт химии новых материалов "
            "Национальной академии наук Беларуси», ул. Ф. Скорины, 36, Минск."
        ),
        "personal_p2": (
            "Обращения по персональным данным — через электронные обращения или форму обратной связи. "
            "Полный текст политики появится после передачи юридического файла Институтом. "
            "Этот текст — каркас страницы, не официальная политика."
        ),
        "legal_mock": "Макет. Не используйте формулировку как утверждённый документ.",
    },
    "en": {
        "bvi_font": "Font size",
        "bvi_colors": "Colours",
        "bvi_images": "Images",
        "bvi_spacing": "Spacing",
        "bvi_off": "Standard version",
        "footer_institute": "Institute",
        "footer_on_site": "On this site",
        "footer_nas_socials": "NAS social media",
        "footer_webmail": "Institute webmail — the address will appear after the PHP hosting plan.",
        "footer_find_us": "How to find us",
        "footer_map_alt": "The institute on the Minsk map, 36 Skaryna Street",
        "footer_pictograms": "Academy resources and government portals",
        "footer_media": "Media about us",
        "footer_education": "Research-oriented education",
        "footer_union": "Trade union",
        "footer_publications": "Publications",
        "footer_feedback": "Feedback",
        "footer_lattice": "Atom lattice demo",
        "search_label": "Site search",
        "search_placeholder": "People, units, instruments, developments",
        "search_submit": "Search",
        "search_close": "Close",
        "search_hint": "Live results and a separate results page, as on the NAS portal.",
        "cookies_page_title": "Cookie policy",
        "cookies_p1": (
            "The institute uses cookies so the site can work and so it can remember "
            "your display choices. This is the same class of legal elements as on bsu.by "
            "(banner, policy, category settings)."
        ),
        "cookies_h_needed": "Necessary",
        "cookies_p_needed": (
            "Interface language, light/dark theme, visually impaired panel, and your cookie choice. "
            "Without them the page resets to the default look every time."
        ),
        "cookies_h_analytics": "Analytics",
        "cookies_p_analytics": (
            "Off in this mock. If the institute later adds a counter, the category will appear "
            "in the banner settings. For now, accept and reject only store your choice."
        ),
        "cookies_personal_link": "Personal data",
        "cookies_appeals_link": "Electronic appeals",
        "personal_page_title": "Personal data",
        "personal_operator": (
            "Controller: State scientific institution “Institute of Chemistry of New Materials "
            "of the National Academy of Sciences of Belarus”, 36 F. Skaryna Street, Minsk."
        ),
        "personal_p2": (
            "Requests about personal data go through electronic appeals or the feedback form. "
            "The full policy will appear after the institute supplies the legal file. "
            "This page is a shell, not an approved policy."
        ),
        "legal_mock": "Mock. Do not treat this wording as an approved document.",
    },
    "be": {
        "bvi_font": "Шрыфт",
        "bvi_colors": "Колеры",
        "bvi_images": "Выявы",
        "bvi_spacing": "Інтэрвал",
        "bvi_off": "Звычайная версія",
        "footer_institute": "Інстытут",
        "footer_on_site": "На сайце",
        "footer_nas_socials": "Сацсеткі НАН",
        "footer_webmail": "Вэб-пошта інстытута — адрас з’явіцца пасля PHP-тарыфу.",
        "footer_find_us": "Як нас знайсці",
        "footer_map_alt": "Інстытут на карце Мінска, вул. Ф. Скарыны, 36",
        "footer_pictograms": "Рэсурсы Акадэміі і дзяржаўныя парталы",
        "footer_media": "СМІ пра нас",
        "footer_education": "Навукова-арыентаваная адукацыя",
        "footer_union": "Прафсаюз",
        "footer_publications": "Публікацыі",
        "footer_feedback": "Зваротная сувязь",
        "footer_lattice": "Дэма сеткі атамаў",
        "search_label": "Пошук па сайце",
        "search_placeholder": "Персаналіі, падраздзяленні, прыборы, распрацоўкі",
        "search_submit": "Знайсці",
        "search_close": "Закрыць",
        "search_hint": "Як на партале НАН: жывы спіс і асобная старонка вынікаў.",
        "cookies_page_title": "Палітыка cookie",
        "cookies_p1": (
            "Інстытут выкарыстоўвае файлы cookie, каб сайт працаваў і каб запомніць "
            "ваш выбар афармлення. Гэта той жа клас юрыдычных элементаў, што на bsu.by "
            "(банер, палітыка, налады катэгорый)."
        ),
        "cookies_h_needed": "Неабходныя",
        "cookies_p_needed": (
            "Мова інтэрфейсу, дзённая/начная тэма, панэль для слабавідушчых, рашэнне па cookie. "
            "Без іх старонка кожны раз скідаецца да выгляду па змаўчанні."
        ),
        "cookies_h_analytics": "Аналітычныя",
        "cookies_p_analytics": (
            "У макеце выключаны. Калі інстытут пазней падключыць лічыльнік, катэгорыя з’явіцца "
            "ў наладах банера. Зараз адмова і згода толькі захоўваюць ваш выбар."
        ),
        "cookies_personal_link": "Апрацоўка персанальных даных",
        "cookies_appeals_link": "Электронныя звароты",
        "personal_page_title": "Персанальныя даныя",
        "personal_operator": (
            "Аператар: Дзяржаўная навуковая ўстанова «Інстытут хіміі новых матэрыялаў "
            "Нацыянальнай акадэміі навук Беларусі», вул. Ф. Скарыны, 36, Мінск."
        ),
        "personal_p2": (
            "Звароты па персанальных даных — праз электронныя звароты або форму зваротнай сувязі. "
            "Поўны тэкст палітыкі з’явіцца пасля перадачы юрыдычнага файла Інстытутам. "
            "Гэты тэкст — каркас старонкі, не афіцыйная палітыка."
        ),
        "legal_mock": "Макет. Не выкарыстоўвайце фармулёўку як зацверджаны дакумент.",
    },
    "zh": {
        "bvi_font": "字体",
        "bvi_colors": "颜色",
        "bvi_images": "图像",
        "bvi_spacing": "间距",
        "bvi_off": "标准版",
        "footer_institute": "研究所",
        "footer_on_site": "本站",
        "footer_nas_socials": "科学院社交账号",
        "footer_webmail": "研究所网页邮箱——地址将在开通 PHP 主机后公布。",
        "footer_find_us": "如何找到我们",
        "footer_map_alt": "明斯克地图上的研究所，斯卡里纳大街 36 号",
        "footer_pictograms": "科学院资源与政府门户",
        "footer_media": "媒体报道",
        "footer_education": "科研导向教育",
        "footer_union": "工会",
        "footer_publications": "论文",
        "footer_feedback": "反馈",
        "footer_lattice": "原子网格演示",
        "search_label": "站内搜索",
        "search_placeholder": "人员、部门、仪器、成果",
        "search_submit": "搜索",
        "search_close": "关闭",
        "search_hint": "与科学院门户同类：即时列表和单独的结果页。",
        "cookies_page_title": "Cookie 政策",
        "cookies_p1": (
            "研究所使用 Cookie，以便网站运行并记住您的显示选择。"
            "这与 bsu.by 上的同类法律要素相同（横幅、政策、分类设置）。"
        ),
        "cookies_h_needed": "必要",
        "cookies_p_needed": (
            "界面语言、浅色/深色主题、无障碍面板，以及您对 Cookie 的选择。"
            "没有它们，页面每次都会恢复为默认外观。"
        ),
        "cookies_h_analytics": "分析",
        "cookies_p_analytics": (
            "本预览中已关闭。若研究所日后接入统计，该类别会出现在横幅设置中。"
            "目前接受与拒绝仅保存您的选择。"
        ),
        "cookies_personal_link": "个人数据处理",
        "cookies_appeals_link": "电子诉求",
        "personal_page_title": "个人数据",
        "personal_operator": (
            "控制者：白俄罗斯国家科学院新材料化学研究所（国家科学机构），"
            "明斯克斯卡里纳大街 36 号。"
        ),
        "personal_p2": (
            "有关个人数据的请求可通过电子诉求或反馈表提交。"
            "完整政策将在研究所提供法律文件后发布。"
            "本页仅为框架，不是已批准的政策。"
        ),
        "legal_mock": "预览稿。请勿将此表述视为已批准文件。",
    },
}
for _code, _keys in _CHROME_MORE.items():
    _CHROME[_code].update(_keys)

_LOCALE_CHROME_PAGES = frozenset()

_STUB_SECTION_LEAD = {
    "en": "This section will appear in English after translation.",
    "be": "Гэты раздзел з’явіцца па-беларуску пасля перакладу.",
    "zh": "本栏目将在翻译后提供中文内容。",
}

_HOME_LABELS = {
    "official_intro": "Об институте",
    "news": "Новости",
    "structure_entry": "Структура",
    "developments_entry": "Разработки",
    "next_event": "Ближайшее мероприятие",
}

_WIDE_PAGES = frozenset(
    {
        "leadership",
        "media_about",
        "about",
        "news",
        "events",
        "contacts",
        "facilities",
        "developments",
        "education",
        "documents",
        "science",
        "cooperation",
        "structure",
        "publications",
        "union",
        "young-scientists",
        "vacancies",
        "scientific-council",
        "cookies",
        "personal-data",
        "aist",
        "events",
        "hr",
        "labor-protection",
        "engineering",
        "accounting",
        "about-overview",
        "search",
        "sitemap",
    }
)

_HUB_BLURB = {
    "about-overview": "Задачи и направления исследований",
    "leadership": "Директор, заместители, учёный секретарь, приёмная",
    "scientific-council": "Заседания и состав совета",
    "documents": "Устав, антикоррупция, обращения",
    "charter": "Текст устава — после передачи PDF",
    "anti-corruption": "Положение и план — после передачи PDF",
    "e-appeals": "Куда писать и какие сроки",
    "requisites": "Юридический адрес и банк",
    "aist": "Конференция по сырью и топливу",
    "hr": "Состав и контакты отдела кадров",
    "labor-protection": "Специалист по охране труда",
    "engineering": "Главный инженер Института",
    "accounting": "Главный бухгалтер",
    "education": "Аспирантура, докторантура, совет по защитам",
    "union": "Первичная организация",
    "young-scientists": "Совет молодых учёных Института",
    "media_about": "Публикации в прессе и «Навуке»",
    "developments": "Продукты и методики лабораторий",
    "publications": "Статьи Института и лабораторий",
    "facilities": "Приборы и установки лабораторий",
    "vacancies": "Открытые ставки",
    "structure": "Лаборатории и подразделения",
    "science": "Направления исследований Института",
    "cooperation": "Центры, договоры, карта партнёров",
    "search": "Персоналии, подразделения, разработки",
    "sitemap": "Все разделы одним списком",
    "aspirantura": "Открыта в 1999 году",
    "doctorate": "Правила приёма уточнит учёный секретарь",
    "defense-council": "Специальности и состав — после сверки",
    "internships": "Стажировки для студентов и молодых исследователей",
    "courses": "Повышение квалификации",
}

_LIST_AS_CARDS = frozenset({"about"})

_FISH_FILE_PAGES = {
    "charter": ["Устав ГНУ «ИХНМ НАН Беларуси».pdf"],
    "anti-corruption": ["Положение о противодействии коррупции.pdf", "План мероприятий.pdf"],
    "scientific-council": ["Состав учёного совета.pdf", "Регламент.pdf"],
    "defense-council": ["Состав совета по защитам.pdf", "Специальности.pdf"],
    "internships": ["Положение о стажировках.pdf"],
    "courses": ["Программы курсов.pdf"],
    "doctorate": ["Правила приёма в докторантуру.pdf"],
    "requisites": ["Банковские реквизиты.pdf"],
    "union": ["Положение первичной организации.pdf"],
    "aspirantura": ["Правила приёма в аспирантуру.pdf", "Перечень специальностей.pdf"],
}

_NBSP = "\u00a0"

_TYPO_PHRASES = (
    "Институт химии новых материалов",
    "Национальной академии наук Беларуси",
    "НАН Беларуси",
    "ИХНМ НАН",
    "доктор технических наук",
    "доктор химических наук",
    "кандидат химических наук",
    "кандидат технических наук",
    "член-корреспондент НАН",
    "Ф. Скорины",
)


def _typo(text: str) -> str:
    """Keep titles, street abbreviations, and numbers with their nouns."""
    if not text:
        return ""
    s = text
    for phrase in _TYPO_PHRASES:
        s = s.replace(phrase, phrase.replace(" ", _NBSP))
    s = s.replace("ул. ", "ул." + _NBSP)
    s = s.replace("г. ", "г." + _NBSP)
    s = s.replace("д. ", "д." + _NBSP)
    s = s.replace("тел./факс ", "тел./факс" + _NBSP)
    s = s.replace("тел. ", "тел." + _NBSP)
    s = s.replace("Тел. ", "Тел." + _NBSP)
    s = s.replace("Скорины, 36", "Скорины," + _NBSP + "36")
    s = re.sub(r"\+(\d{3})\s+", r"+\1" + _NBSP, s)
    s = re.sub(r"\((\d{2})\)\s+", r"(\1)" + _NBSP, s)
    s = re.sub(r"([А-ЯЁ]\.)\s+(?=[А-ЯЁа-яё])", r"\1" + _NBSP, s)
    return s


def _t(text: str) -> str:
    return escape(_typo(text))


def _st(key: str) -> str:
    return i18n.string(key)


def _mt(item: MenuItem) -> str:
    return i18n.menu_title(item.id, item.title)


def _lt(lab: dict) -> str:
    return i18n.lab_from_row(lab)


def _page_prefix() -> str:
    return "../" * _NEST.get()


def _asset_prefix() -> str:
    extra = 0 if _LOCALE.get() == "ru" else 1
    return "../" * (_NEST.get() + extra)


def _ui(key: str) -> str:
    pack = _CHROME.get(_LOCALE.get(), _CHROME["ru"])
    if key in pack:
        return pack[key]
    text = i18n.string(key)
    if text != key:
        return text
    return _CHROME["ru"].get(key) or key


def _ui_href(filename: str, prefix: str | None = None) -> str:
    return f"{prefix if prefix is not None else _page_prefix()}{filename}"


def _admin_unit_ids() -> set[str]:
    return {str(unit["id"]) for unit in load_migrated_copy().get("admin_units", [])}


def _begin_page(depth: int = 0, letter: str = "") -> None:
    _NEST.set(depth)
    _ASSET_PREFIX.set(_asset_prefix())
    if letter:
        _CURRENT_LETTER.set(letter)


def _fish_src(kind: str, label: str, letter: str = "") -> str:
    mark = letter or _CURRENT_LETTER.get()
    digest = sha1(f"{kind}:{label}".encode("utf-8")).hexdigest()[:12]
    rel = f"media/fish/{kind}-{digest}.svg"
    _FISH_FILES[rel] = fish_svg(label, mark, kind)
    return _ASSET_PREFIX.get() + rel


def render_site_files(model: SiteModel) -> dict[str, str]:
    """Public HTML files keyed by relative path. Same IA as the WordPress adapter."""
    _FISH_FILES.clear()
    files: dict[str, str] = {}
    files["site.css"] = (_HERE / "preview.css").read_text(encoding="utf-8")
    files["site.js"] = (_HERE / "preview.js").read_text(encoding="utf-8")
    for code in ("ru", "en", "be", "zh"):
        tree = _render_locale_tree(model, code)
        if code == "ru":
            files.update(tree)
        else:
            files.update({f"{code}/{path}": html for path, html in tree.items()})
    files.update(_FISH_FILES)
    return files


def _render_locale_tree(model: SiteModel, code: str) -> dict[str, str]:
    _LOCALE.set(code)
    _begin_page(0)
    files: dict[str, str] = {}

    def put(
        rel: str,
        title: str,
        body: str,
        current: str,
        *,
        depth: int = 0,
        is_home: bool = False,
        extra_body_class: str = "",
    ) -> None:
        files[rel] = _page(
            model,
            title,
            body,
            current,
            is_home=is_home,
            depth=depth,
            extra_body_class=extra_body_class,
            current_code=code,
            rel_path=rel,
        )

    put("index.html", _st("home"), _home_body(model), "home", is_home=True)
    for item in _walk(model.menu_roots()):
        if item.kind == "folder":
            continue
        put(f"{item.id}.html", _mt(item), _vitrine_body(model, item), item.id)
    media_about = MenuItem(
        id="media_about",
        title=i18n.menu_title("media_about", "СМИ о нас"),
        kind="feed",
    )
    put(
        "media_about.html",
        media_about.title,
        _vitrine_body(model, media_about),
        "news",
    )
    for lab in load_migrated_copy().get("labs", []):
        slug = lab.get("slug") or lab["id"]
        title = _lt(lab)
        put(f"{lab['id']}.html", title, _lab_body(model, lab, depth=0), str(lab["id"]))
        put(
            f"labs/{slug}/index.html",
            title,
            _lab_body(model, lab, depth=2),
            str(lab["id"]),
            depth=2,
            extra_body_class="is-lab-site",
        )
    copy = load_migrated_copy()
    for pid, person in roster.all_people().items():
        put(
            f"people/{pid}/index.html",
            person["name"],
            _person_body(model, person, depth=2),
            roster.person_nav_current(person),
            depth=2,
        )
    for person in copy.get("leadership_people", []):
        put(
            f"leadership-{person['id']}.html",
            person["name"],
            _person_body(model, person, depth=0),
            roster.person_nav_current(person),
        )
    for item in copy.get("news", []):
        slug = item.get("slug")
        if not slug:
            continue
        put(
            f"news/{slug}/index.html",
            item["title"],
            _news_article_body(model, item, depth=2),
            "news",
            depth=2,
        )
    for parent, current, items in (
        ("science", "science", roster.science_catalog()),
        ("developments", "developments", roster.developments_catalog()),
        ("facilities", "facilities", roster.facilities_catalog()),
    ):
        for item in items:
            put(
                f"{parent}/{item['slug']}/index.html",
                item["title"],
                _catalog_detail_body(model, parent, item, depth=2),
                current,
                depth=2,
            )
    for person in copy.get("council_people", []):
        record = roster.person(person["id"]) or person
        put(
            f"council-{person['id']}.html",
            person["name"],
            _person_body(model, record, depth=0),
            roster.person_nav_current(record),
        )
    for unit in copy.get("admin_units", []):
        for person in unit.get("people", []):
            pid = person.get("id")
            if not pid:
                continue
            record = roster.person(pid) or person
            put(
                f"office-{pid}.html",
                person["name"],
                _person_body(model, record, depth=0),
                roster.person_nav_current(record),
            )
    for person in roster.people_for_unit("young-scientists") or list(roster.SMU_PEOPLE):
        put(
            f"{person['id']}.html",
            person["name"],
            _person_body(model, person, depth=0),
            "young-scientists",
        )
    put(
        "lattice-demo.html",
        _ui("footer_lattice"),
        _lattice_demo_body(),
        "home",
        extra_body_class="is-lattice-demo",
    )
    files["cookies.html"] = _legal_page(model, "cookies", code)
    files["personal-data.html"] = _legal_page(model, "personal", code)
    for item in copy.get("conferences", []):
        put(
            f"conferences/{item['slug']}/index.html",
            item["title"],
            _conference_body(model, item, depth=2),
            "events",
            depth=2,
        )
    search_title = _ui("search")
    put(
        "search.html",
        search_title,
        _with_page_hero(
            search_title,
            _search_page_inner(),
            trail=_trail(model, "search", search_title),
            wide=True,
        ),
        "search",
    )
    sitemap_title = _ui("sitemap")
    put(
        "sitemap.html",
        sitemap_title,
        _with_page_hero(
            sitemap_title,
            _sitemap_inner(model),
            trail=_trail(model, "sitemap", sitemap_title),
            wide=True,
        ),
        "sitemap",
    )
    files["search.json"] = json.dumps(build_search_index(), ensure_ascii=False, indent=2)
    return files


def _walk(items: tuple[MenuItem, ...]) -> list[MenuItem]:
    out: list[MenuItem] = []
    for item in items:
        out.append(item)
        out.extend(_walk(item.children))
    return out


def _href(page_id: str, depth: int = 0) -> str:
    prefix = "../" * depth
    if page_id == "home":
        return f"{prefix}index.html"
    return f"{prefix}{page_id}.html"


def _item_href(item: MenuItem, depth: int = 0) -> str:
    if item.kind == "folder":
        return ""
    prefix = "../" * depth
    if item.href:
        return f"{prefix}{item.href}"
    return _href(item.id, depth)


def _structure_menu_children(item: MenuItem) -> tuple[MenuItem, ...]:
    labs: list[MenuItem] = []
    for lab in load_migrated_copy().get("labs", []):
        slug = lab.get("slug") or lab["id"]
        labs.append(
            MenuItem(
                id=str(lab["id"]),
                title=_lt(lab),
                kind="lab",
                href=f"labs/{slug}/index.html",
            )
        )
    return tuple(labs + list(item.children))


def _menu_kids(item: MenuItem) -> tuple[MenuItem, ...]:
    if item.id == "structure":
        return _structure_menu_children(item)
    return item.children


def _item_matches(item: MenuItem, current: str) -> bool:
    if item.id == current:
        return True
    return any(_item_matches(child, current) for child in _menu_kids(item))


def _nav(model: SiteModel, current: str, depth: int = 0) -> str:
    admin_ids = _admin_unit_ids()
    community_ids = roster.community_unit_ids()

    def branch(
        items: tuple[MenuItem, ...] | list[MenuItem],
        *,
        admin_sep: bool = False,
    ) -> str:
        parts = ["<ul>"]
        marked_admin = False
        marked_community = False
        for item in items:
            classes: list[str] = []
            if _item_matches(item, current):
                classes.append("is-current")
            kids = _menu_kids(item)
            if kids:
                classes.append("has-children")
            if admin_sep and item.id in admin_ids and not marked_admin:
                classes.append("nav-admin-start")
                marked_admin = True
            if admin_sep and item.id in community_ids and not marked_community:
                classes.append("nav-community-start")
                marked_community = True
            cls = f' class="{" ".join(classes)}"' if classes else ""
            href = _item_href(item, depth)
            parts.append(f"<li{cls}>")
            folder = item.kind == "folder"
            if kids:
                parts.append('<div class="nav-parent">')
            if folder:
                parts.append(f'<span class="nav-folder">{_t(_mt(item))}</span>')
            else:
                parts.append(f'<a href="{href}">{_t(_mt(item))}</a>')
            if kids:
                sep = item.id == "structure"
                parts.append(
                    f'<button type="button" class="submenu-toggle" aria-expanded="false" '
                    f'aria-label="{escape(_ui("submenu") + ": " + _mt(item))}"></button>'
                    "</div>"
                )
                nested = branch(kids, admin_sep=sep)
                if href:
                    self_link = (
                        f'<li class="nav-self"><a href="{href}">{_t(_mt(item))}</a></li>'
                    )
                    nested = nested[:4] + self_link + nested[4:]
                parts.append(nested)
            parts.append("</li>")
        parts.append("</ul>")
        return "".join(parts)

    roots = model.menu_roots()
    visible = tuple(item for item in roots if item.id in _VISIBLE_MENU)
    more = [item for item in roots if item.id not in _VISIBLE_MENU]
    more_open = any(_item_matches(item, current) for item in more)
    more_cls = ' class="has-children is-current"' if more_open else ' class="has-children"'
    more_block = ""
    if more:
        more_block = (
            f"<li{more_cls}><span class=\"nav-more-label\">Ещё</span>"
            '<button type="button" class="submenu-toggle" aria-expanded="false" '
            'aria-label="Подменю: Ещё"></button>'
            f"{branch(more)}</li>"
        )
    primary = branch(visible)
    # Insert "Ещё" into the top-level <ul> before closing.
    if more_block:
        primary = primary[:-5] + more_block + "</ul>"
    return (
        f'<nav id="primary-nav" class="ichnm-menu" aria-label="{escape(_ui("nav_label"))}">'
        f"{primary}</nav>"
    )


def _locale_nav(current: str, depth: int) -> str:
    bits = [
        f'<nav id="primary-nav" class="ichnm-menu" aria-label="{escape(_ui("nav_label"))}"><ul>'
    ]
    for section, key in _LOCALE_SECTIONS:
        href = "index.html" if section == "home" else f"{section}.html"
        cls = ' class="is-current"' if current == section else ""
        bits.append(f'<li{cls}><a href="{escape(href)}">{escape(_ui(key))}</a></li>')
    bits.append("</ul></nav>")
    return "".join(bits)


def _bvi_panel() -> str:
    return (
        '<div class="bvi-panel" id="bvi-panel" hidden>'
        f'<p class="bvi-heading">{escape(_ui("bvi"))}</p>'
        f'<div class="bvi-row"><span>{escape(_ui("bvi_font"))}</span>'
        '<button type="button" data-bvi-size="normal">A</button>'
        '<button type="button" data-bvi-size="large">A+</button>'
        '<button type="button" data-bvi-size="xlarge">A++</button></div>'
        f'<div class="bvi-row"><span>{escape(_ui("bvi_colors"))}</span>'
        '<button type="button" data-bvi-scheme="bw">A</button>'
        '<button type="button" data-bvi-scheme="wb" class="bvi-invert">A</button>'
        '<button type="button" data-bvi-scheme="blue" class="bvi-blue">A</button></div>'
        '<div class="bvi-row">'
        f'<button type="button" data-bvi-images>{escape(_ui("bvi_images"))}</button>'
        f'<button type="button" data-bvi-spacing>{escape(_ui("bvi_spacing"))}</button>'
        f'<button type="button" data-bvi-off>{escape(_ui("bvi_off"))}</button>'
        "</div></div>"
    )


def _cookie_banner(prefix: str) -> str:
    cookie_href = _ui_href("cookies.html", prefix)
    personal_href = _ui_href("personal-data.html", prefix)
    return (
        '<div class="cookie-banner" id="cookie-banner" hidden role="dialog" '
        'aria-modal="true" aria-labelledby="cookie-title">'
        f'<p id="cookie-title">{escape(_ui("cookie_title"))}</p>'
        f"<p>{escape(_ui('cookie_body'))}"
        f'<a href="{cookie_href}">{escape(_ui("cookie_policy"))}</a> · '
        f'<a href="{personal_href}">{escape(_ui("cookie_personal"))}</a>.</p>'
        '<div class="cookie-actions">'
        f'<button type="button" data-cookie="accept">{escape(_ui("cookie_accept"))}</button>'
        f'<button type="button" data-cookie="reject">{escape(_ui("cookie_reject"))}</button>'
        f'<button type="button" data-cookie="settings">{escape(_ui("cookie_settings"))}</button>'
        "</div>"
        '<form class="cookie-settings" id="cookie-settings" hidden>'
        f'<label><input type="checkbox" name="necessary" checked disabled> {escape(_ui("cookie_necessary"))}</label>'
        f'<label><input type="checkbox" name="analytics"> {escape(_ui("cookie_analytics"))}</label>'
        f'<button type="submit">{escape(_ui("cookie_save"))}</button>'
        "</form></div>"
    )


def _social_icon(network: str) -> str:
    paths = {
        "facebook": "M10 3.2h1.8V.9H10c-2 0-3.3 1.2-3.3 3.4v1.5H5v2.4h1.7V16h2.5V8.2h2.1l.4-2.4H9.2V4.6c0-.7.3-1.4 1.4-1.4z",
        "vk": (
            "M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 "
            "8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 "
            "15.684 0zm3.692 17.123h-1.744c-.66 0-.864-.525-2.05-1.727-1.033-1.01"
            "-1.49-1.147-1.744-1.147-.356 0-.458.102-.458.593v1.575c0 .424-.135.688"
            "-1.261.688-1.862 0-3.926-1.126-5.379-3.224C4.24 10.883 3.5 8.68 3.5 "
            "8.36c0-.323.102-.594.593-.594h1.744c.44 0 .61.253.78.843.863 2.49 "
            "2.303 4.677 2.896 4.677.226 0 .338-.105.338-.688V9.721c-.068-1.186"
            "-.695-1.287-.695-1.71 0-.204.17-.407.44-.407h2.744c.44 0 .525.22.525.688"
            "v3.686c0 .355.16.479.254.479.226 0 .407-.124.814-.53 1.254-1.406 "
            "2.151-3.574 2.151-3.574.119-.254.322-.593.763-.593h1.744c.525 0 "
            ".643.27.525.643-.22 1.017-2.354 4.031-2.354 4.031-.186.305-.256.44 "
            "0 .78.186.254.796.779 1.203 1.253.745.847 1.32 1.558 1.473 2.05.17.49"
            "-.085.743-.576.743z"
        ),
        "telegram": "M15.7 2.3 1.8 7.6c-.9.4-.9 1 .2 1.3l3.5 1.1 1.3 4.1c.2.5.3.7.7.7.4 0 .6-.2.8-.5l2-2.1 4.1 3c.8.4 1.3.2 1.5-.7l2.7-12.7c.3-1.1-.4-1.6-1.2-1.3zM6.7 9.9l7.3-4.6-5.7 5.5-.2 2.5-1.4-3.4z",
        "instagram": "M8 4.4A3.6 3.6 0 1 0 8 11.6 3.6 3.6 0 0 0 8 4.4zm0 5.9A2.3 2.3 0 1 1 8 5.7a2.3 2.3 0 0 1 0 4.6zM12.4 4.2a.84.84 0 1 1-1.68 0 .84.84 0 0 1 1.68 0zM14.7 4.3a4.1 4.1 0 0 0-1.1-2.9 4.1 4.1 0 0 0-2.9-1.1H5.3A4.1 4.1 0 0 0 2.4 1.4 4.1 4.1 0 0 0 1.3 4.3v5.4a4.1 4.1 0 0 0 1.1 2.9 4.1 4.1 0 0 0 2.9 1.1h5.4a4.1 4.1 0 0 0 2.9-1.1 4.1 4.1 0 0 0 1.1-2.9V4.3zm-1.3 5.4a2.8 2.8 0 0 1-.8 2 2.8 2.8 0 0 1-2 .8H5.3a2.8 2.8 0 0 1-2-.8 2.8 2.8 0 0 1-.8-2V4.3a2.8 2.8 0 0 1 .8-2 2.8 2.8 0 0 1 2-.8h5.4a2.8 2.8 0 0 1 2 .8 2.8 2.8 0 0 1 .8 2z",
        "youtube": "M15.6 4.4s-.1-1.2-.5-1.7c-.5-.5-1.1-.5-1.3-.6C11.9 2 8 2 8 2h0s-3.9 0-5.8.1c-.3 0-.8.1-1.3.6-.4.5-.5 1.7-.5 1.7S0 5.8 0 7.2v1.6c0 1.4.2 2.8.2 2.8s.1 1.2.5 1.7c.5.5 1.2.5 1.5.6 1.1.1 5.8.1 5.8.1s3.9 0 5.8-.1c.3 0 .8-.1 1.3-.6.4-.5.5-1.7.5-1.7s.2-1.4.2-2.8V7.2c0-1.4-.2-2.8-.2-2.8zM6.4 10.6V5.4L12 8z",
    }
    d = paths.get(network)
    if not d:
        return ""
    box = "0 0 24 24" if network == "vk" else "0 0 16 16"
    return (
        f'<svg class="social-icon" viewBox="{box}" width="16" height="16" aria-hidden="true">'
        f'<path fill="currentColor" d="{d}"/></svg>'
    )


def _langs(model: SiteModel, current_code: str = "ru", depth: int = 0) -> str:
    bits = [f'<nav class="ichnm-lang-switch" aria-label="{escape(_ui("lang"))}">']
    extra = 0 if current_code == "ru" else 1
    root = "../" * (depth + extra)
    rel = _REL_PATH.get() or "index.html"
    for lang in model.languages:
        code = lang.code.upper()
        if lang.code == "ru":
            href = f"{root}{rel}"
        else:
            href = f"{root}{lang.code}/{rel}"
        cls = "is-current" if lang.code == current_code else ""
        bits.append(f'<a class="{cls}" href="{href}">{escape(code)}</a>')
    bits.append("</nav>")
    return "".join(bits)


def _footer(model: SiteModel, depth: int = 0) -> str:
    page = _page_prefix()
    asset = _asset_prefix()
    contacts = page_copy("contacts")
    address = " ".join(contacts.get("paragraphs", [])[1:2]) or _st("street")
    social = "".join(
        f'<li><a href="{escape(profile.href)}">{_social_icon(profile.network)}'
        f"<span>{escape(profile.label or profile.network)}</span></a></li>"
        for profile in model.nas_social_profiles
    )
    pics = []
    for link in model.footer_pictograms:
        title = i18n.pictogram_title(link.href, link.title)
        if link.icon:
            mark = (
                f'<img class="picto-img" src="{asset}{escape(link.icon)}" alt="" width="140" height="66">'
            )
        else:
            mark = f'<span class="picto-mark">{escape(link.mark or title[:2])}</span>'
        pics.append(
            f'<li><a href="{escape(link.href)}" rel="noopener noreferrer">'
            f"{mark}<span class=\"picto-title\">{escape(title)}</span></a></li>"
        )
    pics = "".join(pics)
    site_links = (
        f'<li><a href="{page}news.html">{escape(_ui("nav_news"))}</a></li>'
        f'<li><a href="{page}media_about.html">{escape(_ui("footer_media"))}</a></li>'
        f'<li><a href="{page}education.html">{escape(_ui("footer_education"))}</a></li>'
        f'<li><a href="{page}union.html">{escape(_ui("footer_union"))}</a></li>'
        f'<li><a href="{page}publications.html">{escape(_ui("footer_publications"))}</a></li>'
        f'<li><a href="{page}feedback.html">{escape(_ui("footer_feedback"))}</a></li>'
        f'<li><a href="{page}search.html">{escape(_ui("search"))}</a></li>'
        f'<li><a href="{page}sitemap.html">{escape(_ui("sitemap"))}</a></li>'
        f'<li><a href="{page}cookies.html">{escape(_ui("cookies_page_title"))}</a></li>'
        f'<li><a href="{page}personal-data.html">{escape(_ui("personal_page_title"))}</a></li>'
        f'<li><a href="{page}lattice-demo.html">{escape(_ui("footer_lattice"))}</a></li>'
    )
    return (
        '<footer class="ichnm-footer">'
        '<div class="wrap footer-grid">'
        f'<div><p class="footer-heading">{escape(_ui("footer_institute"))}</p>'
        f'<p class="ichnm-identity"><strong>{_t(model.legal_name)}</strong></p>'
        f"<p>{_t(address)}</p>"
        f'<p><a href="{escape(model.nas_portal_href)}">{escape(_ui("kicker"))}</a></p>'
        "</div>"
        f'<div><p class="footer-heading">{escape(_ui("footer_on_site"))}</p>'
        f'<ul class="ichnm-footer-legal">{site_links}</ul></div>'
        f'<div><p class="footer-heading">{escape(_ui("footer_nas_socials"))}</p>'
        f'<ul class="ichnm-footer-social">{social}</ul>'
        f"<p>{escape(_ui('footer_webmail'))}</p>"
        "</div>"
        '<div class="footer-place">'
        f'<p class="footer-heading">{escape(_ui("footer_find_us"))}</p>'
        f'<a class="footer-map" href="{page}contacts.html" '
        f'aria-label="{escape(_ui("footer_map_alt"))}">'
        f"{_minsk_map(compact=True)}"
        f"<span>{_t(_st('street'))}</span></a>"
        "</div></div>"
        f'<div class="footer-pictograms" aria-label="{escape(_ui("footer_pictograms"))}">'
        '<div class="wrap">'
        f'<p class="footer-heading">{escape(_ui("footer_pictograms"))}</p>'
        f'<ul class="picto-strip">{pics}</ul>'
        "</div></div></footer>"
    )


def _search_overlay(prefix: str) -> str:
    return (
        '<div class="site-search" id="site-search" hidden role="dialog" '
        'aria-modal="true" aria-label="' + escape(_ui("search")) + '">'
        '<div class="site-search-panel">'
        f'<form class="site-search-form" action="{prefix}search.html" method="get" role="search">'
        f'<label class="visually-hidden" for="q-live">{escape(_ui("search_label"))}</label>'
        f'<input id="q-live" name="q" type="search" placeholder="{escape(_ui("search_placeholder"))}" '
        'autocomplete="off">'
        f'<button type="submit">{escape(_ui("search_submit"))}</button>'
        f'<button type="button" data-search-close>{escape(_ui("search_close"))}</button>'
        "</form>"
        f'<p class="search-hint">{escape(_ui("search_hint"))}</p>'
        '<div id="search-live" class="search-live" role="status"></div>'
        "</div></div>"
    )


def _search_page_inner() -> str:
    return (
        f"<p>{escape(_st('search_page_lead'))}</p>"
        '<form class="site-search-form is-page" action="search.html" method="get" role="search">'
        f'<label class="visually-hidden" for="q-page">{escape(_st("search_query"))}</label>'
        f'<input id="q-page" name="q" type="search" placeholder="{escape(_st("search_example"))}">'
        f'<button type="submit">{escape(_ui("search_submit"))}</button>'
        "</form>"
        '<div id="search-results" class="search-results"></div>'
    )


def _sitemap_inner(model: SiteModel) -> str:
    def tree(items: tuple[MenuItem, ...] | list[MenuItem]) -> str:
        parts = ["<ul>"]
        for item in items:
            href = _item_href(item, 0)
            title = _mt(item)
            if item.kind == "folder":
                parts.append(f"<li><span>{_t(title)}</span>")
            else:
                parts.append(f'<li><a href="{href}">{_t(title)}</a>')
            if item.id == "news":
                parts.append(
                    "<ul>"
                    f'<li><a href="news.html#institute-news">{escape(_st("institute_news"))}</a></li>'
                    f'<li><a href="news.html#smi">{escape(_ui("footer_media"))}</a></li>'
                    "</ul>"
                )
            kids = _menu_kids(item)
            if kids:
                parts.append(tree(kids))
            parts.append("</li>")
        parts.append("</ul>")
        return "".join(parts)

    people = "".join(
        f'<li><a href="{escape(roster.person_href(str(row["id"])))}">{escape(row["name"])}</a></li>'
        for row in roster.leadership_people()
    )
    return (
        f"<p>{escape(_st('sitemap_lead'))}</p>"
        f'<nav class="sitemap" aria-label="{escape(_ui("sitemap"))}">{tree(model.menu_roots())}</nav>'
        f"<h2>{escape(_st('sitemap_people'))}</h2>"
        f'<ul class="plain-list">{people}</ul>'
        f'<p><a href="search.html">{escape(_st("sitemap_search"))}</a></p>'
    )


def _shell(
    model: SiteModel,
    title: str,
    body: str,
    current: str,
    depth: int = 0,
    current_code: str = "ru",
    is_home: bool = False,
    extra_body_class: str = "",
    rel_path: str = "",
) -> str:
    _LOCALE.set(current_code)
    _NEST.set(depth)
    if rel_path:
        _REL_PATH.set(rel_path)
    _ASSET_PREFIX.set(_asset_prefix())
    page = _page_prefix()
    asset = _asset_prefix()
    home = f"{page}index.html"
    css = f"{asset}site.css?v=lab-proj1"
    js = f"{asset}site.js?v=lab-proj1"
    mark = f"{asset}media/ichnm-mark.svg?v=bew"
    nas = f"{asset}media/nas-emblem.webp"
    body_class = " ".join(
        part for part in ("is-home" if is_home else "is-inner", extra_body_class) if part
    )
    nav = _nav(model, current, depth=depth)
    nas_block = (
        f'<a class="nas-emblem" href="{escape(model.nas_portal_href)}" '
        f'title="{escape(_ui("kicker"))}">'
        f'<img src="{nas}" width="220" height="120" alt="{escape(_st("nas_emblem_alt"))}">'
        "</a>"
    )
    search_i18n = escape(
        json.dumps(
            {
                "hint": _st("search_js_hint"),
                "empty": _st("search_js_empty"),
                "person": _st("search_kind_person"),
                "unit": _st("search_kind_unit"),
                "facility": _st("search_kind_facility"),
                "development": _st("search_kind_development"),
            },
            ensure_ascii=False,
        )
    )
    boot = (
        "<script>"
        "(function(){"
        "try{"
        'if(document.cookie.indexOf("ichnm-cookies=")!==-1||localStorage.getItem("ichnm-cookies"))'
        '{document.documentElement.classList.add("cookies-ok")}'
        "}catch(e){}"
        "try{"
        'var t=localStorage.getItem("ichnm-theme");'
        "var h=new Date().getHours();"
        'var night=t==="night"||(t!=="day"&&(h>=21||h<7));'
        'if(night)document.documentElement.classList.add("theme-night")'
        "}catch(e){}"
        "try{"
        'var reduce=window.matchMedia("(prefers-reduced-motion: reduce)").matches;'
        "if(!reduce)document.documentElement.classList.add(\"js-motion\")"
        "}catch(e){}"
        "})();"
        "</script>"
    )
    html_lang = {"ru": "ru", "en": "en", "be": "be", "zh": "zh"}.get(current_code, "ru")
    return f"""<!DOCTYPE html>
<html lang="{html_lang}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>{escape(title)} — {escape(_ui("title_suffix"))}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&amp;family=Roboto:wght@400;500;700&amp;display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{css}">
  <link rel="icon" href="{mark}" type="image/svg+xml">
  {boot}
</head>
<body class="{body_class}" data-search-index="{page}search.json" data-search-i18n="{search_i18n}">
  <div class="page-veil" aria-hidden="true"></div>
  <a class="skip-link" href="#content">{escape(_ui("skip"))}</a>
  <header class="site-header">
    <div class="masthead">
      <div class="wrap masthead-inner">
        <div class="brand-row">
          {nas_block}
          <a class="brand" href="{home}">
            <img class="brand-mark" src="{mark}" width="397" height="392" alt="{escape(_st("icnm_mark_alt"))}">
            <span class="brand-lockup">
              <span class="brand-kicker">{escape(_ui("kicker"))}</span>
              <span class="brand-title">{escape(_ui("brand"))}</span>
              <span class="brand-title-short" aria-hidden="true">{escape(_ui("brand_short"))}</span>
            </span>
          </a>
        </div>
        <div class="header-tools">
          {('<button type="button" class="menu-toggle" data-nav-toggle aria-expanded="false" aria-controls="primary-nav">' + escape(_ui("menu")) + "</button>" if nav else "")}
        </div>
        <div class="header-panel">
          <div class="header-utilities">
            <button type="button" class="tool-btn" data-search-open aria-controls="site-search">{escape(_ui("search"))}</button>
            <a class="tool-btn" href="{page}sitemap.html">{escape(_ui("sitemap"))}</a>
            <button type="button" class="tool-btn" data-theme-toggle aria-pressed="false" data-label-day="{escape(_ui('day'))}" data-label-night="{escape(_ui('night'))}">{escape(_ui("night"))}</button>
            <button type="button" class="tool-btn" data-bvi aria-pressed="false" aria-controls="bvi-panel">{escape(_ui("bvi"))}</button>
            <a class="tool-btn" href="{page}feedback.html">{escape(_ui("write"))}</a>
            {_langs(model, current_code=current_code, depth=depth)}
          </div>
          {nav}
        </div>
      </div>
    </div>
    {_bvi_panel()}
  </header>
  <main id="content">{body}</main>
  {_search_overlay(page)}
  {_footer(model, depth=depth)}
  {_cookie_banner(page)}
  <script src="{js}" defer></script>
</body>
</html>
"""


def _page(
    model: SiteModel,
    title: str,
    body: str,
    current: str,
    is_home: bool = False,
    depth: int = 0,
    extra_body_class: str = "",
    current_code: str | None = None,
    rel_path: str = "",
) -> str:
    return _shell(
        model,
        title,
        body,
        current,
        is_home=is_home,
        depth=depth,
        extra_body_class=extra_body_class,
        current_code=current_code or _LOCALE.get(),
        rel_path=rel_path,
    )


def _legal_page(model: SiteModel, kind: str, code: str = "ru") -> str:
    _LOCALE.set(code)
    depth = 0
    _begin_page(0)
    rel = "cookies.html" if kind == "cookies" else "personal-data.html"
    title_key = "cookies_page_title" if kind == "cookies" else "personal_page_title"
    title = _ui(title_key)
    inner = _cookies_body() if kind == "cookies" else _personal_data_body()
    return _shell(
        model,
        title,
        _with_page_hero(title, inner),
        current="contacts",
        depth=depth,
        current_code=code,
        extra_body_class="",
        rel_path=rel,
    )


def _with_page_hero(title: str, inner: str, trail: str = "", wide: bool = False) -> str:
    extra = " is-wide" if wide else ""
    return (
        f'<header class="page-hero"><div class="wrap">{trail}'
        f"<h1>{escape(title)}</h1></div></header>"
        f'<div class="page-body{extra}">{inner}</div>'
    )


def _find_parent(model: SiteModel, page_id: str) -> MenuItem | None:
    def walk(items: tuple[MenuItem, ...], parent: MenuItem | None) -> MenuItem | None:
        for item in items:
            if item.id == page_id:
                return parent
            found = walk(item.children, item)
            if found is not None:
                return found
        return None

    return walk(model.menu_roots(), None)


def _trail(model: SiteModel, page_id: str, title: str, depth: int = 0) -> str:
    prefix = "../" * depth
    bits = [f'<a href="{prefix}index.html">{escape(_st("home"))}</a>']
    crumbs = {
        "lab-": ("structure.html", i18n.menu_title("structure", "Структура")),
        "labs/": ("structure.html", i18n.menu_title("structure", "Структура")),
        "people/": ("structure.html", i18n.menu_title("structure", "Структура")),
        "leadership-": ("leadership.html", i18n.menu_title("leadership", "Руководство")),
        "council-": ("scientific-council.html", i18n.menu_title("scientific-council", "Учёный совет")),
        "office-": ("structure.html", i18n.menu_title("structure", "Структура")),
        "smu-": ("young-scientists.html", i18n.menu_title("young-scientists", "Совет молодых учёных")),
        "news/": ("news.html", i18n.menu_title("news", "Новости")),
        "science/": ("science.html", i18n.menu_title("science", "Направления работы")),
        "developments/": ("developments.html", i18n.menu_title("developments", "Разработки")),
        "facilities/": ("facilities.html", i18n.menu_title("facilities", "Материальная база")),
        "conferences/": ("events.html", i18n.menu_title("events", "Мероприятия")),
    }
    matched = False
    if page_id == "media_about":
        bits.append(
            f'<a href="{prefix}news.html">{escape(i18n.menu_title("news", "Новости"))}</a>'
        )
        matched = True
    else:
        for prefix_id, (href, label) in crumbs.items():
            if page_id.startswith(prefix_id):
                bits.append(f'<a href="{prefix}{href}">{escape(label)}</a>')
                matched = True
                break
    if not matched:
        parent = _find_parent(model, page_id)
        while parent is not None and parent.kind == "folder":
            parent = _find_parent(model, parent.id)
        if parent:
            bits.append(
                f'<a href="{prefix}{parent.id}.html">{escape(_mt(parent))}</a>'
            )
    bits.append(f'<span aria-current="page">{escape(title)}</span>')
    sep = '<span class="crumbs-sep"> / </span>'
    return f'<nav class="crumbs" aria-label="{escape(_st("crumbs"))}">{sep.join(bits)}</nav>'


def _hub_cards(
    children: tuple[MenuItem, ...],
    heading: str | None = None,
) -> str:
    if heading is None:
        heading = _st("section_pages")
    if not children:
        return ""
    cards = []
    for child in children:
        if child.kind == "folder":
            continue
        blurb = i18n.hub_blurb(child.id, _HUB_BLURB.get(child.id, ""))
        extra = f"<p>{_t(blurb)}</p>" if blurb else ""
        cards.append(
            f'<li><a href="{child.id}.html">{_t(_mt(child))}{extra}</a></li>'
        )
    if not cards:
        return ""
    head = f"<h2>{escape(heading)}</h2>" if heading else ""
    return f'{head}<ul class="dir-grid">{"".join(cards)}</ul>'


def _empty_state(title: str, text: str) -> str:
    return (
        f'<div class="empty-state"><h2>{escape(title)}</h2>'
        f"<p>{escape(text)}</p></div>"
    )


def _card_list(rows: list[str], heading: str) -> str:
    if not rows:
        return ""
    items = "".join(
        f'<article class="info-card"><p>{_t(row)}</p></article>' for row in rows
    )
    return f"<h2>{escape(heading)}</h2><div class=\"info-grid\">{items}</div>"


def _contact_cards(rows: list[str]) -> str:
    cards = []
    for row in rows:
        if ":" not in row:
            cards.append(f'<article class="info-card"><p>{_t(row)}</p></article>')
            continue
        label, rest = row.split(":", 1)
        cards.append(
            f'<article class="info-card"><p class="leader-role">{_t(label.strip())}</p>'
            f"<p>{_t(rest.strip())}</p></article>"
        )
    return f'<div class="info-grid">{"".join(cards)}</div>'


def _news_feed_inner() -> str:
    copy = page_copy("news")
    parts = [f"<p>{escape(para)}</p>" for para in copy.get("paragraphs", [])]
    parts.append(
        f'<nav class="section-jump" aria-label="{escape(_st("on_this_page"))}">'
        f'<a href="#institute-news">{escape(_st("institute_news"))}</a>'
        f'<a href="#smi">{escape(_ui("footer_media"))}</a>'
        "</nav>"
    )
    parts.append(f'<h2 id="institute-news">{escape(_st("institute_news"))}</h2>')
    cards = "".join(
        f'<a class="news-card" href="news/{escape(item["slug"])}/index.html">'
        f"{_news_card_photo(item)}"
        f"<time>{escape(item.get('date_label') or item.get('date', ''))}</time>"
        f"<h3>{escape(item['title'])}</h3>"
        "</a>"
        for item in load_migrated_copy().get("news", [])
        if item.get("slug")
    )
    parts.append(f'<div class="news-grid">{cards}</div>')
    parts.append(f'<h2 id="smi">{escape(_ui("footer_media"))}</h2>')
    parts.append(_media_about_inner())
    return "".join(parts)


def _home_mix_cards() -> str:
    rows: list[dict] = []
    for item in load_migrated_copy().get("news", []):
        if not item.get("slug"):
            continue
        rows.append(
            {
                "date": item.get("date", ""),
                "label": item.get("date_label") or item.get("date", ""),
                "title": item["title"],
                "href": f"news/{item['slug']}/index.html",
                "kicker": _st("news_kicker"),
                "cover": _news_image_src(item),
            }
        )
    for item in load_migrated_copy().get("media_items", []):
        rows.append(
            {
                "date": item.get("date", ""),
                "label": item.get("date_label", ""),
                "title": item["title"],
                "href": item.get("href") or "media_about.html",
                "kicker": item.get("outlet") or _st("media_kicker"),
            }
        )
    rows.sort(key=lambda row: row["date"], reverse=True)
    return "".join(
        f'<a class="news-card" href="{escape(row["href"])}">'
        f"{_news_thumb(row['title'], row.get('cover'))}"
        f'<p class="news-kicker">{escape(row["kicker"])}</p>'
        f"<time>{escape(row['label'])}</time>"
        f"<h3>{escape(row['title'])}</h3></a>"
        for row in rows[:6]
    )


def _events_inner() -> str:
    copy = page_copy("events")
    event = load_migrated_copy().get("next_event") or {}
    parts = [f"<p>{escape(para)}</p>" for para in copy.get("paragraphs", [])]
    parts.append(
        '<div class="event-banner">'
        f"<h2>{escape(_st('next_event'))}</h2>"
        f"<p><strong>{escape(event.get('title', ''))}</strong></p>"
        f"<p>{escape(event.get('when', ''))}</p>"
        f'<p><a href="aist.html">{escape(_st("aist_section"))}</a> · '
        f'<a href="{escape(event.get("href", "aist.html"))}">{escape(_st("register_cycle"))}</a></p>'
        "</div>"
    )
    parts.append(_conference_archive())
    return "".join(parts)


def _conference_archive() -> str:
    rows = load_migrated_copy().get("conferences") or []
    aist = [row for row in rows if row.get("kind") == "aist"]
    other = [row for row in rows if row.get("kind") != "aist"]
    def cards(items: list[dict]) -> str:
        bits = []
        for item in items:
            bits.append(
                '<article class="conf-card">'
                f"{_photo_slot(item['title'], kind='cover') if is_filled_copy() else ''}"
                f'<p class="leader-role">{escape(item.get("series", ""))}</p>'
                f'<h3><a href="conferences/{escape(item["slug"])}/index.html">{escape(item["title"])}</a></h3>'
                f'<p>{escape(item.get("when", ""))}</p></article>'
            )
        return f'<div class="conf-grid">{"".join(bits)}</div>'
    chunks = [f"<h2>{escape(_st('aist_archive'))}</h2>", cards(aist)]
    if other:
        chunks.append(f"<h2>{escape(_st('other_conferences'))}</h2>")
        chunks.append(
            f"<p>{escape(_st('other_conferences_lead'))}</p>"
        )
        chunks.append(cards(other))
    return "".join(chunks)


def _real_file_shelf(files: list[dict]) -> str:
    if not files:
        return ""
    prefix = _asset_prefix()
    items: list[str] = []
    for row in files:
        name = str(row.get("file") or "")
        if not name:
            continue
        label = str(row.get("label") or name)
        href = f"{prefix}media/aist/{name}"
        kind = Path(name).suffix.lstrip(".").upper() or "FILE"
        items.append(
            f'<li class="file-slot">'
            f'<a href="{escape(href)}">{escape(label)}</a>'
            f"<small>{escape(kind)}</small></li>"
        )
    if not items:
        return ""
    return f'<ul class="file-shelf" aria-label="{escape(_st("aist_materials"))}">{"".join(items)}</ul>'


def _aist_org_block() -> str:
    hub = load_migrated_copy().get("aist_hub") or {}
    if not hub:
        return ""
    sid = str(hub.get("secretary_id") or "")
    name = ""
    if sid:
        person = roster.all_people().get(sid) or {}
        name = str(person.get("name") or "")
    href = roster.person_href(sid, _NEST.get()) if sid else ""
    who = (
        f'<a href="{escape(href)}">{escape(name)}</a>'
        if href and name
        else escape(name or sid)
    )
    lines = [
        f"<h2>{escape(_st('aist_org'))}</h2>",
        f"<p>{escape(_st('aist_secretary'))}{who}</p>",
    ]
    if hub.get("phone"):
        lines.append(f"<p>{escape(_st('tel'))}{escape(str(hub['phone']))}</p>")
    if hub.get("email"):
        email = escape(str(hub["email"]))
        lines.append(f'<p>E-mail: <a href="mailto:{email}">{email}</a></p>')
    press = hub.get("press") or []
    if press:
        lines.append(f"<h2>{escape(_st('aist_press'))}</h2>")
        lines.append("<ul>")
        for row in press:
            title = escape(str(row.get("title") or row.get("href") or ""))
            link = escape(str(row.get("href") or "#"))
            lines.append(f'<li><a href="{link}" rel="noopener noreferrer">{title}</a></li>')
        lines.append("</ul>")
    return "".join(lines)


def _conference_body(model: SiteModel, item: dict, depth: int = 2) -> str:
    prefix = "../" * depth
    _begin_page(depth)
    series = escape(item.get("series", ""))
    paras = item.get("paragraphs") or []
    files = item.get("files") or []
    body = "".join(f"<p>{escape(p)}</p>" for p in paras)
    if not body:
        body = f"<p>{escape(_st('conference_placeholder'))}</p>"
    banner = "" if files or paras else _fish_banner()
    inner = (
        f"{banner}"
        f"{_photo_slot(item['title'], kind='cover')}"
        f'<p class="leader-role">{series}</p>'
        f"<p>{escape(item.get('when', ''))}</p>"
        f"{body}"
        f"{_real_file_shelf(files)}"
        f'<p><a href="{prefix}events.html">{escape(_st("back_to_events"))}</a> · '
        f'<a href="{prefix}aist.html">{escape(_st("aist_section"))}</a></p>'
    )
    return _with_page_hero(
        item["title"],
        inner,
        trail=_trail(model, f"conferences/{item['slug']}", item["title"], depth=depth),
        wide=True,
    )


def _cookies_body() -> str:
    prefix = _page_prefix()
    banner = (
        _fish_banner()
        if _LOCALE.get() == "ru"
        else f'<p class="fish-banner" role="note">{escape(_ui("legal_mock"))}</p>'
    )
    return (
        f"{banner}"
        f"<p>{escape(_ui('cookies_p1'))}</p>"
        f"<h2>{escape(_ui('cookies_h_needed'))}</h2>"
        f"<p>{escape(_ui('cookies_p_needed'))}</p>"
        f"<h2>{escape(_ui('cookies_h_analytics'))}</h2>"
        f"<p>{escape(_ui('cookies_p_analytics'))}</p>"
        f'<p><a href="{_ui_href("personal-data.html", prefix)}">{escape(_ui("cookies_personal_link"))}</a> · '
        f'<a href="{prefix}e-appeals.html">{escape(_ui("cookies_appeals_link"))}</a></p>'
    )


def _personal_data_body() -> str:
    prefix = _page_prefix()
    banner = (
        '<p class="fish-banner">Макет. Не используйте формулировку как утверждённый документ.</p>'
        if _LOCALE.get() == "ru"
        else f'<p class="fish-banner" role="note">{escape(_ui("legal_mock"))}</p>'
    )
    return (
        f"<p>{_t(_ui('personal_operator'))}</p>"
        f"<p>{escape(_ui('personal_p2'))}</p>"
        f"{banner}"
        f'<p><a href="{_ui_href("cookies.html", prefix)}">{escape(_ui("cookies_page_title"))}</a></p>'
    )


def _lattice_demo_body() -> str:
    def row(name: str, label: str, min_v: str, max_v: str, step: str, hint: str) -> str:
        return (
            "<label>"
            f"<span>{escape(label)}</span>"
            f'<input type="range" name="{name}" min="{min_v}" max="{max_v}" step="{step}">'
            "<output></output>"
            f"<small>{escape(hint)}</small>"
            "</label>"
        )

    controls = (
        f"<p class=\"lattice-group\">{escape(_st('lattice_group_grid'))}</p>"
        + '<label class="lattice-check"><input type="checkbox" name="showHex" value="1"> '
        f"<span>{escape(_st('lattice_hex'))}</span></label>"
        + row("hexSize", "Ребро шестигранника", "18", "72", "1", "Размер ячейки сетки, px")
        + row("hexLine", "Контраст сетки", "0", "0.6", "0.01", "0 — сетка не видна")
        + row("idleRadius", "Атом в покое", "0", "16", "0.5", "0 — покоящиеся атомы скрыты")
        + row("peakRadius", "Атом на пике волны", "4", "36", "0.5", "Максимальный радиус")
        + row("paletteStep", "Шаг цвета новой волны", "0.02", "0.25", "0.01",
              "Насколько сдвигается общий цвет, когда появляется новое кольцо")
        + "<p class=\"lattice-group\">Случайные волны</p>"
        + "<p><small>Курсор сетку не трогает. Цвет всех волн общий: после зелёного — белый, "
        "затем оранжевый, дальше по кругу без жёлтого.</small></p>"
        + row("waveWidth", "Толщина фронта", "16", "180", "1", "Ширина кольца")
        + row("waveSpeed", "Скорость", "80", "700", "10", "Пикселей в секунду")
        + row("waveLife", "Время жизни", "1", "6", "0.1", "Секунды")
        + row("waveDecay", "Затухание к краю", "0", "1", "0.02", "0 — не слабеет, 1 — гаснет у края")
        + row("randomAmp", "Сила волны", "0.1", "1.5", "0.05", "Амплитуда случайных колец")
        + row("randomEvery", "Интервал волн, мс", "0", "8000", "100", "0 — не запускать")
    )
    inner = (
        '<section class="lattice-stage">'
        '<canvas class="hero-lattice" width="1290" height="800" aria-hidden="true"></canvas>'
        '<form class="lattice-panel" id="lattice-form">'
        f"<h1>{escape(_st('lattice_title'))}</h1>"
        f"<p>{escape(_st('lattice_lead'))}</p>"
        f"{controls}"
        '<div class="lattice-actions">'
        f'<button type="button" data-lattice-action="save">{escape(_st("lattice_save"))}</button>'
        f'<button type="button" data-lattice-action="copy">{escape(_st("lattice_copy"))}</button>'
        f'<button type="button" data-lattice-action="download">{escape(_st("lattice_download"))}</button>'
        f'<button type="button" data-lattice-action="reset">{escape(_st("lattice_reset"))}</button>'
        "</div>"
        '<p id="lattice-status" role="status"></p>'
        f"<p>{escape(_st('lattice_note'))}</p>"
        '<pre id="lattice-json"></pre>'
        f'<p><a href="index.html">{escape(_st("lattice_home"))}</a></p>'
        "</form></section>"
    )
    return inner


def _home_body(model: SiteModel) -> str:
    _begin_page(0)
    copy = load_migrated_copy()
    intro = _t(_st("home_intro") if _LOCALE.get() != "ru" else copy.get("home_intro", model.legal_name))
    news_cards = _home_mix_cards()
    event = copy.get("next_event") or {}
    labs = copy.get("labs", [])
    lab_cards = "".join(
        f'<li><a href="labs/{escape(lab.get("slug") or lab["id"])}/index.html">'
        f'{escape(_lt(lab))}</a></li>'
        for lab in labs
    )
    founded = int(copy.get("founded_year", 1998))
    years = date.today().year - founded
    note = ""
    if _LOCALE.get() != "ru" and _st("copy_note"):
        note = f'<p class="fish-banner" role="note">{escape(_st("copy_note"))}</p>'
    ribbon = (
        f'<p class="filled-ribbon" role="note">{escape(_st("filled_ribbon"))}</p>'
        if is_filled_copy()
        else note
    )
    return (
        f"{ribbon}"
        '<section class="hero ichnm-home-block" data-ichnm-block="official_intro">'
        '<canvas class="hero-lattice" width="1290" height="720" aria-hidden="true"></canvas>'
        '<div class="wrap hero-layout"><div class="hero-copy">'
        f'<p class="hero-kicker">{escape(_ui("kicker"))}</p>'
        f"<h1>{escape(_ui('brand'))}</h1>"
        f'<p class="hero-lead">{intro}</p>'
        '<div class="hero-entries">'
        f'<a class="hero-pill hero-pill-primary" href="about.html">{escape(_st("overview_pill"))}</a>'
        f'<a class="hero-pill ichnm-home-block" data-ichnm-block="structure_entry" href="structure.html">{escape(i18n.menu_title("structure", "Структура"))}</a>'
        f'<a class="hero-pill ichnm-home-block" data-ichnm-block="developments_entry" href="developments.html">{escape(i18n.menu_title("developments", "Разработки"))}</a>'
        f'<a class="hero-pill" href="feedback.html">{escape(_ui("write"))}</a>'
        f"</div></div></div></section>"
        f'<section class="stats-band" aria-label="{escape(_st("stats_label"))}">'
        '<div class="wrap stats-grid">'
        f'<article class="stat" data-count-to="{years}" data-suffix="+" tabindex="0" '
        f'aria-label="{years} {_st("years_aria")}, {founded}—{date.today().year}">'
        f'<p class="stat-value" aria-hidden="true"><span class="stat-num">{years}</span>'
        '<span class="stat-suffix">+</span></p>'
        f"<h2>{escape(_st('years_work'))}</h2>"
        f'<p class="stat-note">{founded} — {date.today().year}</p></article>'
        f'<article class="stat" data-count-to="{len(labs)}" tabindex="0" '
        f'aria-label="{len(labs)} {_st("labs_aria")}">'
        f'<p class="stat-value" aria-hidden="true"><span class="stat-num">{len(labs)}</span></p>'
        f"<h2>{escape(_st('labs_count'))}</h2>"
        f'<p class="stat-note"><a href="structure.html">{escape(_st("units_link"))}</a></p></article>'
        "</div></section>"
        '<section class="home-band">'
        f'<div class="wrap"><h2>{escape(_st("main_directions"))}</h2>'
        '<ul class="dir-grid">'
        f'<li><a href="science.html">{escape(_st("home_dir_1"))}'
        f"<p>{escape(_st('home_dir_1p'))}</p></a></li>"
        f'<li><a href="developments.html">{escape(_st("home_dir_2"))}'
        f"<p>{escape(_st('home_dir_2p'))}</p></a></li>"
        f'<li><a href="science.html">{escape(_st("home_dir_3"))}'
        f"<p>{escape(_st('home_dir_3p'))}</p></a></li>"
        "</ul></div></section>"
        '<section class="home-band band-muted ichnm-home-block" data-ichnm-block="news">'
        f'<div class="wrap"><h2>{escape(_st("home_news"))}</h2>'
        f'<div class="news-grid">{news_cards}</div>'
        f'<p><a class="band-link" href="news.html">{escape(_st("all_news"))}</a>'
        f' · <a class="band-link" href="news.html#smi">{escape(_ui("footer_media"))}</a>'
        f' · <a class="band-link" href="feedback.html">{escape(_ui("write"))}</a></p>'
        "</div></section>"
        '<section class="home-band">'
        f'<div class="wrap"><h2>{escape(i18n.menu_title("structure", "Структура"))}</h2>'
        f'<ul class="lab-grid">{lab_cards}</ul>'
        f'<p><a class="band-link" href="structure.html">{escape(_st("all_units"))}</a></p>'
        "</div></section>"
        '<section class="home-band ichnm-home-block" data-ichnm-block="next_event">'
        '<div class="wrap"><div class="event-banner">'
        f"<h2>{escape(_st('next_event'))}</h2>"
        f"<p><strong>{escape(event.get('title', ''))}</strong></p>"
        f"<p>{escape(event.get('when', ''))}</p>"
        f'<p><a href="aist.html">{escape(_st("aist_section"))}</a> · '
        f'<a href="{escape(event.get("href", "aist.html"))}">{escape(_st("register"))}</a></p>'
        "</div></div></section>"
    )

def _vitrine_inner(model: SiteModel, item: MenuItem) -> str:
    if item.id == "aist":
        event = load_migrated_copy().get("next_event") or {}
        hub = load_migrated_copy().get("aist_hub") or {}
        current = next(
            (
                row
                for row in load_migrated_copy().get("conferences") or []
                if row.get("slug") == "aist-2025"
            ),
            {},
        )
        leads = hub.get("lead") or [
            _st("aist_body") + "aist.ichnm.by" + _st("aist_no_domain")
        ]
        lead_html = "".join(f"<p>{escape(p)}</p>" for p in leads)
        return (
            '<div class="conf-hero">'
            f'<p class="leader-role">{escape(_st("aist_series"))}</p>'
            f"<h2>{escape(event.get('title', 'AIST'))}</h2>"
            f"<p>{escape(event.get('when', ''))}</p>"
            f"{lead_html}"
            f"{_real_file_shelf(current.get('files') or [])}"
            f'<p><a class="hero-pill hero-pill-primary" href="{escape(event.get("href", "http://aist.ichnm.by/"))}">{escape(_st("register"))}</a> '
            f'<a class="hero-pill" href="events.html">{escape(_st("all_events"))}</a></p>'
            "</div>"
            + _aist_org_block()
            + _conference_archive()
        )
    if item.id == "feedback":
        return (
            f"<p>{escape(_st('feedback_lead'))}"
            f'<a href="e-appeals.html">{escape(_st("separate_page"))}</a>.</p>'
            '<form class="form" action="mailto:ichnm@ichnm.by" method="post" enctype="text/plain">'
            f'<label for="name">{escape(_st("form_name"))}</label><input id="name" name="name" required>'
            f'<label for="email">{escape(_st("form_email"))}</label>'
            '<input id="email" name="email" type="email" required>'
            f'<label for="message">{escape(_st("form_message"))}</label>'
            '<textarea id="message" name="message" rows="6" required></textarea>'
            f'<button type="submit">{escape(_st("form_send"))}</button></form>'
        )
    if item.id == "science":
        paras = "".join(f"<p>{escape(p)}</p>" for p in page_copy("science").get("paragraphs", []))
        inst = [row for row in roster.science_catalog() if row.get("scope") == "institute"]
        labs = [row for row in roster.science_catalog() if row.get("scope") == "lab"]
        return (
            paras
            + f"<h2>{escape(_st('science_institute'))}</h2>"
            + _catalog_cards(inst, "science", columns=3)
            + f"<h2>{escape(_st('science_labs'))}</h2>"
            + _catalog_cards(labs, "science", columns=3)
        )
    if item.id == "developments":
        paras = "".join(
            f"<p>{escape(p)}</p>" for p in page_copy("developments").get("paragraphs", [])
        )
        inst = [row for row in roster.developments_catalog() if row.get("scope") == "institute"]
        labs = [row for row in roster.developments_catalog() if row.get("scope") == "lab"]
        return (
            paras
            + (f"<h2>{escape(_st('dev_institute'))}</h2>" + _catalog_cards(inst, "developments", columns=3) if inst else "")
            + f"<h2>{escape(_st('dev_labs'))}</h2>"
            + _catalog_cards(labs, "developments", columns=3)
        )
    if item.id == "facilities":
        paras = "".join(
            f"<p>{escape(p)}</p>" for p in page_copy("facilities").get("paragraphs", [])
        )
        inst = [row for row in roster.facilities_catalog() if row.get("scope") == "institute"]
        labs = [row for row in roster.facilities_catalog() if row.get("scope") == "lab"]
        return (
            paras
            + (
                f"<h2>{escape(_st('fac_institute'))}</h2>"
                + _catalog_cards(inst, "facilities", columns=3)
                if inst
                else ""
            )
            + f"<h2>{escape(_st('fac_labs'))}</h2>"
            + _catalog_cards(labs, "facilities", columns=3)
        )
    if item.id == "cooperation":
        copied = page_copy("cooperation")
        paras = "".join(f"<p>{escape(p)}</p>" for p in copied.get("paragraphs", []))
        partners = load_migrated_copy().get("partners") or []
        partner_cards = "".join(
            '<article class="info-card">'
            f'<p class="leader-role">{escape(row.get("place", ""))}</p>'
            f"<h3>{escape(row['title'])}</h3>"
            f"<p>{escape(row.get('note', ''))}</p></article>"
            for row in partners
        )
        partner_block = (
            f"<h2>{escape(_st('partners'))}</h2>"
            f'<div class="info-grid">{partner_cards}</div>'
            if partners
            else ""
        )
        return paras + partner_block + _world_map()
    if item.id == "scientific-council":
        return _council_listing()
    if item.id in _admin_unit_ids():
        return _admin_unit_inner(item.id)
    if item.id == "media_about":
        return (
            f'<p class="unit-back"><a href="news.html#smi">{escape(_st("back_to_news_smi"))}</a></p>'
            + _media_about_inner()
        )
    if item.id == "leadership":
        return _leadership_inner(item)
    if item.id == "news":
        return _news_feed_inner()
    if item.id == "events":
        return _events_inner()
    if item.id == "announcement":
        paras = page_copy("announcement").get("paragraphs", [])
        intro = "".join(f"<p>{escape(p)}</p>" for p in paras)
        return intro + _empty_state(_st("no_announcements"), _st("no_announcements_text"))
    if item.id == "vacancies":
        copied = page_copy("vacancies")
        paras = "".join(f"<p>{escape(p)}</p>" for p in copied.get("paragraphs", []))
        rows = copied.get("list") or []
        if rows:
            items = "".join(f"<li>{escape(row)}</li>" for row in rows)
            return paras + f'<ul class="plain-list">{items}</ul>'
        return paras + _empty_state(_st("no_vacancies"), _st("no_vacancies_text"))
    if item.id == "publications":
        paras = "".join(
            f"<p>{escape(p)}</p>" for p in page_copy("publications").get("paragraphs", [])
        )
        return paras + _publications_inner()
    if item.id == "about-overview":
        copied = page_copy(item.id)
        chunks = [f"<p>{_t(para)}</p>" for para in copied.get("paragraphs", [])]
        if copied.get("list"):
            chunks.append(_card_list(copied["list"], _st("research_directions")))
        chunks.append(_about_gallery())
        return "".join(chunks)
    copied = page_copy(item.id)
    chunks: list[str] = []
    for para in copied.get("paragraphs", []):
        chunks.append(f"<p>{_t(para)}</p>")
    heading = {
        "about": _st("awards"),
        "developments": i18n.menu_title("developments", "Разработки"),
        "facilities": i18n.menu_title("facilities", "Материальная база"),
        "cooperation": i18n.menu_title("cooperation", "Сотрудничество"),
        "science": i18n.menu_title("science", "Направления работы"),
    }.get(item.id, "")
    if copied.get("list") and item.id in _LIST_AS_CARDS:
        chunks.append(_card_list(copied["list"], heading or _st("list_heading")))
    elif copied.get("list") and item.id == "contacts":
        chunks.append(_contact_cards(copied["list"]))
        chunks.append(
            f'<p><a href="requisites.html">{escape(_st("requisites_link"))}</a> · '
            f'<a href="feedback.html">{escape(_st("feedback_form_link"))}</a> · '
            f'<a href="e-appeals.html">{escape(_ui("cookies_appeals_link"))}</a></p>'
        )
        chunks.append(
            '<div class="map-slot is-filled">'
            f"{_minsk_map()}"
            f"<p>{_t(_st('street'))}</p>"
            "</div>"
        )
    elif copied.get("list") and item.id not in {"structure"}:
        items = "".join(f"<li>{escape(row)}</li>" for row in copied["list"])
        chunks.append(f'<ul class="plain-list">{items}</ul>')
    if item.id == "structure":
        labs = load_migrated_copy().get("labs", [])
        if labs:
            chunks.append(f"<h2>{escape(_st('labs_heading'))}</h2>")
            lab_links = "".join(
                f'<li><a href="labs/{escape(lab.get("slug") or lab["id"])}/index.html">'
                f'{_t(_lt(lab))}</a></li>'
                for lab in labs
            )
            chunks.append(f'<ul class="lab-grid">{lab_links}</ul>')
        chunks.append(f"<h2>{escape(_st('admin_heading'))}</h2>")
        admin_ids = _admin_unit_ids()
        admin_children = tuple(child for child in item.children if child.id in admin_ids)
        community_children = tuple(
            child for child in item.children if child.id in roster.community_unit_ids()
        )
        chunks.append(_hub_cards(admin_children, heading=""))
        if community_children:
            chunks.append(f"<h2>{escape(_st('community_heading'))}</h2>")
            chunks.append(_hub_cards(community_children, heading=""))
    if item.id == "union":
        chunks.append(
            f'<p><a href="https://profnan.by/">{escape(_st("union_nas_note"))}</a> '
            f"{escape(_st('union_nas_aside'))}</p>"
        )
    if item.id == "young-scientists":
        chunks.append('<div class="people-list">')
        smu = roster.people_for_unit("young-scientists") or list(roster.SMU_PEOPLE)
        for person in smu:
            chunks.append(
                _person_card(
                    name=person["name"],
                    role=person.get("unit_role") or person["role"],
                    initials=person.get("initials", ""),
                    href=roster.person_href(person["id"]),
                    lines=[_st("contact_pending")]
                    if not is_filled_copy()
                    else [person.get("degree") or ""],
                )
            )
        chunks.append("</div>")
    if item.id in _FISH_FILE_PAGES:
        chunks.insert(0, _fish_banner())
        chunks.append(_file_slots(_FISH_FILE_PAGES[item.id]))
    if item.children and item.id != "structure":
        chunks.append(_hub_cards(item.children))
    if not chunks:
        chunks.append(
            f'<p class="placeholder">{escape(_st("placeholder"))}</p>'
        )
    return "".join(chunks)


def _vitrine_body(model: SiteModel, item: MenuItem) -> str:
    _begin_page(0)
    extra = " is-wide" if item.id in _WIDE_PAGES else ""
    trail = _trail(model, item.id, _mt(item))
    return (
        f'<header class="page-hero"><div class="wrap">{trail}'
        f"<h1>{_t(_mt(item))}</h1></div></header>"
        f'<div class="page-body{extra}">{_vitrine_inner(model, item)}</div>'
    )


def _photo_slot(
    label: str,
    initials: str = "",
    compact: bool = False,
    kind: str = "cover",
    letter: str = "",
) -> str:
    if is_filled_copy():
        src = _fish_src("person" if kind == "person" else kind, label, letter)
        return (
            f'<div class="photo-slot is-filled" role="img" '
            f'aria-label="{escape(label)}">'
            f'<img src="{escape(src)}" alt="" width="640" height="400"></div>'
        )
    mark = f"<span>{escape(initials)}</span>" if initials else ""
    note = "" if compact else f"<p>{escape(_st('photo_pending'))}</p>"
    return (
        f'<div class="photo-slot" role="img" aria-label="{escape(_st("photo_slot") + label)}">'
        f"{mark}{note}</div>"
    )


def _person_card(
    *,
    name: str,
    role: str,
    initials: str = "",
    href: str = "",
    lines: tuple[str, ...] | list[str] | None = None,
) -> str:
    photo = _photo_slot(name, initials, compact=True, kind="person")
    extra = "".join(f"<p>{_t(line)}</p>" for line in (lines or []) if line)
    inner = (
        f'<div class="leader-photo">{photo}</div><div>'
        f'<p class="leader-role">{_t(role)}</p>'
        f"<h2>{_t(name)}</h2>{extra}</div>"
    )
    if href:
        return f'<a class="leader-card" href="{escape(href)}">{inner}</a>'
    return f'<article class="leader-card">{inner}</article>'


def _cover_card(href: str, title: str, lead: str, meta: str = "", letter: str = "") -> str:
    extra = f'<p class="cover-meta">{escape(meta)}</p>' if meta else ""
    return (
        f'<a class="cover-card" href="{escape(href)}">'
        f"{_photo_slot(title, kind='cover', letter=letter)}"
        f"<h3>{escape(title)}</h3>"
        f"<p>{escape(lead)}</p>{extra}</a>"
    )


def _news_image_src(item: dict) -> str:
    images = item.get("images") or []
    slug = item.get("slug") or ""
    if not images or not slug:
        return ""
    return f"{_asset_prefix()}media/news/{slug}/{images[0]}"


def _news_thumb(title: str, src: str | None) -> str:
    if src:
        return (
            f'<div class="photo-slot is-filled" role="img" aria-label="{escape(title)}">'
            f'<img src="{escape(src)}" alt="" width="640" height="400"></div>'
        )
    return _photo_slot(title, kind="cover") if is_filled_copy() else ""


def _news_card_photo(item: dict) -> str:
    return _news_thumb(item.get("title") or "", _news_image_src(item))


def _news_gallery(item: dict) -> str:
    images = item.get("images") or []
    slug = item.get("slug") or ""
    if not images or not slug:
        return _photo_slot(item["title"], kind="cover")
    prefix = _asset_prefix()
    figs = []
    for name in images:
        src = f"{prefix}media/news/{slug}/{name}"
        figs.append(
            f'<figure class="news-photo"><img src="{escape(src)}" alt="" width="700" height="525"></figure>'
        )
    return f'<div class="news-gallery">{"".join(figs)}</div>'


def _news_article_body(model: SiteModel, item: dict, depth: int = 2) -> str:
    prefix = "../" * depth
    _begin_page(depth)
    paras = "".join(f"<p>{escape(p)}</p>" for p in item.get("paragraphs", []))
    source = ""
    if item.get("source_href"):
        source = (
            f'<p class="news-source"><a href="{escape(item["source_href"])}" '
            f'rel="noopener noreferrer">{escape(_st("news_source"))}</a></p>'
        )
    inner = (
        f"{_news_gallery(item)}"
        f"<p class=\"leader-role\">{escape(item.get('date_label') or item.get('date', ''))}</p>"
        f"{paras}{source}"
        f'<p><a href="{prefix}news.html">{escape(_st("back_to_news"))}</a></p>'
    )
    return _with_page_hero(
        item["title"],
        inner,
        trail=_trail(model, f"news/{item.get('slug', '')}", item["title"], depth=depth),
        wide=True,
    )


def _catalog_detail_body(model: SiteModel, parent: str, item: dict, depth: int = 2) -> str:
    prefix = "../" * depth
    letter = letter_for_lab(str(item["lab_id"])) if item.get("lab_id") else "И"
    _begin_page(depth, letter)
    parent_file = f"{parent}.html"
    parent_title = {
        "science": i18n.menu_title("science", "Направления работы"),
        "developments": i18n.menu_title("developments", "Разработки"),
        "facilities": i18n.menu_title("facilities", "Материальная база"),
    }[parent]
    spec = item.get("spec") or item.get("product") or ""
    contacts = item.get("contacts") or roster.catalog_meta(item) or "ichnm@ichnm.by"
    lab_id = item.get("lab_id") or ""
    staff_id = item.get("staff_id") or item.get("head_id") or ""
    extra_links = []
    if lab_id:
        href, title = roster.unit_link(str(lab_id), depth)
        extra_links.append(f'<p>{escape(_st("lab_label"))}<a href="{escape(href)}">{escape(title)}</a></p>')
    if staff_id:
        extra_links.append(
            f'<p>{escape(_st("assigned"))}<a href="{escape(roster.person_href(str(staff_id), depth))}">'
            f"{escape(roster.person_contact_line(str(staff_id)) or staff_id)}</a></p>"
        )
    kind = "equipment" if parent == "facilities" else "cover"
    inner = (
        f"{_fish_banner()}"
        f"{_photo_slot(item['title'], kind=kind)}"
        f"<p>{escape(item.get('lead', ''))}</p>"
        f"<h2>{escape(_st('description_contacts'))}</h2>"
        f"<p>{escape(contacts)}</p>"
        f"{''.join(extra_links)}"
        f"<h2>{escape(_st('spec' if parent == 'facilities' else 'product'))}</h2>"
        f"<p>{escape(spec)}</p>"
        f'<p><a href="{prefix}{parent_file}">{escape(parent_title)}</a></p>'
    )
    return _with_page_hero(
        item["title"],
        inner,
        trail=_trail(model, f"{parent}/{item['slug']}", item["title"], depth=depth),
        wide=True,
    )


def _person_body(model: SiteModel, person: dict, depth: int = 0) -> str:
    prefix = "../" * depth
    letter = "И"
    for aff in person.get("affiliations") or []:
        uid = str(aff.get("unit_id") or "")
        if uid.startswith("lab-"):
            letter = letter_for_lab(uid)
            break
    _begin_page(depth, letter)
    contacts = []
    if person.get("phone"):
        contacts.append(f"<p>{_t(_st('tel') + person['phone'])}</p>")
    if person.get("email"):
        contacts.append(
            f'<p><a href="mailto:{escape(person["email"])}">{escape(person["email"])}</a></p>'
        )
    photo = (
        f'<div class="leader-photo leader-photo-lg">{_photo_slot(person["name"], person.get("initials", ""), kind="person", letter=letter)}</div>'
        if is_filled_copy()
        else (
            '<div class="leader-photo leader-photo-lg" role="img" '
            f'aria-label="{escape(_st("official_photo_slot") + person["name"])}">'
            f'<span>{escape(person.get("initials", ""))}</span>'
            f"<p>{escape(_st('official_photo_pending'))}</p>"
            "</div>"
        )
    )
    aff_items = []
    for aff in person.get("affiliations") or []:
        href, title = roster.unit_link(str(aff.get("unit_id") or ""), depth)
        role = aff.get("role") or ""
        label = escape(title)
        if role:
            label = f"{label} — {_t(str(role))}"
        aff_items.append(f'<li><a href="{escape(href)}">{label}</a></li>')
    aff_block = (
        f"<h2>{escape(_st('units'))}</h2>"
        f'<ul class="plain-list">{"".join(aff_items)}</ul>'
        if aff_items
        else ""
    )
    bio = "".join(f"<p>{escape(para)}</p>" for para in person.get("bio") or [])
    interests = "".join(f"<li>{escape(row)}</li>" for row in person.get("interests") or [])
    pub_items = []
    for row in person.get("publications") or []:
        if isinstance(row, str):
            pub_items.append(f"<li>{escape(row)}</li>")
        else:
            pub_items.append(_publication_line(row, depth, show_lab=False))
    pubs = "".join(pub_items)
    role = person.get("unit_role") or person.get("role") or ""
    profile_bits: list[str] = [
        _fish_banner(),
        '<div class="leader-profile">',
        photo,
        '<div class="leader-profile-copy">',
        f'<p class="leader-role">{_t(role)}</p>',
        f"<p>{_t(person.get('degree', ''))}</p>",
        *contacts,
        aff_block,
        _person_metrics(model, person),
    ]
    if person.get("bio"):
        profile_bits.append(f"<h2>{escape(_st('biography'))}</h2>")
        profile_bits.append(bio)
    if person.get("interests"):
        profile_bits.append(f"<h2>{escape(_st('interests'))}</h2>")
        profile_bits.append(f'<ul class="plain-list">{interests}</ul>')
    if person.get("publications"):
        profile_bits.append(f"<h2>{escape(_st('selected_pubs'))}</h2>")
        profile_bits.append(f'<ul class="plain-list">{pubs}</ul>')
    if person.get("sources_note"):
        profile_bits.append(f"<p class=\"leader-source\">{escape(person['sources_note'])}</p>")
    profile_bits.append(f'<p><a href="{prefix}structure.html">{escape(_st("to_structure"))}</a></p>')
    profile_bits.append("</div></div>")
    inner = "".join(profile_bits)
    return (
        f'<header class="page-hero"><div class="wrap">'
        f'{_trail(model, "people/" + str(person.get("id", "")), person["name"], depth=depth)}'
        f"<h1>{escape(person['name'])}</h1></div></header>"
        f'<div class="page-body is-wide">{inner}</div>'
    )


def _catalog_cards(items: list[dict], parent: str, *, columns: int = 2) -> str:
    cards = []
    for item in items:
        letter = letter_for_lab(str(item["lab_id"])) if item.get("lab_id") else "И"
        cards.append(
            _cover_card(
                f"{parent}/{item['slug']}/index.html",
                item["title"],
                item.get("lead", ""),
                meta=roster.catalog_meta(item),
                letter=letter,
            )
        )
    cls = "cover-grid is-3" if columns == 3 else "cover-grid"
    return f'<div class="{cls}">{"".join(cards)}</div>'


def _doi_href(doi: str) -> str:
    raw = doi.strip()
    if not raw:
        return ""
    if raw.lower().startswith("http://") or raw.lower().startswith("https://"):
        return raw
    ident = raw[4:].strip() if raw.lower().startswith("doi:") else raw
    return f"https://doi.org/{ident}"


def _publication_line(row: dict, depth: int = 0, *, show_lab: bool = True) -> str:
    cite = str(row.get("cite") or "").strip()
    if not cite:
        bits = [
            str(row.get("authors") or "").strip(),
            str(row.get("title") or "").strip(),
            str(row.get("journal") or "").strip(),
        ]
        year = row.get("year")
        if year:
            bits.append(str(year))
        cite = ". ".join(bit for bit in bits if bit)
    doi = str(row.get("doi") or "").strip()
    doi_html = ""
    if doi:
        href = _doi_href(doi)
        label = doi
        for prefix in ("https://doi.org/", "http://doi.org/", "doi:"):
            if label.lower().startswith(prefix):
                label = label[len(prefix) :].strip()
                break
        doi_html = (
            f' <a class="doi-link" href="{escape(href)}" rel="noopener noreferrer">'
            f"doi:{escape(label)}</a>"
        )
    lab_html = ""
    lab_id = str(row.get("lab_id") or "")
    if show_lab and lab_id:
        lab_href, lab_title = roster.unit_link(lab_id, depth)
        lab_html = (
            f' <span class="cover-meta">('
            f'<a href="{escape(lab_href)}">{escape(lab_title)}</a>)</span>'
        )
    return f"<li>{escape(cite)}{doi_html}{lab_html}</li>"


def _pub_year(row: dict) -> int:
    try:
        return int(row.get("year") or 0)
    except (TypeError, ValueError):
        return 0


def _publications_grouped(rows: list, depth: int, *, show_lab: bool) -> str:
    groups: dict[int, list] = {}
    for row in rows:
        groups.setdefault(_pub_year(row), []).append(row)
    if not groups:
        return ""
    parts = []
    for year in sorted(groups, reverse=True):
        label = str(year) if year else _ui("year_unknown")
        slug = str(year) if year else "unknown"
        items = "".join(
            _publication_line(row, depth, show_lab=show_lab) for row in groups[year]
        )
        parts.append(
            f'<section class="pub-year" aria-labelledby="pub-year-{escape(slug)}">'
            f'<h3 id="pub-year-{escape(slug)}">{escape(label)}</h3>'
            f'<ul class="plain-list">{items}</ul></section>'
        )
    return "".join(parts)


def _profile_href(field: str, raw: str) -> str:
    value = raw.strip()
    if not value:
        return ""
    if value.lower().startswith("http://") or value.lower().startswith("https://"):
        return value
    if field == "orcid":
        return f"https://orcid.org/{value}"
    return ""


def _person_metrics(model: SiteModel, person: dict) -> str:
    profiles = dict(person.get("profiles") or {})
    if person.get("orcid") and not profiles.get("orcid"):
        profiles["orcid"] = str(person["orcid"])
    bibliometrics = person.get("bibliometrics") or {}
    rows: list[str] = []
    for field in model.staff_metric_fields:
        href = _profile_href(field, str(profiles.get(field) or person.get(field) or ""))
        stats = bibliometrics.get(field) or {}
        h_index = stats.get("h_index") if isinstance(stats, dict) else None
        citations = stats.get("citations") if isinstance(stats, dict) else None
        if not href and h_index is None and citations is None:
            continue
        bits: list[str] = []
        if h_index is not None and h_index != "":
            bits.append(f"{escape(_st('h_index'))} {escape(str(h_index))}")
        if citations is not None and citations != "":
            bits.append(f"{escape(_st('citations'))} {escape(str(citations))}")
        if href:
            bits.append(
                f'<a href="{escape(href)}" rel="noopener noreferrer">{escape(_st("profile"))}</a>'
            )
        label = _METRIC_LABELS.get(field, field)
        rows.append(f"<dt>{escape(label)}</dt><dd>{' · '.join(bits) or '—'}</dd>")
    if not rows:
        return _empty_state(_st("metrics_empty"), _st("metrics_empty_text"))
    return (
        f"<h2>{escape(_st('metrics'))}</h2>"
        f"<p class=\"metrics-note\">{escape(_st('metrics_note'))}</p>"
        f'<dl class="metrics-list">{"".join(rows)}</dl>'
    )


def _about_gallery() -> str:
    photos = load_migrated_copy().get("about_photos") or []
    if not photos:
        return ""
    figs = []
    for row in photos:
        figs.append(
            "<figure class=\"about-photo\">"
            f'<img src="{escape(_asset_prefix() + row["src"] if not str(row["src"]).startswith("http") else row["src"])}" alt="{escape(row.get("caption") or "")}" '
            'width="640" height="400" loading="lazy">'
            f"<figcaption>{escape(row.get('caption') or '')}</figcaption>"
            "</figure>"
        )
    return f"<h2>{escape(_st('photo_archive'))}</h2>" f'<div class="about-gallery">{"".join(figs)}</div>'


_WORLD_SVG = _HERE.parents[1] / "assets" / "maps" / "world-countries.svg"
_MINSK_SVG = _HERE.parents[1] / "assets" / "maps" / "minsk.svg"


def _world_outline() -> str:
    raw = _WORLD_SVG.read_text(encoding="utf-8")
    return re.sub(r"<\?xml[^?]*\?>", "", raw).strip()


def _minsk_outline() -> str:
    raw = _MINSK_SVG.read_text(encoding="utf-8")
    return re.sub(r"<\?xml[^?]*\?>", "", raw).strip()


def _minsk_pin_pct() -> tuple[str, str]:
    raw = _MINSK_SVG.read_text(encoding="utf-8")
    left = re.search(r'data-pin-left="([\d.]+)"', raw)
    top = re.search(r'data-pin-top="([\d.]+)"', raw)
    if not left or not top:
        raise RuntimeError("minsk.svg is missing data-pin-left/top")
    return left.group(1), top.group(1)


def _minsk_map(*, compact: bool = False) -> str:
    left, top = _minsk_pin_pct()
    hotspot = (
        f'<div class="map-hotspot" style="left:{left}%;top:{top}%">'
    )
    if compact:
        pin = f'{hotspot}<span class="map-pin"></span></div>'
        return (
            '<figure class="world-map city-map is-compact" aria-hidden="true">'
            f"{_minsk_outline()}{pin}</figure>"
        )
    pin = (
        f'{hotspot}'
        f'<button type="button" class="map-pin" aria-describedby="pop-ichnm">'
        f'<span class="visually-hidden">{escape(_st("minsk_pin"))}</span></button>'
        f'<div class="map-pop" id="pop-ichnm">'
        f'<p class="leader-role">{escape(_st("minsk_city"))}</p>'
        f'<h3>{escape(_st("minsk_pin"))}</h3>'
        f'<p>{escape(_st("street"))}</p>'
        "</div></div>"
    )
    return (
        f'<figure class="world-map city-map" aria-label="{escape(_st("minsk_map"))}">'
        f"{_minsk_outline()}{pin}</figure>"
    )


def _world_map() -> str:
    pins = []
    for partner in load_migrated_copy().get("partners", []):
        pins.append(
            f'<div class="map-hotspot" style="left:{float(partner["x"])}%;top:{float(partner["y"])}%">'
            f'<button type="button" class="map-pin" aria-describedby="pop-{escape(partner["slug"])}">'
            f'<span class="visually-hidden">{escape(partner["title"])}</span></button>'
            f'<div class="map-pop" id="pop-{escape(partner["slug"])}">'
            f'{_photo_slot(partner["title"], kind="cover")}'
            f'<p class="leader-role">{escape(partner.get("place", ""))}</p>'
            f"<h3>{escape(partner['title'])}</h3>"
            f"<p>{escape(partner.get('note', ''))}</p>"
            "</div></div>"
        )
    return (
        f'<figure class="world-map" aria-label="{escape(_st("coop_map"))}">'
        f"{_world_outline()}{''.join(pins)}</figure>"
    )


def _council_listing() -> str:
    copy = page_copy("scientific-council")
    parts = [_fish_banner()]
    parts.extend(f"<p>{escape(para)}</p>" for para in copy.get("paragraphs", []))
    cards = []
    for person in load_migrated_copy().get("council_people", []):
        pid = person["id"]
        record = roster.person(pid) or person
        cards.append(
            _person_card(
                name=record["name"],
                role=record.get("unit_role") or record.get("role", ""),
                initials=record.get("initials", ""),
                href=roster.person_href(pid),
                lines=[record.get("degree", "")],
            )
        )
    parts.append(f'<div class="people-list">{"".join(cards)}</div>')
    return "".join(parts)


def _admin_unit_inner(unit_id: str) -> str:
    units = load_migrated_copy().get("admin_units", [])
    unit = next((row for row in units if row["id"] == unit_id), None)
    if not unit:
        return f"<p>{escape(_st('unit_pending'))}</p>"
    seen: set[str] = set()
    seen_names: set[str] = set()
    people = []
    for person in roster.people_for_unit(unit_id):
        pid = str(person["id"])
        seen.add(pid)
        name = str(person.get("name") or "").strip()
        if name:
            seen_names.add(name)
        people.append(
            _person_card(
                name=person["name"],
                role=person.get("unit_role") or person.get("role", ""),
                initials=person.get("initials", ""),
                href=roster.person_href(pid),
                lines=[
                    f"{_st('tel')}{person.get('phone', '')}" if person.get("phone") else "",
                    person.get("email") or "",
                ],
            )
        )
    for person in unit.get("people", []):
        pid = str(person.get("id") or "")
        name = str(person.get("name") or "").strip()
        if (pid and pid in seen) or (name and name in seen_names):
            continue
        people.append(
            _person_card(
                name=person["name"],
                role=person.get("role", ""),
                initials=person.get("initials", ""),
                href=roster.person_href(pid) if pid else "",
                lines=[
                    f"{_st('tel')}{person.get('phone', '')}" if person.get("phone") else "",
                    person.get("email") or "",
                ],
            )
        )
    return (
        f"{_fish_banner()}"
        f'<p class="unit-back"><a href="structure.html">{escape(_st("back_to_units"))}</a></p>'
        f"<p>{_t(_st('unit_phone') + str(unit.get('phone', '')))}</p>"
        f'<div class="people-list">{"".join(people)}</div>'
    )


def _fish_banner() -> str:
    key = "fish_filled" if is_filled_copy() else "fish_honest"
    cls = "fish-banner is-filled" if is_filled_copy() else "fish-banner"
    return f'<p class="{cls}" role="note">{escape(_st(key))}</p>'


def _file_slots(labels: list[str]) -> str:
    if is_filled_copy():
        items = "".join(
            f'<li class="file-slot is-filled">'
            f'<img src="{escape(_fish_src("document", label))}" alt="" width="64" height="64">'
            f"<span>{escape(label)}</span>"
            f"<small>{escape(_st('mock_file'))}</small></li>"
            for label in labels
        )
    else:
        items = "".join(
            f'<li class="file-slot"><span>{escape(label)}</span>'
            f"<small>{escape(_st('file_missing'))}</small></li>"
            for label in labels
        )
    return f'<ul class="file-shelf" aria-label="{escape(_st("file_slots"))}">{items}</ul>'


def _publications_inner() -> str:
    data = load_migrated_copy().get("publication_years") or {}
    series = data.get("series") or []
    max_count = max((int(row.get("count", 0)) for row in series), default=1) or 1
    counts = [int(row.get("count", 0)) for row in series]
    sma: list[float] = []
    for index, value in enumerate(counts):
        window = counts[max(0, index - 2) : index + 1]
        sma.append(sum(window) / len(window))
    sma_max = max(sma + [float(max_count)]) or 1
    count_n = max(len(sma), 1)
    points = []
    for index, value in enumerate(sma):
        x = (100 / count_n) * (index + 0.5)
        y = 36 - (30 * value / sma_max)
        points.append(f"{x:.1f},{y:.1f}")
    sma_path = " ".join(points)
    bars = []
    for offset, row in enumerate(series):
        year = int(row.get("year", 0))
        count = int(row.get("count", 0))
        px = max(8, round(168 * count / max_count))
        delay = round(0.05 * offset, 2)
        bars.append(
            f'<li><button type="button" class="chart-bar" data-year="{year}" '
            f'data-count="{count}" aria-label="{year}: {count} {_st("articles_n")}">'
            f'<span class="chart-col" style="height:{px}px;animation-delay:{delay}s"></span>'
            f'<span class="chart-year">{year}</span></button></li>'
        )
    payload = json.dumps(data, ensure_ascii=False)
    note = escape(str(data.get("note", "")))
    catalog = _publications_grouped(roster.lab_publications(), 0, show_lab=True)
    if not catalog:
        catalog = f"<p>{escape(_st('pub_list_pending'))}</p>"
    return (
        f'<script type="application/json" id="pub-year-data">{payload}</script>'
        f'<p class="fish-banner" role="note">{note}</p>'
        '<figure class="pub-chart">'
        f"<figcaption>{escape(_st('articles_by_year'))}</figcaption>"
        f'<p class="chart-readout" aria-live="polite">{escape(_st("chart_hint"))}</p>'
        '<div class="chart-plot">'
        f'<ul class="chart-bars">{"".join(bars)}</ul>'
        '<svg class="chart-sma" viewBox="0 0 100 40" preserveAspectRatio="none" aria-hidden="true">'
        f'<polyline class="chart-sma-line" pathLength="1" points="{sma_path}" fill="none" '
        'stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" stroke-linecap="round"/>'
        "</svg>"
        "</div>"
        "</figure>"
        f"<h2>{escape(_st('pubs_labs'))}</h2>"
        f"{_fish_banner()}"
        f"{catalog}"
    )


def _lab_projects_block(lab: dict) -> str:
    rows = lab.get("projects") or []
    if not rows:
        return f"<p>{escape(_st('lab_projects_pending'))}</p>"
    chunks: list[str] = []
    for status, heading in (
        ("active", "lab_projects_active"),
        ("completed", "lab_projects_done"),
    ):
        group = [row for row in rows if row.get("status") == status]
        if not group:
            continue
        badge = _st("project_active" if status == "active" else "project_completed")
        cards = []
        for item in group:
            years = str(item.get("years") or "").strip()
            kicker = f"{badge} · {years}" if years else badge
            lead = str(item.get("lead") or "").strip()
            cards.append(
                '<article class="conf-card">'
                f'<p class="leader-role">{escape(kicker)}</p>'
                f"<h3>{escape(item.get('title') or '')}</h3>"
                + (f"<p>{escape(lead)}</p>" if lead else "")
                + "</article>"
            )
        chunks.append(
            f"<h3>{escape(_st(heading))}</h3>"
            f'<div class="conf-grid">{"".join(cards)}</div>'
        )
    return "".join(chunks) if chunks else f"<p>{escape(_st('lab_projects_pending'))}</p>"


def _lab_body(model: SiteModel, lab: dict, depth: int = 0) -> str:
    prefix = "../" * depth
    _begin_page(depth, letter_for_lab(str(lab["id"])))
    kicker = escape(str(lab.get("kicker") or _st("lab_kicker_default")))
    people = []
    for pid in lab.get("staff_ids") or []:
        row = roster.person(str(pid))
        if not row:
            continue
        role = row.get("role") or ""
        for aff in row.get("affiliations") or []:
            if aff.get("unit_id") == lab["id"] and aff.get("role"):
                role = aff["role"]
                break
        people.append(
            _person_card(
                name=row["name"],
                role=role,
                initials=row.get("initials", ""),
                href=roster.person_href(str(pid), depth),
                lines=[row.get("degree") or ""]
                + ([f"Тел. {row['phone']}"] if row.get("phone") else []),
            )
        )
    direction_cards = []
    for item in lab.get("directions") or []:
        direction_cards.append(
            _cover_card(
                f"{prefix}science/{item['slug']}/index.html",
                item["title"],
                item.get("lead", ""),
                meta=roster.head_contact_line(lab),
            )
        )
    directions = (
        f'<div class="cover-grid is-3">{"".join(direction_cards)}</div>'
        if direction_cards
        else f"<p>{escape(_st('directions_pending'))}</p>"
    )
    equip_items = []
    for item in lab.get("equipment") or []:
        staff = roster.person_contact_line(str(item.get("staff_id") or ""))
        staff_html = f" — {escape(staff)}" if staff else ""
        href = f'{prefix}facilities/{escape(item["slug"])}/index.html'
        equip_items.append(
            f'<li class="lab-equip-item">'
            f'<a class="lab-equip-card" href="{href}">'
            f'{_photo_slot(item["title"], kind="equipment")}'
            f"<h3>{escape(item['title'])}</h3>"
            f"<p>{escape(item.get('lead', ''))}{staff_html}</p>"
            "</a></li>"
        )
    equipment = (
        f'<ul class="lab-equip">{"".join(equip_items)}</ul>'
        if equip_items
        else f"<p>{escape(_st('equipment_pending'))}</p>"
    )
    service_cards = []
    for item in lab.get("developments") or []:
        service_cards.append(
            _cover_card(
                f"{prefix}developments/{item['slug']}/index.html",
                item["title"],
                item.get("lead", ""),
                meta=roster.person_contact_line(str(item.get("staff_id") or lab.get("head_id") or "")),
            )
        )
    services = (
        f'<div class="cover-grid is-3">{"".join(service_cards)}</div>'
        if service_cards
        else f"<p>{escape(_st('services_pending'))}</p>"
    )
    pubs = _publications_grouped(lab.get("publications") or [], depth, show_lab=False)
    if not pubs:
        pubs = f"<p>{escape(_st('lab_pubs_pending'))}</p>"
    projects = _lab_projects_block(lab)
    phone = lab.get("phone") or "+375 (17) 000-00-00"
    email = lab.get("email") or "lab@ichnm.by"
    head_line = roster.head_contact_line(lab)
    inner = (
        f"{_fish_banner()}"
        f'<p class="unit-back"><a href="{prefix}structure.html">{escape(_st("back_to_units"))}</a></p>'
        f'<nav class="lab-local" aria-label="{escape(_st("lab_local"))}">'
        f'<a href="#about">{escape(_st("lab_about"))}</a>'
        f'<a href="#directions">{escape(_st("lab_directions"))}</a>'
        f'<a href="#projects">{escape(_st("lab_projects"))}</a>'
        f'<a href="#equipment">{escape(_st("lab_equipment"))}</a>'
        f'<a href="#services">{escape(_st("lab_services"))}</a>'
        f'<a href="#staff">{escape(_st("lab_team"))}</a>'
        f'<a href="#pubs">{escape(_st("lab_pubs"))}</a>'
        f'<a href="#contacts">{escape(_st("lab_contacts"))}</a>'
        "</nav>"
        '<section id="about" class="lab-split">'
        f'{_photo_slot(_lt(lab), kicker[:2] if kicker else "ЛБ", kind="cover")}'
        "<div>"
        f"<h2>{escape(_st('lab_about'))}</h2>"
        + (
            f"<p>{escape(lab['about_filled'])}</p>"
            if lab.get("about_filled")
            else (
                f"<p>{escape(_st('lab_about_placeholder'))}</p>"
                f"<p>{escape(_st('lab_about_fish'))}</p>"
            )
        )
        + "</div></section>"
        '<section id="directions">'
        f"<h2>{escape(_st('lab_directions'))}</h2>"
        f"{directions}</section>"
        '<section id="projects">'
        f"<h2>{escape(_st('lab_projects'))}</h2>"
        f"{projects}</section>"
        '<section id="equipment">'
        f"<h2>{escape(_st('lab_equipment'))}</h2>"
        f"{equipment}</section>"
        '<section id="services">'
        f"<h2>{escape(_st('lab_services'))}</h2>"
        f"{services}</section>"
        '<section id="staff">'
        f"<h2>{escape(_st('our_team'))}</h2>"
        '<div class="staff-tab">'
        f"<p>{escape(_st('staff_tab_note'))}</p>"
        f'<div class="people-list">{"".join(people)}</div>'
        "</div></section>"
        '<section id="pubs">'
        f"<h2>{escape(_st('selected_pubs'))}</h2>"
        f"{pubs}"
        "</section>"
        '<section id="contacts" class="lab-contacts">'
        f"<h2>{escape(_st('lab_contacts'))}</h2>"
        f"<p>{escape(_st('address_label'))}{_t(_st('address_full'))}</p>"
        f"<p>{escape(_st('tel'))}{_t(str(phone))}</p>"
        f"<p>E-mail: {escape(str(email))}</p>"
        + (f"<p>{escape(_st('head_label'))}{_t(head_line)}</p>" if head_line else "")
        + "</section>"
    )
    extra = (
        f'<p class="lab-kicker">{kicker}</p>'
        if kicker
        else ""
    )
    trail = _trail(model, lab["id"], _lt(lab), depth=depth)
    return (
        f'<header class="page-hero"><div class="wrap">{trail}'
        f"{extra}<h1>{escape(_lt(lab))}</h1></div></header>"
        f'<div class="page-body is-wide is-lab">{inner}</div>'
    )

def _media_card(item: dict) -> str:
    issue = escape(item.get("date_label", ""))
    outlet = escape(item.get("outlet", ""))
    title = escape(item.get("title", ""))
    href = escape(item.get("href", "#"))
    summary = escape(item.get("summary", ""))
    return (
        "<article class=\"media-card\">"
        f"<p class=\"media-meta\"><span>{outlet}</span><time datetime=\"{escape(item.get('date', ''))}\">{issue}</time></p>"
        f"<h3><a href=\"{href}\" rel=\"noopener noreferrer\">{title}</a></h3>"
        f"<p>{summary}</p>"
        f"<p class=\"media-source\"><a href=\"{href}\" rel=\"noopener noreferrer\">{escape(_st('read_original'))}</a></p>"
        "</article>"
    )


def _media_about_inner() -> str:
    copy = page_copy("media_about")
    items = load_migrated_copy().get("media_items", [])
    parts = [f"<p>{escape(para)}</p>" for para in copy.get("paragraphs", [])]
    press = [row for row in items if row.get("kind") == "press"]
    navuka = [row for row in items if row.get("kind") == "navuka"]
    press.sort(key=lambda row: row.get("date", ""), reverse=True)
    navuka.sort(key=lambda row: row.get("date", ""), reverse=True)
    if press:
        cards = "".join(_media_card(row) for row in press)
        parts.append(f"<h2>{escape(_st('press'))}</h2>")
        parts.append(f'<div class="media-grid">{cards}</div>')
    if navuka:
        cards = "".join(_media_card(row) for row in navuka)
        parts.append(f"<h2>{escape(_st('navuka'))}</h2>")
        parts.append(
            f"<p>{escape(_st('navuka_note'))}"
            f'<a href="https://gazeta-navuka.by/">{escape(_st("navuka_archive"))}</a>.</p>'
        )
        parts.append(f'<div class="media-grid">{cards}</div>')
    return "".join(parts)


def _leadership_inner(item: MenuItem) -> str:
    copy = page_copy(item.id)
    parts = [f"<p>{_t(para)}</p>" for para in copy.get("paragraphs", [])]
    cards: list[str] = []
    for person in roster.leadership_people():
        cards.append(
            _person_card(
                name=person["name"],
                role=person.get("unit_role") or person.get("role", ""),
                initials=person.get("initials", ""),
                href=roster.person_href(person["id"]),
                lines=[person.get("degree", "")],
            )
        )
    if cards:
        parts.append(f'<div class="people-list">{"".join(cards)}</div>')
    return "".join(parts)
