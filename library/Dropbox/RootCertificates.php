<?php
namespace Dropbox;

/**
 * See: {@link RootCertificates::useExternalPaths()}
 */
class RootCertificates
{
    /* @var boolean */
    private static $useExternalFile = false;

    /* @var string[]|null */
    private static $paths = null;  // A tuple of (rootCertsFilePath, rootCertsFolderPath)

    /**
     * If you're running within a PHAR, call this method before you use the SDK
     * to make any network requests.
     *
     * Normally, the SDK tells cURL to look in the "certs" folder for root certificate
     * information.  But this won't work if this SDK is running from within a PHAR because
     * cURL won't read files that are packaged in a PHAR.
     */
    static function useExternalPaths()
    {
        if (!self::$useExternalFile and self::$paths !== null) {
            throw new \Exception("You called \"useExternalFile\" too late.  The SDK already used the root ".
                                 "certificate file (probably to make an API call).");
        }

        self::$useExternalFile = true;
    }

    private static $originalPath = '/certs/trusted-certs.crt';

    /**
     * @internal
     *
     * @return string[]
     *    A tuple of (rootCertsFilePath, rootCertsFolderPath).  To be used with cURL options CAINFO and CAPATH.
     */
    static function getPaths()
    {
        if (self::$paths === null) {
            if (self::$useExternalFile) {
                try {
                    $baseFolder = sys_get_temp_dir();
                    $file = self::createExternalCaFile($baseFolder);
                    $folder = self::createExternalCaFolder($baseFolder);
                }
                catch (\Exception $ex) {
                    throw new \Exception("Unable to create external root certificate file and folder: ".$ex->getMessage());
                }
            }
            else {
                if (substr(__DIR__, 0, 7) === 'phar://') {
                    throw new \Exception("The code appears to be running in a PHAR.  You need to call \\Dropbox\\RootCertificates\\useExternalPaths() before making any API calls.");
                }
                $file = __DIR__.self::$originalPath;
                $folder = \dirname($file);
            }
            self::$paths = array($file, $folder);
        }

        return self::$paths;
    }

    /**
     * @param string $baseFolder
     *
     * @return string
     */
    private static function createExternalCaFolder($baseFolder)
    {
        // This is hacky, but I can't find a simple way to do this.

        // This process isn't atomic, so give it three tries.
        for ($i = 0; $i < 3; $i++) {
            $path = \tempnam($baseFolder, "dropbox-php-sdk-trusted-certs-empty-dir");
            if ($path === false) {
                throw new \Exception("Couldn't create temp file in folder ".Util::q($baseFolder).".");
            }
            if (!\unlink($path)) {
                throw new \Exception("Couldn't remove temp file to make way for temp dir: ".Util::q($path));
            }
            // TODO: Figure out how to make the folder private on Windows.  The '700' only works on Unix.
            if (!\mkdir($path, 700)) {
                // Someone snuck in between the unlink() and the mkdir() and stole our path.
                throw new \Exception("Couldn't create temp dir: ".Util::q($path));
            }
            \register_shutdown_function(function() use ($path) {
                \rmdir($path);
            });
            return $path;
        }

        throw new \Exception("Unable to create temp dir in ".Util::q($baseFolder).", there's always something in the way.");
    }

    /**
     * @param string $baseFolder
     *
     * @return string
     */
    private static function createExternalCaFile($baseFolder)
    {
        $path = \tempnam($baseFolder, "dropbox-php-sdk-trusted-certs");
        if ($path === false) {
            throw new \Exception("Couldn't create temp file in folder ".Util::q($baseFolder).".");
        }
        \register_shutdown_function(function() use ($path) {
            \unlink($path);
        });

        // NOTE: Can't use the standard PHP copy().  That would clobber the locked-down
        // permissions set by tempnam().
        self::copyInto(__DIR__.self::$originalPath, $path);

        return $path;
    }

    /**
     * @param string $src
     * @param string $dest
     */
    private static function copyInto($src, $dest)
    {
        $srcFd = \fopen($src, "r");
        if ($srcFd === false) {
            throw new \Exception("Couldn't open " . Util::q($src) . " for reading.");
        }
        $destFd = \fopen($dest, "w");
        if ($destFd === false) {
            \fclose($srcFd);
            throw new \Exception("Couldn't open " . Util::q($dest) . " for writing.");
        }

        \stream_copy_to_stream($srcFd, $destFd);

        fclose($srcFd);
        if (!\fclose($destFd)) {
            throw new \Exception("Error closing file ".Util::q($dest).".");
        }
    }
}
