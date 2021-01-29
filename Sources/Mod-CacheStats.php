<?php
/**
 * @package SMF Cache Stats
 * @file Mod-CacheStats.php
 * @author digger <digger@mysmf.net> <http://mysmf.net>
 * @copyright Copyright (c) 2017-2018, digger
 * @license The MIT License (MIT) https://opensource.org/licenses/MIT
 * @version 1.0
 */

if (!defined('SMF')) {
    die('Hacking attempt...');
}


/**
 * Load all needed hooks
 */
function loadCacheStatsHooks()
{
    add_integration_function('integrate_menu_buttons', 'addCacheStatsCopyright', false);
    add_integration_function('integrate_admin_areas', 'viewCacheStats', false);
}

function prepareCacheStatsInfo($stats = array(), $title = '')
{
    global $txt;

    $text =
        '<div class="information">' .
        '<dl class="settings">' .
        '<dt><strong>' . ucwords($title) . '</strong></dt><dd>&nbsp;</dd>';

    foreach ($stats as $key => $value) {
        $text .= '<dt>' . $txt[$key] . '</dt><dd>' . $value . '</dd>';
    }

    $text .= '</dl></div>';

    return $text;
}

function replaceBufferCacheStats($buffer)
{
    global $context;

    if (empty($context['cacheStatsBuffer'])) {
        return $buffer;
    }

    $buffer = str_replace(
        '<hr class="hrcolor clear" />',
        '<hr class="hrcolor clear" />' . $context['cacheStatsBuffer'] . '<hr class="hrcolor clear" />',
        $buffer
    );

    return $buffer;
}

/**
 * Add mod admin area
 * @return bool
 */

function viewCacheStats()
{
    global $sourcedir, $context, $modSettings;
    require_once($sourcedir . '/Class-CacheStats.php');
    loadLanguage('CacheStats/CacheStats');

    if (!empty($context['current_action']) && $context['current_action'] == 'admin' && $context['current_subaction'] == 'cache') {
        add_integration_function('integrate_buffer', 'replaceBufferCacheStats', false);
    }

    // Check for opcache
    $opcacheStats = new CacheStats('opcache');
    $opcacheStats->getStats();

    $context['cacheStatsBuffer'] = prepareCacheStatsInfo($opcacheStats->stats, $opcacheStats->cache);

    $cacheStats = new CacheStats();

    if (!$cacheStats->getStats()) {
        return false;
    }

    $context['cacheStatsBuffer'] .= prepareCacheStatsInfo($cacheStats->stats, $cacheStats->cache);

    unset($opcacheStats, $cacheStats);
    return true;
}

/**
 * Add mod copyright to the forum credit's page
 */
function addCacheStatsCopyright()
{
    global $context;

    if ($context['current_action'] == 'credits') {
        $context['copyrights']['mods'][] = '<a href="https://mysmf.net/mods/cache-stats" target="_blank">Cache Stats</a> &copy; 2017-2021, digger';
    }
}
