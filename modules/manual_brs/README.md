# Временный модуль: ручное выставление БРС

Модуль на один учебный год / семестр. Не меняет логику электронного журнала и текущих ведомостей.

## Как удалить

1. Удалить папку `modules/manual_brs/`.
2. Удалить тонкие входы:
   - `teacher/manual_brs.php`
   - `curator/manual_brs_gradebook.php`
   - `curator/manual_brs_gradebook_pdf.php`
   - `deputy/manual_brs_gradebook.php`
   - `deputy/manual_brs_gradebook_pdf.php`
   - `admin/manual_brs_gradebook.php`
   - `admin/manual_brs_gradebook_pdf.php`
3. В навигации убрать блоки `manual_brs` / `file_exists(...manual_brs...)`:
   - `includes/teacher_nav.php`
   - `includes/curator_nav.php`
   - `includes/deputy_nav.php`
   - `includes/admin_nav.php`
4. В `includes/migrations.php` убрать вызов `manual_brs_ensure_schema`.
5. (Опционально) удалить таблицы:
   - `manual_brs_sheets`
   - `manual_brs_entries`
   - `manual_brs_attestations`

## Периоды (четверти)

| Период | Семестр | Название |
|--------|---------|----------|
| 1 | 1 | 1 период |
| 2 | 1 | 2 период |
| 3 | 2 | 3 период |
| 4 | 2 | 4 период |

Итог семестра = среднее баллов двух периодов → оценка по шкале БРС.

**ПА (промежуточная аттестация):**
- пусто → итог только по баллам периодов;
- `2` → итог семестра = 2;
- `3`/`4`/`5` → среднее арифметическое оценки БРС и ПА (как в журнале).

## Отдельные ведомости

Доступ: куратор, завуч, админ.  
В ячейках только итоговая оценка семестра из ручного БРС.  
Обычная электронная ведомость и журнал не затрагиваются.
