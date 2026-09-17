# WordPress: готовность к приёмке и cutover

Status: done

Tracker is local markdown (`docs/work/specs/`, `docs/work/tickets/`). The repo has not run `setup-engineering-workflow`; tickets live under `docs/work/tickets/`.

Supersedes the *open gaps* of `docs/work/specs/2026-08-30-ichnm-public-site-v1.md` (that slice’s scaffold is largely done). Product locks remain in `docs/DECISIONS.md` / glossary in `docs/CONTEXT.md`.

## Problem

Локальный WordPress-контур ИХНМ уже поднимает IA, честный контент из модели, пакеты лабораторий и административных подразделений, Polylang-оболочки структуры и chrome. До официальной замены на `ichnm.by` не хватает: устойчивой приёмки на test URL, доводки витрин/каталогов до контура preview, языкового меню без сюрпризов, редакционной брони шапки/подвала, и операционного cutover (PHP-тариф, DNS). Старая спека v1 устарела (объявления сняты; структура уже на четырёх языках; персональные страницы и lab packs есть).

## Solution

Закрыть срез **cutover readiness** на уже выбранном стеке (WordPress + Kadence child + плагин контента + Polylang): довести витрины и каталоги к honest preview, укрепить i18n-структуру и роль редактора лент, зафиксировать smoke-приёмку и handoff на PHP-хостинг. Домен не переключать без sign-off. Пакет коллег (PDF, полные составы, стартовые публикации, соцсети ИХНМ, вектор НАН) подключается по мере поступления — не блокирует test URL.

## User stories

1. As руководство НАН / приёмщик, I want открыть test URL и пройти главные разделы IA без сломанных пакетов лабораторий и подразделений, so that можно дать sign-off на переключение домена.
2. As научный партнёр, I want на страницах направлений, разработок и материальной базы видеть атрибуцию лаборатории и контакты, so that понятно, к кому обращаться.
3. As посетитель EN/BE/ZH, I want меню и оболочки структуры вести на эквивалентные страницы языка, so that переключалка и IA не сбрасывают в чужой язык.
4. As сотрудник-редактор лент, I want публиковать новости, мероприятия, «СМИ о нас» и публикации и не иметь прав ломать шапку, подвал и витринные страницы, so that официальная оболочка цела.
5. As разработчик от института, I want чеклист smoke + бэкап + handoff на PHP-тариф, so that cutover не зависит от памяти чата.
6. As посетитель персональных страниц, I want видеть аффилиации и (если есть данные) профили научных метрик в locked order, so that карточка человека соответствует решениям v1.

## Implementation decisions

- Стек без смены: WordPress + дочерняя тема Kadence + плагин синхронизации контента + Polylang. Не Java Forever, не второй theme stack.
- Источник правды по IA и честному тексту: модель сайта и migrated copy (`src/core`). Синк в WP идемпотентен по seed-version.
- Пакет лаборатории и страница административного подразделения уже следуют контуру honest preview; доводка — каталоги института (наука / разработки / приборы), персональные метрики, хабы лент на языковых оболочках.
- Языки: **структура** RU/EN/BE/ZH. Тела институтских текстов остаются русскими до официального перевода. Без Polylang Pro допустимы slug-суффиксы (`about-en`); share-slug `/en/about/` — только если появится Pro или отдельное решение.
- Редактор лент: одна роль на новости, мероприятия, «СМИ о нас», публикации. Витринные страницы и chrome — не в зоне этой роли.
- Cutover: test URL → sign-off → DNS. Старый сайт живёт до переключения. PHP-тариф Active.by / Hoster.by.
- Пиксельный клон IBOCH и полная перекладка на блоки Kadence Customizer — **отдельный** крупный срез после или параллельно приёмки контента; не блокирует test URL, если chrome института стабилен.

## Testing decisions (seams)

1. **Модель сайта** (`src/core`: site model + migrated copy) — pytest-инварианты IA: корни меню, наличие lab packs / admin units / people ids, запрет отдельной ленты объявлений, четыре языковых префикса структуры.
2. **Сборка HTML пакетов подразделений** — чистые функции «lab / admin unit → набор секций» (якоря about…contacts; unit-back + people-list + телефон). Тесты на обязательные секции и пустые слоты без Docker/WordPress.
3. **Не шов этого среза:** визуальный паритет Kadence↔IBOCH, DNS/хостинг, клики в wp-admin, Polylang Pro share-slug.

Smoke на Docker (ручной / wp-cli): `/`, `/labs/{slug}/`, `/hr/`, `/people/{id}/`, `/en/…-en/`, поиск, sitemap, форма обратной связи.

## Out of scope

- Официальные переводы тел новостей/биографий (post-v1)
- Регистрация AIST и отдельный мини-сайт конференции
- Агрегация новостей НАН
- Bitrix / смена CMS
- Обязательное наличие всех PDF и полных составов до test URL (плейсхолдеры допустимы; cutover day требует пакет коллег по DECISIONS)

## Notes

- Glossary: витринная страница, лента, пакет лаборатории, персональная страница, идентификационный блок НАН, официальная замена.
- Uncommitted local work may already include lab-pack + admin unit contour; commit when asked.
- After tickets: work the frontier (unblocked tickets) with `implement`.
