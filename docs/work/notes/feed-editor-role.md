# Роль «Редактор лент ИХНМ» (`ichnm_feed_editor`)

Кратко для администратора сайта после cutover.

## Назначение

Одна штатная роль публикует четыре ленты (как в `docs/DECISIONS.md`):

- новости (`news`)
- мероприятия (`event`)
- «СМИ о нас» (`media_about`)
- публикации (`publication`)

Витринные страницы, шапка/подвал (меню Appearance) и структура (подразделения / персоналии) этой роли недоступны.

## Как выдать

1. Пользователи → Добавить / Изменить.
2. Роль: **Редактор лент ИХНМ**.
3. Не комбинировать с Administrator / Editor — иначе броня снимается через `manage_options` / чужие caps.

Сиды плагина (`ichnm_register_feed_editor_role` на activation и `init`) создают роль и **синхронизируют** caps при каждом заходе; вручную в БД caps править не нужно.

## Что запрещено

- страницы (home, about, structure, contacts, leadership, science, facilities, developments, documents, aist, хабы news/events/publications, языковые оболочки `*-en`/`*-be`/`*-zh`, …);
- CPT `department` / `person` и обычные записи;
- Appearance → Themes / Customize / Menus / Widgets (`edit_theme_options`, `customize`).

Прямой URL `post.php?post=…` на витрину даёт 403. Известный список slug’ов — `ichnm_locked_page_slugs()` в `wp-content/plugins/ichnm-site/includes/roles.php` (фактически роль не редактирует ни одну `page`).

## Если нужно поправить витрину или меню

Только пользователь с **Administrator** (или разработчик через сид / `content-sync`). После правок меню — не отдавать feed-editor’у роль Editor WordPress.
