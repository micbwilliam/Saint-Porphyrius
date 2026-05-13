# Release v6.3.1 — Hotfix: AI Integration

**Date:** 2026-05-13

## 🐛 Fix

- **AI API call visibility** — `SP_Quiz_AI::call_api()` changed from `private` to `public`, fixing "missing identifier" errors in the Lesson Preparation System's quiz generation and AI content detection features. Removed fragile ReflectionClass-based workarounds in `SP_Lesson_Prep` in favor of direct method calls.
