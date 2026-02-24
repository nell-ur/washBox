# Copilot instructions for WashBox (backend + mobile) ✅

Purpose: Short, actionable guidance for AI coding agents to become productive quickly with this repository.

## Big picture 🧭
- Monorepo with two main apps:
  - **backend/** — Laravel 12 monolith that serves both web admin/staff UIs (Blade in `resources/views/{admin,staff}`) and a versioned JSON API under `routes/api.php` (prefix `/v1`).
  - **mobile/** — Expo React Native app (file-based routing) that calls backend API (`API_BASE_URL` in `mobile/constants/config.js`).
- Separation of concerns:
  - Web UI routes/use-cases live in `routes/web.php` (Admin/Staff middleware).
  - Mobile & SPA clients use the API controllers in `app/Http/Controllers/Api/`.
  - Auth: API uses Laravel Sanctum tokens; web uses `auth:sanctum` plus `admin`/`staff` middleware.

## How to get a dev environment running (exact commands) ⚙️
- Backend (dev):
  - Install + build: `composer install` then `npm install` (or run `composer run-script setup` to do setup script defined in `composer.json`).
  - Run migrations + seed sample data: `php artisan migrate:fresh --seed` (Admin creds are printed by `AdminUserSeeder`: `admin@washbox.com / Admin@123`).
  - Link storage for public assets: **`php artisan storage:link`** (views use `asset('storage/...')`).
  - Start helpers & dev server: `composer run-script dev` runs `php artisan serve`, queue listener, `pail`, and Vite concurrently.
  - Run tests: `composer test` or `php artisan test`. To run a single test class: `php artisan test --filter=PromotionValidationApiTest`.

- Mobile:
  - Install + start: inside `mobile/`: `npm install` then `npx expo start`.
  - Set `API_BASE_URL` in `mobile/constants/config.js` depending on environment (emulator vs device): examples are commented in that file (10.0.2.2 for Android emulator, localhost for iOS simulator, device IP for real device).

## Common patterns & conventions (do not deviate) 📐
- API versioning: always add new REST endpoints under `routes/api.php` inside `Route::prefix('v1')->group(...)`.
- Controllers: API controllers live in `app/Http/Controllers/Api/`. Web/admin controllers live in `app/Http/Controllers/Admin/` or `Staff/` namespaces.
- Resource routes + toggles: Admin resource routes use `Route::resource(...)` and often add action endpoints like `POST /{resource}/toggle-status` (e.g., `services.toggle-status`). Follow existing naming patterns.
- Storage: images use `Storage::disk('public')` and are served via `asset('storage/...')` — remember to set `FILESYSTEM_DISK=public` in `.env` for public access in dev/production.
- Auth tokens: `AuthController@login` and `register` return a `token` (Sanctum plainTextToken). Mobile stores it in AsyncStorage under key `STORAGE_KEYS.TOKEN` and sends `Authorization: Bearer <token>` in headers (see `mobile/app/notifications.js` for examples).
- Frontend/Blade: many admin/staff pages contain inline JavaScript that fetches `/api/v1/...`—update both the route **and** any inline fetches when renaming endpoints.

## Integration points & external services 🔌
- Google Maps: `alexpechkarev/google-maps` used for directions/route optimizations (`LogisticsController`). Put API keys in env/config per package docs.
- Firebase/FCM: `kreait/laravel-firebase` present; FCM settings are edited in Admin `SettingsController` and persisted in system settings.
- Storage: local `public` disk is used for user-uploaded images; production may use S3 — check `FILESYSTEM_DISK`.

## Tests & examples to follow 🧪
- Look at `tests/Feature/PromotionValidationApiTest.php` for style and API contract examples (use `getJson` / `postJson` and assert JSON structure and success boolean).
- When adding API behavior, add feature tests that call `/api/v1/...` endpoints and validate `success` and `data` shapes (this repo follows that pattern).

## Developer workflow notes & tips 💡
- Use `composer run-script dev` during development (it runs queue, log tailing, and Vite). For short tasks, `php artisan serve` + `npm run dev` are sufficient.
- Use `make_all_models.sh` when scaffolding many models (it runs `php artisan make:model -m` for the curated list).
- When changing DB schema, update seeders in `database/seeders/` so `php artisan migrate:fresh --seed` leaves the system in a known state.
- Check `routes/web.php` for admin/staff UI endpoints (Blade pages) and `routes/api.php` for mobile/API endpoints.

## Where AI should be cautious ⚠️
- Modifying authentication flows or token handling requires testing both Mobile and Web flows. Mobile expects a `token` in JSON response and uses `Authorization: Bearer` headers.
- Public URLs for images depend on `php artisan storage:link` + `FILESYSTEM_DISK` and `asset('storage/...')` usage; don't assume direct `public/` file paths.
- Follow the existing naming and HTTP verb conventions (e.g., use POST for `mark-all-read`, DELETE for clearing read notifications).

---
If anything is unclear or you'd like the instructions to include more examples (e.g., a checklist for adding an API endpoint or an example test template), tell me which areas to expand and I'll update this file. 🔧
