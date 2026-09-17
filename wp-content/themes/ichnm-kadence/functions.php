<?php
/**
 * Kadence child: NAS identity, language switcher, primary menu, stable footer pack.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wp_resource_hints', static function (array $urls, string $relation_type): array {
    if ($relation_type === 'preconnect') {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = [
            'href' => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous',
        ];
    }
    return $urls;
}, 10, 2);

add_action('wp_enqueue_scripts', static function (): void {
    // Same Google stack as honest preview (Montserrat display + Roboto body).
    wp_enqueue_style(
        'ichnm-fonts',
        'https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Roboto:wght@400;500;700&display=swap',
        [],
        null
    );

    $parent_style = get_template_directory() . '/style.css';
    if (is_readable($parent_style)) {
        wp_enqueue_style(
            'kadence',
            get_template_directory_uri() . '/style.css',
            [],
            wp_get_theme('kadence')->get('Version')
        );
    }
    wp_enqueue_style(
        'ichnm-kadence',
        get_stylesheet_uri(),
        array_values(array_filter([
            'ichnm-fonts',
            is_readable($parent_style) ? 'kadence' : null,
        ])),
        wp_get_theme()->get('Version')
    );
    $ver = (string) wp_get_theme()->get('Version');
    $chrome_js = get_stylesheet_directory() . '/assets/chrome.js';
    if (is_readable($chrome_js)) {
        wp_enqueue_script(
            'ichnm-chrome',
            get_stylesheet_directory_uri() . '/assets/chrome.js',
            [],
            $ver,
            true
        );
        if (function_exists('ichnm_search_catalog')) {
            wp_add_inline_script(
                'ichnm-chrome',
                'window.ichnmSearchIndex = ' . wp_json_encode(ichnm_search_catalog()) . ';',
                'before'
            );
        }
    }
    $lattice_js = get_stylesheet_directory() . '/assets/lattice.js';
    if (is_front_page() && is_readable($lattice_js)) {
        wp_enqueue_script(
            'ichnm-lattice',
            get_stylesheet_directory_uri() . '/assets/lattice.js',
            [],
            $ver,
            true
        );
    }
});

function ichnm_theme_model(): array
{
    return function_exists('ichnm_site_model') ? ichnm_site_model() : [];
}

/**
 * Chrome / utility UI strings for the active language (structure shells; body stays RU).
 *
 * @return array<string, string>
 */
