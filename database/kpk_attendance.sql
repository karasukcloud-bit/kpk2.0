-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Авг 26 2026 г., 17:10
-- Версия сервера: 8.0.30
-- Версия PHP: 8.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `kpk_attendance`
--

-- --------------------------------------------------------

--
-- Структура таблицы `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `action` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `entity_id` int UNSIGNED DEFAULT NULL,
  `group_id` int UNSIGNED DEFAULT NULL,
  `details_json` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `group_id`, `details_json`, `ip_address`, `created_at`) VALUES
(1, 3, 'student.create', 'student', 8, 2, '{\"student_name\":\"Грицук Владимио Владимирович\",\"group_number\":\"201\"}', '127.0.0.1', '2026-08-24 05:37:40'),
(2, 3, 'journal.grade_save', 'journal_grade', 19, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":19,\"lesson_date\":\"23.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Грамматика английского языка\",\"student_name\":\"Грицук Владимио\",\"old_mark\":\"—\",\"mark\":\"5\"}', '127.0.0.1', '2026-08-24 05:38:07'),
(3, 3, 'journal.grade_save', 'journal_grade', 25, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":25,\"lesson_date\":\"23.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Чтение по ролям\",\"student_name\":\"Грицук Владимио\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-24 05:38:09'),
(4, 3, 'journal.grade_save', 'journal_grade', 20, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":20,\"lesson_date\":\"23.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"Грамматика английского языка\",\"student_name\":\"Грицук Владимио\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-24 05:38:13'),
(5, 1, 'student.create', 'student', 9, 2, '{\"student_name\":\"Астахов Максим Викторович\",\"group_number\":\"201\"}', '127.0.0.1', '2026-08-24 11:55:59'),
(6, 1, 'student.create', 'student', 10, 5, '{\"student_name\":\"Сидоров Константин Иванович\",\"group_number\":\"202\"}', '127.0.0.1', '2026-08-24 15:30:38'),
(7, 3, 'journal.grade_save', 'journal_grade', 20, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":20,\"lesson_date\":\"23.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"Грамматика английского языка\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-25 06:35:10'),
(8, 3, 'journal.grade_save', 'journal_grade', 36, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":36,\"lesson_date\":\"23.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-25 06:35:22'),
(9, 3, 'journal.grade_save', 'journal_grade', 26, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":26,\"lesson_date\":\"23.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"Чтение по ролям\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-25 06:35:30'),
(10, 3, 'journal.grade_save', 'journal_grade', 18, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":18,\"lesson_date\":\"22.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"История англии\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"—\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-25 06:35:42'),
(11, 3, 'journal.grade_save', 'journal_grade', 18, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":18,\"lesson_date\":\"22.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"История англии\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"—\"}', '127.0.0.1', '2026-08-25 06:35:43'),
(12, 3, 'journal.grade_save', 'journal_grade', 18, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":18,\"lesson_date\":\"22.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"История англии\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-25 06:36:00'),
(13, 3, 'journal.grade_save', 'journal_grade', 18, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":18,\"lesson_date\":\"22.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"История англии\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"4\",\"mark\":\"2\"}', '127.0.0.1', '2026-08-25 06:36:11'),
(14, 1, 'student.create', 'student', 11, 2, '{\"student_name\":\"Никитенко Кирил Никитович\",\"group_number\":\"201\"}', '127.0.0.1', '2026-08-26 16:23:02'),
(15, 1, 'student.create', 'student', 12, 8, '{\"student_name\":\"Утеуов Бауржан Ермекович\",\"group_number\":\"221\"}', '127.0.0.1', '2026-08-26 16:27:36'),
(16, 1, 'student.create', 'student', 13, 8, '{\"student_name\":\"Исакова Марина Петровна\",\"group_number\":\"221\"}', '127.0.0.1', '2026-08-26 16:29:18'),
(17, 1, 'student.create', 'student', 14, 8, '{\"student_name\":\"Шальнов Григорий Игоревич\",\"group_number\":\"221\"}', '127.0.0.1', '2026-08-26 16:29:49'),
(18, 1, 'student.create', 'student', 15, 8, '{\"student_name\":\"Малахов Валентин Дмитриевич\",\"group_number\":\"221\"}', '127.0.0.1', '2026-08-26 16:30:29'),
(19, 1, 'journal.lesson_add', 'journal_lesson', 37, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":37,\"lesson_date\":\"26.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:33:00'),
(20, 1, 'journal.grade_save', 'journal_grade', 37, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":37,\"lesson_date\":\"26.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Исакова Марина\",\"old_mark\":\"—\",\"mark\":\"5\"}', '127.0.0.1', '2026-08-26 16:33:06'),
(21, 1, 'journal.grade_save', 'journal_grade', 37, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":37,\"lesson_date\":\"26.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Малахов Валентин\",\"old_mark\":\"—\",\"mark\":\"3\"}', '127.0.0.1', '2026-08-26 16:33:10'),
(22, 1, 'journal.grade_save', 'journal_grade', 37, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":37,\"lesson_date\":\"26.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Малахов Валентин\",\"old_mark\":\"3\",\"mark\":\"3\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-26 16:33:11'),
(23, 1, 'journal.lesson_add', 'journal_lesson', 38, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":38,\"lesson_date\":\"10.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:33:20'),
(24, 1, 'journal.grade_save', 'journal_grade', 38, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":38,\"lesson_date\":\"10.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Утеуов Бауржан\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:33:26'),
(25, 1, 'journal.grade_save', 'journal_grade', 38, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":38,\"lesson_date\":\"10.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Шальнов Григорий\",\"old_mark\":\"—\",\"mark\":\"Н\"}', '127.0.0.1', '2026-08-26 16:33:30'),
(26, 1, 'journal.lesson_add', 'journal_lesson', 39, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":39,\"lesson_date\":\"17.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:33:41'),
(27, 1, 'journal.grade_save', 'journal_grade', 39, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":39,\"lesson_date\":\"17.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Исакова Марина\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:33:47'),
(28, 1, 'journal.grade_save', 'journal_grade', 39, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":39,\"lesson_date\":\"17.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Малахов Валентин\",\"old_mark\":\"—\",\"mark\":\"3\"}', '127.0.0.1', '2026-08-26 16:33:49'),
(29, 1, 'journal.grade_save', 'journal_grade', 39, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":39,\"lesson_date\":\"17.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Утеуов Бауржан\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:33:51'),
(30, 1, 'journal.grade_save', 'journal_grade', 39, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":39,\"lesson_date\":\"17.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Шальнов Григорий\",\"old_mark\":\"—\",\"mark\":\"3\"}', '127.0.0.1', '2026-08-26 16:33:53'),
(31, 1, 'journal.grade_save', 'journal_grade', 37, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":37,\"lesson_date\":\"26.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Исакова Марина\",\"old_mark\":\"5\",\"mark\":\"5\",\"extra\":\"активность\"}', '127.0.0.1', '2026-08-26 16:33:58'),
(32, 1, 'journal.lesson_add', 'journal_lesson', 40, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":40,\"lesson_date\":\"24.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:34:06'),
(33, 1, 'journal.grade_save', 'journal_grade', 40, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":40,\"lesson_date\":\"24.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Исакова Марина\",\"old_mark\":\"—\",\"mark\":\"5\"}', '127.0.0.1', '2026-08-26 16:34:13'),
(34, 1, 'journal.grade_save', 'journal_grade', 40, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":40,\"lesson_date\":\"24.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Малахов Валентин\",\"old_mark\":\"—\",\"mark\":\"2\"}', '127.0.0.1', '2026-08-26 16:34:17'),
(35, 1, 'journal.grade_save', 'journal_grade', 40, 8, '{\"curriculum_item_id\":13,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":40,\"lesson_date\":\"24.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Утеуов Бауржан\",\"old_mark\":\"—\",\"mark\":\"—\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-26 16:34:19'),
(36, 1, 'journal.lesson_add', 'journal_lesson', 41, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":41,\"lesson_date\":\"07.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:34:38'),
(37, 1, 'journal.lesson_add', 'journal_lesson', 42, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":42,\"lesson_date\":\"14.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:34:49'),
(38, 1, 'journal.lesson_add', 'journal_lesson', 43, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":43,\"lesson_date\":\"21.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:34:58'),
(39, 1, 'journal.grade_save', 'journal_grade', 41, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":41,\"lesson_date\":\"07.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Исакова Марина\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:35:06'),
(40, 1, 'journal.grade_save', 'journal_grade', 42, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":42,\"lesson_date\":\"14.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Исакова Марина\",\"old_mark\":\"—\",\"mark\":\"3\"}', '127.0.0.1', '2026-08-26 16:35:08'),
(41, 1, 'journal.grade_save', 'journal_grade', 43, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":43,\"lesson_date\":\"21.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Исакова Марина\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:35:09'),
(42, 1, 'journal.grade_save', 'journal_grade', 41, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":41,\"lesson_date\":\"07.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Малахов Валентин\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:35:13'),
(43, 1, 'journal.grade_save', 'journal_grade', 42, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":42,\"lesson_date\":\"14.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Малахов Валентин\",\"old_mark\":\"—\",\"mark\":\"—\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-26 16:35:14'),
(44, 1, 'journal.grade_save', 'journal_grade', 41, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":41,\"lesson_date\":\"07.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Утеуов Бауржан\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:35:16'),
(45, 1, 'journal.grade_save', 'journal_grade', 41, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":41,\"lesson_date\":\"07.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Утеуов Бауржан\",\"old_mark\":\"4\",\"mark\":\"4\",\"extra\":\"активность\"}', '127.0.0.1', '2026-08-26 16:35:17'),
(46, 1, 'journal.grade_save', 'journal_grade', 43, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":43,\"lesson_date\":\"21.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Утеуов Бауржан\",\"old_mark\":\"—\",\"mark\":\"3\"}', '127.0.0.1', '2026-08-26 16:35:19'),
(47, 1, 'journal.grade_save', 'journal_grade', 43, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":43,\"lesson_date\":\"21.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Малахов Валентин\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:35:21'),
(48, 1, 'journal.grade_save', 'journal_grade', 43, 8, '{\"curriculum_item_id\":12,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":43,\"lesson_date\":\"21.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Шальнов Григорий\",\"old_mark\":\"—\",\"mark\":\"3\"}', '127.0.0.1', '2026-08-26 16:35:23'),
(49, 1, 'journal.lesson_add', 'journal_lesson', 44, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":44,\"lesson_date\":\"06.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:35:55'),
(50, 1, 'journal.lesson_add', 'journal_lesson', 45, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":45,\"lesson_date\":\"13.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:36:07'),
(51, 1, 'journal.lesson_add', 'journal_lesson', 46, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":46,\"lesson_date\":\"19.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:36:16'),
(52, 1, 'journal.grade_save', 'journal_grade', 46, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":46,\"lesson_date\":\"19.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Малахов Валентин\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:36:22'),
(53, 1, 'journal.grade_save', 'journal_grade', 44, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":44,\"lesson_date\":\"06.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Исакова Марина\",\"old_mark\":\"—\",\"mark\":\"5\"}', '127.0.0.1', '2026-08-26 16:36:24'),
(54, 1, 'journal.grade_save', 'journal_grade', 46, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":46,\"lesson_date\":\"19.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Исакова Марина\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:36:28'),
(55, 1, 'journal.grade_save', 'journal_grade', 46, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":46,\"lesson_date\":\"19.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Утеуов Бауржан\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:36:31'),
(56, 1, 'journal.grade_save', 'journal_grade', 46, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":46,\"lesson_date\":\"19.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Шальнов Григорий\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:36:33'),
(57, 1, 'journal.grade_save', 'journal_grade', 44, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":44,\"lesson_date\":\"06.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Утеуов Бауржан\",\"old_mark\":\"—\",\"mark\":\"—\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-26 16:36:35'),
(58, 1, 'journal.grade_save', 'journal_grade', 45, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":45,\"lesson_date\":\"13.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Малахов Валентин\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:36:38'),
(59, 1, 'journal.grade_save', 'journal_grade', 45, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":45,\"lesson_date\":\"13.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Малахов Валентин\",\"old_mark\":\"4\",\"mark\":\"4\",\"extra\":\"активность\"}', '127.0.0.1', '2026-08-26 16:36:38'),
(60, 1, 'journal.grade_save', 'journal_grade', 44, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":44,\"lesson_date\":\"06.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Шальнов Григорий\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:36:44'),
(61, 1, 'journal.grade_save', 'journal_grade', 44, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":44,\"lesson_date\":\"06.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Утеуов Бауржан\",\"old_mark\":\"—\",\"mark\":\"2\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-26 16:51:15'),
(62, 1, 'journal.grade_save', 'journal_grade', 45, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":45,\"lesson_date\":\"13.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Утеуов Бауржан\",\"old_mark\":\"—\",\"mark\":\"2\"}', '127.0.0.1', '2026-08-26 16:51:17'),
(63, 1, 'journal.grade_save', 'journal_grade', 45, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":45,\"lesson_date\":\"13.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Утеуов Бауржан\",\"old_mark\":\"2\",\"mark\":\"—\"}', '127.0.0.1', '2026-08-26 16:51:19'),
(64, 1, 'journal.grade_save', 'journal_grade', 46, 8, '{\"curriculum_item_id\":14,\"group_id\":8,\"group_number\":\"221\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":46,\"lesson_date\":\"19.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Утеуов Бауржан\",\"old_mark\":\"4\",\"mark\":\"3\"}', '127.0.0.1', '2026-08-26 16:51:21'),
(65, 1, 'student.create', 'student', 16, 15, '{\"student_name\":\"Ветрова Светлана Кириловна\",\"group_number\":\"441\"}', '127.0.0.1', '2026-08-26 16:52:42'),
(66, 1, 'student.create', 'student', 17, 15, '{\"student_name\":\"Иванова Анастасия Михайловна\",\"group_number\":\"441\"}', '127.0.0.1', '2026-08-26 16:53:22'),
(67, 1, 'student.create', 'student', 18, 15, '{\"student_name\":\"Михайлова Татьяна Игоревна\",\"group_number\":\"441\"}', '127.0.0.1', '2026-08-26 16:55:20'),
(68, 1, 'journal.lesson_add', 'journal_lesson', 47, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":47,\"lesson_date\":\"05.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:56:56'),
(69, 1, 'journal.lesson_add', 'journal_lesson', 48, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":48,\"lesson_date\":\"05.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:57:03'),
(70, 1, 'journal.lesson_add', 'journal_lesson', 49, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":49,\"lesson_date\":\"12.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:57:09'),
(71, 1, 'journal.lesson_add', 'journal_lesson', 50, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":50,\"lesson_date\":\"12.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:57:17'),
(72, 1, 'journal.grade_save', 'journal_grade', 47, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":47,\"lesson_date\":\"05.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Ветрова Светлана\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:57:21'),
(73, 1, 'journal.grade_save', 'journal_grade', 48, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":48,\"lesson_date\":\"05.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Ветрова Светлана\",\"old_mark\":\"—\",\"mark\":\"—\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-26 16:57:23'),
(74, 1, 'journal.grade_save', 'journal_grade', 48, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":48,\"lesson_date\":\"05.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Ветрова Светлана\",\"old_mark\":\"—\",\"mark\":\"—\"}', '127.0.0.1', '2026-08-26 16:57:24'),
(75, 1, 'journal.grade_save', 'journal_grade', 48, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":48,\"lesson_date\":\"05.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Ветрова Светлана\",\"old_mark\":\"—\",\"mark\":\"—\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-26 16:57:26'),
(76, 1, 'journal.grade_save', 'journal_grade', 50, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":50,\"lesson_date\":\"12.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Ветрова Светлана\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:57:28'),
(77, 1, 'journal.grade_save', 'journal_grade', 50, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":50,\"lesson_date\":\"12.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"—\",\"mark\":\"3\"}', '127.0.0.1', '2026-08-26 16:57:31'),
(78, 1, 'journal.grade_save', 'journal_grade', 50, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":50,\"lesson_date\":\"12.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Михайлова Татьяна\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:57:33'),
(79, 1, 'journal.grade_save', 'journal_grade', 48, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":48,\"lesson_date\":\"05.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"—\",\"mark\":\"5\"}', '127.0.0.1', '2026-08-26 16:57:36'),
(80, 1, 'journal.grade_save', 'journal_grade', 47, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":47,\"lesson_date\":\"05.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Михайлова Татьяна\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:57:39'),
(81, 1, 'journal.grade_save', 'journal_grade', 47, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":47,\"lesson_date\":\"05.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:57:44'),
(82, 1, 'journal.grade_save', 'journal_grade', 47, 15, '{\"curriculum_item_id\":16,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":47,\"lesson_date\":\"05.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"4\",\"mark\":\"4\",\"extra\":\"активность\"}', '127.0.0.1', '2026-08-26 16:57:46'),
(83, 1, 'journal.lesson_add', 'journal_lesson', 51, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":51,\"lesson_date\":\"04.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:57:59'),
(84, 1, 'journal.lesson_add', 'journal_lesson', 52, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":52,\"lesson_date\":\"04.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:58:05'),
(85, 1, 'journal.lesson_add', 'journal_lesson', 53, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":53,\"lesson_date\":\"11.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:58:10'),
(86, 1, 'journal.lesson_add', 'journal_lesson', 54, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":54,\"lesson_date\":\"11.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:58:25'),
(87, 1, 'journal.lesson_add', 'journal_lesson', 55, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":55,\"lesson_date\":\"18.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:58:34'),
(88, 1, 'journal.grade_save', 'journal_grade', 52, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":52,\"lesson_date\":\"04.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Ветрова Светлана\",\"old_mark\":\"—\",\"mark\":\"5\"}', '127.0.0.1', '2026-08-26 16:58:41'),
(89, 1, 'journal.grade_save', 'journal_grade', 53, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":53,\"lesson_date\":\"11.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Ветрова Светлана\",\"old_mark\":\"—\",\"mark\":\"5\"}', '127.0.0.1', '2026-08-26 16:58:42'),
(90, 1, 'journal.grade_save', 'journal_grade', 55, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":55,\"lesson_date\":\"18.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Ветрова Светлана\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:58:44'),
(91, 1, 'journal.grade_save', 'journal_grade', 52, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":52,\"lesson_date\":\"04.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:58:47'),
(92, 1, 'journal.grade_save', 'journal_grade', 51, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":51,\"lesson_date\":\"04.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"—\",\"mark\":\"5\"}', '127.0.0.1', '2026-08-26 16:58:49'),
(93, 1, 'journal.grade_save', 'journal_grade', 53, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":53,\"lesson_date\":\"11.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:58:51'),
(94, 1, 'journal.grade_save', 'journal_grade', 54, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":54,\"lesson_date\":\"11.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:58:53'),
(95, 1, 'journal.grade_save', 'journal_grade', 55, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":55,\"lesson_date\":\"18.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:58:55'),
(96, 1, 'journal.grade_save', 'journal_grade', 55, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":55,\"lesson_date\":\"18.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Михайлова Татьяна\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:58:57'),
(97, 1, 'journal.grade_save', 'journal_grade', 51, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":51,\"lesson_date\":\"04.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Михайлова Татьяна\",\"old_mark\":\"—\",\"mark\":\"—\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-26 16:58:59'),
(98, 1, 'journal.grade_save', 'journal_grade', 52, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":52,\"lesson_date\":\"04.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Михайлова Татьяна\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:59:02'),
(99, 1, 'journal.grade_save', 'journal_grade', 53, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":53,\"lesson_date\":\"11.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Михайлова Татьяна\",\"old_mark\":\"—\",\"mark\":\"—\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-26 16:59:03'),
(100, 1, 'journal.grade_save', 'journal_grade', 54, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":54,\"lesson_date\":\"11.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Михайлова Татьяна\",\"old_mark\":\"—\",\"mark\":\"—\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-26 16:59:07'),
(101, 1, 'journal.grade_save', 'journal_grade', 54, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":54,\"lesson_date\":\"11.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Ветрова Светлана\",\"old_mark\":\"—\",\"mark\":\"—\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-26 16:59:11'),
(102, 1, 'journal.grade_save', 'journal_grade', 54, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":54,\"lesson_date\":\"11.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Ветрова Светлана\",\"old_mark\":\"—\",\"mark\":\"—\"}', '127.0.0.1', '2026-08-26 16:59:12'),
(103, 1, 'journal.grade_save', 'journal_grade', 54, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":54,\"lesson_date\":\"11.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"4\",\"mark\":\"4\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-26 16:59:13'),
(104, 1, 'journal.grade_save', 'journal_grade', 54, 15, '{\"curriculum_item_id\":15,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Физическая культура\",\"academic_year\":\"2025-2026\",\"lesson_id\":54,\"lesson_date\":\"11.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"4\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 16:59:14'),
(105, 1, 'journal.lesson_add', 'journal_lesson', 56, 15, '{\"curriculum_item_id\":17,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":56,\"lesson_date\":\"07.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:59:35'),
(106, 1, 'journal.lesson_add', 'journal_lesson', 57, 15, '{\"curriculum_item_id\":17,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":57,\"lesson_date\":\"07.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:59:44'),
(107, 1, 'journal.lesson_add', 'journal_lesson', 58, 15, '{\"curriculum_item_id\":17,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":58,\"lesson_date\":\"14.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:59:50'),
(108, 1, 'journal.lesson_add', 'journal_lesson', 59, 15, '{\"curriculum_item_id\":17,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":59,\"lesson_date\":\"14.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\"}', '127.0.0.1', '2026-08-26 16:59:56'),
(109, 1, 'journal.grade_save', 'journal_grade', 56, 15, '{\"curriculum_item_id\":17,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":56,\"lesson_date\":\"07.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Ветрова Светлана\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 17:00:03'),
(110, 1, 'journal.grade_save', 'journal_grade', 59, 15, '{\"curriculum_item_id\":17,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":59,\"lesson_date\":\"14.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Ветрова Светлана\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-26 17:00:04'),
(111, 1, 'journal.grade_save', 'journal_grade', 56, 15, '{\"curriculum_item_id\":17,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":56,\"lesson_date\":\"07.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Михайлова Татьяна\",\"old_mark\":\"—\",\"mark\":\"3\"}', '127.0.0.1', '2026-08-26 17:00:09'),
(112, 1, 'journal.grade_save', 'journal_grade', 59, 15, '{\"curriculum_item_id\":17,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":59,\"lesson_date\":\"14.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Михайлова Татьяна\",\"old_mark\":\"—\",\"mark\":\"3\"}', '127.0.0.1', '2026-08-26 17:00:12'),
(113, 1, 'journal.grade_save', 'journal_grade', 58, 15, '{\"curriculum_item_id\":17,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":58,\"lesson_date\":\"14.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Михайлова Татьяна\",\"old_mark\":\"—\",\"mark\":\"2\"}', '127.0.0.1', '2026-08-26 17:00:15'),
(114, 1, 'journal.grade_save', 'journal_grade', 57, 15, '{\"curriculum_item_id\":17,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":57,\"lesson_date\":\"07.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"—\",\"mark\":\"5\"}', '127.0.0.1', '2026-08-26 17:00:18'),
(115, 1, 'journal.grade_save', 'journal_grade', 59, 15, '{\"curriculum_item_id\":17,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":59,\"lesson_date\":\"14.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"—\",\"mark\":\"3\"}', '127.0.0.1', '2026-08-26 17:00:21');

-- --------------------------------------------------------

--
-- Структура таблицы `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `app_settings`
--

