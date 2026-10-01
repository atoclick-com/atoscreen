# AtoFood Digital Signage Management System

A high-performance, SaaS-grade Digital Signage Management Web Application built with **Laravel 12** and **Vue 3 (Composition API)**. Designed specifically for restaurant and shop owners to control promotional content displayed on smart TVs (Samsung Tizen, LG webOS, Android TV, Fire TV) via kiosk browsers.

---

## 🌟 Highlights & Key Features

### 1. Rock-Solid Public TV Display Engine (`/display/{uuid}`)
- **Kiosk Ready**: Zero authentication required, no navigation chrome, pure black background, cursor auto-hides after 3 seconds.
- **Zero-Flicker Asset Preloading**: Double-buffered asset caching preloads the next image or video asset before transitioning, eliminating blank screen flashes.
- **Cinematic Ken Burns Effect**: Subtle zoom and panning animation for food and promotional imagery (`animate-ken-burns`).
- **Seamless Video Support**: HTML5 muted autoplay with immediate advance to the next slide on `@ended` or fallback on error.
- **Chef Special HTML Promo Cards**: Sleek built-in rendering for dish specials, badges (`🔥 CHEF SPECIAL`), prices, subtitles, and gradient presets.
- **Overlay Widgets**:
  - **Live Digital Clock & Date**: 24h / 12h formats + live date with glassmorphic backdrop.
  - **Bottom Marquee Ticker**: Continuous smooth scrolling banner for announcements, Wi-Fi passwords, and chef specials.
  - **Branding Overlay**: Custom logo upload or AtoFood badge positioned via a **3x3 visual grid**.
- **Offline Resilience**: Automatically caches the active playlist in `localStorage`. If the restaurant's network drops, playback continues looping seamlessly with an unobtrusive offline status pill.
- **Heartbeat & Telemetry**: Sends periodic pings to the backend (`POST /api/v1/display/{uuid}/ping`) logging slide plays for analytics and updating screen online status.
- **Background Playlist Polling**: Polls every 60s (configurable) and stages updates without interrupting the current slide mid-play.
- **Kiosk Shortcuts**: Press `F` or double-click to toggle fullscreen; `Space` / `ArrowRight` to advance slide.

### 2. Linear / Vercel SaaS Admin Dashboard
- **Modern Aesthetic**: Deep onyx dark mode, curated color tokens, HSL accent colors, subtle borders, and smooth micro-animations.
- **Screen Fleet Management**:
  - Card grid showing screen thumbnails, resolution, and slide counts.
  - Real-time online/offline status with a pulsing green indicator.
  - One-click copy for the public TV link.
- **Slide Playlist Editor**:
  - Reorderable drag-and-drop slide sequence powered by `vuedraggable` / `SortableJS`.
  - Drag-and-drop media upload zone validating formats (`JPEG, PNG, WEBP, MP4, WebM`) and file sizes (up to 100MB).
  - Promo Card Designer modal with live real-time preview card as you type.
  - Slide active/inactive toggle switch without deleting.
  - Date range & Day-of-week schedule picker (e.g. weekend brunch, weekday lunch).
- **Screen Settings & Branding**:
  - Visual **3x3 position selector grid** for logo placement.
  - Interactive ticker input with a **live real-time preview strip** below it.
  - Default duration slider (3s to 60s) and transition selector (fade, slide, zoom, cut).
  - Instant **Smart TV Pairing QR Code** for quick pairing on TV or mobile.
  - Danger Zone: Regenerate TV URL, Reset Slides, or Delete Screen.
- **Slide Play Analytics**:
  - Visual play distribution bar chart.
  - Total impressions and peak slide statistics.

---

## 🗄️ Database Architecture

### `screens`
| Field | Type | Description |
|---|---|---|
| `id` | `uuid` (PK) | Unique screen identifier used in public URL |
| `user_id` | `foreignId` | Owner / client identifier |
| `name` | `string` | Human-readable name (e.g. "Front Window TV") |
| `last_ping_at` | `timestamp` | Updated by display heartbeat |
| `default_slide_duration` | `integer` | Default seconds per slide (default: 10s) |
| `transition_effect` | `string` | `fade`, `slide`, `zoom`, `none` |
| `orientation` | `string` | `landscape` or `portrait` |
| `resolution_hint` | `string` | `1920x1080`, `3840x2160`, etc. |