function ichnm_chrome_strings(?string $lang = null): array
{
    $lang = $lang ?: (function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru');
    if ($lang === '') {
        $lang = 'ru';
    }
    $pack = [
        'ru' => [
            'lang_aria' => 'Язык',
            'bvi' => 'Версия для слабовидящих',
            'search' => 'Поиск',
            'sitemap' => 'Карта сайта',
            'theme_day' => 'Дневная тема',
            'theme_night' => 'Ночная тема',
            'write' => 'Написать нам',
            'menu' => 'Меню',
            'search_aria' => 'Поиск',
            'search_label' => 'Поиск по сайту',
            'search_placeholder' => 'Персоналии, подразделения, приборы, разработки',
            'find' => 'Найти',
            'close' => 'Закрыть',
            'search_hint' => 'Введите не меньше двух букв. Есть и отдельная страница результатов.',
            'nas' => 'Национальная академия наук Беларуси',
            'cookies' => 'Политика cookie',
            'personal' => 'Персональные данные',
            'cookie_title' => 'Файлы cookie',
            'cookie_lead' => 'Сайт запоминает язык, тему и версию для слабовидящих. Это необходимые cookie. Аналитические cookie в v1 выключены. Подробнее —',
            'cookie_accept' => 'Принять',
            'cookie_reject' => 'Отклонить необязательные',
            'cookie_settings' => 'Настроить',
            'cookie_necessary' => 'Необходимые (всегда)',
            'cookie_analytics' => 'Аналитические',
            'cookie_save' => 'Сохранить',
            'sections' => 'Разделы',
            'footer_institute' => 'Институт',
            'footer_on_site' => 'На сайте',
            'footer_nas_socials' => 'Соцсети НАН',
            'footer_webmail' => 'Веб-почта института — адрес появится после PHP-тарифа.',
            'footer_find_us' => 'Как нас найти',
            'footer_map_alt' => 'Институт на карте Минска, ул. Ф. Скорины, 36',
            'footer_pictograms' => 'Ресурсы Академии и государственные порталы',
            'footer_news' => 'Новости',
            'footer_media' => 'СМИ о нас',
            'footer_education' => 'Научно-ориентированное образование',
            'footer_union' => 'Профсоюз',
            'footer_publications' => 'Публикации',
            'footer_feedback' => 'Обратная связь',
            'footer_street' => 'ул. Ф. Скорины, 36',
            'footer_address' => '220084, Республика Беларусь, г. Минск, ул. Ф. Скорины, 36',
            'fb_name' => 'Имя',
            'fb_email' => 'Email',
            'fb_message' => 'Сообщение',
            'fb_submit' => 'Отправить',
            'fb_ok_journal' => 'Сообщение принято. На локальном контуре оно сохранено в журнал WordPress.',
            'fb_ok_mail' => 'Сообщение отправлено. Мы ответим на указанный адрес.',
            'fb_err_nonce' => 'Сессия формы устарела. Обновите страницу.',
            'fb_err_fields' => 'Проверьте имя, email и текст сообщения.',
            'fb_err_mail' => 'Не удалось отправить сообщение. Попробуйте позже или напишите на адрес института.',
        ],
        'en' => [
            'lang_aria' => 'Language',
            'bvi' => 'Visually impaired version',
            'search' => 'Search',
            'sitemap' => 'Sitemap',
            'theme_day' => 'Light mode',
            'theme_night' => 'Dark mode',
            'write' => 'Contact us',
            'menu' => 'Menu',
            'search_aria' => 'Search',
            'search_label' => 'Site search',
            'search_placeholder' => 'People, units, facilities, developments',
            'find' => 'Search',
            'close' => 'Close',
            'search_hint' => 'Enter at least two characters. A full results page is also available.',
            'nas' => 'National Academy of Sciences of Belarus',
            'cookies' => 'Cookie policy',
            'personal' => 'Personal data',
            'cookie_title' => 'Cookies',
            'cookie_lead' => 'The site remembers language, theme, and the visually impaired mode. These are necessary cookies. Analytics cookies are off in v1. See',
            'cookie_accept' => 'Accept',
            'cookie_reject' => 'Reject optional',
            'cookie_settings' => 'Settings',
            'cookie_necessary' => 'Necessary (always on)',
            'cookie_analytics' => 'Analytics',
            'cookie_save' => 'Save',
            'sections' => 'Sections',
            'footer_institute' => 'Institute',
            'footer_on_site' => 'On this site',
            'footer_nas_socials' => 'NAS social media',
            'footer_webmail' => 'Institute webmail — the address will appear after the PHP hosting plan.',
            'footer_find_us' => 'How to find us',
            'footer_map_alt' => 'The institute on the Minsk map, 36 Skaryna Street',
            'footer_pictograms' => 'Academy resources and government portals',
            'footer_news' => 'News',
            'footer_media' => 'Media about us',
            'footer_education' => 'Research-oriented education',
            'footer_union' => 'Trade union',
            'footer_publications' => 'Publications',
            'footer_feedback' => 'Feedback',
            'footer_street' => '36 Skaryna Street',
            'footer_address' => '220084, Republic of Belarus, Minsk, 36 Skaryna Street',
            'fb_name' => 'Name',
            'fb_email' => 'Email',
            'fb_message' => 'Message',
            'fb_submit' => 'Send',
            'fb_ok_journal' => 'Message accepted. On the local contour it is stored in the WordPress journal.',
            'fb_ok_mail' => 'Message sent. We will reply to the address you provided.',
            'fb_err_nonce' => 'The form session expired. Please reload the page.',
            'fb_err_fields' => 'Please check your name, email, and message.',
            'fb_err_mail' => 'Could not send the message. Try again later or write to the institute address.',
        ],
        'be' => [
            'lang_aria' => 'Мова',
            'bvi' => 'Версія для слабавідушчых',
            'search' => 'Пошук',
            'sitemap' => 'Карта сайта',
            'theme_day' => 'Дзённы рэжым',
            'theme_night' => 'Начны рэжым',
            'write' => 'Напісаць нам',
            'menu' => 'Меню',
            'search_aria' => 'Пошук',
            'search_label' => 'Пошук па сайце',
            'search_placeholder' => 'Персаналіі, падраздзяленні, прыборы, распрацоўкі',
            'find' => 'Знайсці',
            'close' => 'Закрыць',
            'search_hint' => 'Увядзіце не менш за дзве літары. Ёсць і асобная старонка вынікаў.',
            'nas' => 'Нацыянальная акадэмія навук Беларусі',
            'cookies' => 'Палітыка cookie',
            'personal' => 'Персанальныя даныя',
            'cookie_title' => 'Файлы cookie',
            'cookie_lead' => 'Сайт запамінае мову, тэму і версію для слабавідушчых. Гэта неабходныя cookie. Аналітычныя cookie ў v1 выключаны. Падрабязней —',
            'cookie_accept' => 'Прыняць',
            'cookie_reject' => 'Адхіліць неабавязковыя',
            'cookie_settings' => 'Наладзіць',
            'cookie_necessary' => 'Неабходныя (заўсёды)',
            'cookie_analytics' => 'Аналітычныя',
            'cookie_save' => 'Захаваць',
            'sections' => 'Раздзелы',
            'footer_institute' => 'Інстытут',
            'footer_on_site' => 'На сайце',
            'footer_nas_socials' => 'Сацсеткі НАН',
            'footer_webmail' => 'Вэб-пошта інстытута — адрас з’явіцца пасля PHP-тарыфу.',
            'footer_find_us' => 'Як нас знайсці',
            'footer_map_alt' => 'Інстытут на карце Мінска, вул. Ф. Скарыны, 36',
            'footer_pictograms' => 'Рэсурсы Акадэміі і дзяржаўныя парталы',
            'footer_news' => 'Навіны',
            'footer_media' => 'СМІ пра нас',
            'footer_education' => 'Навукова-арыентаваная адукацыя',
            'footer_union' => 'Прафсаюз',
            'footer_publications' => 'Публікацыі',
            'footer_feedback' => 'Зваротная сувязь',
            'footer_street' => 'вул. Ф. Скарыны, 36',
            'footer_address' => '220084, Рэспубліка Беларусь, г. Мінск, вул. Ф. Скарыны, 36',
            'fb_name' => 'Імя',
            'fb_email' => 'Email',
            'fb_message' => 'Паведамленне',
            'fb_submit' => 'Адправіць',
            'fb_ok_journal' => 'Паведамленне прынята. На лакальным контуры яно захавана ў журнале WordPress.',
            'fb_ok_mail' => 'Паведамленне адпраўлена. Мы адкажам на пазначаны адрас.',
            'fb_err_nonce' => 'Сесія формы састарэла. Абнавіце старонку.',
            'fb_err_fields' => 'Праверце імя, email і тэкст паведамлення.',
            'fb_err_mail' => 'Не ўдалося адправіць паведамленне. Паспрабуйце пазней або напішыце на адрас інстытута.',
        ],
        'zh' => [
            'lang_aria' => '语言',
            'bvi' => '视力障碍版本',
            'search' => '搜索',
            'sitemap' => '网站地图',
            'theme_day' => '浅色模式',
            'theme_night' => '深色模式',
            'write' => '联系我们',
            'menu' => '菜单',
            'search_aria' => '搜索',
            'search_label' => '站内搜索',
            'search_placeholder' => '人员、单位、设备、研发成果',
            'find' => '搜索',
            'close' => '关闭',
            'search_hint' => '请至少输入两个字符。也可打开完整结果页。',
            'nas' => '白俄罗斯国家科学院',
            'cookies' => 'Cookie 政策',
            'personal' => '个人数据',
            'cookie_title' => 'Cookie',
            'cookie_lead' => '网站会记住语言、主题和无障碍模式。这些是必要 Cookie。v1 中分析 Cookie 已关闭。详见',
            'cookie_accept' => '接受',
            'cookie_reject' => '拒绝非必要项',
            'cookie_settings' => '设置',
            'cookie_necessary' => '必要（始终开启）',
            'cookie_analytics' => '分析',
            'cookie_save' => '保存',
            'sections' => '栏目',
            'footer_institute' => '研究所',
            'footer_on_site' => '本站',
            'footer_nas_socials' => '科学院社交账号',
            'footer_webmail' => '研究所网页邮箱——地址将在开通 PHP 主机后公布。',
            'footer_find_us' => '如何找到我们',
            'footer_map_alt' => '明斯克地图上的研究所，斯卡里纳大街 36 号',
            'footer_pictograms' => '科学院资源与政府门户',
            'footer_news' => '新闻',
            'footer_media' => '媒体报道',
            'footer_education' => '科研导向教育',
            'footer_union' => '工会',
            'footer_publications' => '论文',
            'footer_feedback' => '反馈',
            'footer_street' => '斯卡里纳大街 36 号',
            'footer_address' => '220084，白俄罗斯共和国，明斯克，斯卡里纳大街 36 号',
            'fb_name' => '姓名',
            'fb_email' => '电子邮件',
            'fb_message' => '留言',
            'fb_submit' => '发送',
            'fb_ok_journal' => '留言已接收。在本地环境中保存到 WordPress 日志。',
            'fb_ok_mail' => '留言已发送。我们会回复到您填写的地址。',
            'fb_err_nonce' => '表单会话已过期，请刷新页面。',
            'fb_err_fields' => '请检查姓名、电子邮件和留言内容。',
            'fb_err_mail' => '无法发送留言。请稍后再试，或写信至研究所邮箱。',
        ],
    ];
    return $pack[$lang] ?? $pack['ru'];
}

/**
 * Structural UI strings for feed hubs and catalogue shells (body copy stays RU).
 *
 * @return array<string, string>
 */
function ichnm_hub_strings(?string $lang = null): array
{
    $lang = $lang ?: (function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru');
    if ($lang === '') {
        $lang = 'ru';
    }
    $pack = [
        'ru' => [
            'institute_news' => 'Новости Института',
            'to_feed' => 'К ленте',
            'news_empty' => 'Новости Института появятся после публикации ленты.',
            'media_about' => 'СМИ о нас',
            'all_media' => 'Все материалы',
            'media_empty' => 'Публикации СМИ появятся после переноса карточек.',
            'next_event' => 'Ближайшее мероприятие',
            'event_register' => 'Регистрация / сайт серии',
            'aist_section' => 'Раздел AIST',
            'events_calendar' => 'Календарь и архив',
            'events_empty' => 'Мероприятия появятся после публикации календаря.',
            'pubs_labs' => 'Публикации лабораторий',
            'pubs_empty' => 'Стартовый список ещё не засеян. После sync появятся записи, снятые с ichnm.by, либо таблица коллег.',
            'articles_by_year' => 'Статьи по годам',
            'search_query' => 'Запрос',
            'search_placeholder' => 'Фамилия, лаборатория, прибор, разработка',
            'search_min' => 'Введите не меньше двух букв.',
            'search_empty' => 'Ничего не найдено. Попробуйте фамилию, лабораторию, прибор или разработку.',
            'search_kind_person' => 'Персоналии',
            'search_kind_unit' => 'Подразделения',
            'search_kind_facility' => 'Приборы',
            'search_kind_development' => 'Разработки',
            'sitemap_aria' => 'Карта сайта',
            'leadership' => 'Руководство',
            'open_search' => 'Открыть поиск',
        ],
        'en' => [
            'institute_news' => 'Institute news',
            'to_feed' => 'To the feed',
            'news_empty' => 'Institute news will appear after the feed is published.',
            'media_about' => 'Media about us',
            'all_media' => 'All items',
            'media_empty' => 'Media items will appear after the cards are migrated.',
            'next_event' => 'Next event',
            'event_register' => 'Registration / series site',
            'aist_section' => 'AIST section',
            'events_calendar' => 'Calendar and archive',
            'events_empty' => 'Events will appear after the calendar is published.',
            'pubs_labs' => 'Laboratory publications',
            'pubs_empty' => 'The starter list is not seeded yet. After sync, rows scraped from ichnm.by or the colleague spreadsheet will appear.',
            'articles_by_year' => 'Papers by year',
            'search_query' => 'Query',
            'search_placeholder' => 'Surname, laboratory, instrument, development',
            'search_min' => 'Enter at least two characters.',
            'search_empty' => 'Nothing found. Try a surname, laboratory, instrument or development.',
            'search_kind_person' => 'People',
            'search_kind_unit' => 'Units',
            'search_kind_facility' => 'Instruments',
            'search_kind_development' => 'Developments',
            'sitemap_aria' => 'Sitemap',
            'leadership' => 'Leadership',
            'open_search' => 'Open search',
        ],
        'be' => [
            'institute_news' => 'Навіны Інстытута',
            'to_feed' => 'Да стужкі',
            'news_empty' => 'Навіны Інстытута з’явяцца пасля публікацыі стужкі.',
            'media_about' => 'СМІ пра нас',
            'all_media' => 'Усе матэрыялы',
            'media_empty' => 'Публікацыі СМІ з’явяцца пасля пераносу картак.',
            'next_event' => 'Бліжэйшае мерапрыемства',
            'event_register' => 'Рэгістрацыя / сайт серыі',
            'aist_section' => 'Раздзел AIST',
            'events_calendar' => 'Календар і архіў',
            'events_empty' => 'Мерапрыемствы з’явяцца пасля публікацыі календара.',
            'pubs_labs' => 'Публікацыі лабараторый',
            'pubs_empty' => 'Стартавы спіс яшчэ не засеяны. Пасля sync з’явяцца запісы з ichnm.by або табліцы калег.',
            'articles_by_year' => 'Артыкулы па гадах',
            'search_query' => 'Запыт',
            'search_placeholder' => 'Прозвішча, лабараторыя, прыбор, распрацоўка',
            'search_min' => 'Увядзіце не менш за дзве літары.',
            'search_empty' => 'Нічога не знойдзена. Паспрабуйце прозвішча, лабараторыю, прыбор або распрацоўку.',
            'search_kind_person' => 'Персаналіі',
            'search_kind_unit' => 'Падраздзяленні',
            'search_kind_facility' => 'Прыборы',
            'search_kind_development' => 'Распрацоўкі',
            'sitemap_aria' => 'Карта сайта',
            'leadership' => 'Кіраўніцтва',
            'open_search' => 'Адкрыць пошук',
        ],
        'zh' => [
            'institute_news' => '研究所新闻',
            'to_feed' => '查看资讯',
            'news_empty' => '研究所新闻将在发布资讯流后显示。',
            'media_about' => '媒体报道',
            'all_media' => '全部内容',
            'media_empty' => '媒体报道将在卡片迁入后显示。',
            'next_event' => '近期活动',
            'event_register' => '注册 / 系列网站',
            'aist_section' => 'AIST 栏目',
            'events_calendar' => '日历与档案',
            'events_empty' => '活动将在日历发布后显示。',
            'pubs_labs' => '各实验室论文',
            'pubs_empty' => '起始列表尚未导入。同步后将显示从 ichnm.by 抓取的条目或同事表格。',
            'articles_by_year' => '按年论文数',
            'search_query' => '查询',
            'search_placeholder' => '姓氏、实验室、仪器、成果',
            'search_min' => '请至少输入两个字符。',
            'search_empty' => '没有结果。请尝试姓氏、实验室、仪器或成果。',
            'search_kind_person' => '人员',
            'search_kind_unit' => '单位',
            'search_kind_facility' => '仪器',
            'search_kind_development' => '成果',
            'sitemap_aria' => '网站地图',
            'leadership' => '领导班子',
            'open_search' => '打开搜索',
        ],
    ];
    return $pack[$lang] ?? $pack['ru'];
}

/**
 * Hub kicker labels for CPT singles.
 *
 * @return array{label:string,href:string}
 */
function ichnm_hub_kicker(string $hub_slug): array
{
    $labels = [
        'news' => ['ru' => 'Новости', 'en' => 'News', 'be' => 'Навіны', 'zh' => '新闻'],
        'events' => ['ru' => 'Мероприятия', 'en' => 'Events', 'be' => 'Мерапрыемствы', 'zh' => '活动'],
        'publications' => ['ru' => 'Публикации', 'en' => 'Publications', 'be' => 'Публікацыі', 'zh' => '出版物'],
        'leadership' => ['ru' => 'Руководство', 'en' => 'Leadership', 'be' => 'Кіраўніцтва', 'zh' => '领导班子'],
        'structure' => ['ru' => 'Структура', 'en' => 'Structure', 'be' => 'Структура', 'zh' => '机构设置'],
        'media_about' => ['ru' => 'СМИ о нас', 'en' => 'Media about us', 'be' => 'СМІ пра нас', 'zh' => '媒体报道'],
    ];
    $lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru';
    if ($lang === '') {
        $lang = 'ru';
    }
    $page_slug = $hub_slug === 'media_about' ? 'news' : $hub_slug;
    $page = ichnm_translated_page($page_slug);
    $href = $page instanceof WP_Post
        ? (string) get_permalink($page)
        : home_url('/' . rawurlencode($page_slug) . '/');
    $label = $labels[$hub_slug][$lang] ?? ($labels[$hub_slug]['ru'] ?? $hub_slug);
    return ['label' => $label, 'href' => $href];
}

/**
 * Canonical RU hub slug for a page (handles news-en shells and Polylang links).
 */
function ichnm_page_hub_base(?WP_Post $page = null): string
{
    if (!$page instanceof WP_Post) {
        $page = get_queried_object();
    }
    if (!$page instanceof WP_Post) {
        return '';
    }
    $base = (string) $page->post_name;
    if (function_exists('pll_get_post_language') && function_exists('pll_get_post')) {
        $lang = (string) pll_get_post_language((int) $page->ID);
        if ($lang && $lang !== 'ru') {
            $ru_id = (int) pll_get_post((int) $page->ID, 'ru');
            if ($ru_id > 0) {
                $ru = get_post($ru_id);
                if ($ru instanceof WP_Post) {
                    $base = (string) $ru->post_name;
                }
            }
        }
    }
    $stripped = preg_replace('/-(en|be|zh)$/', '', $base);
    return is_string($stripped) && $stripped !== '' ? $stripped : $base;
}

/**
 * Page by exact slug, ignoring Polylang language filters (any language shell).
 */
function ichnm_find_page_by_slug(string $slug): ?WP_Post
{
    if ($slug === '') {
        return null;
    }
    if (function_exists('ichnm_find_ru_page') && !preg_match('/-(en|be|zh)$/', $slug)) {
        $ru = ichnm_find_ru_page($slug);
        if ($ru instanceof WP_Post) {
            return $ru;
        }
    }
    $posts = get_posts([
        'name' => $slug,
        'post_type' => 'page',
        'post_status' => ['publish', 'draft', 'private'],
        'posts_per_page' => 5,
        'suppress_filters' => true,
    ]);
    foreach ($posts as $post) {
        if ($post instanceof WP_Post && $post->post_name === $slug) {
            return $post;
        }
    }
    return null;
}

function ichnm_translated_page(string $slug): ?WP_Post
{
    $ru = null;
    if (function_exists('ichnm_find_ru_page')) {
        $ru = ichnm_find_ru_page($slug);
    }
    if (!$ru instanceof WP_Post) {
        $ru = ichnm_find_page_by_slug($slug) ?: get_page_by_path($slug);
    }
    if (!$ru instanceof WP_Post) {
        return null;
    }
    $lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru';
    if ($lang === '' || $lang === 'ru' || !function_exists('pll_get_post')) {
        return $ru;
    }
    $translated = (int) pll_get_post((int) $ru->ID, $lang);
    if ($translated > 0) {
        $post = get_post($translated);
        if ($post instanceof WP_Post && $post->post_status === 'publish') {
            return $post;
        }
    }
    $shell = ichnm_find_page_by_slug($slug . '-' . $lang);
    if ($shell instanceof WP_Post && $shell->post_status === 'publish') {
        return $shell;
    }
    return $ru;
}

/**
 * Bind institute menu only to the custom chrome location (not Kadence primary).
 */
function ichnm_bind_primary_menu(): void
{
    $locations = get_theme_mod('nav_menu_locations');
    if (!is_array($locations)) {
        $locations = [];
    }
    $menu = wp_get_nav_menu_object('Главное меню ИХНМ');
    $menu_id = $menu ? (int) $menu->term_id : (int) ($locations['ichnm-primary'] ?? 0);
    if ($menu_id <= 0) {
        return;
    }
    $changed = false;
    if ((int) ($locations['ichnm-primary'] ?? 0) !== $menu_id) {
        $locations['ichnm-primary'] = $menu_id;
        $changed = true;
    }
    foreach (['primary', 'mobile', 'secondary'] as $slot) {
        if (!empty($locations[$slot])) {
            $locations[$slot] = 0;
            $changed = true;
        }
    }
    if ($changed) {
        set_theme_mod('nav_menu_locations', $locations);
    }
}
add_action('init', 'ichnm_bind_primary_menu', 30);

/**
 * NAS-class page-open veil markup. Hidden via CSS when prefers-reduced-motion
 * or BVI is active; chrome.js only runs the wipe when js-motion is allowed.
 */
function ichnm_render_veil(): void
{
    echo '<div class="ichnm-page-veil" aria-hidden="true"></div>';
}

function ichnm_render_chrome_open(): void
{
    echo '<div class="ichnm-chrome"><div class="wrap ichnm-chrome-inner">';
}

function ichnm_render_identity(): void
{
    $model = ichnm_theme_model();
    $ui = ichnm_chrome_strings();
    $short = $model['identity']['short_name'] ?? 'ИХНМ НАН Беларуси';
    $nas = $model['identity']['nas_portal_href'] ?? 'https://nasb.gov.by/rus/index.php';
    $home = function_exists('pll_home_url') ? (string) pll_home_url() : home_url('/');
    echo '<div class="ichnm-identity">';
    echo '<a class="nas-emblem" href="' . esc_url($nas) . '" title="' . esc_attr($ui['nas']) . '">';
    $nas_svg = get_stylesheet_directory() . '/assets/nas-emblem.svg';
    $nas_src = (is_readable($nas_svg) && (int) filesize($nas_svg) > 0)
        ? get_stylesheet_directory_uri() . '/assets/nas-emblem.svg'
        : get_stylesheet_directory_uri() . '/assets/nas-emblem.webp';
    echo '<img src="' . esc_url($nas_src) . '" width="220" height="120" alt="' . esc_attr($ui['nas']) . '">';
    echo '</a>';
    echo '<a href="' . esc_url($home) . '">';
    echo '<img class="ichnm-mark" src="' . esc_url(get_stylesheet_directory_uri() . '/assets/ichnm-mark.svg') . '" width="230" height="193" alt="Эмблема ИХНМ">';
    echo '</a>';
    echo '<div class="ichnm-identity-text">';
    echo '<strong>' . esc_html($short) . '</strong>';
    echo '<a href="' . esc_url($nas) . '">' . esc_html($ui['nas']) . '</a>';
    echo '</div></div>';
}

function ichnm_render_language_switch(): void
{
    $model = ichnm_theme_model();
    $ui = ichnm_chrome_strings();
    $langs = $model['languages'] ?? [];
    $search = ichnm_translated_page('search');
    $sitemap = ichnm_translated_page('sitemap');
    $search_href = $search instanceof WP_Post ? get_permalink($search) : home_url('/search/');
    $sitemap_href = $sitemap instanceof WP_Post ? get_permalink($sitemap) : home_url('/sitemap/');

    $current_id = (int) get_queried_object_id();
    $current_lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru';

    echo '<div class="ichnm-chrome-tools">';
    echo '<button type="button" class="ichnm-menu-toggle tool-btn" aria-expanded="false" aria-controls="ichnm-primary-nav">' . esc_html($ui['menu']) . '</button>';
    echo '</div>';
    echo '<div class="ichnm-header-panel">';
    echo '<div class="ichnm-header-utilities">';
    // Preview order: search → sitemap → theme → BVI → write → languages.
    echo '<button type="button" class="ichnm-tool-btn tool-btn" data-ichnm-search-open aria-controls="ichnm-site-search">' . esc_html($ui['search']) . '</button>';
    echo '<a class="ichnm-tool-btn tool-btn" href="' . esc_url($sitemap_href) . '">' . esc_html($ui['sitemap']) . '</a>';
    echo '<button type="button" class="ichnm-tool-btn tool-btn" data-theme-toggle aria-pressed="false" data-label-day="' . esc_attr($ui['theme_day']) . '" data-label-night="' . esc_attr($ui['theme_night']) . '">' . esc_html($ui['theme_night']) . '</button>';
    if (shortcode_exists('bvi')) {
        echo '<div class="ichnm-bvi">' . do_shortcode('[bvi text="' . esc_attr($ui['bvi']) . '"]') . '</div>';
    }
    $feedback = ichnm_translated_page('feedback');
    $feedback_href = $feedback instanceof WP_Post ? get_permalink($feedback) : home_url('/feedback/');
    echo '<a class="ichnm-tool-btn tool-btn" href="' . esc_url($feedback_href) . '">' . esc_html($ui['write']) . '</a>';
    if ($langs) {
        echo '<nav class="ichnm-lang-switch" aria-label="' . esc_attr($ui['lang_aria']) . '">';
        foreach ($langs as $lang) {
            $code = strtolower((string) $lang['code']);
            $label = strtoupper($code);
            $href = home_url((string) ($lang['prefix'] ?? '/'));
            if (function_exists('pll_home_url')) {
                $href = (string) pll_home_url($code);
            }
            if ($current_id > 0 && function_exists('pll_get_post')) {
                $translated = (int) pll_get_post($current_id, $code);
                if ($translated > 0) {
                    $href = (string) get_permalink($translated);
                } elseif (is_singular('page')) {
                    // Fallback for hub shells if Polylang link is missing: news → news-en.
                    $base = ichnm_page_hub_base();
                    if ($base !== '') {
                        $shell_slug = $code === 'ru' ? $base : $base . '-' . $code;
                        $shell = ichnm_find_page_by_slug($shell_slug);
                        if ($shell instanceof WP_Post && $shell->post_status === 'publish') {
                            $href = (string) get_permalink($shell);
                        }
                    }
                }
            }
            $class = ($code === $current_lang || ($current_lang === '' && $code === 'ru')) ? 'is-current' : '';
            echo '<a class="' . esc_attr($class) . '" href="' . esc_url($href) . '" hreflang="' . esc_attr($code) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';
    }
    echo '</div>'; // .ichnm-header-utilities
}

function ichnm_render_primary_menu(): void
{
    // Resolve by menu name — Polylang can strip custom theme_location assignments
    // from theme_mod_nav_menu_locations on the front, so has_nav_menu() may lie.
    $menu = wp_get_nav_menu_object('Главное меню ИХНМ');
    if (!$menu && !has_nav_menu('ichnm-primary')) {
        echo '</div>'; // close .ichnm-header-panel opened in language_switch
        return;
    }
    echo '<nav id="ichnm-primary-nav" class="ichnm-menu" aria-label="' . esc_attr(ichnm_chrome_strings()['sections']) . '">';
    $args = [
        'container' => false,
        'fallback_cb' => false,
        'depth' => 3,
        'menu_class' => 'ichnm-menu-list',
    ];
    if ($menu) {
        $args['menu'] = (int) $menu->term_id;
    } else {
        $args['theme_location'] = 'ichnm-primary';
    }
    wp_nav_menu($args);
    echo '</nav>';
    echo '</div>'; // .ichnm-header-panel
}

function ichnm_render_chrome_close(): void
{
    echo '</div></div>';
}

function ichnm_render_search_overlay(): void
{
    $ui = ichnm_chrome_strings();
    $search = ichnm_translated_page('search');
    $action = $search instanceof WP_Post ? get_permalink($search) : home_url('/search/');
    echo '<div class="ichnm-site-search" id="ichnm-site-search" hidden role="dialog" aria-modal="true" aria-label="' . esc_attr($ui['search_aria']) . '">';
    echo '<div class="ichnm-site-search-panel">';
    echo '<form class="ichnm-search-form" action="' . esc_url($action) . '" method="get" role="search">';
    echo '<label class="screen-reader-text" for="ichnm-q-live">' . esc_html($ui['search_label']) . '</label>';
    echo '<input id="ichnm-q-live" name="q" type="search" placeholder="' . esc_attr($ui['search_placeholder']) . '" autocomplete="off" minlength="2">';
    echo '<button type="submit" class="ichnm-pill ichnm-pill-primary">' . esc_html($ui['find']) . '</button>';
    echo '<button type="button" class="ichnm-tool-btn" data-ichnm-search-close>' . esc_html($ui['close']) . '</button>';
    echo '</form>';
    echo '<p class="ichnm-search-hint">' . esc_html($ui['search_hint']) . '</p>';
    echo '<div class="ichnm-search-live" data-ichnm-search-live role="status"></div>';
    echo '</div></div>';
}

/**
 * Early theme + motion + cookie consent boot (same FOUC guard as honest preview).
 * Motion (js-motion) skips prefers-reduced-motion and active BVI cookie/class.
 */
function ichnm_theme_boot_script(): void
{
    echo '<script>(function(){try{if(document.cookie.indexOf("ichnm-cookies=")!==-1||localStorage.getItem("ichnm-cookies")){document.documentElement.classList.add("cookies-ok")}}catch(e){}try{var t=localStorage.getItem("ichnm-theme");var h=new Date().getHours();var night=t==="night"||(t!=="day"&&(h>=21||h<7));if(night)document.documentElement.classList.add("theme-night")}catch(e){}try{var d=document.documentElement;var reduce=window.matchMedia("(prefers-reduced-motion: reduce)").matches;var bvi=d.classList.contains("bvi-active")||d.classList.contains("is-bvi")||/(?:^|;\\s*)bvi[^=]*=/.test(document.cookie||"");if(!reduce&&!bvi)d.classList.add("js-motion")}catch(e){}})();</script>' . "\n";
}
add_action('wp_head', 'ichnm_theme_boot_script', 0);

add_action('wp_body_open', 'ichnm_render_veil', 1);
add_action('wp_body_open', 'ichnm_render_chrome_open', 4);
add_action('wp_body_open', 'ichnm_render_identity', 5);
add_action('wp_body_open', 'ichnm_render_language_switch', 6);
add_action('wp_body_open', 'ichnm_render_primary_menu', 7);
add_action('wp_body_open', 'ichnm_render_chrome_close', 8);
add_action('wp_body_open', 'ichnm_render_search_overlay', 9);

/**
 * Inline social mark for footer (preview-class icons; label stays visible).
 */
function ichnm_footer_social_icon(string $network): string
{
    $paths = [
        'facebook' => 'M10 3.2h1.8V.9H10c-2 0-3.3 1.2-3.3 3.4v1.5H5v2.4h1.7V16h2.5V8.2h2.1l.4-2.4H9.2V4.6c0-.7.3-1.4 1.4-1.4z',
        'vk' => 'M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 15.684 0zm3.692 17.123h-1.744c-.66 0-.864-.525-2.05-1.727-1.033-1.01-1.49-1.147-1.744-1.147-.356 0-.458.102-.458.593v1.575c0 .424-.135.688-1.261.688-1.862 0-3.926-1.126-5.379-3.224C4.24 10.883 3.5 8.68 3.5 8.36c0-.323.102-.594.593-.594h1.744c.44 0 .61.253.78.843.863 2.49 2.303 4.677 2.896 4.677.226 0 .338-.105.338-.688V9.721c-.068-1.186-.695-1.287-.695-1.71 0-.204.17-.407.44-.407h2.744c.44 0 .525.22.525.688v3.686c0 .355.16.479.254.479.226 0 .407-.124.814-.53 1.254-1.406 2.151-3.574 2.151-3.574.119-.254.322-.593.763-.593h1.744c.525 0 .643.27.525.643-.22 1.017-2.354 4.031-2.354 4.031-.186.305-.256.44 0 .78.186.254.796.779 1.203 1.253.745.847 1.32 1.558 1.473 2.05.17.49-.085.743-.576.743z',
        'telegram' => 'M15.7 2.3 1.8 7.6c-.9.4-.9 1 .2 1.3l3.5 1.1 1.3 4.1c.2.5.3.7.7.7.4 0 .6-.2.8-.5l2-2.1 4.1 3c.8.4 1.3.2 1.5-.7l2.7-12.7c.3-1.1-.4-1.6-1.2-1.3zM6.7 9.9l7.3-4.6-5.7 5.5-.2 2.5-1.4-3.4z',
        'instagram' => 'M8 4.4A3.6 3.6 0 1 0 8 11.6 3.6 3.6 0 0 0 8 4.4zm0 5.9A2.3 2.3 0 1 1 8 5.7a2.3 2.3 0 0 1 0 4.6zM12.4 4.2a.84.84 0 1 1-1.68 0 .84.84 0 0 1 1.68 0zM14.7 4.3a4.1 4.1 0 0 0-1.1-2.9 4.1 4.1 0 0 0-2.9-1.1H5.3A4.1 4.1 0 0 0 2.4 1.4 4.1 4.1 0 0 0 1.3 4.3v5.4a4.1 4.1 0 0 0 1.1 2.9 4.1 4.1 0 0 0 2.9 1.1h5.4a4.1 4.1 0 0 0 2.9-1.1 4.1 4.1 0 0 0 1.1-2.9V4.3zm-1.3 5.4a2.8 2.8 0 0 1-.8 2 2.8 2.8 0 0 1-2 .8H5.3a2.8 2.8 0 0 1-2-.8 2.8 2.8 0 0 1-.8-2V4.3a2.8 2.8 0 0 1 .8-2 2.8 2.8 0 0 1 2-.8h5.4a2.8 2.8 0 0 1 2 .8 2.8 2.8 0 0 1 .8 2z',
        'youtube' => 'M15.6 4.4s-.1-1.2-.5-1.7c-.5-.5-1.1-.5-1.3-.6C11.9 2 8 2 8 2h0s-3.9 0-5.8.1c-.3 0-.8.1-1.3.6-.4.5-.5 1.7-.5 1.7S0 5.8 0 7.2v1.6c0 1.4.2 2.8.2 2.8s.1 1.2.5 1.7c.5.5 1.2.5 1.5.6 1.1.1 5.8.1 5.8.1s3.9 0 5.8-.1c.3 0 .8-.1 1.3-.6.4-.5.5-1.7.5-1.7s.2-1.4.2-2.8V7.2c0-1.4-.2-2.8-.2-2.8zM6.4 10.6V5.4L12 8z',
    ];
    $key = strtolower($network);
    if (!isset($paths[$key])) {
        return '';
    }
    $box = $key === 'vk' ? '0 0 24 24' : '0 0 16 16';
    return '<svg class="social-icon" viewBox="' . esc_attr($box) . '" width="16" height="16" aria-hidden="true">'
        . '<path fill="currentColor" d="' . esc_attr($paths[$key]) . '"/></svg>';
}

/**
 * Site utility links for the footer «На сайте» column (preview contour; no lattice demo).
 *
 * @return list<array{href:string,title:string}>
 */
function ichnm_footer_site_links(array $ui): array
{
    $rows = [
        ['slug' => 'news', 'title' => $ui['footer_news']],
        ['slug' => 'news', 'title' => $ui['footer_media']],
        ['slug' => 'education', 'title' => $ui['footer_education']],
        ['slug' => 'union', 'title' => $ui['footer_union']],
        ['slug' => 'publications', 'title' => $ui['footer_publications']],
        ['slug' => 'feedback', 'title' => $ui['footer_feedback']],
        ['slug' => 'search', 'title' => $ui['search']],
        ['slug' => 'sitemap', 'title' => $ui['sitemap']],
        ['slug' => 'cookies', 'title' => $ui['cookies']],
        ['slug' => 'personal-data', 'title' => $ui['personal']],
    ];
    $out = [];
    foreach ($rows as $row) {
        $page = ichnm_translated_page($row['slug']);
        if (!$page instanceof WP_Post) {
            continue;
        }
        $out[] = ['href' => (string) get_permalink($page), 'title' => $row['title']];
    }
    return $out;
}

/**
 * Drop Kadence #colophon / theme credit from the public DOM.
 * Institute footer is rendered by ichnm_render_footer(); CSS hide alone leaves screen-reader text.
 */
add_action('wp', static function (): void {
    remove_action('kadence_footer', 'Kadence\\footer_markup');
}, 20);

/**
 * Institute footer matching honest preview: identity / on-site / social / map + NAS pictograms.
 */
function ichnm_render_footer(): void
{
    $model = ichnm_theme_model();
    $ui = ichnm_chrome_strings();
    $identity = $model['identity'] ?? [];
    $legal_name = (string) ($identity['legal_name'] ?? 'ИХНМ НАН Беларуси');
    $nas = (string) ($identity['nas_portal_href'] ?? 'https://nasb.gov.by/rus/index.php');
    $nas_social = $model['footer']['nas_social'] ?? [];
    $icnm_social = function_exists('ichnm_effective_icnm_social')
        ? ichnm_effective_icnm_social()
        : array_values(array_filter(
            $model['footer']['icnm_social'] ?? [],
            static function ($row): bool {
                return is_array($row) && !empty($row['href']);
            }
        ));
    $pictograms = $model['footer']['pictograms'] ?? [];
    $contacts = ichnm_translated_page('contacts');
    $contacts_href = $contacts instanceof WP_Post ? (string) get_permalink($contacts) : home_url('/contacts/');

    echo '<footer class="ichnm-footer ichnm-footer-extra">';
    echo '<div class="wrap footer-grid ichnm-footer-grid">';

    echo '<div class="ichnm-footer-col">';
    echo '<p class="footer-heading">' . esc_html($ui['footer_institute']) . '</p>';
    echo '<p class="ichnm-footer-identity"><strong>' . esc_html($legal_name) . '</strong></p>';
    echo '<p>' . esc_html($ui['footer_address']) . '</p>';
    echo '<p><a href="' . esc_url($nas) . '">' . esc_html($ui['nas']) . '</a></p>';
    echo '</div>';

    echo '<div class="ichnm-footer-col">';
    echo '<p class="footer-heading">' . esc_html($ui['footer_on_site']) . '</p>';
    echo '<ul class="ichnm-footer-legal">';
    foreach (ichnm_footer_site_links($ui) as $link) {
        echo '<li><a href="' . esc_url($link['href']) . '">' . esc_html($link['title']) . '</a></li>';
    }
    echo '</ul></div>';

    echo '<div class="ichnm-footer-col">';
    echo '<p class="footer-heading">' . esc_html($ui['footer_nas_socials']) . '</p>';
    echo '<ul class="ichnm-footer-social">';
    foreach (array_merge($nas_social, $icnm_social) as $link) {
        if (!is_array($link) || empty($link['href'])) {
            continue;
        }
        $label = (string) ($link['label'] ?? $link['network'] ?? '');
        $network = (string) ($link['network'] ?? '');
        echo '<li><a href="' . esc_url((string) $link['href']) . '" rel="noopener noreferrer">';
        echo ichnm_footer_social_icon($network);
        echo '<span>' . esc_html($label) . '</span></a></li>';
    }
    echo '</ul>';
    echo '<p>' . esc_html($ui['footer_webmail']) . '</p>';
    echo '</div>';

    echo '<div class="footer-place ichnm-footer-col">';
    echo '<p class="footer-heading">' . esc_html($ui['footer_find_us']) . '</p>';
    echo '<a class="footer-map ichnm-footer-map" href="' . esc_url($contacts_href) . '" aria-label="' . esc_attr($ui['footer_map_alt']) . '">';
    if (function_exists('ichnm_minsk_map_html')) {
        echo ichnm_minsk_map_html();
    }
    echo '<span>' . esc_html($ui['footer_street']) . '</span></a>';
    echo '</div>';

    echo '</div>'; // .footer-grid

    if (is_array($pictograms) && $pictograms) {
        echo '<div class="footer-pictograms" aria-label="' . esc_attr($ui['footer_pictograms']) . '">';
        echo '<div class="wrap">';
        echo '<p class="footer-heading">' . esc_html($ui['footer_pictograms']) . '</p>';
        echo '<ul class="picto-strip">';
        foreach ($pictograms as $pic) {
            if (!is_array($pic) || empty($pic['href'])) {
                continue;
            }
            $title = (string) ($pic['title'] ?? '');
            $mark = (string) ($pic['mark'] ?? '');
            if ($mark === '') {
                $mark = function_exists('mb_substr') ? (string) mb_substr($title, 0, 2) : substr($title, 0, 2);
            }
            $icon = (string) ($pic['icon'] ?? '');
            $icon = preg_replace('#^media/#', '', $icon) ?? $icon;
            echo '<li><a href="' . esc_url((string) $pic['href']) . '" rel="noopener noreferrer">';
            $shown = false;
            if ($icon !== '' && function_exists('ichnm_source_path') && function_exists('ichnm_source_url')) {
                $path = ichnm_source_path($icon);
                if (is_readable($path)) {
                    echo '<img class="picto-img" src="' . esc_url(ichnm_source_url($icon)) . '" alt="" width="140" height="66" loading="lazy">';
                    $shown = true;
                }
            }
            if (!$shown) {
                echo '<span class="picto-mark">' . esc_html($mark) . '</span>';
            }
            echo '<span class="picto-title">' . esc_html($title) . '</span></a></li>';
        }
        echo '</ul></div></div>';
    }

    echo '</footer>';
}

add_action('wp_footer', 'ichnm_render_footer', 5);

/**
 * Cookie consent banner (honest preview parity). Analytics stay off in v1.
 */
function ichnm_render_cookie_banner(): void
{
    $ui = ichnm_chrome_strings();
    $cookies = ichnm_translated_page('cookies');
    $personal = ichnm_translated_page('personal-data');
    $cookies_href = $cookies instanceof WP_Post ? (string) get_permalink($cookies) : home_url('/cookies/');
    $personal_href = $personal instanceof WP_Post ? (string) get_permalink($personal) : home_url('/personal-data/');

    echo '<div class="cookie-banner" id="cookie-banner" hidden role="dialog" aria-modal="true" aria-labelledby="cookie-title">';
    echo '<p id="cookie-title">' . esc_html($ui['cookie_title']) . '</p>';
    echo '<p>' . esc_html($ui['cookie_lead']) . ' ';
    echo '<a href="' . esc_url($cookies_href) . '">' . esc_html($ui['cookies']) . '</a>';
    echo ' · ';
    echo '<a href="' . esc_url($personal_href) . '">' . esc_html($ui['personal']) . '</a>.</p>';
    echo '<div class="cookie-actions">';
    echo '<button type="button" data-cookie="accept">' . esc_html($ui['cookie_accept']) . '</button>';
    echo '<button type="button" data-cookie="reject">' . esc_html($ui['cookie_reject']) . '</button>';
    echo '<button type="button" data-cookie="settings">' . esc_html($ui['cookie_settings']) . '</button>';
    echo '</div>';
    echo '<form class="cookie-settings" id="cookie-settings" hidden>';
    echo '<label><input type="checkbox" name="necessary" checked disabled> ' . esc_html($ui['cookie_necessary']) . '</label>';
    echo '<label><input type="checkbox" name="analytics"> ' . esc_html($ui['cookie_analytics']) . '</label>';
    echo '<button type="submit">' . esc_html($ui['cookie_save']) . '</button>';
    echo '</form></div>';
}

add_action('wp_footer', 'ichnm_render_cookie_banner', 15);

/**
 * Language shells use slugs like news-en; map them onto dedicated hub templates.
 */
add_filter('template_include', static function (string $template): string {
    if (!is_singular('page')) {
        return $template;
    }
    $page = get_queried_object();
    if (!$page instanceof WP_Post) {
        return $template;
    }
    $base = ichnm_page_hub_base($page);
    $map = [
        'news' => 'page-news.php',
        'events' => 'page-events.php',
        'publications' => 'page-publications.php',
        'search' => 'page-search.php',
        'sitemap' => 'page-sitemap.php',
        'hr' => 'page-unit.php',
        'labor-protection' => 'page-unit.php',
        'engineering' => 'page-unit.php',
        'accounting' => 'page-unit.php',
        'union' => 'page-unit.php',
        'young-scientists' => 'page-unit.php',
    ];
    if (!isset($map[$base])) {
        return $template;
    }
    $candidate = get_stylesheet_directory() . '/' . $map[$base];
    return is_readable($candidate) ? $candidate : $template;
}, 40);

/**
 * Point the institute chrome menu at Polylang translations when browsing EN/BE/ZH.
 *
 * @param list<\WP_Post> $items
 * @return list<\WP_Post>
 */
add_filter('wp_nav_menu_objects', static function (array $items, $args): array {
    $location = is_object($args) ? (string) ($args->theme_location ?? '') : '';
    if ($location !== 'ichnm-primary' || !function_exists('pll_current_language') || !function_exists('pll_get_post')) {
        return $items;
    }
    $lang = (string) pll_current_language('slug');
    if ($lang === '' || $lang === 'ru') {
        return $items;
    }
    foreach ($items as $item) {
        $object = (string) ($item->object ?? '');
        $type = (string) ($item->type ?? '');
        $object_id = (int) ($item->object_id ?? 0);
        if ($object_id <= 0) {
            continue;
        }
        if (!in_array($type, ['post_type', 'post_type_archive'], true) && $object === 'custom') {
            continue;
        }
        if (!in_array($object, ['page', 'department', 'person', 'news', 'event', 'media_about', 'publication'], true)
            && $type !== 'post_type') {
            continue;
        }
        $translated = (int) pll_get_post($object_id, $lang);
        if ($translated <= 0 || $translated === $object_id) {
            continue;
        }
        $post = get_post($translated);
        if (!$post instanceof WP_Post || $post->post_status !== 'publish') {
            continue;
        }
        $item->object_id = $translated;
        $item->url = (string) get_permalink($translated);
        $item->title = get_the_title($translated);
    }
    return $items;
}, 20, 2);