INSERT INTO `app_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('academic_year_extra_years', '10', '2026-08-24 15:22:08'),
('active_academic_year', '2025-2026', '2026-08-20 09:13:26'),
('active_semester', '1', '2026-08-20 09:13:26'),
('grading_config', '{\"system\":\"brs\",\"brs\":{\"weight_current\":30,\"weight_control\":45,\"weight_attendance\":15,\"weight_punctuality\":5,\"weight_activity\":5,\"scale_3\":64,\"scale_4\":70,\"scale_5\":84}}', '2026-08-21 08:11:39');

-- --------------------------------------------------------

--
-- Структура таблицы `archive_gradebook_grades`
--

CREATE TABLE `archive_gradebook_grades` (
  `id` int UNSIGNED NOT NULL,
  `archive_id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `grade` tinyint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_gradebook_grades`
--

INSERT INTO `archive_gradebook_grades` (`id`, `archive_id`, `group_id`, `student_id`, `curriculum_item_id`, `grade`) VALUES
(25, 9, 2, 3, 3, 5),
(26, 9, 2, 3, 4, 4),
(27, 9, 2, 3, 9, 5),
(28, 9, 2, 3, 10, 4),
(29, 9, 2, 3, 11, 5),
(30, 9, 2, 2, 3, 2),
(31, 9, 2, 2, 4, 2),
(32, 9, 2, 2, 9, 4),
(33, 9, 2, 2, 10, 2),
(34, 9, 2, 2, 11, 2),
(35, 9, 2, 7, 3, 4),
(36, 9, 2, 7, 4, 3),
(37, 9, 2, 7, 9, 4),
(38, 9, 2, 7, 10, 3),
(39, 9, 2, 7, 11, 2);

-- --------------------------------------------------------

--
-- Структура таблицы `archive_gradebook_groups`
--

CREATE TABLE `archive_gradebook_groups` (
  `id` int UNSIGNED NOT NULL,
  `archive_id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `group_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialty_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `specialty_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `curator_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_gradebook_groups`
--

INSERT INTO `archive_gradebook_groups` (`id`, `archive_id`, `group_id`, `group_number`, `specialty_name`, `specialty_code`, `curator_name`) VALUES
(4, 9, 2, '201', 'Преподавание в начальных классах', '44.02.02', 'Полянский Александр Сергеевич'),
(5, 9, 5, '202', 'Преподавание в начальных классах', '44.02.02', ''),
(6, 9, 6, '401', 'Преподавание в начальных классах', '44.02.02', '');

-- --------------------------------------------------------

--
-- Структура таблицы `archive_gradebook_students`
--

