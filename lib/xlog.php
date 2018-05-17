<?php

class XLog
{
    /**
     * X-Correlation-ID
     */
    private static $crid = "-";

    /**
     * root directory for log files
     */
    private static $dir;

    /**
     * name project name
     */
    private static $name;

    /**
     * opened files
     */
    private static $fds = array();

    /**
     * turns possible null to "", and trim string
     */
    private static function _sanitize($str, $dft = "", $lower = false)
    {
        if (empty($str)) {
            return $dft;
        }
        $str = trim($str);
        if ($lower) {
            $str = strtolower($str);
        }
        if (empty($str)) {
            return $dft;
        }
        return $str;
    }

    private static function _random8()
    {
        return strtolower(str_pad(dechex(rand(0x00000000, 0xFFFFFFFF)), 8, "0", STR_PAD_LEFT));
    }

    /**
     * get crid
     */
    public static function crid()
    {
        return self::$crid;
    }

    /**
     * setup the XLog environment
     */
    public static function setup($opts)
    {
        // variables
        self::$dir = self::_sanitize($opts["dir"]);
        self::$name = self::_sanitize($opts["name"], "unnamed", true);

        // setup crid
        if (isset($_SERVER)) {
            $crid = isset($_SERVER["HTTP_X_CORRELATION_ID"]) ? isset($_SERVER["HTTP_X_CORRELATION_ID"]) : null;
            self::$crid = self::_sanitize($crid, "", true);
        }
        if (empty(self::$crid)) {
            self::$crid = self::_random8() . self::_random8();
        }

        // set response header
        @header("X-Correlation-ID: " . self::$crid);
    }

    /**
     * write log
     */
    public static function write($method, $content, $name = null)
    {
        $content = self::_sanitize($content);
        if (empty($content)) {
            return;
        }
        $name = self::_sanitize($name, self::$name, true);
        $method = self::_sanitize($method, "unnamed", true);

        // time
        list($msec, $sec) = explode(' ', microtime());
        $datetime = date('Y/m/d H:i:s', $sec) . "." . str_pad(intval($msec * 1000), 3, 0, STR_PAD_LEFT);
        $date = date('Y-m-d', $sec);

        // find existing fd or create
        $key = $method . "-" . $name . "-" . $date;
        $fd = isset(self::$fds[$key]) ? self::$fds[$key] : null;
        if (!$fd) {
            $dir = implode(DIRECTORY_SEPARATOR, array(self::$dir, $method));
            $file = implode(DIRECTORY_SEPARATOR, array($dir, $name . "." . $date . ".log"));
            // ensure dir
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            // open file
            $fd = fopen($file, "a+");
            if (!$fd) {
                return;
            }
            // cache fd
            self::$fds[$key] = $fd;
        }

        // write
        fwrite($fd, "[" . $datetime . "] CRID[" . self::$crid . "] " . $content . "\r\n");
    }

    /**
     * magic method for various log types
     */
    public static function __callStatic($method, $args)
    {
        if (count($args) == 1) {
            self::write($method, $args[0]);
        } else if (count($args) == 2) {
            self::write($method, $args[0], $args[1]);
        }
    }
}
