# Release v6.3.0 — نظام تحضير الدروس (Lesson Preparation System)

**Date:** 2026-05-13

## 🆕 New Feature: Lesson Preparation System (نظام تحضير الدروس)

A complete, integrated module for structured lesson delivery, AI-assisted quiz generation, guided preparation workflow, and admin review — all within the Saint Porphyrius platform.

### Core Capabilities

- **Lesson Management** — Create lessons linked to events, assign grades (1–6), upload PDFs, configure quizzes
- **AI Quiz Generation** — Extract text from PDFs and generate child-appropriate Christian quiz questions via OpenAI (GPT-4o)
- **7-Step Preparation Wizard** — Guided workflow for eligible members to prepare lessons section by section
- **AI Content Detection** — Two-tier detection (LLM-as-judge + heuristics) for the "كتابة الدرس" section with configurable penalties
- **Admin Review Dashboard** — Per-section point adjustment, AI detection reports, approve/revision workflow
- **Points Integration** — Quiz completion points + preparation approval points via existing `SP_Points` system
- **Grade-Based Access Control** — Per-lesson, per-grade user whitelists

### New Database Tables

| Table | Purpose |
|-------|---------|
| `wp_sp_lessons` | Lesson metadata, event linkage, PDF URLs, quiz/prep configs |
| `wp_sp_lesson_access` | Per-grade per-user access whitelist |
| `wp_sp_lesson_quiz_questions` | Quiz questions per lesson |
| `wp_sp_lesson_quiz_attempts` | User quiz submission records |
| `wp_sp_lesson_preparations` | 7-section preparation data + AI detection results |
| `wp_sp_lesson_prep_config` | Global settings key-value store |
| `wp_sp_lesson_ai_log` | Immutable AI action audit trail |

### New Routes

| Route | Access |
|-------|--------|
| `/app/lesson-prep` | Member — lesson list |
| `/app/lesson-prep/prepare/{id}` | Member — 7-step preparation wizard |
| `/app/lesson-prep/quiz/{id}` | Member — take quiz |
| `/app/lesson-prep/view/{id}` | Member — view preparation |
| `/app/admin/lesson-prep` | Admin — lesson management |
| `/app/admin/lesson-prep/create` | Admin — 6-step creation wizard |
| `/app/admin/lesson-prep/edit/{id}` | Admin — edit lesson |
| `/app/admin/lesson-prep/review` | Admin — review queue |
| `/app/admin/lesson-prep/review/{id}` | Admin — review detail |
| `/app/admin/lesson-prep/settings` | Admin — global settings |

### New AJAX Endpoints (20)

`sp_lesson_create`, `sp_lesson_update`, `sp_lesson_delete`, `sp_lesson_get`, `sp_lessons_list`, `sp_lesson_pdf_upload`, `sp_lesson_quiz_generate`, `sp_lesson_quiz_save`, `sp_lesson_quiz_get`, `sp_lesson_access_set`, `sp_lesson_access_get`, `sp_lesson_users_by_grade`, `sp_lesson_review_approve`, `sp_lesson_review_revision`, `sp_lesson_review_list`, `sp_lesson_config_update`, `sp_lesson_quiz_submit`, `sp_lesson_prep_save`, `sp_lesson_prep_get`, `sp_lesson_prep_my_list`, `sp_lesson_ai_detect`

### New Files

| File | Purpose |
|------|---------|
| `includes/class-sp-lesson-prep.php` | Core singleton class — all business logic |
| `migrations/2026_05_13_000001_create_lesson_prep_tables.php` | Database schema migration |
| `templates/unified/lesson-prep.php` | User lesson list |
| `templates/unified/lesson-prep-quiz.php` | Quiz-taking interface |
| `templates/unified/lesson-prep-prepare.php` | 7-step preparation wizard |
| `templates/unified/lesson-prep-view.php` | View submitted preparation |
| `templates/unified/admin/lesson-prep.php` | Admin lesson management |
| `templates/unified/admin/lesson-prep-create.php` | Admin creation wizard (create + edit) |
| `templates/unified/admin/lesson-prep-edit.php` | Edit lesson (reuses create template) |
| `templates/unified/admin/lesson-prep-review.php` | Admin review queue |
| `templates/unified/admin/lesson-prep-review-detail.php` | Detailed review with point adjustment |
| `templates/unified/admin/lesson-prep-settings.php` | Global configuration |

### Modified Files

- `saint-porphyrius.php` — Added class require, 10 rewrite rules, 2 query vars, flush version bump
- `templates/app-wrapper.php` — Added 10 route cases, page titles, route arrays
- `includes/class-sp-ajax.php` — Added 20 AJAX handlers
- `templates/unified/admin/dashboard.php` — Added admin nav link

### Dependencies

- OpenAI API key (reuses existing `sp_quiz_settings` configuration)
- `pdftotext` on server (recommended for PDF text extraction; falls back gracefully)

### Configuration Defaults

- Section points: 10-30 per section (total 100)
- AI detection threshold: 70%
- AI penalty: 50% of writing section points
- Quiz defaults: 10 questions, 50 points, 60% passing
- Quiz required before preparation: Yes
- Max preparation submissions: 3
