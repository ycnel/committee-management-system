<?php
/**
 * includes/ai_config.php
 * ------------------------------------------------------------------
 * Default configuration for the Google Gemini decision-support
 * layer. These are FALLBACK defaults only — an administrator can
 * override every one of them at runtime from Workload Distribution
 * -> Smart AI Settings -> Google Gemini AI, which writes to the
 * `ai_system_settings` table (see database/migration_ai_hybrid.sql).
 * The AI service loader reads the DB first and falls back to
 * these constants only if a setting has never been saved.
 *
 * The Gemini API key is read from the GEMINI_API_KEY environment variable
 * and is never stored in the database or sent to the browser.
 * ------------------------------------------------------------------
 */

if (!defined('GEMINI_ENABLED'))  define('GEMINI_ENABLED', true);
if (!defined('GEMINI_MODEL'))    define('GEMINI_MODEL', 'gemini-3.5-flash');
if (!defined('GEMINI_TIMEOUT'))  define('GEMINI_TIMEOUT', 30); // seconds, request timeout
if (!defined('GEMINI_CONNECT_TIMEOUT')) define('GEMINI_CONNECT_TIMEOUT', 5); // seconds, TCP connect timeout
if (!defined('GEMINI_CACHE_MINUTES')) define('GEMINI_CACHE_MINUTES', 10); // reuse a recent AI answer if candidate data hasn't changed
