<?php
/**
 * @package SMF Cache Stats
 * @file Class-CacheStats.php
 * @author digger <digger@mysmf.net> <http://mysmf.net>
 * @copyright Copyright (c) 2017-2021, digger
 * @license The MIT License (MIT) https://opensource.org/licenses/MIT
 * @version 1.0
 */

class CacheStats
{
    public
        $cache,
        $name,
        $rawStats,
        $stats,

        $version,
        $maxbytes,
        $bytes,
        $hits,
        $miss,
        $connections,
        $items,
        $scripts,
        $errors;
    /*
    $info['php_max'] = 0;
    $info['php_cur'] = 0;
    $info['var_max'] = 0;
    $info['var_cur'] = 0;
    $info['php_hits'] = 0;
    $info['var_hits'] = 0;
    $info['php_miss'] = 0;
    $info['var_miss'] = 0;
    $info['php_cached'] = 0;
    $info['var_cached'] = 0;
    $info['php_errors'] = 0;
    $info['var_errors'] = 0
     */

    /**
     * CacheStats constructor
     * @param string $cache
     * @return bool
     */
    public function __construct($cache = '')
    {
        if ($cache == 'opcache') {
            $this->cache = 'opcache';
        } elseif (!$this->detectCache()) {
            return false;
        }
    }


    /**
     * Detect supported optimizer
     * return void
     */
    private function detectCache()
    {
        if (function_exists('eaccelerator_put')) {
            $this->cache = 'eaccelerator';
        } elseif (function_exists('mmcache_put')) {
            $this->cache = 'mmcache';
        } elseif (function_exists('apc_store')) {
            $this->cache = 'apc';
        } elseif (function_exists('apcu_store')) {
            $this->cache = 'apcu';
        } elseif (function_exists('output_cache_put')) {
            $this->cache = 'zend';
        } elseif (function_exists('memcache_set')) {
            $this->cache = 'memcached';
        } elseif (function_exists('xcache_set')) {
            $this->cache = 'xcache';
        } else {
            $this->cache = false;
        }
    }

    /**
     * Get stats
     * @return bool
     */
    public function getStats()
    {
        switch ($this->cache) {
            case 'opcache':
                return $this->getOpcacheStats();
                break;
            case 'memcached':
                return $this->getMemcacheStats();
                break;
            case 'apc':
                return $this->getApcStats();
                break;
            case 'apcu':
                return $this->getApcuStats();
                break;
        }
    }


    /**
     * Get stats for Memcache
     * http://php.net/manual/en/memcache.getextendedstats.php
     */
    private function getMemcacheStats()
    {
        if (!class_exists('Memcache')) {
            return false;
        }

        $memcache = new Memcache();
        $servers  = $this->getMemcacheServersList(
            !empty($modSettings['cache_memcached']) ? $modSettings['cache_memcached'] : 'localhost'
        );

        foreach ($servers as $server) {
            $memcache->addServer($server);
        }

        $this->rawStats = $memcache->getExtendedStats();

        foreach ($this->rawStats as $server => $stats) {
            if (count($this->rawStats) > 1) {
                $server = '(' . $server . ')  ';
            } else {
                $server = '';
            }

            $this->stats['cache_stats_server_version'] .= $stats['version'] . $server;
            $this->stats['cache_stats_maxbytes']       .= round(
                    ($stats['limit_maxbytes']) / 1024 / 1024,
                    2
                ) . $server;
            $this->stats['cache_stats_bytes']          .= round(($stats['bytes']) / 1024 / 1024, 2) . $server;
            $this->stats['cache_stats_connections']    .= $stats['curr_connections'] . $server;
            $this->stats['cache_stats_items']          .= $stats['curr_items'] . $server;
            $this->stats['cache_stats_hits']           .= $stats['get_hits'] . $server;
            $this->stats['cache_stats_miss']           .= $stats['get_misses'] . $server;

            return true;
        }
    }

    /**
     * Get stats for Apc
     * https://php.net/manual/ru/function.apcu-cache-info.php
     */
    private function getApcStats()
    {
        if (!function_exists('apc_cache_info')) {
            return false;
        }

        $this->rawStats = apc_cache_info();

        $this->stats['cache_stats_num_slots']  = $this->rawStats['num_slots'];
        $this->stats['cache_stats_ttl']        = $this->rawStats['ttl'];
        $this->stats['cache_stats_start_time'] = timeformat($this->rawStats['start_time']);
        $this->stats['cache_stats_hits']       = $this->rawStats['num_hits'];
        $this->stats['cache_stats_miss']       = $this->rawStats['num_misses'];

        return true;
    }

    /**
     * Get stats for Apcu
     * https://php.net/manual/ru/function.apcu-cache-info.php
     */
    private function getApcuStats()
    {
        if (!function_exists('apcu_cache_info')) {
            return false;
        }

        $this->rawStats = apcu_cache_info();

        $this->stats['cache_stats_num_slots']  = $this->rawStats['num_slots'];
        $this->stats['cache_stats_ttl']        = $this->rawStats['ttl'];
        $this->stats['cache_stats_start_time'] = timeformat($this->rawStats['start_time']);
        $this->stats['cache_stats_hits']       = $this->rawStats['num_hits'];
        $this->stats['cache_stats_miss']       = $this->rawStats['num_misses'];

        return true;
    }

    /**
     * Get stats for Opcache
     * http://php.net/manual/en/function.opcache-get-status.php
     */
    private function getOpcacheStats()
    {
        if (!function_exists('opcache_get_status')) {
            return false;
        }

        $config         = opcache_get_configuration();
        $this->rawStats = opcache_get_status();

        if (!$this->rawStats['opcache_enabled']) {
            return false;
        }

        $this->stats['cache_stats_version']  = $config['version']['version'];
        $this->stats['cache_stats_maxbytes'] = round(
            ($config['directives']['opcache.memory_consumption']) / 1024 / 1024,
            2
        );
        $this->stats['cache_stats_bytes']    = round(($this->rawStats['memory_usage']['used_memory']) / 1024 / 1024, 2);
        $this->stats['cache_stats_scripts']  = $this->rawStats['opcache_statistics']['num_cached_scripts'];
        $this->stats['cache_stats_hits']     = $this->rawStats['opcache_statistics']['hits'];
        $this->stats['cache_stats_miss']     = $this->rawStats['opcache_statistics']['misses'];

        return true;
    }

    /**
     * Get stats for eAccelerator
     */
    private function getEacceleratorStats()
    {
        if (function_exists('eaccelerator_info')) {
            $this->rawStats = eaccelerator_info();
        }
    }

    /**
     * Get stats for XCache
     */
    private function getXCacheStats()
    {
        if (function_exists('xcache_info') && !ini_get('xcache.admin.enable_auth')) {
            $this->rawStats = xcache_info(XC_TYPE_VAR, 0);
        }
    }

    private function getZendCacheStats()
    {
    }

    /**
     * Get list of memcache(d) servers from settings list
     * @param string $list string with servers list
     * @return array array of servers
     */
    private function getMemcacheServersList($list = '')
    {
        if (trim($list) == '') {
            return array('localhost');
        } else {
            return array_map('trim', explode(',', $list));
        }
    }

}