CREATE TABLE `archive_gradebook_students` (
  `id` int UNSIGNED NOT NULL,
  `archive_id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_gradebook_students`
--

INSERT INTO `archive_gradebook_students` (`id`, `archive_id`, `group_id`, `student_id`, `full_name`, `sort_order`) VALUES
(5, 9, 2, 3, 'Власенко Татьяна Алексеевна', 1),
(6, 9, 2, 2, 'Иванов Петр Петрович', 2),
(7, 9, 2, 7, 'Смирнов Данил Владимирович', 3);

-- --------------------------------------------------------

--
-- Структура таблицы `archive_gradebook_subjects`
--

CREATE TABLE `archive_gradebook_subjects` (
  `id` int UNSIGNED NOT NULL,
  `archive_id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `subject_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_gradebook_subjects`
--

INSERT INTO `archive_gradebook_subjects` (`id`, `archive_id`, `group_id`, `curriculum_item_id`, `subject_name`, `teacher_name`, `sort_order`) VALUES
(7, 9, 2, 3, 'Иностранный язык', '', 1),
(8, 9, 2, 4, 'Физическая культура', '', 2),
(9, 9, 2, 9, 'История', '', 3),
(10, 9, 2, 10, 'Математика', '', 4),
(11, 9, 2, 11, 'Физкультура', '', 5);

-- --------------------------------------------------------

--
-- Структура таблицы `archive_grade_changes`
--

CREATE TABLE `archive_grade_changes` (
  `id` int UNSIGNED NOT NULL,
  `archive_id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `old_grade` tinyint UNSIGNED DEFAULT NULL,
  `new_grade` tinyint UNSIGNED NOT NULL,
  `reason_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_text` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `changed_by` int UNSIGNED DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `archive_journal_grades`
--

CREATE TABLE `archive_journal_grades` (
  `id` int UNSIGNED NOT NULL,
  `lesson_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `mark` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `activity` tinyint(1) NOT NULL DEFAULT '0',
  `late` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_journal_grades`
--

INSERT INTO `archive_journal_grades` (`id`, `lesson_id`, `student_id`, `mark`, `activity`, `late`) VALUES
(259, 82, 3, '5', 0, 0),
(260, 82, 2, 'Н', 0, 0),
(261, 82, 7, '', 0, 0),
(262, 83, 3, '', 0, 0),
(263, 83, 2, '', 0, 0),
(264, 83, 7, '', 0, 0),
(265, 84, 3, '4', 0, 0),
(266, 84, 2, 'Н', 0, 0),
(267, 84, 7, '4', 0, 1),
(268, 85, 3, '5', 0, 0),
(269, 85, 2, 'Н', 0, 0),
(270, 85, 7, '4', 0, 0),
(271, 86, 3, '5', 0, 0),
(272, 86, 2, '', 0, 1),
(273, 86, 7, '', 0, 0),
(274, 87, 3, '5', 0, 0),
(275, 87, 2, '3', 0, 0),
(276, 87, 7, '4', 1, 0),
(277, 88, 3, '', 0, 0),
(278, 88, 2, '', 0, 0),
(279, 88, 7, '4', 1, 0),
(280, 89, 3, '', 0, 0),
(281, 89, 2, '', 0, 0),
(282, 89, 7, '', 0, 0),
(283, 90, 3, '', 0, 0),
(284, 90, 2, '', 0, 0),
(285, 90, 7, '', 0, 0),
(286, 91, 3, '', 0, 0),
(287, 91, 2, '', 0, 0),
(288, 91, 7, '', 0, 0),
(289, 92, 3, '4', 0, 0),
(290, 92, 2, '4', 0, 0),
(291, 92, 7, '4', 0, 0),
(292, 93, 3, '', 0, 0),
(293, 93, 2, '', 0, 0),
(294, 93, 7, '', 0, 0),
(295, 94, 3, '', 0, 0),
(296, 94, 2, '', 0, 0),
(297, 94, 7, '', 0, 0),
(298, 95, 3, '', 0, 0),
(299, 95, 2, '', 0, 0),
(300, 95, 7, '', 0, 0),
(301, 96, 3, '', 0, 0),
(302, 96, 2, '', 0, 0),
(303, 96, 7, '', 0, 0),
(304, 97, 3, '3', 0, 0),
(305, 97, 2, '3', 0, 0),
(306, 97, 7, '3', 0, 0),
(307, 98, 3, '5', 1, 0),
(308, 98, 2, '3', 0, 1),
(309, 98, 7, '5', 0, 0),
(310, 99, 3, '5', 0, 0),
(311, 99, 2, '5', 0, 0),
(312, 99, 7, '5', 0, 0),
(313, 100, 3, '4', 0, 0),
(314, 100, 2, '3', 0, 0),
(315, 100, 7, '4', 0, 1),
(316, 101, 3, 'Н', 0, 0),
(317, 101, 2, '4', 1, 0),
(318, 101, 7, '3', 0, 0),
(319, 102, 3, '4', 0, 0),
(320, 102, 2, '2', 0, 0),
(321, 102, 7, '3', 0, 0),
(322, 103, 3, '4', 0, 0),
(323, 103, 2, '', 0, 0),
(324, 103, 7, '', 0, 0),
(325, 104, 3, '5', 0, 0),
(326, 104, 2, '', 0, 0),
(327, 104, 7, '', 0, 0),
(328, 105, 3, '', 0, 0),
(329, 105, 2, '', 0, 0),
(330, 105, 7, '', 0, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `archive_journal_items`
--

CREATE TABLE `archive_journal_items` (
  `id` int UNSIGNED NOT NULL,
  `archive_id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `group_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `subject_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `semester` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_journal_items`
--

INSERT INTO `archive_journal_items` (`id`, `archive_id`, `group_id`, `group_number`, `curriculum_item_id`, `subject_name`, `teacher_name`, `semester`) VALUES
(35, 8, 2, '201', 3, 'Иностранный язык', 'Полянский Александр Сергеевич', 'both'),
(36, 8, 2, '201', 4, 'Физическая культура', 'Полянский Александр Сергеевич', 'both'),
(37, 8, 2, '201', 9, 'История', 'Полянский Александр Сергеевич', '1'),
(38, 8, 2, '201', 10, 'Математика', 'Полянский Александр Сергеевич', '1'),
(39, 8, 2, '201', 11, 'Физкультура', 'Полянский Александр Сергеевич', '1');

-- --------------------------------------------------------

--
-- Структура таблицы `archive_journal_lessons`
--

CREATE TABLE `archive_journal_lessons` (
  `id` int UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `source_lesson_id` int UNSIGNED DEFAULT NULL,
  `lesson_date` date NOT NULL,
  `topic_title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `topic_lesson_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `topic_hours` decimal(4,1) DEFAULT NULL,
  `grade_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'current',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_journal_lessons`
--

INSERT INTO `archive_journal_lessons` (`id`, `item_id`, `source_lesson_id`, `lesson_date`, `topic_title`, `topic_lesson_type`, `topic_hours`, `grade_type`, `sort_order`) VALUES
(82, 35, 17, '2026-08-22', 'История англии', 'lecture', '1.0', 'current', 1),
(83, 35, 18, '2026-08-22', 'История англии', 'lecture', '1.0', 'current', 2),
(84, 35, 19, '2026-08-23', 'Грамматика английского языка', 'lecture', '1.0', 'current', 3),
(85, 35, 20, '2026-08-23', 'Грамматика английского языка', 'lecture', '1.0', 'control', 4),
(86, 35, 25, '2026-08-23', 'Чтение по ролям', 'practice', '1.0', 'current', 5),
(87, 35, 26, '2026-08-23', 'Чтение по ролям', 'practice', '1.0', 'control', 6),
(88, 35, 27, '2026-08-23', 'Чтение текста', 'independent', '1.0', 'current', 7),
(89, 35, 28, '2026-08-23', 'Промежуточная аттестация. Дифференцированный зачёт', 'diff_credit', '1.0', 'current', 8),
(90, 35, 29, '2026-08-23', 'Промежуточная аттестация. Дифференцированный зачёт', 'diff_credit', '1.0', 'current', 9),
(91, 35, 30, '2026-08-23', '', '', NULL, 'current', 10),
(92, 35, 31, '2026-08-23', '', '', NULL, 'control', 11),
(93, 35, 32, '2026-08-23', '', '', NULL, 'current', 12),
(94, 35, 33, '2026-08-23', '', '', NULL, 'current', 13),
(95, 35, 34, '2026-08-23', '', '', NULL, 'current', 14),
(96, 35, 35, '2026-08-23', '', '', NULL, 'current', 15),
(97, 35, 36, '2026-08-23', '', '', NULL, 'control', 16),
(98, 36, 16, '2026-08-22', '', '', NULL, 'control', 1),
(99, 37, 12, '2026-08-22', '', '', NULL, 'current', 1),
(100, 37, 13, '2026-08-22', '', '', NULL, 'control', 2),
(101, 38, 22, '2026-08-23', '', '', NULL, 'current', 1),
(102, 38, 23, '2026-08-23', '', '', NULL, 'control', 2),
(103, 38, 24, '2026-08-23', '', '', NULL, 'current', 3),
(104, 39, 11, '2026-08-21', 'Техника безопасности на уроках ФК', 'lecture', '1.0', 'current', 1),
(105, 39, 21, '2026-08-22', 'Техника безопасности на уроках ФК', 'lecture', '1.0', 'current', 2);

-- --------------------------------------------------------

--
-- Структура таблицы `archive_journal_students`
--

CREATE TABLE `archive_journal_students` (
  `id` int UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_journal_students`
--

INSERT INTO `archive_journal_students` (`id`, `item_id`, `student_id`, `full_name`, `sort_order`) VALUES
(109, 35, 3, 'Власенко Татьяна Алексеевна', 1),
(110, 35, 2, 'Иванов Петр Петрович', 2),
(111, 35, 7, 'Смирнов Данил Владимирович', 3),
(112, 36, 3, 'Власенко Татьяна Алексеевна', 1),
(113, 36, 2, 'Иванов Петр Петрович', 2),
(114, 36, 7, 'Смирнов Данил Владимирович', 3),
(115, 37, 3, 'Власенко Татьяна Алексеевна', 1),
(116, 37, 2, 'Иванов Петр Петрович', 2),
(117, 37, 7, 'Смирнов Данил Владимирович', 3),
(118, 38, 3, 'Власенко Татьяна Алексеевна', 1),
(119, 38, 2, 'Иванов Петр Петрович', 2),
(120, 38, 7, 'Смирнов Данил Владимирович', 3),
(121, 39, 3, 'Власенко Татьяна Алексеевна', 1),
(122, 39, 2, 'Иванов Петр Петрович', 2),
(123, 39, 7, 'Смирнов Данил Владимирович', 3);

-- --------------------------------------------------------

--
-- Структура таблицы `archive_journal_topics`
--

CREATE TABLE `archive_journal_topics` (
  `id` int UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `source_topic_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lesson_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lecture',
  `hours` decimal(4,1) NOT NULL DEFAULT '2.0',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1',
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `first_lesson_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_journal_topics`
--

INSERT INTO `archive_journal_topics` (`id`, `item_id`, `source_topic_id`, `title`, `lesson_type`, `hours`, `sort_order`, `completed`, `first_lesson_date`) VALUES
(43, 35, 8, 'История англии', 'lecture', '1.0', 1, 1, '2026-08-22'),
(44, 35, 9, 'История англии', 'lecture', '1.0', 2, 1, '2026-08-22'),
(45, 35, 10, 'Грамматика английского языка', 'lecture', '1.0', 3, 1, '2026-08-23'),
(46, 35, 11, 'Грамматика английского языка', 'lecture', '1.0', 4, 1, '2026-08-23'),
(47, 35, 12, 'Чтение по ролям', 'practice', '1.0', 5, 1, '2026-08-23'),
(48, 35, 13, 'Чтение по ролям', 'practice', '1.0', 6, 1, '2026-08-23'),
(49, 35, 14, 'Чтение текста', 'independent', '1.0', 7, 1, '2026-08-23'),
(50, 35, 15, 'Промежуточная аттестация. Дифференцированный зачёт', 'diff_credit', '1.0', 8, 1, '2026-08-23'),
(51, 35, 16, 'Промежуточная аттестация. Дифференцированный зачёт', 'diff_credit', '1.0', 9, 1, '2026-08-23'),
(52, 35, 17, 'Виды легкой атлетики', 'independent', '1.0', 10, 0, NULL),
(53, 35, 18, 'Виды легкой атлетики', 'independent', '1.0', 11, 0, NULL),
(54, 39, 1, 'Техника безопасности на уроках ФК', 'lecture', '1.0', 1, 1, '2026-08-21'),
(55, 39, 5, 'Техника безопасности на уроках ФК', 'lecture', '1.0', 2, 1, '2026-08-22'),
(56, 39, 2, 'Виды легкой атлетики', 'practice', '2.0', 3, 0, NULL),
(57, 39, 3, 'Техника бега на короткие дистанции', 'practice', '1.0', 4, 0, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `archive_journal_totals`
--

CREATE TABLE `archive_journal_totals` (
  `id` int UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `final_grade` tinyint UNSIGNED DEFAULT NULL,
  `average` decimal(4,1) DEFAULT NULL,
  `points` decimal(5,1) DEFAULT NULL,
  `display` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_journal_totals`
--

INSERT INTO `archive_journal_totals` (`id`, `item_id`, `student_id`, `final_grade`, `average`, `points`, `display`) VALUES
(111, 35, 3, 5, NULL, '86.3', '86.3 → 5'),
(112, 35, 2, 2, NULL, '46.8', '46.8 → 2'),
(113, 35, 7, 4, NULL, '78.1', '78.1 → 4'),
(114, 36, 3, 4, NULL, '70.0', '70 → 4'),
(115, 36, 2, 2, NULL, '42.0', '42 → 2'),
(116, 36, 7, 3, NULL, '65.0', '65 → 3'),
(117, 37, 3, 5, NULL, '86.0', '86 → 5'),
(118, 37, 2, 4, NULL, '77.0', '77 → 4'),
(119, 37, 7, 4, NULL, '83.5', '83.5 → 4'),
(120, 38, 3, 4, NULL, '75.0', '75 → 4'),
(121, 38, 2, 2, NULL, '63.7', '63.7 → 2'),
(122, 38, 7, 3, NULL, '65.0', '65 → 3'),
(123, 39, 3, 5, NULL, '95.0', '95 → 5'),
(124, 39, 2, 2, NULL, '20.0', '20 → 2'),
(125, 39, 7, 2, NULL, '20.0', '20 → 2');

-- --------------------------------------------------------

--
-- Структура таблицы `archive_periods`
--

CREATE TABLE `archive_periods` (
  `id` int UNSIGNED NOT NULL,
  `archive_type` enum('gradebook','journal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('1','2') COLLATE utf8mb4_unicode_ci NOT NULL,
  `archived_by` int UNSIGNED DEFAULT NULL,
  `archived_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_periods`
--

INSERT INTO `archive_periods` (`id`, `archive_type`, `academic_year`, `semester`, `archived_by`, `archived_at`) VALUES
(8, 'journal', '2025-2026', '1', 1, '2026-08-23 18:04:31'),
(9, 'gradebook', '2025-2026', '1', 1, '2026-08-24 05:21:19');

-- --------------------------------------------------------

--
-- Структура таблицы `attendance_days`
--

CREATE TABLE `attendance_days` (
  `id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `academic_year` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `attendance_days`
--

INSERT INTO `attendance_days` (`id`, `group_id`, `attendance_date`, `academic_year`, `created_at`) VALUES
(3, 2, '2026-08-25', '2025-2026', '2026-08-25 06:43:16'),
(4, 2, '2026-08-26', '2025-2026', '2026-08-26 16:24:24'),
(5, 2, '2026-08-10', '2025-2026', '2026-08-26 16:24:58'),
(6, 2, '2026-08-11', '2025-2026', '2026-08-26 16:25:45'),
(7, 8, '2026-08-26', '2025-2026', '2026-08-26 16:37:37'),
(8, 8, '2026-08-19', '2025-2026', '2026-08-26 16:37:56'),
(9, 8, '2026-07-01', '2025-2026', '2026-08-26 16:48:37'),
(10, 8, '2026-07-08', '2025-2026', '2026-08-26 16:49:18');

-- --------------------------------------------------------

--
-- Структура таблицы `attendance_entries`
--

CREATE TABLE `attendance_entries` (
  `id` int UNSIGNED NOT NULL,
  `attendance_day_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `excused_lessons` smallint UNSIGNED NOT NULL DEFAULT '0',
  `unexcused_lessons` smallint UNSIGNED NOT NULL DEFAULT '0',
  `reason_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `attendance_entries`
--

INSERT INTO `attendance_entries` (`id`, `attendance_day_id`, `student_id`, `excused_lessons`, `unexcused_lessons`, `reason_id`, `created_at`, `updated_at`) VALUES
(15, 3, 9, 6, 0, 3, '2026-08-25 06:47:39', '2026-08-25 06:47:39'),
(16, 3, 2, 6, 0, 4, '2026-08-25 06:47:39', '2026-08-25 06:47:39'),
(17, 3, 7, 0, 6, NULL, '2026-08-25 06:47:39', '2026-08-25 06:47:39'),
(18, 4, 9, 5, 0, 3, '2026-08-26 16:24:25', '2026-08-26 16:24:25'),
(19, 5, 7, 6, 0, 5, '2026-08-26 16:24:58', '2026-08-26 16:24:58'),
(20, 6, 11, 0, 4, NULL, '2026-08-26 16:25:45', '2026-08-26 16:25:45'),
(21, 7, 15, 6, 0, 5, '2026-08-26 16:37:37', '2026-08-26 16:37:37'),
(22, 8, 14, 0, 6, NULL, '2026-08-26 16:37:56', '2026-08-26 16:37:56'),
(23, 9, 13, 6, 0, 3, '2026-08-26 16:48:37', '2026-08-26 16:48:37'),
(24, 10, 15, 4, 0, 4, '2026-08-26 16:49:18', '2026-08-26 16:49:18'),
(25, 10, 14, 0, 4, NULL, '2026-08-26 16:49:18', '2026-08-26 16:49:18');

-- --------------------------------------------------------

--
-- Структура таблицы `attendance_reasons`
--

CREATE TABLE `attendance_reasons` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `attendance_reasons`
--

INSERT INTO `attendance_reasons` (`id`, `name`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'ОРЗ', 1, 1, '2026-08-20 10:27:55'),
(2, 'ОРВИ', 2, 1, '2026-08-20 10:27:55'),
(3, 'Заявление', 3, 1, '2026-08-20 10:27:55'),
(4, 'Приказ', 4, 1, '2026-08-21 13:17:33'),
(5, 'Военкомат', 5, 1, '2026-08-21 13:17:50');

-- --------------------------------------------------------

--
-- Структура таблицы `curriculum_items`
--

CREATE TABLE `curriculum_items` (
  `id` int UNSIGNED NOT NULL,
  `curriculum_plan_id` int UNSIGNED NOT NULL,
  `subject_id` int UNSIGNED NOT NULL,
  `teacher_id` int UNSIGNED DEFAULT NULL,
  `semester` enum('1','2','both') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `curriculum_items`
--

INSERT INTO `curriculum_items` (`id`, `curriculum_plan_id`, `subject_id`, `teacher_id`, `semester`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 2, 3, 3, 'both', 1, '2026-08-20 07:01:55', '2026-08-22 10:13:23'),
(4, 2, 4, 3, 'both', 2, '2026-08-20 07:02:32', '2026-08-22 10:13:19'),
(8, 2, 7, 3, '2', 3, '2026-08-20 09:44:39', '2026-08-22 13:16:36'),
(9, 2, 6, 3, '1', 4, '2026-08-20 09:44:46', '2026-08-22 10:13:10'),
(10, 2, 1, 3, '1', 5, '2026-08-20 09:45:00', '2026-08-22 10:13:05'),
(11, 2, 2, 3, '1', 6, '2026-08-21 09:27:11', '2026-08-21 09:27:11'),
(12, 4, 3, 28, 'both', 1, '2026-08-26 16:31:22', '2026-08-26 16:31:22'),
(13, 4, 1, 3, '1', 2, '2026-08-26 16:31:47', '2026-08-26 16:31:47'),
(14, 4, 6, 26, '1', 3, '2026-08-26 16:32:24', '2026-08-26 16:32:24'),
(15, 5, 4, 12, 'both', 1, '2026-08-26 16:56:11', '2026-08-26 16:56:11'),
(16, 5, 6, 7, '1', 2, '2026-08-26 16:56:21', '2026-08-26 16:56:21'),
(17, 5, 1, 20, 'both', 3, '2026-08-26 16:56:35', '2026-08-26 16:56:35');

-- --------------------------------------------------------

--
-- Структура таблицы `curriculum_plans`
--

CREATE TABLE `curriculum_plans` (
  `id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `academic_year` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `curriculum_plans`
--

INSERT INTO `curriculum_plans` (`id`, `group_id`, `academic_year`, `created_at`, `updated_at`) VALUES
(2, 2, '2025-2026', '2026-08-20 07:01:27', '2026-08-20 07:01:27'),
(4, 8, '2025-2026', '2026-08-26 16:31:04', '2026-08-26 16:31:04'),
(5, 15, '2025-2026', '2026-08-26 16:55:50', '2026-08-26 16:55:50');

-- --------------------------------------------------------

--
-- Структура таблицы `expelled_courseworks`
--

CREATE TABLE `expelled_courseworks` (
  `id` int UNSIGNED NOT NULL,
  `expelled_id` int UNSIGNED NOT NULL,
  `subject_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `topic` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `defense_date` date DEFAULT NULL,
  `teacher_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `grade` tinyint UNSIGNED DEFAULT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `expelled_debts`
--

CREATE TABLE `expelled_debts` (
  `id` int UNSIGNED NOT NULL,
  `expelled_id` int UNSIGNED NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL DEFAULT '0',
  `group_id` int UNSIGNED DEFAULT NULL,
  `group_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subject_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('1','2') COLLATE utf8mb4_unicode_ci NOT NULL,
  `archived_at` datetime DEFAULT NULL,
  `liquidation_date` date DEFAULT NULL,
  `liquidation_time` time DEFAULT NULL,
  `commission_json` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `expelled_debts`
--

INSERT INTO `expelled_debts` (`id`, `expelled_id`, `curriculum_item_id`, `group_id`, `group_number`, `subject_name`, `academic_year`, `semester`, `archived_at`, `liquidation_date`, `liquidation_time`, `commission_json`) VALUES
(1, 1, 11, 2, '201', 'Физкультура', '2025-2026', '1', '2026-08-22 10:16:21', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `expelled_gia`
--

CREATE TABLE `expelled_gia` (
  `id` int UNSIGNED NOT NULL,
  `expelled_id` int UNSIGNED NOT NULL,
  `form_type` enum('demo_exam','vkr') COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `points` decimal(8,2) DEFAULT NULL,
  `topic` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `defense_date` date DEFAULT NULL,
  `grade` tinyint UNSIGNED DEFAULT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `expelled_practices`
--

CREATE TABLE `expelled_practices` (
  `id` int UNSIGNED NOT NULL,
  `expelled_id` int UNSIGNED NOT NULL,
  `module_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `org_supervisor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `college_supervisor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `grade` tinyint UNSIGNED DEFAULT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `expelled_record_book`
--

CREATE TABLE `expelled_record_book` (
  `id` int UNSIGNED NOT NULL,
  `expelled_id` int UNSIGNED NOT NULL,
  `academic_year` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('1','2') COLLATE utf8mb4_unicode_ci NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL DEFAULT '0',
  `subject_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attestation_form` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `grade` tinyint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `expelled_record_book`
--

INSERT INTO `expelled_record_book` (`id`, `expelled_id`, `academic_year`, `semester`, `curriculum_item_id`, `subject_name`, `teacher_name`, `attestation_form`, `grade`) VALUES
(1, 1, '2025-2026', '1', 3, 'Иностранный язык', '', '', 3),
(2, 1, '2025-2026', '1', 9, 'История', '', '', 3),
(3, 1, '2025-2026', '1', 10, 'Математика', '', '', 4),
(4, 1, '2025-2026', '1', 8, 'Физика', '', '', 4),
(5, 1, '2025-2026', '1', 4, 'Физическая культура', '', '', 3),
(6, 1, '2025-2026', '1', 11, 'Физкультура', '', '', 2);

-- --------------------------------------------------------

--
-- Структура таблицы `expelled_restorations`
--

CREATE TABLE `expelled_restorations` (
  `id` int UNSIGNED NOT NULL,
  `expelled_id` int UNSIGNED NOT NULL,
  `restore_date` date NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `group_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `additional_info` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `restored_by` int UNSIGNED DEFAULT NULL,
  `restored_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `new_student_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `expelled_restorations`
--

INSERT INTO `expelled_restorations` (`id`, `expelled_id`, `restore_date`, `group_id`, `group_number`, `additional_info`, `restored_by`, `restored_at`, `new_student_id`) VALUES
(1, 1, '2026-08-24', 5, '202', '', 1, '2026-08-24 15:30:38', 10);

-- --------------------------------------------------------

--
-- Структура таблицы `expelled_students`
--

CREATE TABLE `expelled_students` (
  `id` int UNSIGNED NOT NULL,
  `original_student_id` int UNSIGNED DEFAULT NULL,
  `group_id` int UNSIGNED DEFAULT NULL,
  `group_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `specialty_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `specialty_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mother_phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mother_workplace` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `father_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `father_phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `father_workplace` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_registered` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_region` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_district` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_locality` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_street` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_house` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_actual` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `district` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_low_income` tinyint(1) NOT NULL DEFAULT '0',
  `family_type` enum('complete','no_father','no_mother') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siblings_under_18` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `residence_type` enum('family','dormitory','apartment') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_nonresident` tinyint(1) NOT NULL DEFAULT '0',
  `without_parental_care` tinyint(1) NOT NULL DEFAULT '0',
  `expulsion_order` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `expulsion_date` date NOT NULL,
  `expulsion_reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `expelled_by` int UNSIGNED DEFAULT NULL,
  `expelled_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_restored` tinyint(1) NOT NULL DEFAULT '0',
  `restored_at` datetime DEFAULT NULL,
  `restored_student_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `expelled_students`
--

INSERT INTO `expelled_students` (`id`, `original_student_id`, `group_id`, `group_number`, `specialty_name`, `specialty_code`, `full_name`, `phone`, `birth_date`, `gender`, `mother_name`, `mother_phone`, `mother_workplace`, `father_name`, `father_phone`, `father_workplace`, `address_registered`, `address_region`, `address_district`, `address_locality`, `address_street`, `address_house`, `address_actual`, `district`, `is_low_income`, `family_type`, `siblings_under_18`, `residence_type`, `is_nonresident`, `without_parental_care`, `expulsion_order`, `expulsion_date`, `expulsion_reason`, `expelled_by`, `expelled_at`, `is_restored`, `restored_at`, `restored_student_id`) VALUES
(1, 6, 2, '201', 'Преподавание в начальных классах', '44.02.02', 'Сидоров Константин Иванович', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, NULL, 0, NULL, 0, 0, '124', '2026-08-23', 'Академическая задолженность', 1, '2026-08-23 05:11:40', 1, '2026-08-24 15:30:38', 10);

-- --------------------------------------------------------

--
-- Структура таблицы `glaz_commission_members`
--

CREATE TABLE `glaz_commission_members` (
  `id` int UNSIGNED NOT NULL,
  `schedule_id` int UNSIGNED NOT NULL,
  `teacher_id` int UNSIGNED NOT NULL,
  `sort_order` tinyint UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `glaz_commission_members`
--

INSERT INTO `glaz_commission_members` (`id`, `schedule_id`, `teacher_id`, `sort_order`) VALUES
(6, 2, 3, 1),
(13, 1, 17, 1),
(14, 1, 7, 2);

-- --------------------------------------------------------

--
-- Структура таблицы `glaz_schedules`
--

CREATE TABLE `glaz_schedules` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `academic_year` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('1','2') COLLATE utf8mb4_unicode_ci NOT NULL,
  `liquidation_date` date DEFAULT NULL,
  `liquidation_time` time DEFAULT NULL,
  `updated_by` int UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `glaz_schedules`
--

INSERT INTO `glaz_schedules` (`id`, `student_id`, `curriculum_item_id`, `academic_year`, `semester`, `liquidation_date`, `liquidation_time`, `updated_by`, `updated_at`) VALUES
(1, 2, 3, '2025-2026', '1', '2026-08-22', '14:50:00', 1, '2026-08-22 12:45:58'),
(2, 2, 4, '2025-2026', '1', NULL, NULL, 1, '2026-08-22 12:35:48');

-- --------------------------------------------------------

--
-- Структура таблицы `grade_entries`
--

CREATE TABLE `grade_entries` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `grade` tinyint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `grade_entries`
--

INSERT INTO `grade_entries` (`id`, `student_id`, `curriculum_item_id`, `grade`, `created_at`, `updated_at`) VALUES
(6, 3, 4, 5, '2026-08-20 09:15:49', '2026-08-20 09:59:27'),
(7, 2, 3, 2, '2026-08-20 09:15:49', '2026-08-20 10:04:15'),
(8, 2, 4, 5, '2026-08-20 09:15:49', '2026-08-20 09:59:12'),
(10, 3, 3, 5, '2026-08-20 09:43:56', '2026-08-20 09:59:21'),
(14, 3, 8, 5, '2026-08-20 09:50:53', '2026-08-20 10:04:00'),
(15, 3, 9, 5, '2026-08-20 09:50:54', '2026-08-20 09:50:54'),
(16, 3, 10, 5, '2026-08-20 09:50:55', '2026-08-20 09:50:55'),
(17, 2, 10, 4, '2026-08-20 09:50:57', '2026-08-20 09:50:57'),
(18, 2, 9, 2, '2026-08-20 09:50:59', '2026-08-20 09:50:59'),
(19, 2, 8, 2, '2026-08-20 09:51:01', '2026-08-20 09:51:01');

-- --------------------------------------------------------

--
-- Структура таблицы `group_promotions`
--

CREATE TABLE `group_promotions` (
  `id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `from_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `promoted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `group_promotions`
--

INSERT INTO `group_promotions` (`id`, `group_id`, `from_number`, `to_number`, `academic_year`, `promoted_at`) VALUES
(1, 6, '301', '401', '2025-2026', '2026-08-23 05:37:32');

-- --------------------------------------------------------

--
-- Структура таблицы `journal_grades`
--

CREATE TABLE `journal_grades` (
  `id` int UNSIGNED NOT NULL,
  `lesson_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `mark` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `activity` tinyint(1) NOT NULL DEFAULT '0',
  `late` tinyint(1) NOT NULL DEFAULT '0',
  `grade` tinyint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `journal_grades`
--

INSERT INTO `journal_grades` (`id`, `lesson_id`, `student_id`, `mark`, `activity`, `late`, `grade`, `created_at`, `updated_at`) VALUES
(172, 11, 3, '5', 0, 0, NULL, '2026-08-21 19:17:07', '2026-08-21 19:17:19'),
(181, 12, 3, '5', 0, 0, NULL, '2026-08-22 10:14:03', '2026-08-22 10:14:03'),
(182, 12, 2, '5', 0, 0, NULL, '2026-08-22 10:14:04', '2026-08-22 10:14:04'),
(184, 12, 7, '5', 0, 0, NULL, '2026-08-22 10:14:07', '2026-08-22 10:14:07'),
(185, 13, 3, '4', 0, 0, NULL, '2026-08-22 10:14:17', '2026-08-22 10:14:17'),
(186, 13, 2, '3', 0, 0, NULL, '2026-08-22 10:14:19', '2026-08-22 10:14:19'),
(188, 13, 7, '4', 0, 1, NULL, '2026-08-22 10:14:21', '2026-08-22 10:14:23'),
(195, 15, 3, '5', 1, 0, NULL, '2026-08-22 10:15:01', '2026-08-22 10:15:02'),
(197, 15, 2, '5', 1, 0, NULL, '2026-08-22 10:15:05', '2026-08-22 10:15:06'),
(201, 15, 7, '4', 1, 0, NULL, '2026-08-22 10:15:10', '2026-08-22 10:15:12'),
(203, 16, 3, '5', 1, 0, NULL, '2026-08-22 10:15:25', '2026-08-22 10:15:26'),
(205, 16, 2, '3', 0, 1, NULL, '2026-08-22 10:15:27', '2026-08-22 10:15:28'),
(208, 16, 7, '5', 0, 0, NULL, '2026-08-22 10:15:31', '2026-08-22 10:15:31'),
(209, 17, 3, '5', 0, 0, NULL, '2026-08-22 10:59:19', '2026-08-22 10:59:23'),
(212, 17, 2, 'Н', 0, 0, NULL, '2026-08-22 10:59:27', '2026-08-22 10:59:27'),
(218, 19, 3, '4', 0, 0, NULL, '2026-08-22 11:00:44', '2026-08-22 11:00:44'),
(219, 19, 2, 'Н', 0, 0, NULL, '2026-08-22 11:00:47', '2026-08-22 11:00:47'),
(220, 20, 2, 'Н', 0, 0, NULL, '2026-08-22 11:00:49', '2026-08-22 11:00:49'),
(221, 19, 7, '4', 0, 1, NULL, '2026-08-22 11:00:56', '2026-08-22 11:00:57'),
(223, 20, 3, '5', 0, 0, NULL, '2026-08-22 11:01:02', '2026-08-22 11:01:02'),
(225, 20, 7, '4', 0, 0, NULL, '2026-08-22 11:01:10', '2026-08-22 11:01:10'),
(232, 22, 2, '4', 1, 0, NULL, '2026-08-23 05:58:39', '2026-08-23 06:02:00'),
(233, 22, 7, '3', 0, 0, NULL, '2026-08-23 05:58:42', '2026-08-23 06:02:09'),
(241, 23, 3, '4', 0, 0, NULL, '2026-08-23 05:59:59', '2026-08-23 06:04:49'),
(254, 23, 2, '2', 0, 0, NULL, '2026-08-23 06:01:53', '2026-08-23 06:01:53'),
(257, 23, 7, '3', 0, 0, NULL, '2026-08-23 06:02:04', '2026-08-23 06:02:08'),
(264, 22, 3, 'Н', 0, 0, NULL, '2026-08-23 06:03:38', '2026-08-23 06:03:38'),
(265, 24, 3, '4', 0, 0, NULL, '2026-08-23 06:03:42', '2026-08-23 06:04:48'),
(275, 25, 3, '5', 0, 0, NULL, '2026-08-23 13:46:04', '2026-08-23 13:46:04'),
(276, 26, 3, '5', 0, 0, NULL, '2026-08-23 13:46:06', '2026-08-23 13:46:06'),
(277, 25, 2, '', 0, 1, NULL, '2026-08-23 13:46:10', '2026-08-23 13:46:10'),
(278, 26, 2, '3', 0, 0, NULL, '2026-08-23 13:46:13', '2026-08-23 13:46:13'),
(279, 26, 7, '4', 1, 0, NULL, '2026-08-23 13:46:16', '2026-08-23 13:46:27'),
(281, 27, 7, '4', 1, 0, NULL, '2026-08-23 13:46:36', '2026-08-23 13:46:37'),
(283, 31, 3, '4', 0, 0, NULL, '2026-08-23 18:02:20', '2026-08-23 18:02:20'),
(284, 31, 2, '4', 0, 0, NULL, '2026-08-23 18:02:22', '2026-08-23 18:02:22'),
(285, 31, 7, '4', 0, 0, NULL, '2026-08-23 18:02:23', '2026-08-23 18:02:23'),
(286, 36, 3, '3', 0, 0, NULL, '2026-08-23 18:04:17', '2026-08-23 18:04:17'),
(287, 36, 2, '3', 0, 0, NULL, '2026-08-23 18:04:18', '2026-08-23 18:04:18'),
(288, 36, 7, '3', 0, 0, NULL, '2026-08-23 18:04:21', '2026-08-23 18:04:21'),
(289, 19, 8, '5', 0, 0, NULL, '2026-08-24 05:38:07', '2026-08-24 05:38:07'),
(290, 25, 8, '4', 0, 0, NULL, '2026-08-24 05:38:09', '2026-08-24 05:38:09'),
(291, 20, 8, '4', 0, 0, NULL, '2026-08-24 05:38:13', '2026-08-24 05:38:13'),
(292, 20, 9, '4', 0, 0, NULL, '2026-08-25 06:35:10', '2026-08-25 06:35:10'),
(293, 36, 9, '4', 0, 0, NULL, '2026-08-25 06:35:22', '2026-08-25 06:35:22'),
(294, 26, 9, '4', 0, 0, NULL, '2026-08-25 06:35:30', '2026-08-25 06:35:30'),
(296, 18, 9, '2', 0, 0, NULL, '2026-08-25 06:36:00', '2026-08-25 06:36:11'),
(298, 37, 13, '5', 1, 0, NULL, '2026-08-26 16:33:06', '2026-08-26 16:33:58'),
(299, 37, 15, '3', 0, 1, NULL, '2026-08-26 16:33:10', '2026-08-26 16:33:11'),
(301, 38, 12, '4', 0, 0, NULL, '2026-08-26 16:33:26', '2026-08-26 16:33:26'),
(302, 38, 14, 'Н', 0, 0, NULL, '2026-08-26 16:33:30', '2026-08-26 16:33:30'),
(303, 39, 13, '4', 0, 0, NULL, '2026-08-26 16:33:47', '2026-08-26 16:33:47'),
(304, 39, 15, '3', 0, 0, NULL, '2026-08-26 16:33:49', '2026-08-26 16:33:49'),
(305, 39, 12, '4', 0, 0, NULL, '2026-08-26 16:33:51', '2026-08-26 16:33:51'),
(306, 39, 14, '3', 0, 0, NULL, '2026-08-26 16:33:53', '2026-08-26 16:33:53'),
(308, 40, 13, '5', 0, 0, NULL, '2026-08-26 16:34:13', '2026-08-26 16:34:13'),
(309, 40, 15, '2', 0, 0, NULL, '2026-08-26 16:34:17', '2026-08-26 16:34:17'),
(310, 40, 12, '', 0, 1, NULL, '2026-08-26 16:34:19', '2026-08-26 16:34:19'),
(311, 41, 13, '4', 0, 0, NULL, '2026-08-26 16:35:06', '2026-08-26 16:35:06'),
(312, 42, 13, '3', 0, 0, NULL, '2026-08-26 16:35:08', '2026-08-26 16:35:08'),
(313, 43, 13, '4', 0, 0, NULL, '2026-08-26 16:35:09', '2026-08-26 16:35:09'),
(314, 41, 15, '4', 0, 0, NULL, '2026-08-26 16:35:13', '2026-08-26 16:35:13'),
(315, 42, 15, '', 0, 1, NULL, '2026-08-26 16:35:14', '2026-08-26 16:35:14'),
(316, 41, 12, '4', 1, 0, NULL, '2026-08-26 16:35:16', '2026-08-26 16:35:17'),
(318, 43, 12, '3', 0, 0, NULL, '2026-08-26 16:35:19', '2026-08-26 16:35:19'),
(319, 43, 15, '4', 0, 0, NULL, '2026-08-26 16:35:21', '2026-08-26 16:35:21'),
(320, 43, 14, '3', 0, 0, NULL, '2026-08-26 16:35:23', '2026-08-26 16:35:23'),
(321, 46, 15, '4', 0, 0, NULL, '2026-08-26 16:36:22', '2026-08-26 16:36:22'),
(322, 44, 13, '5', 0, 0, NULL, '2026-08-26 16:36:24', '2026-08-26 16:36:24'),
(323, 46, 13, '4', 0, 0, NULL, '2026-08-26 16:36:28', '2026-08-26 16:36:28'),
(324, 46, 12, '3', 0, 0, NULL, '2026-08-26 16:36:31', '2026-08-26 16:51:21'),
(325, 46, 14, '4', 0, 0, NULL, '2026-08-26 16:36:33', '2026-08-26 16:36:33'),
(326, 44, 12, '2', 0, 1, NULL, '2026-08-26 16:36:35', '2026-08-26 16:51:15'),
(327, 45, 15, '4', 1, 0, NULL, '2026-08-26 16:36:38', '2026-08-26 16:36:38'),
(329, 44, 14, '4', 0, 0, NULL, '2026-08-26 16:36:44', '2026-08-26 16:36:44'),
(333, 47, 16, '4', 0, 0, NULL, '2026-08-26 16:57:21', '2026-08-26 16:57:21'),
(335, 48, 16, '', 0, 1, NULL, '2026-08-26 16:57:26', '2026-08-26 16:57:26'),
(336, 50, 16, '4', 0, 0, NULL, '2026-08-26 16:57:28', '2026-08-26 16:57:28'),
(337, 50, 17, '3', 0, 0, NULL, '2026-08-26 16:57:31', '2026-08-26 16:57:31'),
(338, 50, 18, '4', 0, 0, NULL, '2026-08-26 16:57:33', '2026-08-26 16:57:33'),
(339, 48, 17, '5', 0, 0, NULL, '2026-08-26 16:57:36', '2026-08-26 16:57:36'),
(340, 47, 18, '4', 0, 0, NULL, '2026-08-26 16:57:39', '2026-08-26 16:57:39'),
(341, 47, 17, '4', 1, 0, NULL, '2026-08-26 16:57:44', '2026-08-26 16:57:46'),
(343, 52, 16, '5', 0, 0, NULL, '2026-08-26 16:58:41', '2026-08-26 16:58:41'),
(344, 53, 16, '5', 0, 0, NULL, '2026-08-26 16:58:42', '2026-08-26 16:58:42'),
(345, 55, 16, '4', 0, 0, NULL, '2026-08-26 16:58:44', '2026-08-26 16:58:44'),
(346, 52, 17, '4', 0, 0, NULL, '2026-08-26 16:58:47', '2026-08-26 16:58:47'),
(347, 51, 17, '5', 0, 0, NULL, '2026-08-26 16:58:49', '2026-08-26 16:58:49'),
(348, 53, 17, '4', 0, 0, NULL, '2026-08-26 16:58:51', '2026-08-26 16:58:51'),
(349, 54, 17, '4', 0, 0, NULL, '2026-08-26 16:58:53', '2026-08-26 16:59:14'),
(350, 55, 17, '4', 0, 0, NULL, '2026-08-26 16:58:55', '2026-08-26 16:58:55'),
(351, 55, 18, '4', 0, 0, NULL, '2026-08-26 16:58:57', '2026-08-26 16:58:57'),
(352, 51, 18, '', 0, 1, NULL, '2026-08-26 16:58:59', '2026-08-26 16:58:59'),
(353, 52, 18, '4', 0, 0, NULL, '2026-08-26 16:59:02', '2026-08-26 16:59:02'),
(354, 53, 18, '', 0, 1, NULL, '2026-08-26 16:59:03', '2026-08-26 16:59:03'),
(355, 54, 18, '', 0, 1, NULL, '2026-08-26 16:59:06', '2026-08-26 16:59:06'),
(359, 56, 16, '4', 0, 0, NULL, '2026-08-26 17:00:03', '2026-08-26 17:00:03'),
(360, 59, 16, '4', 0, 0, NULL, '2026-08-26 17:00:04', '2026-08-26 17:00:04'),
(361, 56, 18, '3', 0, 0, NULL, '2026-08-26 17:00:09', '2026-08-26 17:00:09'),
(362, 59, 18, '3', 0, 0, NULL, '2026-08-26 17:00:12', '2026-08-26 17:00:12'),
(363, 58, 18, '2', 0, 0, NULL, '2026-08-26 17:00:15', '2026-08-26 17:00:15'),
(364, 57, 17, '5', 0, 0, NULL, '2026-08-26 17:00:18', '2026-08-26 17:00:18'),
(365, 59, 17, '3', 0, 0, NULL, '2026-08-26 17:00:21', '2026-08-26 17:00:21');

-- --------------------------------------------------------

--
-- Структура таблицы `journal_lessons`
--

CREATE TABLE `journal_lessons` (
  `id` int UNSIGNED NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `lesson_date` date NOT NULL,
  `ktp_topic_id` int UNSIGNED DEFAULT NULL,
  `grade_type` enum('current','control') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'current',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `journal_lessons`
--

INSERT INTO `journal_lessons` (`id`, `curriculum_item_id`, `lesson_date`, `ktp_topic_id`, `grade_type`, `created_at`) VALUES
(11, 11, '2026-08-21', 1, 'current', '2026-08-21 09:45:41'),
(12, 9, '2026-08-22', NULL, 'current', '2026-08-22 10:13:58'),
(13, 9, '2026-08-22', NULL, 'control', '2026-08-22 10:14:12'),
(15, 8, '2026-08-22', NULL, 'control', '2026-08-22 10:14:57'),
(16, 4, '2026-08-22', NULL, 'control', '2026-08-22 10:15:20'),
(17, 3, '2026-08-22', 8, 'current', '2026-08-22 10:58:39'),
(18, 3, '2026-08-22', 9, 'current', '2026-08-22 10:59:02'),
(19, 3, '2026-08-23', 10, 'current', '2026-08-22 11:00:27'),
(20, 3, '2026-08-23', 11, 'control', '2026-08-22 11:00:37'),
(21, 11, '2026-08-22', 5, 'current', '2026-08-22 13:18:29'),
(22, 10, '2026-08-23', NULL, 'current', '2026-08-23 05:58:33'),
(23, 10, '2026-08-23', NULL, 'control', '2026-08-23 05:59:02'),
(24, 10, '2026-08-23', NULL, 'current', '2026-08-23 06:03:32'),
(25, 3, '2026-08-23', 12, 'current', '2026-08-23 13:45:12'),
(26, 3, '2026-08-23', 13, 'control', '2026-08-23 13:45:20'),
(27, 3, '2026-08-23', 14, 'current', '2026-08-23 13:45:29'),
(28, 3, '2026-08-23', 15, 'current', '2026-08-23 13:45:34'),
(29, 3, '2026-08-23', 16, 'current', '2026-08-23 14:18:46'),
(30, 3, '2026-08-23', NULL, 'current', '2026-08-23 18:02:04'),
(31, 3, '2026-08-23', NULL, 'control', '2026-08-23 18:02:13'),
(32, 3, '2026-08-23', NULL, 'current', '2026-08-23 18:03:49'),
(33, 3, '2026-08-23', NULL, 'current', '2026-08-23 18:03:52'),
(34, 3, '2026-08-23', NULL, 'current', '2026-08-23 18:03:57'),
(35, 3, '2026-08-23', NULL, 'current', '2026-08-23 18:04:00'),
(36, 3, '2026-08-23', NULL, 'control', '2026-08-23 18:04:09'),
(37, 13, '2026-08-26', NULL, 'current', '2026-08-26 16:33:00'),
(38, 13, '2026-08-10', NULL, 'current', '2026-08-26 16:33:20'),
(39, 13, '2026-08-17', NULL, 'control', '2026-08-26 16:33:41'),
(40, 13, '2026-08-24', NULL, 'current', '2026-08-26 16:34:06'),
(41, 12, '2026-08-07', NULL, 'current', '2026-08-26 16:34:38'),
(42, 12, '2026-08-14', NULL, 'current', '2026-08-26 16:34:49'),
(43, 12, '2026-08-21', NULL, 'control', '2026-08-26 16:34:58'),
(44, 14, '2026-08-06', NULL, 'current', '2026-08-26 16:35:55'),
(45, 14, '2026-08-13', NULL, 'current', '2026-08-26 16:36:07'),
(46, 14, '2026-08-19', NULL, 'control', '2026-08-26 16:36:15'),
(47, 16, '2026-08-05', NULL, 'current', '2026-08-26 16:56:56'),
(48, 16, '2026-08-05', NULL, 'current', '2026-08-26 16:57:03'),
(49, 16, '2026-08-12', NULL, 'current', '2026-08-26 16:57:09'),
(50, 16, '2026-08-12', NULL, 'control', '2026-08-26 16:57:17'),
(51, 15, '2026-08-04', NULL, 'current', '2026-08-26 16:57:59'),
(52, 15, '2026-08-04', NULL, 'current', '2026-08-26 16:58:05'),
(53, 15, '2026-08-11', NULL, 'current', '2026-08-26 16:58:10'),
(54, 15, '2026-08-11', NULL, 'current', '2026-08-26 16:58:25'),
(55, 15, '2026-08-18', NULL, 'control', '2026-08-26 16:58:34'),
(56, 17, '2026-08-07', NULL, 'current', '2026-08-26 16:59:35'),
(57, 17, '2026-08-07', NULL, 'current', '2026-08-26 16:59:44'),
(58, 17, '2026-08-14', NULL, 'current', '2026-08-26 16:59:50'),
(59, 17, '2026-08-14', NULL, 'control', '2026-08-26 16:59:56');

-- --------------------------------------------------------

--
-- Структура таблицы `ktp_topics`
--

CREATE TABLE `ktp_topics` (
  `id` int UNSIGNED NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lesson_type` enum('lecture','practice','independent','diff_credit','credit','exam','control') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lecture',
  `hours` decimal(4,1) NOT NULL DEFAULT '2.0',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `ktp_topics`
--

INSERT INTO `ktp_topics` (`id`, `curriculum_item_id`, `title`, `lesson_type`, `hours`, `sort_order`, `created_at`) VALUES
(1, 11, 'Техника безопасности на уроках ФК', 'lecture', '1.0', 1, '2026-08-21 09:44:50'),
(2, 11, 'Виды легкой атлетики', 'practice', '2.0', 3, '2026-08-21 09:45:12'),
(3, 11, 'Техника бега на короткие дистанции', 'practice', '1.0', 4, '2026-08-21 11:02:24'),
(5, 11, 'Техника безопасности на уроках ФК', 'lecture', '1.0', 2, '2026-08-21 11:07:46'),
(8, 3, 'История англии', 'lecture', '1.0', 1, '2026-08-22 10:52:41'),
(9, 3, 'История англии', 'lecture', '1.0', 2, '2026-08-22 10:52:41'),
(10, 3, 'Грамматика английского языка', 'lecture', '1.0', 3, '2026-08-22 10:53:35'),
(11, 3, 'Грамматика английского языка', 'lecture', '1.0', 4, '2026-08-22 10:53:35'),
(12, 3, 'Чтение по ролям', 'practice', '1.0', 5, '2026-08-22 10:54:14'),
(13, 3, 'Чтение по ролям', 'practice', '1.0', 6, '2026-08-22 10:54:14'),
(14, 3, 'Чтение текста', 'independent', '1.0', 7, '2026-08-22 10:54:54'),
(15, 3, 'Промежуточная аттестация. Дифференцированный зачёт', 'diff_credit', '1.0', 8, '2026-08-22 10:55:39'),
(16, 3, 'Промежуточная аттестация. Дифференцированный зачёт', 'diff_credit', '1.0', 9, '2026-08-22 10:55:39'),
(17, 3, 'Виды легкой атлетики', 'independent', '1.0', 10, '2026-08-23 14:18:17'),
(18, 3, 'Виды легкой атлетики', 'independent', '1.0', 11, '2026-08-23 14:18:17'),
(19, 9, 'Промежуточная аттестация. Экзамен', 'exam', '4.0', 1, '2026-08-24 18:28:52');

-- --------------------------------------------------------

--
-- Структура таблицы `notifications`
--

CREATE TABLE `notifications` (
  `id` int UNSIGNED NOT NULL,
  `sender_id` int UNSIGNED DEFAULT NULL,
  `notification_type` enum('personal','announcement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `notifications`
--

INSERT INTO `notifications` (`id`, `sender_id`, `notification_type`, `title`, `body`, `recipient_id`, `created_at`) VALUES
(1, 1, 'announcement', 'Обновление системы', 'Появилась система уведомлений, обратите внимание на колокольчик в шапке.', NULL, '2026-08-22 11:21:00'),
(2, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 3, '2026-08-22 12:55:07');

-- --------------------------------------------------------

--
-- Структура таблицы `notification_reads`
--

CREATE TABLE `notification_reads` (
  `id` int UNSIGNED NOT NULL,
  `notification_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `read_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `notification_reads`
--

INSERT INTO `notification_reads` (`id`, `notification_id`, `user_id`, `read_at`) VALUES
(1, 1, 3, '2026-08-22 11:21:23'),
(2, 1, 1, '2026-08-22 11:50:29'),
(3, 2, 3, '2026-08-22 12:55:25'),
(4, 1, 6, '2026-08-23 08:05:08');

-- --------------------------------------------------------

--
-- Структура таблицы `organization`
--

CREATE TABLE `organization` (
  `id` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `name` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `phone` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `additional_info` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `organization`
--

INSERT INTO `organization` (`id`, `name`, `address`, `phone`, `email`, `additional_info`, `updated_at`) VALUES
(1, 'ГАПОУ НСО \"Карасукский педагогический колледж\"', 'г. Карасук', '+7 900 000-00-00', 'college@test.local', 'Тестовая информация', '2026-08-21 19:12:38');

-- --------------------------------------------------------

--
-- Структура таблицы `specialties`
--

CREATE TABLE `specialties` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `specialties`
--

INSERT INTO `specialties` (`id`, `name`, `code`, `created_at`, `updated_at`) VALUES
(2, 'Преподавание в начальных классах', '44.02.02', '2026-08-20 06:28:50', '2026-08-20 06:28:50'),
(5, 'Физическая культура', '49.02.01', '2026-08-21 13:23:57', '2026-08-21 13:23:57'),
(6, 'Педагогика дополнительного образования', '44.02.03', '2026-08-24 08:20:35', '2026-08-24 08:20:35');

-- --------------------------------------------------------

--
-- Структура таблицы `students`
--

CREATE TABLE `students` (
  `id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `snils` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mother_phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mother_workplace` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `father_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `father_phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `father_workplace` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_registered` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_region` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_district` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_locality` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_street` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_house` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_actual` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `district` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_low_income` tinyint(1) NOT NULL DEFAULT '0',
  `family_type` enum('complete','no_father','no_mother') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siblings_under_18` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `residence_type` enum('family','dormitory','apartment') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_nonresident` tinyint(1) NOT NULL DEFAULT '0',
  `without_parental_care` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `students`
--

INSERT INTO `students` (`id`, `group_id`, `user_id`, `full_name`, `phone`, `snils`, `birth_date`, `gender`, `mother_name`, `mother_phone`, `mother_workplace`, `father_name`, `father_phone`, `father_workplace`, `address_registered`, `address_region`, `address_district`, `address_locality`, `address_street`, `address_house`, `address_actual`, `district`, `is_low_income`, `family_type`, `siblings_under_18`, `residence_type`, `is_nonresident`, `without_parental_care`, `created_at`, `updated_at`) VALUES
(2, 2, NULL, 'Иванов Петр Петрович', '8 923 704-71-74', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, NULL, 0, NULL, 0, 0, '2026-08-20 08:51:50', '2026-08-20 08:51:50'),
(3, 2, NULL, 'Власенко Татьяна Алексеевна', '', '', NULL, 'female', '', '', '', '', '', '', 'Новосибирская область, Карасукский округ, с. Ирбизино, ул.Школьная 29', 'Новосибирская область', 'Карасукский округ', 'с. Ирбизино', 'ул.Школьная 29', '', 'Общежитие, г. Карасук', 'Карасукский округ', 1, 'complete', 0, 'dormitory', 1, 0, '2026-08-20 09:03:44', '2026-08-23 13:23:48'),
(7, 2, 6, 'Смирнов Данил Владимирович', '8 923 704-71-74', '', '2006-06-22', 'male', 'Лобанова Раиса Дмитриевна', '892312345678', '', 'Лобанов Юрий Владимирович', '92345678912', '', 'Карасукский р-он, с. Ирбизино, ул. Школьная 32', '', '', '', '', '', 'ул. Фрунзе 89, комната 34', '', 0, NULL, 0, NULL, 0, 0, '2026-08-22 08:04:25', '2026-08-22 10:04:49'),
(8, 2, 29, 'Грицук Владимир Владимирович', '', '', NULL, 'male', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, NULL, 0, NULL, 0, 0, '2026-08-24 05:37:40', '2026-08-26 16:21:06'),
(9, 2, 30, 'Астахов Максим Викторович', '', '', NULL, 'male', '', '', '', '', '', '', 'Новосибирская область, Купинский район, Купино', 'Новосибирская область', 'Купинский район', 'Купино', '', '', 'г.Карасук, ул. Ленина 134, кв. 5', 'Купинский район', 0, 'complete', 0, 'apartment', 1, 0, '2026-08-24 11:55:59', '2026-08-24 11:55:59'),
(10, 5, 31, 'Сидоров Константин Иванович', '', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, NULL, 0, NULL, 0, 0, '2026-08-24 15:30:38', '2026-08-24 15:30:38'),
(11, 2, 32, 'Никитенко Кирил Никитович', '', '', NULL, 'male', '', '', '', '', '', '', 'Алтайский край, Бурлинский район, с. Бурла', 'Алтайский край', 'Бурлинский район', 'с. Бурла', '', '', 'Общежитие, г. Карасук', 'Бурлинский район', 1, 'complete', 3, 'dormitory', 1, 0, '2026-08-26 16:23:02', '2026-08-26 16:23:02'),
(12, 8, 33, 'Утеуов Бауржан Ермекович', '', '', '2006-03-09', 'male', '', '', '', '', '', '', '', '', '', '', '', '', 'Общежитие, г. Карасук', '', 1, 'no_father', 5, 'dormitory', 1, 0, '2026-08-26 16:27:35', '2026-08-26 16:27:36'),
(13, 8, 34, 'Исакова Марина Петровна', '', '', NULL, 'female', '', '', '', '', '', '', 'Новосибирская область, Карасук, Фрунзе, д. 73', 'Новосибирская область', '', 'Карасук', 'Фрунзе', '73', 'Карасук, ул. Фрунзе 73', 'Карасук', 0, 'complete', 1, 'family', 0, 0, '2026-08-26 16:29:18', '2026-08-26 16:29:18'),
(14, 8, 35, 'Шальнов Григорий Игоревич', '', '', NULL, 'male', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, NULL, 0, NULL, 0, 0, '2026-08-26 16:29:49', '2026-08-26 16:29:49'),
(15, 8, 36, 'Малахов Валентин Дмитриевич', '', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, NULL, 0, NULL, 0, 0, '2026-08-26 16:30:29', '2026-08-26 16:30:29'),
(16, 15, 37, 'Ветрова Светлана Кириловна', '', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 1, 'complete', 4, NULL, 0, 0, '2026-08-26 16:52:42', '2026-08-26 16:52:42'),
(17, 15, 38, 'Иванова Анастасия Михайловна', '', '', NULL, 'female', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, 'complete', 0, 'family', 0, 0, '2026-08-26 16:53:22', '2026-08-26 16:53:22'),
(18, 15, 39, 'Михайлова Татьяна Игоревна', '', '', NULL, 'female', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, NULL, 0, NULL, 0, 0, '2026-08-26 16:55:20', '2026-08-26 16:55:20');

-- --------------------------------------------------------

--
-- Структура таблицы `student_courseworks`
--

CREATE TABLE `student_courseworks` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `subject_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `topic` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `defense_date` date DEFAULT NULL,
  `teacher_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `grade` tinyint UNSIGNED DEFAULT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `student_gia`
--

CREATE TABLE `student_gia` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `form_type` enum('demo_exam','vkr') COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `points` decimal(8,2) DEFAULT NULL,
  `topic` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `defense_date` date DEFAULT NULL,
  `grade` tinyint UNSIGNED DEFAULT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `student_practices`
--

CREATE TABLE `student_practices` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `module_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `org_supervisor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `college_supervisor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `grade` tinyint UNSIGNED DEFAULT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `student_record_book`
--

CREATE TABLE `student_record_book` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `academic_year` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('1','2') COLLATE utf8mb4_unicode_ci NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `subject_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attestation_form` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `grade` tinyint UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `student_record_book`
--

INSERT INTO `student_record_book` (`id`, `student_id`, `academic_year`, `semester`, `curriculum_item_id`, `subject_name`, `teacher_name`, `attestation_form`, `grade`, `updated_at`) VALUES
(1, 3, '2025-2026', '1', 3, 'Иностранный язык', '', '', 5, '2026-08-22 10:16:21'),
(2, 3, '2025-2026', '1', 4, 'Физическая культура', '', '', 4, '2026-08-22 10:16:21'),
(3, 3, '2025-2026', '1', 8, 'Физика', '', '', 4, '2026-08-22 10:16:21'),
(4, 3, '2025-2026', '1', 9, 'История', '', '', 5, '2026-08-22 10:16:21'),
(5, 3, '2025-2026', '1', 10, 'Математика', '', '', 4, '2026-08-22 10:17:45'),
(6, 3, '2025-2026', '1', 11, 'Физкультура', '', '', 5, '2026-08-22 10:18:08'),
(7, 2, '2025-2026', '1', 3, 'Иностранный язык', '', '', 2, '2026-08-22 10:16:21'),
(8, 2, '2025-2026', '1', 4, 'Физическая культура', '', '', 2, '2026-08-22 10:16:21'),
(9, 2, '2025-2026', '1', 8, 'Физика', '', '', 4, '2026-08-22 10:16:21'),
(10, 2, '2025-2026', '1', 9, 'История', '', '', 4, '2026-08-22 10:16:21'),
(11, 2, '2025-2026', '1', 10, 'Математика', '', '', 2, '2026-08-24 05:21:19'),
(12, 2, '2025-2026', '1', 11, 'Физкультура', '', '', 2, '2026-08-22 10:16:21'),
(19, 7, '2025-2026', '1', 3, 'Иностранный язык', 'Полянский Александр Сергеевич', 'diff_credit', 4, '2026-08-24 18:23:28'),
(20, 7, '2025-2026', '1', 4, 'Физическая культура', 'Полянский Александр Сергеевич', '', 3, '2026-08-24 18:08:30'),
(21, 7, '2025-2026', '1', 8, 'Физика', 'Полянский Александр Сергеевич', '', 2, '2026-08-24 18:08:30'),
(22, 7, '2025-2026', '1', 9, 'История', 'Полянский Александр Сергеевич', 'exam', 4, '2026-08-24 18:29:08'),
(23, 7, '2025-2026', '1', 10, 'Математика', 'Полянский Александр Сергеевич', '', 3, '2026-08-24 18:08:30'),
(24, 7, '2025-2026', '1', 11, 'Физкультура', 'Полянский Александр Сергеевич', '', 2, '2026-08-24 18:08:30'),
(98, 10, '2025-2026', '1', 3, 'Иностранный язык', '', '', 3, '2026-08-24 15:30:38'),
(99, 10, '2025-2026', '1', 9, 'История', '', '', 3, '2026-08-24 15:30:38'),
(100, 10, '2025-2026', '1', 10, 'Математика', '', '', 4, '2026-08-24 15:30:38'),
(101, 10, '2025-2026', '1', 8, 'Физика', '', '', 4, '2026-08-24 15:30:38'),
(102, 10, '2025-2026', '1', 4, 'Физическая культура', '', '', 3, '2026-08-24 15:30:38'),
(103, 10, '2025-2026', '1', 11, 'Физкультура', '', '', 2, '2026-08-24 15:30:38');

-- --------------------------------------------------------

--
-- Структура таблицы `student_transfers`
--

CREATE TABLE `student_transfers` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `from_group_id` int UNSIGNED DEFAULT NULL,
  `from_group_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `to_group_id` int UNSIGNED DEFAULT NULL,
  `to_group_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `additional_info` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `transferred_by` int UNSIGNED DEFAULT NULL,
  `transferred_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `study_groups`
--

CREATE TABLE `study_groups` (
  `id` int UNSIGNED NOT NULL,
  `number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialty_id` int UNSIGNED NOT NULL,
  `curator_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `study_groups`
--

INSERT INTO `study_groups` (`id`, `number`, `specialty_id`, `curator_id`, `created_at`, `updated_at`) VALUES
(2, '201', 2, 3, '2026-08-20 06:29:12', '2026-08-23 04:25:59'),
(5, '202', 2, NULL, '2026-08-21 13:19:37', '2026-08-21 13:19:37'),
(6, '401', 2, NULL, '2026-08-21 13:23:24', '2026-08-23 05:37:32'),
(7, '402', 2, NULL, '2026-08-24 08:13:16', '2026-08-24 08:13:16'),
(8, '221', 5, NULL, '2026-08-24 08:13:24', '2026-08-24 08:13:24'),
(9, '321', 5, NULL, '2026-08-24 08:13:33', '2026-08-24 08:13:33'),
(10, '421', 5, NULL, '2026-08-24 08:13:41', '2026-08-24 08:13:41'),
(11, '301', 2, NULL, '2026-08-24 08:16:55', '2026-08-24 08:16:55'),
(12, '302', 2, NULL, '2026-08-24 08:17:12', '2026-08-24 08:17:12'),
(13, '241', 6, NULL, '2026-08-24 08:20:45', '2026-08-24 08:20:45'),
(14, '341', 6, NULL, '2026-08-24 08:20:58', '2026-08-24 08:20:58'),
(15, '441', 6, NULL, '2026-08-24 08:21:05', '2026-08-24 08:21:05');

-- --------------------------------------------------------

--
-- Структура таблицы `subjects`
--

CREATE TABLE `subjects` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `created_at`) VALUES
(1, 'Математика', '2026-08-20 06:50:48'),
(2, 'Физкультура', '2026-08-20 06:50:48'),
(3, 'Иностранный язык', '2026-08-20 07:01:55'),
(4, 'Физическая культура', '2026-08-20 07:02:31'),
(5, 'Алгебра', '2026-08-20 09:13:27'),
(6, 'История', '2026-08-20 09:13:27'),
(7, 'Физика', '2026-08-20 09:13:27');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_plain` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `education` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `additional_info` text COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'icon:default',
  `role` enum('admin','teacher','student') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'teacher',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `password_plain`, `full_name`, `phone`, `position`, `education`, `additional_info`, `avatar`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin@kpk.local', '$2y$10$34gEnq6LlCeGWDkQ0ECCpu7qKBt.zDo4.MtjoMGBhILGM5JC3L/aW', NULL, 'Шнайдер Антон Адамович', '', '', '', '', 'icon:default', 'admin', 1, '2026-08-20 05:30:42', '2026-08-21 14:15:35'),
(3, 'teacher@mail.ru', '$2y$10$ju7juIUxXMVomSug0cVx5e4CcWMEc/Vitr8OUuehl0WzueckWtf/K', '4365нкун547', 'Полянский Александр Сергеевич', '+7 900 000-00-00', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-20 05:36:37', '2026-08-23 04:26:44'),
(6, 'smirnov.danil@student.local', '$2y$10$IyQrR8asZwdy4Pwr/n/75uVMRaNXQlD5JVx2/NTKYSgTTRj222oUO', 'ctYjYds4', 'Смирнов Данил Владимирович', '8 923 704-71-74', '', '', NULL, 'file:u6_91414fb23baa39bf.png', 'student', 1, '2026-08-22 08:04:25', '2026-08-22 10:04:49'),
(7, '79137180652@phone.local', '$2y$10$8S2cNS2g4jty80pWai2aVOovz3xUvd5YpW.NYc0.R.7pycP8AWacW', 'BWI324994j', 'Баштанов Виктор Иванович', '+7 913 718 06 52', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 04:31:56', '2026-08-23 04:31:56'),
(8, '79237031677@phone.local', '$2y$10$XGchrOiWOay.ar7U9NR7rujQPnvYp1OICkjflztd9uRWG3PFklQcq', 'ta78E2Wb', 'Гутова Наталья Владимировна', '+7 923 703 16 77', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 04:35:27', '2026-08-23 04:35:27'),
(9, '79231179233@phone.local', '$2y$10$RiUxVTAEdLw9TubCflbNIODuj040hptEa023abp1WfWDDplxYMITq', 'C5QETEeT', 'Сополева Мария Евгеньевна', '+7 923 117 92 33', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 04:36:48', '2026-08-23 04:36:48'),
(10, '79930325388@phone.local', '$2y$10$dOHcLKWZnjtimrcic1nmh.3rGmH7DE4ZYgcGQjnorKBPq2MV7nKnm', 'h8Fek4Xk', 'Сулима Елена Васильевна', '+7 993 032 53 88', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 04:37:56', '2026-08-23 04:37:56'),
(12, '79232327519@phone.local', '$2y$10$MjeCB9Wx2/3I8aDnL13U4.vLQzYQoseMrTBfKu7k7S8NT6BQxJu.2', 'RFbajaUx', 'Сусленкова Лилия Ивановна', '+7 923 232 75 19', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 06:24:54', '2026-08-23 06:24:54'),
(13, '79139309077@phone.local', '$2y$10$fJsqxsNt475QPLk/08GqgudyX9QYa6hzifik0eoKINZZrOKBW6gKq', 'Rm59cJN5', 'Толстых Аркадий Геннадьевич', '+7 913 930 90 77', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 06:25:54', '2026-08-23 06:25:54'),
(14, '79231208261@phone.local', '$2y$10$NlV/AhLUuNKdhL4KK5mQRuVBaqtL.Hcr9wVHiTx/acr7fH4k18u8q', '95ZaM59z', 'Шуст Марина Николаевна', '+7 923 120 82 61', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 06:27:23', '2026-08-23 06:27:23'),
(15, '79231167030@phone.local', '$2y$10$yx9SThdLZSQCjciU6tPbWuB66BbWmJFbmZ9D2FNE.H.wLpV4IpcBS', 'sRWPJQwM', 'Эккерт Татьяна Анатольевна', '+7 923 116 70 30', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 06:31:46', '2026-08-23 06:31:46'),
(16, '79137190935@phone.local', '$2y$10$zFx68cE.d0G.ONTUyigGEOYUeEeAlkSlv3pLIwpu/DFBsICHVW7py', 'zVYJUkuT', 'Демидов Денис Петрович', '+7 913 719 09 35', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 06:32:37', '2026-08-23 06:32:37'),
(17, '79639468542@phone.local', '$2y$10$RmzdwSWlYpDG9fxjf/.HfOZu4dRhB.HmEFNTdoeuzlxg0EaxNnrha', 'ymZkwHd4', 'Предатченко Ольга Сергеевна', '+7 963 946 85 42', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:04:03', '2026-08-23 08:04:03'),
(18, '79137725424@phone.local', '$2y$10$aPEW3eGD7jLiaTrUWzY.GOT29asWOIUPZ4kjnn1/3ge59x33Bmdhm', 'jcpV7qaB', 'Фоменко Татьяна Михайловна', '+7 913 772 54 24', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:06:12', '2026-08-23 08:06:12'),
(19, '79231665319@phone.local', '$2y$10$hye2QyjC4jBT8uqiXfZTGOMGHdtvadrNsyf8AloaM17CrYF6Ddb6u', 'kbuvFC5Q', 'Шумаков Василий Дмитриевич', '+7 923 166 53 19', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:08:40', '2026-08-23 08:08:40'),
(20, '79039386799@phone.local', '$2y$10$agkMU3qHrhLCwVm51mNMpuPd56SEQ1GnB6ZCYEu3pqAMmAA.wT22W', 'NmUes2eQ', 'Сухоруков Дмитрий Сергеевич', '+7 903 938 67 99', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:09:39', '2026-08-23 08:09:39'),
(21, '79293936723@phone.local', '$2y$10$Yp//sEjeYbAtgqAqMbLQU.p1aPv7Mj9cBCnu5.kOLRMSrwWstwfzi', 'AqcA977q', 'Руденко Елена Федоровна', '+7 929 393 67 23', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:11:21', '2026-08-23 08:11:21'),
(22, '79831231556@phone.local', '$2y$10$vMHww5Npl.atU6Uzg02dTOdxxskS9LQP3CgOMqDc1Fm71VjhwjBiO', '9Svmy7rP', 'Нестеренко Наталья Николаевна', '+7 983 123 15 56', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:12:16', '2026-08-23 08:12:16'),
(23, '79095319910@phone.local', '$2y$10$qucky/O0zLy600hm8ICXoupugWcusz1enw3mZiBHL3U4vaVmG7Sx.', '5NtA4rFH', 'Пискарева Татьяна Борисовна', '+7 909 531 99 10', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:12:51', '2026-08-23 08:12:51'),
(24, '79231900360@phone.local', '$2y$10$Wbkd5GxWbHPkZhhgcfo.3OEh/.7KdcqMA9pk9TVlGF9Sln.U2BoeC', 'mnAd6ZxH', 'Шарушинская Ольга Владимировна', '+7 923 190 03 60', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:13:25', '2026-08-23 08:13:25'),
(25, '79231122723@phone.local', '$2y$10$x8DnjVQLk.qZyqsIhWnQTOdI4xhOJZiqDTpz983npkadjnIBTjUkO', 'DhdBSbpb', 'Ткаченко Светлана Михайловна', '+7 923 112 27 23', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:14:26', '2026-08-23 08:14:26'),
(26, '79232323819@phone.local', '$2y$10$ZfXFbQfvNVbWcQa1W5DA2Olr4.DsCz1eqOdl8z8H8FbF.k1onoB/O', 'ZcfXYdXp', 'Кузнецова Ольга Николаевна', '+7 923 232 38 19', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:14:55', '2026-08-23 08:14:55'),
(27, '79513969858@phone.local', '$2y$10$GU6ARTQwSVgImJsMg9xJeOCFBob176RSSO7o4cY/Go9kjJfroPdTG', 'ryVEqCPk', 'Ишутина Алла Федоровна', '+7 951 396 98 58', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:15:25', '2026-08-23 08:15:25'),
(28, '79231488053@phone.local', '$2y$10$pT2OSQNeBdN7eyAP1xRHF.uPkyNf.RMO7RnEV5Jq19eVz8T5u.OcG', 'pHjt6m5s', 'Вульф Дарья Александровна', '+7 923 148 80 53', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:16:04', '2026-08-23 08:16:04'),
(29, 'gritsuk.vladimio@student.local', '$2y$10$yLnaIqJRgFOhudK1xvVbKOQ3udY6OxCpk/vJBdNIjwXMcER5uhid.', '8vAvaJwJ', 'Грицук Владимир Владимирович', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-24 05:37:40', '2026-08-26 16:21:06'),
(30, 'astahov.maksim@student.local', '$2y$10$gqitL6s7VE6WvgQWZFrmPud5DIwoQw1ytTAyfEpwO.BMC8dSVNzq6', 'BaNKcza7', 'Астахов Максим Викторович', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-24 11:55:59', '2026-08-24 11:55:59'),
(31, 'sidorov.konstantin@student.local', '$2y$10$BN6fltkeiF/O4Zk5QaF4he6rcYr5Lsvv6NIcI4kzdXsrF6jDccmQq', 'AZST55ZG', 'Сидоров Константин Иванович', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-24 15:30:38', '2026-08-24 15:30:38'),
(32, 'nikitenko.kiril@student.local', '$2y$10$YXVg8b7LshgUAiw9u0vnh.f3ZITEFoQLdBNHR8hqxyg9SOQxIMlfq', 'xzGfYKfx', 'Никитенко Кирил Никитович', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:23:02', '2026-08-26 16:23:02'),
(33, 'uteuov.baurzhan@student.local', '$2y$10$TIUFqfV6Vqkvehp/4gEfLuTNlg2KUeqhe950QBgK/iKf1zxGhExyO', 'USzzn3EU', 'Утеуов Бауржан Ермекович', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:27:36', '2026-08-26 16:27:36'),
(34, 'isakova.marina@student.local', '$2y$10$0wzD7bzR96O34ZFR9rieC.qUTGC4gW7N5P1zKy/S6sFtdMLvybArC', '9aEhqKft', 'Исакова Марина Петровна', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:29:18', '2026-08-26 16:29:18'),
(35, 'shalnov.grigoriy@student.local', '$2y$10$QOU57AFTlHChxYslTqfj7.mXubpVnQ6eswbkXllcy5US1mkMzMspe', 'GarDtrtS', 'Шальнов Григорий Игоревич', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:29:49', '2026-08-26 16:29:49'),
(36, 'malahov.valentin@student.local', '$2y$10$gwQkTsaaUfUd7rgMZxzDjuCkmLS8wYBLFWfbQhoGsVix1jSpkSCJO', 'wKr5bctc', 'Малахов Валентин Дмитриевич', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:30:29', '2026-08-26 16:30:29'),
(37, 'vetrova.svetlana@student.local', '$2y$10$0waGm.Zv3ZcQo/kMpZJnFegkHIJKD.BlezqIWqHc2Mxzpi61cO1wy', 'BtcgMQQf', 'Ветрова Светлана Кириловна', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:52:42', '2026-08-26 16:52:42'),
(38, 'ivanova.anastasiya@student.local', '$2y$10$3RnmraZ1L33l/rfRa5q45.QROoa/SGVFlpMii0ibQcV/EwPVxAD8S', 'FNfafp4T', 'Иванова Анастасия Михайловна', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:53:22', '2026-08-26 16:53:22'),
(39, 'mihaylova.tatyana@student.local', '$2y$10$HVQGquIfS8O42Dcf8LrpvuyIj2UH0jMlkXMIKjmz5/ujT8HSEPhoy', '6k3smvZw', 'Михайлова Татьяна Игоревна', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:55:20', '2026-08-26 16:55:20');

-- --------------------------------------------------------

--
-- Структура таблицы `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int UNSIGNED NOT NULL,
  `role` enum('teacher','curator','deputy','educator') COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role`) VALUES
(3, 'teacher'),
(3, 'curator'),
(7, 'teacher'),
(7, 'deputy'),
(8, 'teacher'),
(8, 'deputy'),
(9, 'teacher'),
(9, 'curator'),
(9, 'educator'),
(10, 'educator'),
(12, 'teacher'),
(12, 'curator'),
(13, 'teacher'),
(14, 'teacher'),
(14, 'curator'),
(15, 'teacher'),
(16, 'teacher'),
(16, 'curator'),
(17, 'teacher'),
(18, 'teacher'),
(19, 'teacher'),
(20, 'teacher'),
(20, 'curator'),
(21, 'teacher'),
(22, 'teacher'),
(22, 'curator'),
(23, 'teacher'),
(24, 'teacher'),
(25, 'teacher'),
(26, 'teacher'),
(27, 'teacher'),
(28, 'teacher');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_created` (`created_at`),
  ADD KEY `idx_activity_logs_user` (`user_id`,`created_at`),
  ADD KEY `idx_activity_logs_action` (`action`,`created_at`),
  ADD KEY `idx_activity_logs_entity` (`entity_type`,`entity_id`);

--
-- Индексы таблицы `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Индексы таблицы `archive_gradebook_grades`
--
ALTER TABLE `archive_gradebook_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_archive_gb_grade` (`archive_id`,`student_id`,`curriculum_item_id`);

--
-- Индексы таблицы `archive_gradebook_groups`
--
ALTER TABLE `archive_gradebook_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archive_gb_groups` (`archive_id`,`group_id`);

--
-- Индексы таблицы `archive_gradebook_students`
--
ALTER TABLE `archive_gradebook_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archive_gb_students` (`archive_id`,`group_id`);

--
-- Индексы таблицы `archive_gradebook_subjects`
--
ALTER TABLE `archive_gradebook_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archive_gb_subjects` (`archive_id`,`group_id`);

--
-- Индексы таблицы `archive_grade_changes`
--
ALTER TABLE `archive_grade_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archive_gb_changes` (`archive_id`,`student_id`,`curriculum_item_id`);

--
-- Индексы таблицы `archive_journal_grades`
--
ALTER TABLE `archive_journal_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_archive_jl_grade` (`lesson_id`,`student_id`);

--
-- Индексы таблицы `archive_journal_items`
--
ALTER TABLE `archive_journal_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archive_jl_items` (`archive_id`,`group_id`);

--
-- Индексы таблицы `archive_journal_lessons`
--
ALTER TABLE `archive_journal_lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archive_jl_lessons` (`item_id`);

--
-- Индексы таблицы `archive_journal_students`
--
ALTER TABLE `archive_journal_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archive_jl_students` (`item_id`);

--
-- Индексы таблицы `archive_journal_topics`
--
ALTER TABLE `archive_journal_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archive_jl_topics` (`item_id`);

--
-- Индексы таблицы `archive_journal_totals`
--
ALTER TABLE `archive_journal_totals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_archive_jl_total` (`item_id`,`student_id`);

--
-- Индексы таблицы `archive_periods`
--
ALTER TABLE `archive_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_archive_period` (`archive_type`,`academic_year`,`semester`);

--
-- Индексы таблицы `attendance_days`
--
ALTER TABLE `attendance_days`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_attendance_group_date_year` (`group_id`,`attendance_date`,`academic_year`);

--
-- Индексы таблицы `attendance_entries`
--
ALTER TABLE `attendance_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_attendance_day_student` (`attendance_day_id`,`student_id`),
  ADD KEY `fk_attendance_entries_student` (`student_id`),
  ADD KEY `fk_attendance_entries_reason` (`reason_id`);

--
-- Индексы таблицы `attendance_reasons`
--
ALTER TABLE `attendance_reasons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `curriculum_items`
--
ALTER TABLE `curriculum_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_curriculum_plan_subject` (`curriculum_plan_id`,`subject_id`),
  ADD KEY `fk_curriculum_items_subject` (`subject_id`),
  ADD KEY `fk_curriculum_items_teacher` (`teacher_id`);

--
-- Индексы таблицы `curriculum_plans`
--
ALTER TABLE `curriculum_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_curriculum_group_year` (`group_id`,`academic_year`);

--
-- Индексы таблицы `expelled_courseworks`
--
ALTER TABLE `expelled_courseworks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expelled_courseworks` (`expelled_id`,`sort_order`);

--
-- Индексы таблицы `expelled_debts`
--
ALTER TABLE `expelled_debts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expelled_debts` (`expelled_id`);

--
-- Индексы таблицы `expelled_gia`
--
ALTER TABLE `expelled_gia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expelled_gia` (`expelled_id`,`form_type`,`sort_order`);

--
-- Индексы таблицы `expelled_practices`
--
ALTER TABLE `expelled_practices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expelled_practices` (`expelled_id`,`sort_order`);

--
-- Индексы таблицы `expelled_record_book`
--
ALTER TABLE `expelled_record_book`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expelled_rb` (`expelled_id`,`academic_year`,`semester`);

--
-- Индексы таблицы `expelled_restorations`
--
ALTER TABLE `expelled_restorations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expelled_restore` (`expelled_id`),
  ADD KEY `fk_expelled_restore_by` (`restored_by`);

--
-- Индексы таблицы `expelled_students`
--
ALTER TABLE `expelled_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expelled_name` (`full_name`),
  ADD KEY `idx_expelled_restored` (`is_restored`),
  ADD KEY `fk_expelled_by` (`expelled_by`);

--
-- Индексы таблицы `glaz_commission_members`
--
ALTER TABLE `glaz_commission_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_glaz_comm` (`schedule_id`,`teacher_id`),
  ADD KEY `idx_glaz_comm_schedule` (`schedule_id`),
  ADD KEY `fk_glaz_comm_teacher` (`teacher_id`);

--
-- Индексы таблицы `glaz_schedules`
--
ALTER TABLE `glaz_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_glaz_debt` (`student_id`,`curriculum_item_id`,`academic_year`,`semester`),
  ADD KEY `idx_glaz_period` (`academic_year`,`semester`),
  ADD KEY `fk_glaz_item` (`curriculum_item_id`),
  ADD KEY `fk_glaz_updated_by` (`updated_by`);

--
-- Индексы таблицы `grade_entries`
--
ALTER TABLE `grade_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_grade_student_item` (`student_id`,`curriculum_item_id`),
  ADD KEY `fk_grade_entries_curriculum_item` (`curriculum_item_id`);

--
-- Индексы таблицы `group_promotions`
--
ALTER TABLE `group_promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_group_promotions_group` (`group_id`);

--
-- Индексы таблицы `journal_grades`
--
ALTER TABLE `journal_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_journal_lesson_student` (`lesson_id`,`student_id`),
  ADD KEY `fk_journal_grades_student` (`student_id`);

--
-- Индексы таблицы `journal_lessons`
--
ALTER TABLE `journal_lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_journal_lessons_ktp_topic` (`ktp_topic_id`),
  ADD KEY `idx_journal_item_id` (`curriculum_item_id`),
  ADD KEY `idx_journal_item_date` (`curriculum_item_id`,`lesson_date`);

--
-- Индексы таблицы `ktp_topics`
--
ALTER TABLE `ktp_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ktp_topics_item` (`curriculum_item_id`);

--
-- Индексы таблицы `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_recipient` (`recipient_id`,`created_at`),
  ADD KEY `idx_notifications_type` (`notification_type`,`created_at`),
  ADD KEY `fk_notifications_sender` (`sender_id`);

--
-- Индексы таблицы `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_notification_read` (`notification_id`,`user_id`),
  ADD KEY `idx_notification_reads_user` (`user_id`);

--
-- Индексы таблицы `organization`
--
ALTER TABLE `organization`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `specialties`
--
ALTER TABLE `specialties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Индексы таблицы `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_students_user` (`user_id`),
  ADD KEY `fk_students_group` (`group_id`);

--
-- Индексы таблицы `student_courseworks`
--
ALTER TABLE `student_courseworks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_courseworks` (`student_id`,`sort_order`);

--
-- Индексы таблицы `student_gia`
--
ALTER TABLE `student_gia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_gia` (`student_id`,`form_type`,`sort_order`);

--
-- Индексы таблицы `student_practices`
--
ALTER TABLE `student_practices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_practices` (`student_id`,`sort_order`);

--
-- Индексы таблицы `student_record_book`
--
ALTER TABLE `student_record_book`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_rb` (`student_id`,`academic_year`,`semester`,`curriculum_item_id`),
  ADD KEY `idx_student_rb_student` (`student_id`);

--
-- Индексы таблицы `student_transfers`
--
ALTER TABLE `student_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_transfers_student` (`student_id`),
  ADD KEY `fk_student_transfers_by` (`transferred_by`);

--
-- Индексы таблицы `study_groups`
--
ALTER TABLE `study_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `number` (`number`),
  ADD KEY `fk_study_groups_specialty` (`specialty_id`),
  ADD KEY `fk_study_groups_curator` (`curator_id`);

--
-- Индексы таблицы `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT для таблицы `archive_gradebook_grades`
--
ALTER TABLE `archive_gradebook_grades`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT для таблицы `archive_gradebook_groups`
--
ALTER TABLE `archive_gradebook_groups`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `archive_gradebook_students`
--
ALTER TABLE `archive_gradebook_students`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `archive_gradebook_subjects`
--
ALTER TABLE `archive_gradebook_subjects`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `archive_grade_changes`
--
ALTER TABLE `archive_grade_changes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `archive_journal_grades`
--
ALTER TABLE `archive_journal_grades`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=331;

--
-- AUTO_INCREMENT для таблицы `archive_journal_items`
--
ALTER TABLE `archive_journal_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT для таблицы `archive_journal_lessons`
--
ALTER TABLE `archive_journal_lessons`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT для таблицы `archive_journal_students`
--
ALTER TABLE `archive_journal_students`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT для таблицы `archive_journal_topics`
--
ALTER TABLE `archive_journal_topics`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT для таблицы `archive_journal_totals`
--
ALTER TABLE `archive_journal_totals`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT для таблицы `archive_periods`
--
ALTER TABLE `archive_periods`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `attendance_days`
--
ALTER TABLE `attendance_days`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `attendance_entries`
--
ALTER TABLE `attendance_entries`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблицы `attendance_reasons`
--
ALTER TABLE `attendance_reasons`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `curriculum_items`
--
ALTER TABLE `curriculum_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `curriculum_plans`
--
ALTER TABLE `curriculum_plans`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `expelled_courseworks`
--
ALTER TABLE `expelled_courseworks`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `expelled_debts`
--
ALTER TABLE `expelled_debts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `expelled_gia`
--
ALTER TABLE `expelled_gia`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `expelled_practices`
--
ALTER TABLE `expelled_practices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `expelled_record_book`
--
ALTER TABLE `expelled_record_book`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `expelled_restorations`
--
ALTER TABLE `expelled_restorations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `expelled_students`
--
ALTER TABLE `expelled_students`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `glaz_commission_members`
--
ALTER TABLE `glaz_commission_members`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблицы `glaz_schedules`
--
ALTER TABLE `glaz_schedules`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `grade_entries`
--
ALTER TABLE `grade_entries`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблицы `group_promotions`
--
ALTER TABLE `group_promotions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `journal_grades`
--
ALTER TABLE `journal_grades`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=366;

--
-- AUTO_INCREMENT для таблицы `journal_lessons`
--
ALTER TABLE `journal_lessons`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT для таблицы `ktp_topics`
--
ALTER TABLE `ktp_topics`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `notification_reads`
--
ALTER TABLE `notification_reads`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `specialties`
--
ALTER TABLE `specialties`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `students`
--
ALTER TABLE `students`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `student_courseworks`
--
ALTER TABLE `student_courseworks`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `student_gia`
--
ALTER TABLE `student_gia`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `student_practices`
--
ALTER TABLE `student_practices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `student_record_book`
--
ALTER TABLE `student_record_book`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=379;

--
-- AUTO_INCREMENT для таблицы `student_transfers`
--
ALTER TABLE `student_transfers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `study_groups`
--
ALTER TABLE `study_groups`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблицы `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `archive_gradebook_grades`
--
ALTER TABLE `archive_gradebook_grades`
  ADD CONSTRAINT `fk_archive_gb_grades_archive` FOREIGN KEY (`archive_id`) REFERENCES `archive_periods` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `archive_gradebook_groups`
--
ALTER TABLE `archive_gradebook_groups`
  ADD CONSTRAINT `fk_archive_gb_groups_archive` FOREIGN KEY (`archive_id`) REFERENCES `archive_periods` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `archive_gradebook_students`
--
ALTER TABLE `archive_gradebook_students`
  ADD CONSTRAINT `fk_archive_gb_students_archive` FOREIGN KEY (`archive_id`) REFERENCES `archive_periods` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `archive_gradebook_subjects`
--
ALTER TABLE `archive_gradebook_subjects`
  ADD CONSTRAINT `fk_archive_gb_subjects_archive` FOREIGN KEY (`archive_id`) REFERENCES `archive_periods` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `archive_grade_changes`
--
ALTER TABLE `archive_grade_changes`
  ADD CONSTRAINT `fk_archive_gb_changes_archive` FOREIGN KEY (`archive_id`) REFERENCES `archive_periods` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `archive_journal_grades`
--
ALTER TABLE `archive_journal_grades`
  ADD CONSTRAINT `fk_archive_jl_grades_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `archive_journal_lessons` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `archive_journal_items`
--
ALTER TABLE `archive_journal_items`
  ADD CONSTRAINT `fk_archive_jl_items_archive` FOREIGN KEY (`archive_id`) REFERENCES `archive_periods` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `archive_journal_lessons`
--
ALTER TABLE `archive_journal_lessons`
  ADD CONSTRAINT `fk_archive_jl_lessons_item` FOREIGN KEY (`item_id`) REFERENCES `archive_journal_items` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `archive_journal_students`
--
ALTER TABLE `archive_journal_students`
  ADD CONSTRAINT `fk_archive_jl_students_item` FOREIGN KEY (`item_id`) REFERENCES `archive_journal_items` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `archive_journal_topics`
--
ALTER TABLE `archive_journal_topics`
  ADD CONSTRAINT `fk_archive_jl_topics_item` FOREIGN KEY (`item_id`) REFERENCES `archive_journal_items` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `archive_journal_totals`
--
ALTER TABLE `archive_journal_totals`
  ADD CONSTRAINT `fk_archive_jl_totals_item` FOREIGN KEY (`item_id`) REFERENCES `archive_journal_items` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `attendance_days`
--
ALTER TABLE `attendance_days`
  ADD CONSTRAINT `fk_attendance_days_group` FOREIGN KEY (`group_id`) REFERENCES `study_groups` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `attendance_entries`
--
ALTER TABLE `attendance_entries`
  ADD CONSTRAINT `fk_attendance_entries_day` FOREIGN KEY (`attendance_day_id`) REFERENCES `attendance_days` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_entries_reason` FOREIGN KEY (`reason_id`) REFERENCES `attendance_reasons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_attendance_entries_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `curriculum_items`
--
ALTER TABLE `curriculum_items`
  ADD CONSTRAINT `fk_curriculum_items_plan` FOREIGN KEY (`curriculum_plan_id`) REFERENCES `curriculum_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_curriculum_items_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_curriculum_items_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `curriculum_plans`
--
ALTER TABLE `curriculum_plans`
  ADD CONSTRAINT `fk_curriculum_plans_group` FOREIGN KEY (`group_id`) REFERENCES `study_groups` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `expelled_courseworks`
--
ALTER TABLE `expelled_courseworks`
  ADD CONSTRAINT `fk_expelled_courseworks` FOREIGN KEY (`expelled_id`) REFERENCES `expelled_students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `expelled_debts`
--
ALTER TABLE `expelled_debts`
  ADD CONSTRAINT `fk_expelled_debts` FOREIGN KEY (`expelled_id`) REFERENCES `expelled_students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `expelled_gia`
--
ALTER TABLE `expelled_gia`
  ADD CONSTRAINT `fk_expelled_gia` FOREIGN KEY (`expelled_id`) REFERENCES `expelled_students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `expelled_practices`
--
ALTER TABLE `expelled_practices`
  ADD CONSTRAINT `fk_expelled_practices` FOREIGN KEY (`expelled_id`) REFERENCES `expelled_students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `expelled_record_book`
--
ALTER TABLE `expelled_record_book`
  ADD CONSTRAINT `fk_expelled_rb` FOREIGN KEY (`expelled_id`) REFERENCES `expelled_students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `expelled_restorations`
--
ALTER TABLE `expelled_restorations`
  ADD CONSTRAINT `fk_expelled_restore` FOREIGN KEY (`expelled_id`) REFERENCES `expelled_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_expelled_restore_by` FOREIGN KEY (`restored_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `expelled_students`
--
ALTER TABLE `expelled_students`
  ADD CONSTRAINT `fk_expelled_by` FOREIGN KEY (`expelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `glaz_commission_members`
--
ALTER TABLE `glaz_commission_members`
  ADD CONSTRAINT `fk_glaz_comm_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `glaz_schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_glaz_comm_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `glaz_schedules`
--
ALTER TABLE `glaz_schedules`
  ADD CONSTRAINT `fk_glaz_item` FOREIGN KEY (`curriculum_item_id`) REFERENCES `curriculum_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_glaz_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_glaz_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `grade_entries`
--
ALTER TABLE `grade_entries`
  ADD CONSTRAINT `fk_grade_entries_curriculum_item` FOREIGN KEY (`curriculum_item_id`) REFERENCES `curriculum_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grade_entries_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `group_promotions`
--
ALTER TABLE `group_promotions`
  ADD CONSTRAINT `fk_group_promotions_group` FOREIGN KEY (`group_id`) REFERENCES `study_groups` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `journal_grades`
--
ALTER TABLE `journal_grades`
  ADD CONSTRAINT `fk_journal_grades_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `journal_lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_journal_grades_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `journal_lessons`
--
ALTER TABLE `journal_lessons`
  ADD CONSTRAINT `fk_journal_lessons_item` FOREIGN KEY (`curriculum_item_id`) REFERENCES `curriculum_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_journal_lessons_ktp_topic` FOREIGN KEY (`ktp_topic_id`) REFERENCES `ktp_topics` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `ktp_topics`
--
ALTER TABLE `ktp_topics`
  ADD CONSTRAINT `fk_ktp_topics_item` FOREIGN KEY (`curriculum_item_id`) REFERENCES `curriculum_items` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifications_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD CONSTRAINT `fk_notification_reads_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notification_reads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_group` FOREIGN KEY (`group_id`) REFERENCES `study_groups` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `student_courseworks`
--
ALTER TABLE `student_courseworks`
  ADD CONSTRAINT `fk_student_courseworks_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `student_gia`
--
ALTER TABLE `student_gia`
  ADD CONSTRAINT `fk_student_gia_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `student_practices`
--
ALTER TABLE `student_practices`
  ADD CONSTRAINT `fk_student_practices_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `student_record_book`
--
ALTER TABLE `student_record_book`
  ADD CONSTRAINT `fk_student_rb_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `student_transfers`
--
ALTER TABLE `student_transfers`
  ADD CONSTRAINT `fk_student_transfers_by` FOREIGN KEY (`transferred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_student_transfers_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `study_groups`
--
ALTER TABLE `study_groups`
  ADD CONSTRAINT `fk_study_groups_curator` FOREIGN KEY (`curator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_study_groups_specialty` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