### `slides`
| Field | Type | Description |
|---|---|---|
| `id` | `bigint` (PK) | Auto-increment ID |
| `screen_id` | `uuid` (FK) | Target screen |
| `type` | `string` | `image`, `video`, `html_promo` |
| `file_path` | `string` | Local disk or remote URL |
| `content` | `json` | Promo headline, badge, price, gradient, colors |
| `title` | `string` | Internal name |
| `display_order` | `integer` | Sequence order in playlist |
| `duration_override`| `integer` | Optional custom duration in seconds |
| `active` | `boolean` | Playback toggle |
| `start_date` / `end_date` | `datetime` | Scheduled promo window |
| `day_of_week_schedule` | `json` | Days active (e.g. `[0, 6]` for weekends) |

### `screen_settings`
| Field | Type | Description |
|---|---|---|
| `screen_id` | `uuid` (FK, unique) | Target screen |
| `logo_overlay_enabled` | `boolean` | Logo toggle |
| `logo_path` | `string` | Uploaded brand logo |
| `logo_position` | `string` | `top-left`, `top-right`, `center`, etc. (3x3 grid) |
| `accent_color` | `string` | Hex color (default: `#f59e0b`) |
| `ticker_enabled` | `boolean` | Marquee strip toggle |
| `ticker_text` | `text` | Announcement text |
| `ticker_speed` | `integer` | Seconds per loop (default: 25s) |
| `clock_widget_enabled` | `boolean` | Clock toggle |
| `clock_format` | `string` | `24h` or `12h` |
| `auto_refresh_interval`| `integer` | TV polling interval in seconds |

### `slide_plays` (Analytics)
| Field | Type | Description |
|---|---|---|
| `id` | `bigint` (PK) | Auto-increment ID |
| `screen_id` | `uuid` (FK) | Screen where slide played |
| `slide_id` | `bigint` (FK) | Slide played |
| `played_at` | `timestamp` | Heartbeat timestamp |
| `duration_seconds` | `integer` | Play duration |

---

## 🔌 API Endpoints Summary

### Public Display Routes (No Auth)
- `GET /api/v1/display/{uuid}/playlist` — Returns screen config, settings, and filtered active slides matching schedule.
- `POST /api/v1/display/{uuid}/ping` — Heartbeat telemetry updating `last_ping_at` and recording slide impressions.

### Authentication Routes
- `POST /api/v1/auth/login` — Sign in with email & password, returns Sanctum bearer token.
- `POST /api/v1/auth/register` — Account registration.
- `GET /api/v1/auth/me` — Authenticated user profile (`auth:sanctum`).
- `POST /api/v1/auth/logout` — Revoke token (`auth:sanctum`).

### Screen & Slide Management (`auth:sanctum`)
- `GET /api/v1/screens` — List all screens with counts and thumbnails.
- `POST /api/v1/screens` — Create screen.
- `GET /api/v1/screens/{id}` — Get screen details, slides, and settings.
- `PUT /api/v1/screens/{id}` — Update screen properties.
- `DELETE /api/v1/screens/{id}` — Delete screen.
- `POST /api/v1/screens/{id}/regenerate-url` — Invalidate old TV link and generate fresh UUID.
- `POST /api/v1/screens/{id}/reset` — Reset screen to defaults and clear slides.
- `GET /api/v1/screens/{id}/analytics` — Slide play distribution and impressions.
- `POST /api/v1/screens/{id}/slides` — Upload slide (image/video/promo).
- `POST /api/v1/screens/{id}/slides/batch-upload` — Batch upload multiple media files at once.
- `POST /api/v1/screens/{id}/slides/reorder` — Reorder playlist sequence.
- `PUT /api/v1/slides/{id}` — Update slide details.
- `POST /api/v1/slides/{id}/toggle-active` — Toggle slide pause/play.
- `DELETE /api/v1/slides/{id}` — Delete slide.
- `POST /api/v1/screens/{id}/settings` — Save branding and display settings.
- `POST /api/v1/screens/{id}/settings/logo` — Upload screen logo.

---

## 🚀 Quick Start Guide

### 1. Default Credentials
- **Email**: `admin@atofood.com`
- **Password**: `password123`
*(A convenient "Auto-fill Admin" button is available on the login page)*

### 2. Available Routes
- **Admin Dashboard**: `http://localhost:8000/`
- **Login**: `http://localhost:8000/login`
- **Public TV Display**: `http://localhost:8000/display/{uuid}`
  - Example 1 (Front Window TV): `http://localhost:8000/display/c4ee3683-a741-432c-99b0-95d4a7fd681d`
  - Example 2 (Order Counter Screen): `http://localhost:8000/display/2cb61a3c-fe6e-4f32-adf0-e4a01d03228a`
