<?php

namespace Fastmag;

/**
 * @codeCoverageIgnore
 */
class Common {
    protected $profilers = [];

    public function start_profiler($key) {
        $now = microtime(TRUE);
        $this->profilers[$key] = $now;
        return true;
    }

    public function end_profiler($key) {
        $time = $this->get_profiler($key);
        unset($this->profilers[$key]);
        return $time;
    }

    public function get_profiler($key) {
        $time = round(microtime(TRUE) - $this->profilers[$key], 2);
        return $time;
    }
}
