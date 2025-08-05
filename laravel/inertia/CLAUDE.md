# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Primary Development
- `composer dev` - Start full development environment (PHP server, queue worker, logs, and Vite dev server in parallel)
- `composer dev:ssr` - Start development with SSR support (Server-Side Rendering)

### Frontend Development
- `npm run dev` - Start Vite development server
- `npm run build` - Build frontend assets for production
- `npm run build:ssr` - Build with SSR support
- `npm run lint` - Run ESLint with auto-fix
- `npm run format` - Format code with Prettier
- `npm run format:check` - Check code formatting
- `npm run types` - Run TypeScript type checking

### Backend Development
- `php artisan serve` - Start Laravel development server
- `php artisan test` - Run PHPUnit tests
- `composer test` - Run tests with config clearing
- `php artisan migrate` - Run database migrations
- `php artisan queue:listen --tries=1` - Start queue worker
- `php artisan pail` - Monitor application logs

## Architecture Overview

This is a Laravel + Inertia.js + React application with the following key characteristics:

### Technology Stack
- **Backend**: Laravel 12 with PHP 8.2+
- **Frontend**: React 19 with TypeScript
- **Styling**: Tailwind CSS v4 with Radix UI components
- **Build Tool**: Vite 7 with Laravel Vite plugin
- **Database**: SQLite (development)
- **Testing**: PHPUnit for backend, ESLint/Prettier for frontend

### Key Framework Integrations
- **Inertia.js**: Bridges Laravel backend with React frontend (SPA-like experience without API)
- **Ziggy**: Provides Laravel route helpers in JavaScript
- **Laravel Pint**: PHP code formatting (included in dev dependencies)
- **Laravel Pail**: Real-time log monitoring

### Frontend Architecture
- **Components**: Located in `resources/js/components/` with UI components in `ui/` subdirectory
- **Pages**: Inertia pages in `resources/js/pages/` (mapped to Laravel routes)
- **Layouts**: Reusable layouts in `resources/js/layouts/` with app and auth templates
- **Hooks**: Custom React hooks in `resources/js/hooks/`
- **Types**: TypeScript definitions in `resources/js/types/`

### Backend Structure
- **Controllers**: HTTP controllers in `app/Http/Controllers/` with Auth and Settings subdirectories
- **Middleware**: Custom middleware including `HandleInertiaRequests` for shared data
- **Models**: Eloquent models in `app/Models/`
- **Routes**: Web routes in `routes/web.php` with modular auth and settings routes

### Key Features
- **Authentication**: Complete auth system with email verification
- **User Settings**: Profile and password management
- **Theme Support**: Light/dark mode with client-side persistence
- **Responsive Design**: Mobile-first with sidebar navigation
- **SSR Support**: Server-side rendering capability for better SEO

### Shared Data Flow
The `HandleInertiaRequests` middleware shares:
- App name and configuration
- User authentication state
- Ziggy route data
- Random inspirational quotes
- Sidebar state persistence

### Testing Strategy
- **Backend**: Feature and unit tests with PHPUnit
- **Environment**: In-memory SQLite for testing
- **Coverage**: Authentication, settings, and dashboard functionality