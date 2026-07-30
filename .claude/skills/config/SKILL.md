---
name: config
description: Configuration settings for the application. Use when working with writing, editing or deleting configuration values in the /config folder, or manipulating config() or env() variables.
---

# Config

## Rules

- As much as possible, stick to the existing config structure and conventions.
- Only call `config()` in the code, never `env()`.
- Write specific config values dedicated to OfficeLife in the `config/officelife.php` files.
- Document config values in `config/*.php` following Laravel's conventions: 3 lines of comments per value.
- Document config values in `.env.example`.
