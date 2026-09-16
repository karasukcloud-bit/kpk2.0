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

## Периоды

| Период в БД | Семестр |
|-------------|---------|
| 1 | 1-й семестр |
| 2 | 2-й семестр |

Итог семестра = баллы БРС за этот семестр → оценка по шкале + учёт ПА.

**ПА (промежуточная аттестация):**
- пусто → итог только по баллам БРС;
- `2` → итог семестра = 2;
- `3`/`4`/`5` → среднее арифметическое оценки БРС и ПА (как в журнале).

При первом заходе после обновления старые «четверти» (1–4) автоматически сводятся к семестрам 1 и 2.

## Отдельные ведомости

Доступ: куратор, завуч, админ.  
В ячейках только итоговая оценка семестра из ручного БРС.  
Обычная электронная ведомость и журнал не затрагиваются.
