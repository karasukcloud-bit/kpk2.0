-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Авг 27 2026 г., 19:13
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
-- База данных: `spo_progress`
--

-- --------------------------------------------------------

--
-- Структура таблицы `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `action` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `entity_id` int UNSIGNED DEFAULT NULL,
  `group_id` int UNSIGNED DEFAULT NULL,
  `details_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
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
(115, 1, 'journal.grade_save', 'journal_grade', 59, 15, '{\"curriculum_item_id\":17,\"group_id\":15,\"group_number\":\"441\",\"subject_name\":\"Математика\",\"academic_year\":\"2025-2026\",\"lesson_id\":59,\"lesson_date\":\"14.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"\",\"student_name\":\"Иванова Анастасия\",\"old_mark\":\"—\",\"mark\":\"3\"}', '127.0.0.1', '2026-08-26 17:00:21'),
(116, 1, 'journal.lesson_add', 'journal_lesson', 60, 2, '{\"curriculum_item_id\":9,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"История\",\"academic_year\":\"2025-2026\",\"lesson_id\":60,\"lesson_date\":\"27.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Промежуточная аттестация. Экзамен\"}', '127.0.0.1', '2026-08-27 04:36:37'),
(117, 1, 'journal.grade_save', 'journal_grade', 25, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":25,\"lesson_date\":\"23.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Чтение по ролям\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-27 05:18:05'),
(118, 16, 'student.create', 'student', 19, 9, '{\"student_name\":\"Алешина Эвеленина Игоревна\",\"group_number\":\"321\"}', '127.0.0.1', '2026-08-27 05:37:54'),
(119, 16, 'student.create', 'student', 20, 9, '{\"student_name\":\"Иванов Иван Иванович\",\"group_number\":\"321\"}', '127.0.0.1', '2026-08-27 06:26:58'),
(120, 16, 'journal.lesson_add', 'journal_lesson', 61, 2, '{\"curriculum_item_id\":11,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Физкультура\",\"academic_year\":\"2025-2026\",\"lesson_id\":61,\"lesson_date\":\"27.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"Виды легкой атлетики\"}', '127.0.0.1', '2026-08-27 08:19:27'),
(121, 16, 'journal.grade_save', 'journal_grade', 11, 2, '{\"curriculum_item_id\":11,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Физкультура\",\"academic_year\":\"2025-2026\",\"lesson_id\":11,\"lesson_date\":\"21.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Техника безопасности на уроках ФК\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"5\"}', '127.0.0.1', '2026-08-27 08:19:36'),
(122, 16, 'journal.grade_save', 'journal_grade', 61, 2, '{\"curriculum_item_id\":11,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Физкультура\",\"academic_year\":\"2025-2026\",\"lesson_id\":61,\"lesson_date\":\"27.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"Виды легкой атлетики\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-27 08:19:38'),
(123, 16, 'journal.grade_save', 'journal_grade', 11, 2, '{\"curriculum_item_id\":11,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Физкультура\",\"academic_year\":\"2025-2026\",\"lesson_id\":11,\"lesson_date\":\"21.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Техника безопасности на уроках ФК\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"5\",\"mark\":\"5\",\"extra\":\"активность\"}', '127.0.0.1', '2026-08-27 08:19:43'),
(124, 16, 'journal.grade_save', 'journal_grade', 21, 2, '{\"curriculum_item_id\":11,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Физкультура\",\"academic_year\":\"2025-2026\",\"lesson_id\":21,\"lesson_date\":\"22.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Техника безопасности на уроках ФК\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"—\",\"extra\":\"опоздание\"}', '127.0.0.1', '2026-08-27 08:19:47'),
(125, 16, 'journal.lesson_add', 'journal_lesson', 62, 2, '{\"curriculum_item_id\":11,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Физкультура\",\"academic_year\":\"2025-2026\",\"lesson_id\":62,\"lesson_date\":\"27.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Физическая культура в общекультурной и профессиональной подготовке студентов\"}', '127.0.0.1', '2026-08-27 08:20:09'),
(126, 16, 'journal.grade_save', 'journal_grade', 62, 2, '{\"curriculum_item_id\":11,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Физкультура\",\"academic_year\":\"2025-2026\",\"lesson_id\":62,\"lesson_date\":\"27.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Физическая культура в общекультурной и профессиональной подготовке студентов\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"2\"}', '127.0.0.1', '2026-08-27 08:20:13'),
(127, 16, 'journal.grade_save', 'journal_grade', 11, 2, '{\"curriculum_item_id\":11,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Физкультура\",\"academic_year\":\"2025-2026\",\"lesson_id\":11,\"lesson_date\":\"21.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Техника безопасности на уроках ФК\",\"student_name\":\"Иванов Петр\",\"old_mark\":\"—\",\"mark\":\"5\"}', '127.0.0.1', '2026-08-27 08:20:37'),
(128, 16, 'journal.grade_save', 'journal_grade', 61, 2, '{\"curriculum_item_id\":11,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Физкультура\",\"academic_year\":\"2025-2026\",\"lesson_id\":61,\"lesson_date\":\"27.08.2026\",\"grade_type_label\":\"Контрольная\",\"topic_title\":\"Виды легкой атлетики\",\"student_name\":\"Иванов Петр\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-27 08:20:40'),
(129, 16, 'journal.grade_save', 'journal_grade', 11, 2, '{\"curriculum_item_id\":11,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Физкультура\",\"academic_year\":\"2025-2026\",\"lesson_id\":11,\"lesson_date\":\"21.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Техника безопасности на уроках ФК\",\"student_name\":\"Никитенко Кирил\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-27 08:20:46'),
(130, 16, 'journal.grade_save', 'journal_grade', 62, 2, '{\"curriculum_item_id\":11,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Физкультура\",\"academic_year\":\"2025-2026\",\"lesson_id\":62,\"lesson_date\":\"27.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Физическая культура в общекультурной и профессиональной подготовке студентов\",\"student_name\":\"Никитенко Кирил\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-27 08:20:48'),
(131, 1, 'journal.grade_save', 'journal_grade', 27, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":27,\"lesson_date\":\"23.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Чтение текста\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"Н\"}', '127.0.0.1', '2026-08-27 09:00:43'),
(132, 1, 'journal.grade_save', 'journal_grade', 19, 2, '{\"curriculum_item_id\":3,\"group_id\":2,\"group_number\":\"201\",\"subject_name\":\"Иностранный язык\",\"academic_year\":\"2025-2026\",\"lesson_id\":19,\"lesson_date\":\"23.08.2026\",\"grade_type_label\":\"Текущая\",\"topic_title\":\"Грамматика английского языка\",\"student_name\":\"Астахов Максим\",\"old_mark\":\"—\",\"mark\":\"4\"}', '127.0.0.1', '2026-08-27 09:01:26'),
(133, 3, 'student.delete', 'student', 11, 2, '{\"student_name\":\"Никитенко Кирил Никитович\",\"group_number\":\"201\"}', '127.0.0.1', '2026-08-27 18:35:18');

-- --------------------------------------------------------

--
-- Структура таблицы `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
(40, 11, 2, 9, 3, 4),
(41, 11, 2, 9, 4, 3),
(42, 11, 2, 9, 9, 2),
(43, 11, 2, 9, 10, 2),
(44, 11, 2, 9, 11, 4),
(45, 11, 2, 3, 3, 5),
(46, 11, 2, 3, 4, 4),
(47, 11, 2, 3, 9, 5),
(48, 11, 2, 3, 10, 4),
(49, 11, 2, 3, 11, 2),
(50, 11, 2, 8, 3, 4),
(51, 11, 2, 8, 4, 2),
(52, 11, 2, 8, 9, 2),
(53, 11, 2, 8, 10, 2),
(54, 11, 2, 8, 11, 2),
(55, 11, 2, 2, 3, 2),
(56, 11, 2, 2, 4, 2),
(57, 11, 2, 2, 9, 4),
(58, 11, 2, 2, 10, 2),
(59, 11, 2, 2, 11, 5),
(60, 11, 2, 11, 3, 2),
(61, 11, 2, 11, 4, 2),
(62, 11, 2, 11, 9, 2),
(63, 11, 2, 11, 10, 2),
(64, 11, 2, 11, 11, 2),
(65, 11, 2, 7, 3, 4),
(66, 11, 2, 7, 4, 3),
(67, 11, 2, 7, 9, 5),
(68, 11, 2, 7, 10, 3),
(69, 11, 2, 7, 11, 2),
(70, 11, 8, 13, 12, 4),
(71, 11, 8, 13, 13, 5),
(72, 11, 8, 13, 14, 5),
(73, 11, 8, 15, 12, 4),
(74, 11, 8, 15, 13, 2),
(75, 11, 8, 15, 14, 4),
(76, 11, 8, 12, 12, 4),
(77, 11, 8, 12, 13, 4),
(78, 11, 8, 12, 14, 2),
(79, 11, 8, 14, 12, 2),
(80, 11, 8, 14, 13, 2),
(81, 11, 8, 14, 14, 4),
(82, 11, 15, 16, 15, 5),
(83, 11, 15, 16, 16, 4),
(84, 11, 15, 16, 17, 4),
(85, 11, 15, 17, 15, 4),
(86, 11, 15, 17, 16, 4),
(87, 11, 15, 17, 17, 4),
(88, 11, 15, 18, 15, 4),
(89, 11, 15, 18, 16, 4),
(90, 11, 15, 18, 17, 2);

-- --------------------------------------------------------

--
-- Структура таблицы `archive_gradebook_groups`
--

CREATE TABLE `archive_gradebook_groups` (
  `id` int UNSIGNED NOT NULL,
  `archive_id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `group_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialty_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `specialty_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `curator_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_gradebook_groups`
--

INSERT INTO `archive_gradebook_groups` (`id`, `archive_id`, `group_id`, `group_number`, `specialty_name`, `specialty_code`, `curator_name`) VALUES
(7, 11, 2, '201', 'Преподавание в начальных классах', '44.02.02', 'Полянский Александр Сергеевич'),
(8, 11, 5, '202', 'Преподавание в начальных классах', '44.02.02', ''),
(9, 11, 8, '221', 'Физическая культура', '49.02.01', ''),
(10, 11, 13, '241', 'Педагогика дополнительного образования', '44.02.03', ''),
(11, 11, 11, '301', 'Преподавание в начальных классах', '44.02.02', ''),
(12, 11, 12, '302', 'Преподавание в начальных классах', '44.02.02', ''),
(13, 11, 9, '321', 'Физическая культура', '49.02.01', 'Демидов Денис Петрович'),
(14, 11, 14, '341', 'Педагогика дополнительного образования', '44.02.03', ''),
(15, 11, 6, '401', 'Преподавание в начальных классах', '44.02.02', ''),
(16, 11, 7, '402', 'Преподавание в начальных классах', '44.02.02', ''),
(17, 11, 10, '421', 'Физическая культура', '49.02.01', ''),
(18, 11, 15, '441', 'Педагогика дополнительного образования', '44.02.03', '');

-- --------------------------------------------------------

--
-- Структура таблицы `archive_gradebook_students`
--

CREATE TABLE `archive_gradebook_students` (
  `id` int UNSIGNED NOT NULL,
  `archive_id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_gradebook_students`
--

INSERT INTO `archive_gradebook_students` (`id`, `archive_id`, `group_id`, `student_id`, `full_name`, `sort_order`) VALUES
(8, 11, 2, 9, 'Астахов Максим Викторович', 1),
(9, 11, 2, 3, 'Власенко Татьяна Алексеевна', 2),
(10, 11, 2, 8, 'Грицук Владимир Владимирович', 3),
(11, 11, 2, 2, 'Иванов Петр Петрович', 4),
(12, 11, 2, 11, 'Никитенко Кирил Никитович', 5),
(13, 11, 2, 7, 'Смирнов Данил Владимирович', 6),
(14, 11, 5, 10, 'Сидоров Константин Иванович', 1),
(15, 11, 8, 13, 'Исакова Марина Петровна', 1),
(16, 11, 8, 15, 'Малахов Валентин Дмитриевич', 2),
(17, 11, 8, 12, 'Утеуов Бауржан Ермекович', 3),
(18, 11, 8, 14, 'Шальнов Григорий Игоревич', 4),
(19, 11, 9, 19, 'Алешина Эвеленина Игоревна', 1),
(20, 11, 9, 20, 'Иванов Иван Иванович', 2),
(21, 11, 15, 16, 'Ветрова Светлана Кириловна', 1),
(22, 11, 15, 17, 'Иванова Анастасия Михайловна', 2),
(23, 11, 15, 18, 'Михайлова Татьяна Игоревна', 3);

-- --------------------------------------------------------

--
-- Структура таблицы `archive_gradebook_subjects`
--

CREATE TABLE `archive_gradebook_subjects` (
  `id` int UNSIGNED NOT NULL,
  `archive_id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `subject_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_gradebook_subjects`
--

INSERT INTO `archive_gradebook_subjects` (`id`, `archive_id`, `group_id`, `curriculum_item_id`, `subject_name`, `teacher_name`, `sort_order`) VALUES
(12, 11, 2, 3, 'Иностранный язык', 'Полянский Александр Сергеевич', 1),
(13, 11, 2, 4, 'Физическая культура', 'Полянский Александр Сергеевич', 2),
(14, 11, 2, 9, 'История', 'Полянский Александр Сергеевич', 3),
(15, 11, 2, 10, 'Математика', 'Полянский Александр Сергеевич', 4),
(16, 11, 2, 11, 'Физкультура', 'Демидов Денис Петрович', 5),
(17, 11, 8, 12, 'Иностранный язык', 'Вульф Дарья Александровна', 1),
(18, 11, 8, 13, 'Математика', 'Полянский Александр Сергеевич', 2),
(19, 11, 8, 14, 'История', 'Кузнецова Ольга Николаевна', 3),
(20, 11, 15, 15, 'Физическая культура', 'Сусленкова Лилия Ивановна', 1),
(21, 11, 15, 16, 'История', 'Баштанов Виктор Иванович', 2),
(22, 11, 15, 17, 'Математика', 'Сухоруков Дмитрий Сергеевич', 3);

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
  `reason_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_text` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `changed_by` int UNSIGNED DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_grade_changes`
--

INSERT INTO `archive_grade_changes` (`id`, `archive_id`, `group_id`, `student_id`, `curriculum_item_id`, `old_grade`, `new_grade`, `reason_code`, `reason_text`, `changed_by`, `changed_at`) VALUES
(3, 11, 2, 9, 4, 2, 3, 'retake', '', 1, '2026-08-27 08:32:38');

-- --------------------------------------------------------

--
-- Структура таблицы `archive_journal_grades`
--

CREATE TABLE `archive_journal_grades` (
  `id` int UNSIGNED NOT NULL,
  `lesson_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `mark` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `activity` tinyint(1) NOT NULL DEFAULT '0',
  `late` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_journal_grades`
--

INSERT INTO `archive_journal_grades` (`id`, `lesson_id`, `student_id`, `mark`, `activity`, `late`) VALUES
(331, 106, 9, '', 0, 0),
(332, 106, 3, '5', 0, 0),
(333, 106, 8, '', 0, 0),
(334, 106, 2, 'Н', 0, 0),
(335, 106, 11, '', 0, 0),
(336, 106, 7, '', 0, 0),
(337, 107, 9, '2', 0, 0),
(338, 107, 3, '', 0, 0),
(339, 107, 8, '', 0, 0),
(340, 107, 2, '', 0, 0),
(341, 107, 11, '', 0, 0),
(342, 107, 7, '', 0, 0),
(343, 108, 9, '', 0, 0),
(344, 108, 3, '4', 0, 0),
(345, 108, 8, '5', 0, 0),
(346, 108, 2, 'Н', 0, 0),
(347, 108, 11, '', 0, 0),
(348, 108, 7, '4', 0, 1),
(349, 109, 9, '4', 0, 0),
(350, 109, 3, '5', 0, 0),
(351, 109, 8, '4', 0, 0),
(352, 109, 2, 'Н', 0, 0),
(353, 109, 11, '', 0, 0),
(354, 109, 7, '4', 0, 0),
(355, 110, 9, '4', 0, 0),
(356, 110, 3, '5', 0, 0),
(357, 110, 8, '4', 0, 0),
(358, 110, 2, '', 0, 1),
(359, 110, 11, '', 0, 0),
(360, 110, 7, '', 0, 0),
(361, 111, 9, '4', 0, 0),
(362, 111, 3, '5', 0, 0),
(363, 111, 8, '', 0, 0),
(364, 111, 2, '3', 0, 0),
(365, 111, 11, '', 0, 0),
(366, 111, 7, '4', 1, 0),
(367, 112, 9, '', 0, 0),
(368, 112, 3, '', 0, 0),
(369, 112, 8, '', 0, 0),
(370, 112, 2, '', 0, 0),
(371, 112, 11, '', 0, 0),
(372, 112, 7, '4', 1, 0),
(373, 113, 9, '', 0, 0),
(374, 113, 3, '', 0, 0),
(375, 113, 8, '', 0, 0),
(376, 113, 2, '', 0, 0),
(377, 113, 11, '', 0, 0),
(378, 113, 7, '', 0, 0),
(379, 114, 9, '', 0, 0),
(380, 114, 3, '', 0, 0),
(381, 114, 8, '', 0, 0),
(382, 114, 2, '', 0, 0),
(383, 114, 11, '', 0, 0),
(384, 114, 7, '', 0, 0),
(385, 115, 9, '', 0, 0),
(386, 115, 3, '', 0, 0),
(387, 115, 8, '', 0, 0),
(388, 115, 2, '', 0, 0),
(389, 115, 11, '', 0, 0),
(390, 115, 7, '', 0, 0),
(391, 116, 9, '', 0, 0),
(392, 116, 3, '4', 0, 0),
(393, 116, 8, '', 0, 0),
(394, 116, 2, '4', 0, 0),
(395, 116, 11, '', 0, 0),
(396, 116, 7, '4', 0, 0),
(397, 117, 9, '', 0, 0),
(398, 117, 3, '', 0, 0),
(399, 117, 8, '', 0, 0),
(400, 117, 2, '', 0, 0),
(401, 117, 11, '', 0, 0),
(402, 117, 7, '', 0, 0),
(403, 118, 9, '', 0, 0),
(404, 118, 3, '', 0, 0),
(405, 118, 8, '', 0, 0),
(406, 118, 2, '', 0, 0),
(407, 118, 11, '', 0, 0),
(408, 118, 7, '', 0, 0),
(409, 119, 9, '', 0, 0),
(410, 119, 3, '', 0, 0),
(411, 119, 8, '', 0, 0),
(412, 119, 2, '', 0, 0),
(413, 119, 11, '', 0, 0),
(414, 119, 7, '', 0, 0),
(415, 120, 9, '', 0, 0),
(416, 120, 3, '', 0, 0),
(417, 120, 8, '', 0, 0),
(418, 120, 2, '', 0, 0),
(419, 120, 11, '', 0, 0),
(420, 120, 7, '', 0, 0),
(421, 121, 9, '4', 0, 0),
(422, 121, 3, '3', 0, 0),
(423, 121, 8, '', 0, 0),
(424, 121, 2, '3', 0, 0),
(425, 121, 11, '', 0, 0),
(426, 121, 7, '3', 0, 0),
(427, 122, 9, '', 0, 0),
(428, 122, 3, '5', 1, 0),
(429, 122, 8, '', 0, 0),
(430, 122, 2, '3', 0, 1),
(431, 122, 11, '', 0, 0),
(432, 122, 7, '5', 0, 0),
(433, 123, 9, '', 0, 0),
(434, 123, 3, '5', 0, 0),
(435, 123, 8, '', 0, 0),
(436, 123, 2, '5', 0, 0),
(437, 123, 11, '', 0, 0),
(438, 123, 7, '5', 0, 0),
(439, 124, 9, '', 0, 0),
(440, 124, 3, '4', 0, 0),
(441, 124, 8, '', 0, 0),
(442, 124, 2, '3', 0, 0),
(443, 124, 11, '', 0, 0),
(444, 124, 7, '4', 0, 1),
(445, 125, 9, '', 0, 0),
(446, 125, 3, '', 0, 0),
(447, 125, 8, '', 0, 0),
(448, 125, 2, '', 0, 0),
(449, 125, 11, '', 0, 0),
(450, 125, 7, '', 0, 0),
(451, 126, 9, '', 0, 0),
(452, 126, 3, 'Н', 0, 0),
(453, 126, 8, '', 0, 0),
(454, 126, 2, '4', 1, 0),
(455, 126, 11, '', 0, 0),
(456, 126, 7, '3', 0, 0),
(457, 127, 9, '', 0, 0),
(458, 127, 3, '4', 0, 0),
(459, 127, 8, '', 0, 0),
(460, 127, 2, '2', 0, 0),
(461, 127, 11, '', 0, 0),
(462, 127, 7, '3', 0, 0),
(463, 128, 9, '', 0, 0),
(464, 128, 3, '4', 0, 0),
(465, 128, 8, '', 0, 0),
(466, 128, 2, '', 0, 0),
(467, 128, 11, '', 0, 0),
(468, 128, 7, '', 0, 0),
(469, 129, 9, '5', 1, 0),
(470, 129, 3, '5', 0, 0),
(471, 129, 8, '', 0, 0),
(472, 129, 2, '5', 0, 0),
(473, 129, 11, '4', 0, 0),
(474, 129, 7, '', 0, 0),
(475, 130, 9, '', 0, 1),
(476, 130, 3, '', 0, 0),
(477, 130, 8, '', 0, 0),
(478, 130, 2, '', 0, 0),
(479, 130, 11, '', 0, 0),
(480, 130, 7, '', 0, 0),
(481, 131, 9, '4', 0, 0),
(482, 131, 3, '', 0, 0),
(483, 131, 8, '', 0, 0),
(484, 131, 2, '4', 0, 0),
(485, 131, 11, '', 0, 0),
(486, 131, 7, '', 0, 0),
(487, 132, 9, '2', 0, 0),
(488, 132, 3, '', 0, 0),
(489, 132, 8, '', 0, 0),
(490, 132, 2, '', 0, 0),
(491, 132, 11, '4', 0, 0),
(492, 132, 7, '', 0, 0),
(493, 133, 13, '4', 0, 0),
(494, 133, 15, '4', 0, 0),
(495, 133, 12, '4', 1, 0),
(496, 133, 14, '', 0, 0),
(497, 134, 13, '3', 0, 0),
(498, 134, 15, '', 0, 1),
(499, 134, 12, '', 0, 0),
(500, 134, 14, '', 0, 0),
(501, 135, 13, '4', 0, 0),
(502, 135, 15, '4', 0, 0),
(503, 135, 12, '3', 0, 0),
(504, 135, 14, '3', 0, 0),
(505, 136, 13, '', 0, 0),
(506, 136, 15, '', 0, 0),
(507, 136, 12, '4', 0, 0),
(508, 136, 14, 'Н', 0, 0),
(509, 137, 13, '4', 0, 0),
(510, 137, 15, '3', 0, 0),
(511, 137, 12, '4', 0, 0),
(512, 137, 14, '3', 0, 0),
(513, 138, 13, '5', 0, 0),
(514, 138, 15, '2', 0, 0),
(515, 138, 12, '', 0, 1),
(516, 138, 14, '', 0, 0),
(517, 139, 13, '5', 1, 0),
(518, 139, 15, '3', 0, 1),
(519, 139, 12, '', 0, 0),
(520, 139, 14, '', 0, 0),
(521, 140, 13, '5', 0, 0),
(522, 140, 15, '', 0, 0),
(523, 140, 12, '2', 0, 1),
(524, 140, 14, '4', 0, 0),
(525, 141, 13, '', 0, 0),
(526, 141, 15, '4', 1, 0),
(527, 141, 12, '', 0, 0),
(528, 141, 14, '', 0, 0),
(529, 142, 13, '4', 0, 0),
(530, 142, 15, '4', 0, 0),
(531, 142, 12, '3', 0, 0),
(532, 142, 14, '4', 0, 0),
(533, 143, 16, '', 0, 0),
(534, 143, 17, '5', 0, 0),
(535, 143, 18, '', 0, 1),
(536, 144, 16, '5', 0, 0),
(537, 144, 17, '4', 0, 0),
(538, 144, 18, '4', 0, 0),
(539, 145, 16, '5', 0, 0),
(540, 145, 17, '4', 0, 0),
(541, 145, 18, '', 0, 1),
(542, 146, 16, '', 0, 0),
(543, 146, 17, '4', 0, 0),
(544, 146, 18, '', 0, 1),
(545, 147, 16, '4', 0, 0),
(546, 147, 17, '4', 0, 0),
(547, 147, 18, '4', 0, 0),
(548, 148, 16, '4', 0, 0),
(549, 148, 17, '4', 1, 0),
(550, 148, 18, '4', 0, 0),
(551, 149, 16, '', 0, 1),
(552, 149, 17, '5', 0, 0),
(553, 149, 18, '', 0, 0),
(554, 150, 16, '', 0, 0),
(555, 150, 17, '', 0, 0),
(556, 150, 18, '', 0, 0),
(557, 151, 16, '4', 0, 0),
(558, 151, 17, '3', 0, 0),
(559, 151, 18, '4', 0, 0),
(560, 152, 16, '4', 0, 0),
(561, 152, 17, '', 0, 0),
(562, 152, 18, '3', 0, 0),
(563, 153, 16, '', 0, 0),
(564, 153, 17, '5', 0, 0),
(565, 153, 18, '', 0, 0),
(566, 154, 16, '', 0, 0),
(567, 154, 17, '', 0, 0),
(568, 154, 18, '2', 0, 0),
(569, 155, 16, '4', 0, 0),
(570, 155, 17, '3', 0, 0),
(571, 155, 18, '3', 0, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `archive_journal_items`
--

CREATE TABLE `archive_journal_items` (
  `id` int UNSIGNED NOT NULL,
  `archive_id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `group_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `subject_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `semester` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_journal_items`
--

INSERT INTO `archive_journal_items` (`id`, `archive_id`, `group_id`, `group_number`, `curriculum_item_id`, `subject_name`, `teacher_name`, `semester`) VALUES
(40, 10, 2, '201', 3, 'Иностранный язык', 'Полянский Александр Сергеевич', 'both'),
(41, 10, 2, '201', 4, 'Физическая культура', 'Полянский Александр Сергеевич', 'both'),
(42, 10, 2, '201', 9, 'История', 'Полянский Александр Сергеевич', '1'),
(43, 10, 2, '201', 10, 'Математика', 'Полянский Александр Сергеевич', '1'),
(44, 10, 2, '201', 11, 'Физкультура', 'Демидов Денис Петрович', '1'),
(45, 10, 8, '221', 12, 'Иностранный язык', 'Вульф Дарья Александровна', 'both'),
(46, 10, 8, '221', 13, 'Математика', 'Полянский Александр Сергеевич', '1'),
(47, 10, 8, '221', 14, 'История', 'Кузнецова Ольга Николаевна', '1'),
(48, 10, 15, '441', 15, 'Физическая культура', 'Сусленкова Лилия Ивановна', 'both'),
(49, 10, 15, '441', 16, 'История', 'Баштанов Виктор Иванович', '1'),
(50, 10, 15, '441', 17, 'Математика', 'Сухоруков Дмитрий Сергеевич', 'both');

-- --------------------------------------------------------

--
-- Структура таблицы `archive_journal_lessons`
--

CREATE TABLE `archive_journal_lessons` (
  `id` int UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `source_lesson_id` int UNSIGNED DEFAULT NULL,
  `lesson_date` date NOT NULL,
  `topic_title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `topic_lesson_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `topic_hours` decimal(4,1) DEFAULT NULL,
  `grade_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'current',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_journal_lessons`
--

INSERT INTO `archive_journal_lessons` (`id`, `item_id`, `source_lesson_id`, `lesson_date`, `topic_title`, `topic_lesson_type`, `topic_hours`, `grade_type`, `sort_order`) VALUES
(106, 40, 17, '2026-08-22', 'История англии', 'lecture', '1.0', 'current', 1),
(107, 40, 18, '2026-08-22', 'История англии', 'lecture', '1.0', 'current', 2),
(108, 40, 19, '2026-08-23', 'Грамматика английского языка', 'lecture', '1.0', 'current', 3),
(109, 40, 20, '2026-08-23', 'Грамматика английского языка', 'lecture', '1.0', 'control', 4),
(110, 40, 25, '2026-08-23', 'Чтение по ролям', 'practice', '1.0', 'current', 5),
(111, 40, 26, '2026-08-23', 'Чтение по ролям', 'practice', '1.0', 'control', 6),
(112, 40, 27, '2026-08-23', 'Чтение текста', 'independent', '1.0', 'current', 7),
(113, 40, 28, '2026-08-23', 'Промежуточная аттестация. Дифференцированный зачёт', 'diff_credit', '1.0', 'current', 8),
(114, 40, 29, '2026-08-23', 'Промежуточная аттестация. Дифференцированный зачёт', 'diff_credit', '1.0', 'current', 9),
(115, 40, 30, '2026-08-23', '', '', NULL, 'current', 10),
(116, 40, 31, '2026-08-23', '', '', NULL, 'control', 11),
(117, 40, 32, '2026-08-23', '', '', NULL, 'current', 12),
(118, 40, 33, '2026-08-23', '', '', NULL, 'current', 13),
(119, 40, 34, '2026-08-23', '', '', NULL, 'current', 14),
(120, 40, 35, '2026-08-23', '', '', NULL, 'current', 15),
(121, 40, 36, '2026-08-23', '', '', NULL, 'control', 16),
(122, 41, 16, '2026-08-22', '', '', NULL, 'control', 1),
(123, 42, 12, '2026-08-22', '', '', NULL, 'current', 1),
(124, 42, 13, '2026-08-22', '', '', NULL, 'control', 2),
(125, 42, 60, '2026-08-27', 'Промежуточная аттестация. Экзамен', 'exam', '4.0', 'current', 3),
(126, 43, 22, '2026-08-23', '', '', NULL, 'current', 1),
(127, 43, 23, '2026-08-23', '', '', NULL, 'control', 2),
(128, 43, 24, '2026-08-23', '', '', NULL, 'current', 3),
(129, 44, 11, '2026-08-21', 'Техника безопасности на уроках ФК', 'lecture', '1.0', 'current', 1),
(130, 44, 21, '2026-08-22', 'Техника безопасности на уроках ФК', 'lecture', '1.0', 'current', 2),
(131, 44, 61, '2026-08-27', 'Виды легкой атлетики', 'practice', '2.0', 'control', 3),
(132, 44, 62, '2026-08-27', 'Физическая культура в общекультурной и профессиональной подготовке студентов', 'lecture', '1.0', 'current', 4),
(133, 45, 41, '2026-08-07', '', '', NULL, 'current', 1),
(134, 45, 42, '2026-08-14', '', '', NULL, 'current', 2),
(135, 45, 43, '2026-08-21', '', '', NULL, 'control', 3),
(136, 46, 38, '2026-08-10', '', '', NULL, 'current', 1),
(137, 46, 39, '2026-08-17', '', '', NULL, 'control', 2),
(138, 46, 40, '2026-08-24', '', '', NULL, 'current', 3),
(139, 46, 37, '2026-08-26', '', '', NULL, 'current', 4),
(140, 47, 44, '2026-08-06', '', '', NULL, 'current', 1),
(141, 47, 45, '2026-08-13', '', '', NULL, 'current', 2),
(142, 47, 46, '2026-08-19', '', '', NULL, 'control', 3),
(143, 48, 51, '2026-08-04', '', '', NULL, 'current', 1),
(144, 48, 52, '2026-08-04', '', '', NULL, 'current', 2),
(145, 48, 53, '2026-08-11', '', '', NULL, 'current', 3),
(146, 48, 54, '2026-08-11', '', '', NULL, 'current', 4),
(147, 48, 55, '2026-08-18', '', '', NULL, 'control', 5),
(148, 49, 47, '2026-08-05', '', '', NULL, 'current', 1),
(149, 49, 48, '2026-08-05', '', '', NULL, 'current', 2),
(150, 49, 49, '2026-08-12', '', '', NULL, 'current', 3),
(151, 49, 50, '2026-08-12', '', '', NULL, 'control', 4),
(152, 50, 56, '2026-08-07', '', '', NULL, 'current', 1),
(153, 50, 57, '2026-08-07', '', '', NULL, 'current', 2),
(154, 50, 58, '2026-08-14', '', '', NULL, 'current', 3),
(155, 50, 59, '2026-08-14', '', '', NULL, 'control', 4);

-- --------------------------------------------------------

--
-- Структура таблицы `archive_journal_students`
--

CREATE TABLE `archive_journal_students` (
  `id` int UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_journal_students`
--

INSERT INTO `archive_journal_students` (`id`, `item_id`, `student_id`, `full_name`, `sort_order`) VALUES
(124, 40, 9, 'Астахов Максим Викторович', 1),
(125, 40, 3, 'Власенко Татьяна Алексеевна', 2),
(126, 40, 8, 'Грицук Владимир Владимирович', 3),
(127, 40, 2, 'Иванов Петр Петрович', 4),
(128, 40, 11, 'Никитенко Кирил Никитович', 5),
(129, 40, 7, 'Смирнов Данил Владимирович', 6),
(130, 41, 9, 'Астахов Максим Викторович', 1),
(131, 41, 3, 'Власенко Татьяна Алексеевна', 2),
(132, 41, 8, 'Грицук Владимир Владимирович', 3),
(133, 41, 2, 'Иванов Петр Петрович', 4),
(134, 41, 11, 'Никитенко Кирил Никитович', 5),
(135, 41, 7, 'Смирнов Данил Владимирович', 6),
(136, 42, 9, 'Астахов Максим Викторович', 1),
(137, 42, 3, 'Власенко Татьяна Алексеевна', 2),
(138, 42, 8, 'Грицук Владимир Владимирович', 3),
(139, 42, 2, 'Иванов Петр Петрович', 4),
(140, 42, 11, 'Никитенко Кирил Никитович', 5),
(141, 42, 7, 'Смирнов Данил Владимирович', 6),
(142, 43, 9, 'Астахов Максим Викторович', 1),
(143, 43, 3, 'Власенко Татьяна Алексеевна', 2),
(144, 43, 8, 'Грицук Владимир Владимирович', 3),
(145, 43, 2, 'Иванов Петр Петрович', 4),
(146, 43, 11, 'Никитенко Кирил Никитович', 5),
(147, 43, 7, 'Смирнов Данил Владимирович', 6),
(148, 44, 9, 'Астахов Максим Викторович', 1),
(149, 44, 3, 'Власенко Татьяна Алексеевна', 2),
(150, 44, 8, 'Грицук Владимир Владимирович', 3),
(151, 44, 2, 'Иванов Петр Петрович', 4),
(152, 44, 11, 'Никитенко Кирил Никитович', 5),
(153, 44, 7, 'Смирнов Данил Владимирович', 6),
(154, 45, 13, 'Исакова Марина Петровна', 1),
(155, 45, 15, 'Малахов Валентин Дмитриевич', 2),
(156, 45, 12, 'Утеуов Бауржан Ермекович', 3),
(157, 45, 14, 'Шальнов Григорий Игоревич', 4),
(158, 46, 13, 'Исакова Марина Петровна', 1),
(159, 46, 15, 'Малахов Валентин Дмитриевич', 2),
(160, 46, 12, 'Утеуов Бауржан Ермекович', 3),
(161, 46, 14, 'Шальнов Григорий Игоревич', 4),
(162, 47, 13, 'Исакова Марина Петровна', 1),
(163, 47, 15, 'Малахов Валентин Дмитриевич', 2),
(164, 47, 12, 'Утеуов Бауржан Ермекович', 3),
(165, 47, 14, 'Шальнов Григорий Игоревич', 4),
(166, 48, 16, 'Ветрова Светлана Кириловна', 1),
(167, 48, 17, 'Иванова Анастасия Михайловна', 2),
(168, 48, 18, 'Михайлова Татьяна Игоревна', 3),
(169, 49, 16, 'Ветрова Светлана Кириловна', 1),
(170, 49, 17, 'Иванова Анастасия Михайловна', 2),
(171, 49, 18, 'Михайлова Татьяна Игоревна', 3),
(172, 50, 16, 'Ветрова Светлана Кириловна', 1),
(173, 50, 17, 'Иванова Анастасия Михайловна', 2),
(174, 50, 18, 'Михайлова Татьяна Игоревна', 3);

-- --------------------------------------------------------

--
-- Структура таблицы `archive_journal_topics`
--

CREATE TABLE `archive_journal_topics` (
  `id` int UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `source_topic_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lesson_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lecture',
  `hours` decimal(4,1) NOT NULL DEFAULT '2.0',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1',
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `first_lesson_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_journal_topics`
--

INSERT INTO `archive_journal_topics` (`id`, `item_id`, `source_topic_id`, `title`, `lesson_type`, `hours`, `sort_order`, `completed`, `first_lesson_date`) VALUES
(58, 40, 8, 'История англии', 'lecture', '1.0', 1, 1, '2026-08-22'),
(59, 40, 9, 'История англии', 'lecture', '1.0', 2, 1, '2026-08-22'),
(60, 40, 10, 'Грамматика английского языка', 'lecture', '1.0', 3, 1, '2026-08-23'),
(61, 40, 11, 'Грамматика английского языка', 'lecture', '1.0', 4, 1, '2026-08-23'),
(62, 40, 12, 'Чтение по ролям', 'practice', '1.0', 5, 1, '2026-08-23'),
(63, 40, 13, 'Чтение по ролям', 'practice', '1.0', 6, 1, '2026-08-23'),
(64, 40, 14, 'Чтение текста', 'independent', '1.0', 7, 1, '2026-08-23'),
(65, 40, 15, 'Промежуточная аттестация. Дифференцированный зачёт', 'diff_credit', '1.0', 8, 1, '2026-08-23'),
(66, 40, 16, 'Промежуточная аттестация. Дифференцированный зачёт', 'diff_credit', '1.0', 9, 1, '2026-08-23'),
(67, 40, 17, 'Виды легкой атлетики', 'independent', '1.0', 10, 0, NULL),
(68, 40, 18, 'Виды легкой атлетики', 'independent', '1.0', 11, 0, NULL),
(69, 42, 19, 'Промежуточная аттестация. Экзамен', 'exam', '4.0', 1, 1, '2026-08-27'),
(70, 44, 1, 'Техника безопасности на уроках ФК', 'lecture', '1.0', 1, 1, '2026-08-21'),
(71, 44, 5, 'Техника безопасности на уроках ФК', 'lecture', '1.0', 2, 1, '2026-08-22'),
(72, 44, 2, 'Виды легкой атлетики', 'practice', '2.0', 3, 1, '2026-08-27'),
(73, 44, 20, 'Физическая культура в общекультурной и профессиональной подготовке студентов', 'lecture', '1.0', 4, 1, '2026-08-27'),
(74, 44, 3, 'Техника бега на короткие дистанции', 'practice', '1.0', 5, 0, NULL),
(75, 44, 21, 'Физическая культура в общекультурной и профессиональной подготовке студентов', 'lecture', '1.0', 6, 0, NULL),
(76, 44, 22, 'Физическая культура в общекультурной и профессиональной подготовке студентов', 'lecture', '1.0', 7, 0, NULL);

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
  `display` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_journal_totals`
--

INSERT INTO `archive_journal_totals` (`id`, `item_id`, `student_id`, `final_grade`, `average`, `points`, `display`) VALUES
(126, 40, 9, 4, NULL, '74.0', '74 → 4'),
(127, 40, 3, 5, NULL, '86.3', '86.3 → 5'),
(128, 40, 8, 4, NULL, '83.0', '83 → 4'),
(129, 40, 2, 2, NULL, '46.8', '46.8 → 2'),
(130, 40, 11, 2, NULL, '20.0', '20 → 2'),
(131, 40, 7, 4, NULL, '78.1', '78.1 → 4'),
(132, 41, 9, 3, NULL, '20.0', '3'),
(133, 41, 3, 4, NULL, '70.0', '70 → 4'),
(134, 41, 8, 2, NULL, '20.0', '20 → 2'),
(135, 41, 2, 2, NULL, '42.0', '42 → 2'),
(136, 41, 11, 2, NULL, '20.0', '20 → 2'),
(137, 41, 7, 3, NULL, '65.0', '65 → 3'),
(138, 42, 9, 2, NULL, '20.0', '20 → 2'),
(139, 42, 3, 5, NULL, '86.0', '86 → 5'),
(140, 42, 8, 2, NULL, '20.0', '20 → 2'),
(141, 42, 2, 4, NULL, '77.0', '77 → 4'),
(142, 42, 11, 2, NULL, '20.0', '20 → 2'),
(143, 42, 7, 5, NULL, '84.3', '84.3 → 5'),
(144, 43, 9, 2, NULL, '20.0', '20 → 2'),
(145, 43, 3, 4, NULL, '75.0', '75 → 4'),
(146, 43, 8, 2, NULL, '20.0', '20 → 2'),
(147, 43, 2, 2, NULL, '63.7', '63.7 → 2'),
(148, 43, 11, 2, NULL, '20.0', '20 → 2'),
(149, 43, 7, 3, NULL, '65.0', '65 → 3'),
(150, 44, 9, 4, NULL, '77.0', '77 → 4'),
(151, 44, 3, 2, NULL, '50.0', '50 → 2'),
(152, 44, 8, 2, NULL, '20.0', '20 → 2'),
(153, 44, 2, 5, NULL, '86.0', '86 → 5'),
(154, 44, 11, 2, NULL, '44.0', '44 → 2'),
(155, 44, 7, 2, NULL, '20.0', '20 → 2'),
(156, 45, 13, 4, NULL, '77.0', '77 → 4'),
(157, 45, 15, 4, NULL, '78.3', '78.3 → 4'),
(158, 45, 12, 4, NULL, '72.7', '72.7 → 4'),
(159, 45, 14, 2, NULL, '47.0', '47 → 2'),
(160, 46, 13, 5, NULL, '87.3', '87.3 → 5'),
(161, 46, 15, 2, NULL, '60.8', '60.8 → 2'),
(162, 46, 12, 4, NULL, '78.8', '78.8 → 4'),
(163, 46, 14, 2, NULL, '43.3', '43.3 → 2'),
(164, 47, 13, 5, NULL, '86.0', '86 → 5'),
(165, 47, 15, 4, NULL, '81.7', '81.7 → 4'),
(166, 47, 12, 2, NULL, '57.3', '57.3 → 2'),
(167, 47, 14, 4, NULL, '80.0', '80 → 4'),
(168, 48, 16, 5, NULL, '86.0', '86 → 5'),
(169, 48, 17, 4, NULL, '81.5', '81.5 → 4'),
(170, 48, 18, 4, NULL, '77.0', '77 → 4'),
(171, 49, 16, 4, NULL, '78.8', '78.8 → 4'),
(172, 49, 17, 4, NULL, '75.3', '75.3 → 4'),
(173, 49, 18, 4, NULL, '80.0', '80 → 4'),
(174, 50, 16, 4, NULL, '80.0', '80 → 4'),
(175, 50, 17, 4, NULL, '77.0', '77 → 4'),
(176, 50, 18, 2, NULL, '62.0', '62 → 2');

-- --------------------------------------------------------

--
-- Структура таблицы `archive_periods`
--

CREATE TABLE `archive_periods` (
  `id` int UNSIGNED NOT NULL,
  `archive_type` enum('gradebook','journal') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `archived_by` int UNSIGNED DEFAULT NULL,
  `archived_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `archive_periods`
--

INSERT INTO `archive_periods` (`id`, `archive_type`, `academic_year`, `semester`, `archived_by`, `archived_at`) VALUES
(10, 'journal', '2025-2026', '1', 1, '2026-08-27 08:31:38'),
(11, 'gradebook', '2025-2026', '1', 1, '2026-08-27 08:31:44');

-- --------------------------------------------------------

--
-- Структура таблицы `attendance_days`
--

CREATE TABLE `attendance_days` (
  `id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `academic_year` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
(10, 8, '2026-07-08', '2025-2026', '2026-08-26 16:49:18'),
(11, 9, '2025-09-30', '2025-2026', '2026-08-27 06:28:04'),
(12, 2, '2026-08-27', '2025-2026', '2026-08-27 17:45:16');

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
(21, 7, 15, 6, 0, 5, '2026-08-26 16:37:37', '2026-08-26 16:37:37'),
(22, 8, 14, 0, 6, NULL, '2026-08-26 16:37:56', '2026-08-26 16:37:56'),
(23, 9, 13, 6, 0, 3, '2026-08-26 16:48:37', '2026-08-26 16:48:37'),
(24, 10, 15, 4, 0, 4, '2026-08-26 16:49:18', '2026-08-26 16:49:18'),
(25, 10, 14, 0, 4, NULL, '2026-08-26 16:49:18', '2026-08-26 16:49:18'),
(26, 11, 19, 9, 0, 1, '2026-08-27 06:28:04', '2026-08-27 06:28:04'),
(27, 11, 20, 0, 6, NULL, '2026-08-27 06:28:04', '2026-08-27 06:28:04'),
(28, 5, 7, 6, 0, 1, '2026-08-27 18:10:44', '2026-08-27 18:10:44');

-- --------------------------------------------------------

--
-- Структура таблицы `attendance_reasons`
--

CREATE TABLE `attendance_reasons` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `semester` enum('1','2','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
(11, 2, 2, 16, '1', 6, '2026-08-21 09:27:11', '2026-08-27 08:12:01'),
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
  `academic_year` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `subject_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `topic` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `defense_date` date DEFAULT NULL,
  `teacher_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
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
  `group_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subject_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `archived_at` datetime DEFAULT NULL,
  `liquidation_date` date DEFAULT NULL,
  `liquidation_time` time DEFAULT NULL,
  `commission_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
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
  `form_type` enum('demo_exam','vkr') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `points` decimal(8,2) DEFAULT NULL,
  `topic` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
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
  `module_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `org_supervisor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `college_supervisor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
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
  `academic_year` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL DEFAULT '0',
  `subject_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attestation_form` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
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
  `group_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `additional_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `group_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `specialty_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `specialty_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mother_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mother_workplace` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `father_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `father_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `father_workplace` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_registered` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_region` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_district` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_locality` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_house` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_actual` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `district` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_low_income` tinyint(1) NOT NULL DEFAULT '0',
  `family_type` enum('complete','no_father','no_mother') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siblings_under_18` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `residence_type` enum('family','dormitory','apartment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_nonresident` tinyint(1) NOT NULL DEFAULT '0',
  `without_parental_care` tinyint(1) NOT NULL DEFAULT '0',
  `expulsion_order` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `expulsion_date` date NOT NULL,
  `expulsion_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
(15, 1, 17, 1),
(16, 1, 7, 2),
(17, 1, 12, 3);

-- --------------------------------------------------------

--
-- Структура таблицы `glaz_schedules`
--

CREATE TABLE `glaz_schedules` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `academic_year` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `liquidation_date` date DEFAULT NULL,
  `liquidation_time` time DEFAULT NULL,
  `updated_by` int UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `glaz_schedules`
--

INSERT INTO `glaz_schedules` (`id`, `student_id`, `curriculum_item_id`, `academic_year`, `semester`, `liquidation_date`, `liquidation_time`, `updated_by`, `updated_at`) VALUES
(1, 2, 3, '2025-2026', '1', '2026-08-13', '14:50:00', 1, '2026-08-27 08:27:50'),
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
  `from_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `mark` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
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
(365, 59, 17, '3', 0, 0, NULL, '2026-08-26 17:00:21', '2026-08-26 17:00:21'),
(366, 25, 9, '4', 0, 0, NULL, '2026-08-27 05:18:05', '2026-08-27 05:18:05'),
(367, 11, 9, '5', 1, 0, NULL, '2026-08-27 08:19:36', '2026-08-27 08:19:43'),
(368, 61, 9, '4', 0, 0, NULL, '2026-08-27 08:19:38', '2026-08-27 08:19:38'),
(370, 21, 9, '', 0, 1, NULL, '2026-08-27 08:19:47', '2026-08-27 08:19:47'),
(371, 62, 9, '2', 0, 0, NULL, '2026-08-27 08:20:13', '2026-08-27 08:20:13'),
(372, 11, 2, '5', 0, 0, NULL, '2026-08-27 08:20:37', '2026-08-27 08:20:37'),
(373, 61, 2, '4', 0, 0, NULL, '2026-08-27 08:20:40', '2026-08-27 08:20:40'),
(376, 27, 9, 'Н', 0, 0, NULL, '2026-08-27 09:00:43', '2026-08-27 09:00:43'),
(377, 19, 9, '4', 0, 0, NULL, '2026-08-27 09:01:26', '2026-08-27 09:01:26');

-- --------------------------------------------------------

--
-- Структура таблицы `journal_lessons`
--

CREATE TABLE `journal_lessons` (
  `id` int UNSIGNED NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `lesson_date` date NOT NULL,
  `ktp_topic_id` int UNSIGNED DEFAULT NULL,
  `grade_type` enum('current','control') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'current',
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
(17, 3, '2026-08-22', NULL, 'current', '2026-08-22 10:58:39'),
(18, 3, '2026-08-22', NULL, 'current', '2026-08-22 10:59:02'),
(19, 3, '2026-08-23', NULL, 'current', '2026-08-22 11:00:27'),
(20, 3, '2026-08-23', NULL, 'control', '2026-08-22 11:00:37'),
(21, 11, '2026-08-22', 5, 'current', '2026-08-22 13:18:29'),
(22, 10, '2026-08-23', NULL, 'current', '2026-08-23 05:58:33'),
(23, 10, '2026-08-23', NULL, 'control', '2026-08-23 05:59:02'),
(24, 10, '2026-08-23', NULL, 'current', '2026-08-23 06:03:32'),
(25, 3, '2026-08-23', NULL, 'current', '2026-08-23 13:45:12'),
(26, 3, '2026-08-23', NULL, 'control', '2026-08-23 13:45:20'),
(27, 3, '2026-08-23', NULL, 'current', '2026-08-23 13:45:29'),
(28, 3, '2026-08-23', NULL, 'current', '2026-08-23 13:45:34'),
(29, 3, '2026-08-23', NULL, 'current', '2026-08-23 14:18:46'),
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
(59, 17, '2026-08-14', NULL, 'control', '2026-08-26 16:59:56'),
(60, 9, '2026-08-27', 19, 'current', '2026-08-27 04:36:37'),
(61, 11, '2026-08-27', 2, 'control', '2026-08-27 08:19:27'),
(62, 11, '2026-08-27', 20, 'current', '2026-08-27 08:20:09');

-- --------------------------------------------------------

--
-- Структура таблицы `ktp_topics`
--

CREATE TABLE `ktp_topics` (
  `id` int UNSIGNED NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lesson_type` enum('lecture','practice','independent','diff_credit','credit','exam','control') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lecture',
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
(3, 11, 'Техника бега на короткие дистанции', 'practice', '1.0', 5, '2026-08-21 11:02:24'),
(5, 11, 'Техника безопасности на уроках ФК', 'lecture', '1.0', 2, '2026-08-21 11:07:46'),
(19, 9, 'Промежуточная аттестация. Экзамен', 'exam', '4.0', 1, '2026-08-24 18:28:52'),
(20, 11, 'Физическая культура в общекультурной и профессиональной подготовке студентов', 'lecture', '1.0', 4, '2026-08-27 08:16:33'),
(21, 11, 'Физическая культура в общекультурной и профессиональной подготовке студентов', 'lecture', '1.0', 6, '2026-08-27 08:16:33'),
(22, 11, 'Физическая культура в общекультурной и профессиональной подготовке студентов', 'lecture', '1.0', 7, '2026-08-27 08:16:48'),
(23, 11, '.   Совершенствование техники длительного бега', 'practice', '1.0', 8, '2026-08-27 08:47:50'),
(24, 11, '.   Совершенствование техники длительного бега', 'practice', '1.0', 9, '2026-08-27 08:47:50');

-- --------------------------------------------------------

--
-- Структура таблицы `notifications`
--

CREATE TABLE `notifications` (
  `id` int UNSIGNED NOT NULL,
  `sender_id` int UNSIGNED DEFAULT NULL,
  `notification_type` enum('personal','announcement') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `notifications`
--

INSERT INTO `notifications` (`id`, `sender_id`, `notification_type`, `title`, `body`, `recipient_id`, `created_at`) VALUES
(1, 1, 'announcement', 'Обновление системы', 'Появилась система уведомлений, обратите внимание на колокольчик в шапке.', NULL, '2026-08-22 11:21:00'),
(2, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 3, '2026-08-22 12:55:07'),
(4, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 7, '2026-08-27 08:29:53'),
(5, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 28, '2026-08-27 08:29:54'),
(6, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 8, '2026-08-27 08:29:54'),
(7, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 16, '2026-08-27 08:29:54'),
(8, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 27, '2026-08-27 08:29:54'),
(9, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 26, '2026-08-27 08:29:54'),
(10, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 22, '2026-08-27 08:29:54'),
(11, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 23, '2026-08-27 08:29:54'),
(12, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 3, '2026-08-27 08:29:54'),
(13, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 17, '2026-08-27 08:29:54'),
(14, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 21, '2026-08-27 08:29:54'),
(15, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 9, '2026-08-27 08:29:54'),
(16, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 10, '2026-08-27 08:29:54'),
(17, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 12, '2026-08-27 08:29:54'),
(18, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 20, '2026-08-27 08:29:54'),
(19, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 25, '2026-08-27 08:29:54'),
(20, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 13, '2026-08-27 08:29:54'),
(21, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 18, '2026-08-27 08:29:54'),
(22, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 24, '2026-08-27 08:29:54'),
(23, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 19, '2026-08-27 08:29:54'),
(24, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 14, '2026-08-27 08:29:54'),
(25, 1, 'personal', 'ГЛАЗ — график ликвидации задолженности', 'Завуч направил график ликвидации академической задолженности для ознакомления.', 15, '2026-08-27 08:29:54');

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
(4, 1, 6, '2026-08-23 08:05:08'),
(5, 7, 16, '2026-08-27 08:30:06'),
(6, 12, 3, '2026-08-27 12:02:15');

-- --------------------------------------------------------

--
-- Структура таблицы `organization`
--

CREATE TABLE `organization` (
  `id` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `name` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `phone` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `additional_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `specialties`
--

INSERT INTO `specialties` (`id`, `name`, `code`, `created_at`, `updated_at`) VALUES
(2, 'Преподавание в начальных классах', '44.02.02', '2026-08-20 06:28:50', '2026-08-20 06:28:50'),
(5, 'Физическая культура', '49.02.01', '2026-08-21 13:23:57', '2026-08-21 13:23:57'),
(6, 'Педагогика дополнительного образования', '44.02.03', '2026-08-24 08:20:35', '2026-08-24 08:20:35'),
(7, 'Информационные системы и программирование', '09.02.07', '2026-08-27 15:19:59', '2026-08-27 15:19:59'),
(8, 'Дошкольное образование', '44.02.01', '2026-08-27 15:20:50', '2026-08-27 15:20:50'),
(9, 'Право и организация социального обеспечения', '40.02.01', '2026-08-27 15:24:23', '2026-08-27 15:24:23');

-- --------------------------------------------------------

--
-- Структура таблицы `students`
--

CREATE TABLE `students` (
  `id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `snils` varchar(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mother_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mother_workplace` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `father_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `father_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `father_workplace` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_registered` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_region` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_district` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_locality` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_house` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_actual` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `district` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_low_income` tinyint(1) NOT NULL DEFAULT '0',
  `family_type` enum('complete','no_father','no_mother') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siblings_under_18` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `residence_type` enum('family','dormitory','apartment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
(9, 2, 30, 'Астахов Максим Викторович', '', '', NULL, 'male', '', '', '', '', '', '', 'Новосибирская область, Купинский район, Купино', 'Новосибирская область', 'Купинский район', 'Купино', '', '', 'г.Карасук, ул. Ленина 134, кв. 5', 'Купинский район', 0, 'no_father', 2, 'apartment', 1, 1, '2026-08-24 11:55:59', '2026-08-27 15:44:04'),
(10, 5, 31, 'Сидоров Константин Иванович', '', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, NULL, 0, NULL, 0, 0, '2026-08-24 15:30:38', '2026-08-24 15:30:38'),
(12, 8, 33, 'Утеуов Бауржан Ермекович', '', '', '2006-03-09', 'male', '', '', '', '', '', '', '', '', '', '', '', '', 'Общежитие, г. Карасук', '', 1, 'no_father', 5, 'dormitory', 1, 0, '2026-08-26 16:27:35', '2026-08-26 16:27:36'),
(13, 8, 34, 'Исакова Марина Петровна', '', '', NULL, 'female', '', '', '', '', '', '', 'Новосибирская область, Карасук, Фрунзе, д. 73', 'Новосибирская область', '', 'Карасук', 'Фрунзе', '73', 'Карасук, ул. Фрунзе 73', 'Карасук', 0, 'complete', 1, 'family', 0, 0, '2026-08-26 16:29:18', '2026-08-26 16:29:18'),
(14, 8, 35, 'Шальнов Григорий Игоревич', '', '', NULL, 'male', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, NULL, 0, NULL, 0, 0, '2026-08-26 16:29:49', '2026-08-26 16:29:49'),
(15, 8, 36, 'Малахов Валентин Дмитриевич', '', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, NULL, 0, NULL, 0, 0, '2026-08-26 16:30:29', '2026-08-26 16:30:29'),
(16, 15, 37, 'Ветрова Светлана Кириловна', '', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 1, 'complete', 4, NULL, 0, 0, '2026-08-26 16:52:42', '2026-08-26 16:52:42'),
(17, 15, 38, 'Иванова Анастасия Михайловна', '', '', NULL, 'female', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, 'complete', 0, 'family', 0, 0, '2026-08-26 16:53:22', '2026-08-26 16:53:22'),
(18, 15, 39, 'Михайлова Татьяна Игоревна', '', '', NULL, 'female', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, NULL, 0, NULL, 0, 0, '2026-08-26 16:55:20', '2026-08-26 16:55:20'),
(19, 9, 40, 'Алешина Эвеленина Игоревна', '89237047174', '', NULL, 'female', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, 'complete', 2, NULL, 0, 0, '2026-08-27 05:37:54', '2026-08-27 05:37:54'),
(20, 9, 41, 'Иванов Иван Иванович', '', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, NULL, 0, NULL, 0, 0, '2026-08-27 06:26:58', '2026-08-27 06:26:58');

-- --------------------------------------------------------

--
-- Структура таблицы `student_courseworks`
--

CREATE TABLE `student_courseworks` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `subject_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `topic` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `defense_date` date DEFAULT NULL,
  `teacher_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
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
  `form_type` enum('demo_exam','vkr') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `points` decimal(8,2) DEFAULT NULL,
  `topic` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
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
  `module_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `org_supervisor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `college_supervisor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
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
  `academic_year` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `curriculum_item_id` int UNSIGNED NOT NULL,
  `subject_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attestation_form` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `grade` tinyint UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `student_record_book`
--

INSERT INTO `student_record_book` (`id`, `student_id`, `academic_year`, `semester`, `curriculum_item_id`, `subject_name`, `teacher_name`, `attestation_form`, `grade`, `updated_at`) VALUES
(1, 3, '2025-2026', '1', 3, 'Иностранный язык', 'Полянский Александр Сергеевич', 'diff_credit', 5, '2026-08-27 08:31:44'),
(2, 3, '2025-2026', '1', 4, 'Физическая культура', 'Полянский Александр Сергеевич', '', 4, '2026-08-27 08:31:44'),
(3, 3, '2025-2026', '1', 8, 'Физика', '', '', 4, '2026-08-22 10:16:21'),
(4, 3, '2025-2026', '1', 9, 'История', 'Полянский Александр Сергеевич', 'exam', 5, '2026-08-27 08:31:44'),
(5, 3, '2025-2026', '1', 10, 'Математика', 'Полянский Александр Сергеевич', '', 4, '2026-08-27 08:31:44'),
(6, 3, '2025-2026', '1', 11, 'Физкультура', 'Демидов Денис Петрович', '', 2, '2026-08-27 08:31:44'),
(7, 2, '2025-2026', '1', 3, 'Иностранный язык', 'Полянский Александр Сергеевич', 'diff_credit', 2, '2026-08-27 08:31:44'),
(8, 2, '2025-2026', '1', 4, 'Физическая культура', 'Полянский Александр Сергеевич', '', 2, '2026-08-27 08:31:44'),
(9, 2, '2025-2026', '1', 8, 'Физика', '', '', 4, '2026-08-22 10:16:21'),
(10, 2, '2025-2026', '1', 9, 'История', 'Полянский Александр Сергеевич', 'exam', 4, '2026-08-27 08:31:44'),
(11, 2, '2025-2026', '1', 10, 'Математика', 'Полянский Александр Сергеевич', '', 2, '2026-08-27 08:31:44'),
(12, 2, '2025-2026', '1', 11, 'Физкультура', 'Демидов Денис Петрович', '', 5, '2026-08-27 08:31:44'),
(19, 7, '2025-2026', '1', 3, 'Иностранный язык', 'Полянский Александр Сергеевич', 'diff_credit', 4, '2026-08-24 18:23:28'),
(20, 7, '2025-2026', '1', 4, 'Физическая культура', 'Полянский Александр Сергеевич', '', 3, '2026-08-24 18:08:30'),
(21, 7, '2025-2026', '1', 8, 'Физика', 'Полянский Александр Сергеевич', '', 2, '2026-08-24 18:08:30'),
(22, 7, '2025-2026', '1', 9, 'История', 'Полянский Александр Сергеевич', 'exam', 5, '2026-08-27 08:31:44'),
(23, 7, '2025-2026', '1', 10, 'Математика', 'Полянский Александр Сергеевич', '', 3, '2026-08-24 18:08:30'),
(24, 7, '2025-2026', '1', 11, 'Физкультура', 'Демидов Денис Петрович', '', 2, '2026-08-27 08:31:44'),
(98, 10, '2025-2026', '1', 3, 'Иностранный язык', '', '', 3, '2026-08-24 15:30:38'),
(99, 10, '2025-2026', '1', 9, 'История', '', '', 3, '2026-08-24 15:30:38'),
(100, 10, '2025-2026', '1', 10, 'Математика', '', '', 4, '2026-08-24 15:30:38'),
(101, 10, '2025-2026', '1', 8, 'Физика', '', '', 4, '2026-08-24 15:30:38'),
(102, 10, '2025-2026', '1', 4, 'Физическая культура', '', '', 3, '2026-08-24 15:30:38'),
(103, 10, '2025-2026', '1', 11, 'Физкультура', '', '', 2, '2026-08-24 15:30:38'),
(379, 9, '2025-2026', '1', 3, 'Иностранный язык', 'Полянский Александр Сергеевич', 'diff_credit', 4, '2026-08-27 08:31:44'),
(380, 9, '2025-2026', '1', 4, 'Физическая культура', 'Полянский Александр Сергеевич', '', 3, '2026-08-27 08:32:38'),
(381, 9, '2025-2026', '1', 9, 'История', 'Полянский Александр Сергеевич', 'exam', 2, '2026-08-27 08:31:44'),
(382, 9, '2025-2026', '1', 10, 'Математика', 'Полянский Александр Сергеевич', '', 2, '2026-08-27 08:31:44'),
(383, 9, '2025-2026', '1', 11, 'Физкультура', 'Демидов Денис Петрович', '', 4, '2026-08-27 08:31:44'),
(389, 8, '2025-2026', '1', 3, 'Иностранный язык', 'Полянский Александр Сергеевич', 'diff_credit', 4, '2026-08-27 08:31:44'),
(390, 8, '2025-2026', '1', 4, 'Физическая культура', 'Полянский Александр Сергеевич', '', 2, '2026-08-27 08:31:44'),
(391, 8, '2025-2026', '1', 9, 'История', 'Полянский Александр Сергеевич', 'exam', 2, '2026-08-27 08:31:44'),
(392, 8, '2025-2026', '1', 10, 'Математика', 'Полянский Александр Сергеевич', '', 2, '2026-08-27 08:31:44'),
(393, 8, '2025-2026', '1', 11, 'Физкультура', 'Демидов Денис Петрович', '', 2, '2026-08-27 08:31:44'),
(409, 13, '2025-2026', '1', 12, 'Иностранный язык', 'Вульф Дарья Александровна', '', 4, '2026-08-27 08:31:44'),
(410, 13, '2025-2026', '1', 13, 'Математика', 'Полянский Александр Сергеевич', '', 5, '2026-08-27 08:31:44'),
(411, 13, '2025-2026', '1', 14, 'История', 'Кузнецова Ольга Николаевна', '', 5, '2026-08-27 08:31:44'),
(412, 15, '2025-2026', '1', 12, 'Иностранный язык', 'Вульф Дарья Александровна', '', 4, '2026-08-27 08:31:44'),
(413, 15, '2025-2026', '1', 13, 'Математика', 'Полянский Александр Сергеевич', '', 2, '2026-08-27 08:31:44'),
(414, 15, '2025-2026', '1', 14, 'История', 'Кузнецова Ольга Николаевна', '', 4, '2026-08-27 08:31:44'),
(415, 12, '2025-2026', '1', 12, 'Иностранный язык', 'Вульф Дарья Александровна', '', 4, '2026-08-27 08:31:44'),
(416, 12, '2025-2026', '1', 13, 'Математика', 'Полянский Александр Сергеевич', '', 4, '2026-08-27 08:31:44'),
(417, 12, '2025-2026', '1', 14, 'История', 'Кузнецова Ольга Николаевна', '', 2, '2026-08-27 08:31:44'),
(418, 14, '2025-2026', '1', 12, 'Иностранный язык', 'Вульф Дарья Александровна', '', 2, '2026-08-27 08:31:44'),
(419, 14, '2025-2026', '1', 13, 'Математика', 'Полянский Александр Сергеевич', '', 2, '2026-08-27 08:31:44'),
(420, 14, '2025-2026', '1', 14, 'История', 'Кузнецова Ольга Николаевна', '', 4, '2026-08-27 08:31:44'),
(421, 16, '2025-2026', '1', 15, 'Физическая культура', 'Сусленкова Лилия Ивановна', '', 5, '2026-08-27 08:31:44'),
(422, 16, '2025-2026', '1', 16, 'История', 'Баштанов Виктор Иванович', '', 4, '2026-08-27 08:31:44'),
(423, 16, '2025-2026', '1', 17, 'Математика', 'Сухоруков Дмитрий Сергеевич', '', 4, '2026-08-27 08:31:44'),
(424, 17, '2025-2026', '1', 15, 'Физическая культура', 'Сусленкова Лилия Ивановна', '', 4, '2026-08-27 08:31:44'),
(425, 17, '2025-2026', '1', 16, 'История', 'Баштанов Виктор Иванович', '', 4, '2026-08-27 08:31:44'),
(426, 17, '2025-2026', '1', 17, 'Математика', 'Сухоруков Дмитрий Сергеевич', '', 4, '2026-08-27 08:31:44'),
(427, 18, '2025-2026', '1', 15, 'Физическая культура', 'Сусленкова Лилия Ивановна', '', 4, '2026-08-27 08:31:44'),
(428, 18, '2025-2026', '1', 16, 'История', 'Баштанов Виктор Иванович', '', 4, '2026-08-27 08:31:44'),
(429, 18, '2025-2026', '1', 17, 'Математика', 'Сухоруков Дмитрий Сергеевич', '', 2, '2026-08-27 08:31:44');

-- --------------------------------------------------------

--
-- Структура таблицы `student_transfers`
--

CREATE TABLE `student_transfers` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `from_group_id` int UNSIGNED DEFAULT NULL,
  `from_group_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `to_group_id` int UNSIGNED DEFAULT NULL,
  `to_group_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `additional_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transferred_by` int UNSIGNED DEFAULT NULL,
  `transferred_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `study_groups`
--

CREATE TABLE `study_groups` (
  `id` int UNSIGNED NOT NULL,
  `number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialty_id` int UNSIGNED NOT NULL,
  `curator_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `study_groups`
--

INSERT INTO `study_groups` (`id`, `number`, `specialty_id`, `curator_id`, `created_at`, `updated_at`) VALUES
(2, '201', 2, 3, '2026-08-20 06:29:12', '2026-08-27 18:15:13'),
(5, '202', 2, 17, '2026-08-21 13:19:37', '2026-08-27 15:15:32'),
(6, '401', 2, NULL, '2026-08-21 13:23:24', '2026-08-23 05:37:32'),
(7, '402', 2, NULL, '2026-08-24 08:13:16', '2026-08-24 08:13:16'),
(8, '221', 5, 20, '2026-08-24 08:13:24', '2026-08-27 15:16:30'),
(9, '321', 5, 16, '2026-08-24 08:13:33', '2026-08-27 15:14:18'),
(10, '421', 5, NULL, '2026-08-24 08:13:41', '2026-08-24 08:13:41'),
(11, '301', 2, NULL, '2026-08-24 08:16:55', '2026-08-24 08:16:55'),
(12, '302', 2, 22, '2026-08-24 08:17:12', '2026-08-27 15:14:59'),
(13, '241', 6, NULL, '2026-08-24 08:20:45', '2026-08-24 08:20:45'),
(14, '341', 6, NULL, '2026-08-24 08:20:58', '2026-08-24 08:20:58'),
(15, '441', 6, 9, '2026-08-24 08:21:05', '2026-08-27 15:15:52'),
(16, '331', 7, NULL, '2026-08-27 15:21:25', '2026-08-27 15:21:25'),
(17, '231', 7, NULL, '2026-08-27 15:21:44', '2026-08-27 15:21:44'),
(18, '232', 7, NULL, '2026-08-27 15:21:52', '2026-08-27 15:21:52'),
(19, '211', 8, NULL, '2026-08-27 15:22:06', '2026-08-27 15:22:06'),
(20, '311', 8, NULL, '2026-08-27 15:22:12', '2026-08-27 15:22:12'),
(21, '411', 8, NULL, '2026-08-27 15:22:20', '2026-08-27 15:22:20'),
(22, '101', 2, NULL, '2026-08-27 15:22:50', '2026-08-27 15:22:50'),
(23, '111', 8, NULL, '2026-08-27 15:22:59', '2026-08-27 15:22:59'),
(24, '121', 5, NULL, '2026-08-27 15:23:06', '2026-08-27 15:23:06'),
(25, '141', 6, NULL, '2026-08-27 15:23:18', '2026-08-27 15:23:18'),
(26, '251', 9, NULL, '2026-08-27 15:24:33', '2026-08-27 15:24:33'),
(27, '351', 9, NULL, '2026-08-27 15:24:42', '2026-08-27 15:24:42');

-- --------------------------------------------------------

--
-- Структура таблицы `subjects`
--

CREATE TABLE `subjects` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_plain` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `position` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `education` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `additional_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'icon:default',
  `role` enum('admin','teacher','student') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'teacher',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `password_plain`, `full_name`, `phone`, `position`, `education`, `additional_info`, `avatar`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin@kpk.local', '$2y$10$34gEnq6LlCeGWDkQ0ECCpu7qKBt.zDo4.MtjoMGBhILGM5JC3L/aW', NULL, 'Шнайдер Антон Адамович', '', '', '', '', 'icon:default', 'admin', 1, '2026-08-20 05:30:42', '2026-08-21 14:15:35'),
(3, '79000000000@phone.local', '$2y$10$ju7juIUxXMVomSug0cVx5e4CcWMEc/Vitr8OUuehl0WzueckWtf/K', '4365нкун547', 'Полянский Александр Сергеевич', '+79000000000', '', '', '', 'icon:person', 'teacher', 1, '2026-08-20 05:36:37', '2026-08-27 18:39:09'),
(6, 'smirnov.danil@student.local', '$2y$10$FOpBr02OkIaPu33wzUyV6.1d9adjZ6AGz/fw2vpjPQbC5KtsWI.Xi', 'gabrj5YM', 'Смирнов Данил Владимирович', '8 923 704-71-74', '', '', NULL, 'file:u6_91414fb23baa39bf.png', 'student', 1, '2026-08-22 08:04:25', '2026-08-27 08:39:34'),
(7, '79137180652@phone.local', '$2y$10$8S2cNS2g4jty80pWai2aVOovz3xUvd5YpW.NYc0.R.7pycP8AWacW', 'BWI324994j', 'Баштанов Виктор Иванович', '+79137180652', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 04:31:56', '2026-08-27 07:58:04'),
(8, '79237031677@phone.local', '$2y$10$XGchrOiWOay.ar7U9NR7rujQPnvYp1OICkjflztd9uRWG3PFklQcq', 'ta78E2Wb', 'Гутова Наталья Владимировна', '+79237031677', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 04:35:27', '2026-08-27 15:14:09'),
(9, '79231179233@phone.local', '$2y$10$RiUxVTAEdLw9TubCflbNIODuj040hptEa023abp1WfWDDplxYMITq', 'C5QETEeT', 'Сополева Мария Евгеньевна', '+79231179233', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 04:36:48', '2026-08-27 15:15:52'),
(10, '79930325388@phone.local', '$2y$10$dOHcLKWZnjtimrcic1nmh.3rGmH7DE4ZYgcGQjnorKBPq2MV7nKnm', 'h8Fek4Xk', 'Сулима Елена Васильевна', '+79930325388', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 04:37:56', '2026-08-27 15:15:59'),
(12, '79232327519@phone.local', '$2y$10$MjeCB9Wx2/3I8aDnL13U4.vLQzYQoseMrTBfKu7k7S8NT6BQxJu.2', 'RFbajaUx', 'Сусленкова Лилия Ивановна', '+79232327519', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 06:24:54', '2026-08-27 15:16:07'),
(13, '79139309077@phone.local', '$2y$10$fJsqxsNt475QPLk/08GqgudyX9QYa6hzifik0eoKINZZrOKBW6gKq', 'Rm59cJN5', 'Толстых Аркадий Геннадьевич', '+79139309077', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 06:25:54', '2026-08-27 15:16:48'),
(14, '79231208261@phone.local', '$2y$10$NlV/AhLUuNKdhL4KK5mQRuVBaqtL.Hcr9wVHiTx/acr7fH4k18u8q', '95ZaM59z', 'Шуст Марина Николаевна', '+79231208261', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 06:27:23', '2026-08-27 15:17:26'),
(15, '79231167030@phone.local', '$2y$10$yx9SThdLZSQCjciU6tPbWuB66BbWmJFbmZ9D2FNE.H.wLpV4IpcBS', 'sRWPJQwM', 'Эккерт Татьяна Анатольевна', '+79231167030', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 06:31:46', '2026-08-27 15:17:35'),
(16, '79137190935@phone.local', '$2y$10$PVXNYh6XoaAPD0v86JClwuCsOuwLaIffPiSQEvOGEptELtzIhui0W', '123456', 'Демидов Денис Петрович', '+79137190935', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 06:32:37', '2026-08-27 15:14:18'),
(17, '79639468542@phone.local', '$2y$10$RmzdwSWlYpDG9fxjf/.HfOZu4dRhB.HmEFNTdoeuzlxg0EaxNnrha', 'ymZkwHd4', 'Предатченко Ольга Сергеевна', '+79639468542', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:04:03', '2026-08-27 15:15:32'),
(18, '79137725424@phone.local', '$2y$10$aPEW3eGD7jLiaTrUWzY.GOT29asWOIUPZ4kjnn1/3ge59x33Bmdhm', 'jcpV7qaB', 'Фоменко Татьяна Михайловна', '+79137725424', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:06:12', '2026-08-27 15:16:59'),
(19, '79231665319@phone.local', '$2y$10$hye2QyjC4jBT8uqiXfZTGOMGHdtvadrNsyf8AloaM17CrYF6Ddb6u', 'kbuvFC5Q', 'Шумаков Василий Дмитриевич', '+79231665319', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:08:40', '2026-08-27 15:17:15'),
(20, '79039386799@phone.local', '$2y$10$agkMU3qHrhLCwVm51mNMpuPd56SEQ1GnB6ZCYEu3pqAMmAA.wT22W', 'NmUes2eQ', 'Сухоруков Дмитрий Сергеевич', '+79039386799', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:09:39', '2026-08-27 15:16:30'),
(21, '79293936723@phone.local', '$2y$10$Yp//sEjeYbAtgqAqMbLQU.p1aPv7Mj9cBCnu5.kOLRMSrwWstwfzi', 'AqcA977q', 'Руденко Елена Федоровна', '+79293936723', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:11:21', '2026-08-27 15:15:42'),
(22, '79831231556@phone.local', '$2y$10$vMHww5Npl.atU6Uzg02dTOdxxskS9LQP3CgOMqDc1Fm71VjhwjBiO', '9Svmy7rP', 'Нестеренко Наталья Николаевна', '+79831231556', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:12:16', '2026-08-27 15:14:59'),
(23, '79095319910@phone.local', '$2y$10$qucky/O0zLy600hm8ICXoupugWcusz1enw3mZiBHL3U4vaVmG7Sx.', '5NtA4rFH', 'Пискарева Татьяна Борисовна', '+79095319910', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:12:51', '2026-08-27 15:15:11'),
(24, '79231900360@phone.local', '$2y$10$Wbkd5GxWbHPkZhhgcfo.3OEh/.7KdcqMA9pk9TVlGF9Sln.U2BoeC', 'mnAd6ZxH', 'Шарушинская Ольга Владимировна', '+79231900360', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:13:25', '2026-08-27 15:17:07'),
(25, '79231122723@phone.local', '$2y$10$x8DnjVQLk.qZyqsIhWnQTOdI4xhOJZiqDTpz983npkadjnIBTjUkO', 'DhdBSbpb', 'Ткаченко Светлана Михайловна', '+79231122723', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:14:26', '2026-08-27 15:16:41'),
(26, '79232323819@phone.local', '$2y$10$ZfXFbQfvNVbWcQa1W5DA2Olr4.DsCz1eqOdl8z8H8FbF.k1onoB/O', 'ZcfXYdXp', 'Кузнецова Ольга Николаевна', '+79232323819', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:14:55', '2026-08-27 15:14:50'),
(27, '79513969858@phone.local', '$2y$10$GU6ARTQwSVgImJsMg9xJeOCFBob176RSSO7o4cY/Go9kjJfroPdTG', 'ryVEqCPk', 'Ишутина Алла Федоровна', '+79513969858', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:15:25', '2026-08-27 15:14:27'),
(28, '79231488053@phone.local', '$2y$10$pT2OSQNeBdN7eyAP1xRHF.uPkyNf.RMO7RnEV5Jq19eVz8T5u.OcG', 'pHjt6m5s', 'Вульф Дарья Александровна', '+79231488053', '', '', NULL, 'icon:default', 'teacher', 1, '2026-08-23 08:16:04', '2026-08-27 08:00:10'),
(29, 'gritsuk.vladimio@student.local', '$2y$10$yLnaIqJRgFOhudK1xvVbKOQ3udY6OxCpk/vJBdNIjwXMcER5uhid.', '8vAvaJwJ', 'Грицук Владимир Владимирович', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-24 05:37:40', '2026-08-26 16:21:06'),
(30, 'astahov.maksim@student.local', '$2y$10$gqitL6s7VE6WvgQWZFrmPud5DIwoQw1ytTAyfEpwO.BMC8dSVNzq6', 'BaNKcza7', 'Астахов Максим Викторович', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-24 11:55:59', '2026-08-24 11:55:59'),
(31, 'sidorov.konstantin@student.local', '$2y$10$BN6fltkeiF/O4Zk5QaF4he6rcYr5Lsvv6NIcI4kzdXsrF6jDccmQq', 'AZST55ZG', 'Сидоров Константин Иванович', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-24 15:30:38', '2026-08-24 15:30:38'),
(33, 'uteuov.baurzhan@student.local', '$2y$10$TIUFqfV6Vqkvehp/4gEfLuTNlg2KUeqhe950QBgK/iKf1zxGhExyO', 'USzzn3EU', 'Утеуов Бауржан Ермекович', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:27:36', '2026-08-26 16:27:36'),
(34, 'isakova.marina@student.local', '$2y$10$0wzD7bzR96O34ZFR9rieC.qUTGC4gW7N5P1zKy/S6sFtdMLvybArC', '9aEhqKft', 'Исакова Марина Петровна', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:29:18', '2026-08-26 16:29:18'),
(35, 'shalnov.grigoriy@student.local', '$2y$10$QOU57AFTlHChxYslTqfj7.mXubpVnQ6eswbkXllcy5US1mkMzMspe', 'GarDtrtS', 'Шальнов Григорий Игоревич', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:29:49', '2026-08-26 16:29:49'),
(36, 'malahov.valentin@student.local', '$2y$10$gwQkTsaaUfUd7rgMZxzDjuCkmLS8wYBLFWfbQhoGsVix1jSpkSCJO', 'wKr5bctc', 'Малахов Валентин Дмитриевич', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:30:29', '2026-08-26 16:30:29'),
(37, 'vetrova.svetlana@student.local', '$2y$10$0waGm.Zv3ZcQo/kMpZJnFegkHIJKD.BlezqIWqHc2Mxzpi61cO1wy', 'BtcgMQQf', 'Ветрова Светлана Кириловна', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:52:42', '2026-08-26 16:52:42'),
(38, 'ivanova.anastasiya@student.local', '$2y$10$3RnmraZ1L33l/rfRa5q45.QROoa/SGVFlpMii0ibQcV/EwPVxAD8S', 'FNfafp4T', 'Иванова Анастасия Михайловна', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:53:22', '2026-08-26 16:53:22'),
(39, 'mihaylova.tatyana@student.local', '$2y$10$HVQGquIfS8O42Dcf8LrpvuyIj2UH0jMlkXMIKjmz5/ujT8HSEPhoy', '6k3smvZw', 'Михайлова Татьяна Игоревна', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-26 16:55:20', '2026-08-26 16:55:20'),
(40, 'aleshina.evelenina@student.local', '$2y$10$vwonWekije0mjCPfOppvJOzZIxYJB6K2vOIvkDXeaNuI8C1FcRoAS', '2FuFFZSA', 'Алешина Эвеленина Игоревна', '89237047174', '', '', NULL, 'icon:default', 'student', 1, '2026-08-27 05:37:54', '2026-08-27 05:37:54'),
(41, 'ivanov.ivan@student.local', '$2y$10$JdkEq4Dt8NnBmDIh7awKveYLkbOzRx8h1CQ98fl3qU.9kP9204/tu', '2jwh4GBw', 'Иванов Иван Иванович', '', '', '', NULL, 'icon:default', 'student', 1, '2026-08-27 06:26:58', '2026-08-27 06:26:58');

-- --------------------------------------------------------

--
-- Структура таблицы `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int UNSIGNED NOT NULL,
  `role` enum('teacher','curator','deputy','educator') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
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
(15, 'curator'),
(16, 'teacher'),
(16, 'curator'),
(17, 'teacher'),
(17, 'curator'),
(18, 'teacher'),
(19, 'teacher'),
(20, 'teacher'),
(20, 'curator'),
(21, 'teacher'),
(21, 'curator'),
(22, 'teacher'),
(22, 'curator'),
(23, 'teacher'),
(23, 'curator'),
(24, 'teacher'),
(25, 'teacher'),
(26, 'teacher'),
(26, 'curator'),
(27, 'teacher'),
(28, 'teacher'),
(28, 'curator');

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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT для таблицы `archive_gradebook_grades`
--
ALTER TABLE `archive_gradebook_grades`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT для таблицы `archive_gradebook_groups`
--
ALTER TABLE `archive_gradebook_groups`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `archive_gradebook_students`
--
ALTER TABLE `archive_gradebook_students`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT для таблицы `archive_gradebook_subjects`
--
ALTER TABLE `archive_gradebook_subjects`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT для таблицы `archive_grade_changes`
--
ALTER TABLE `archive_grade_changes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `archive_journal_grades`
--
ALTER TABLE `archive_journal_grades`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=572;

--
-- AUTO_INCREMENT для таблицы `archive_journal_items`
--
ALTER TABLE `archive_journal_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT для таблицы `archive_journal_lessons`
--
ALTER TABLE `archive_journal_lessons`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT для таблицы `archive_journal_students`
--
ALTER TABLE `archive_journal_students`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT для таблицы `archive_journal_topics`
--
ALTER TABLE `archive_journal_topics`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT для таблицы `archive_journal_totals`
--
ALTER TABLE `archive_journal_totals`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- AUTO_INCREMENT для таблицы `archive_periods`
--
ALTER TABLE `archive_periods`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `attendance_days`
--
ALTER TABLE `attendance_days`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `attendance_entries`
--
ALTER TABLE `attendance_entries`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=378;

--
-- AUTO_INCREMENT для таблицы `journal_lessons`
--
ALTER TABLE `journal_lessons`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT для таблицы `ktp_topics`
--
ALTER TABLE `ktp_topics`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT для таблицы `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблицы `notification_reads`
--
ALTER TABLE `notification_reads`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `specialties`
--
ALTER TABLE `specialties`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `students`
--
ALTER TABLE `students`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=476;

--
-- AUTO_INCREMENT для таблицы `student_transfers`
--
ALTER TABLE `student_transfers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `study_groups`
--
ALTER TABLE `study_groups`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT для таблицы `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

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
