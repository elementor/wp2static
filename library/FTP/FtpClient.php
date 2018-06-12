<?php
namespace FtpClient;

use \Countable;
class FtpClient implements Countable
{
    protected $conn;
    private $ftp;
    public function __construct($connection = null)
    {
        if (!extension_loaded('ftp')) {
            throw new FtpException('FTP extension is not loaded!');
        }

        if ($connection) {
            $this->conn = $connection;
        }

        $this->setWrapper(new FtpWrapper($this->conn));
    }
    public function __destruct()
    {
        if ($this->conn) {
            $this->ftp->close();
        }
    }
    public function __call($method, array $arguments)
    {
        return $this->ftp->__call($method, $arguments);
    }
    public function setPhpLimit($memory = null, $time_limit = 0, $ignore_user_abort = true)
    {
        if (null !== $memory) {
            ini_set('memory_limit', $memory);
        }

        ignore_user_abort(true);
        set_time_limit($time_limit);

        return $this;
    }
    public function help()
    {
        return $this->ftp->raw('help');
    }
    public function connect($host, $ssl = false, $port = 21, $timeout = 90)
    {
        if ($ssl) {
            $this->conn = @$this->ftp->ssl_connect($host, $port, $timeout);
        } else {
            $this->conn = @$this->ftp->connect($host, $port, $timeout);
        }

        if (!$this->conn) {
            throw new FtpException('Unable to connect');
        }

        return $this;
    }
    public function close()
    {
        if ($this->conn) {
            $this->ftp->close();
            $this->conn = null;
        }
    }
    public function getConnection()
    {
        return $this->conn;
    }
    public function getWrapper()
    {
        return $this->ftp;
    }
    public function login($username = 'anonymous', $password = '')
    {
        $result = $this->ftp->login($username, $password);

        if ($result === false) {
            throw new FtpException('Login incorrect');
        }

        return $this;
    }
    public function modifiedTime($remoteFile, $format = null)
    {
        $time = $this->ftp->mdtm($remoteFile);

        if ($time !== -1 && $format !== null) {
            return date($format, $time);
        }

        return $time;
    }
    public function up()
    {
        $result = @$this->ftp->cdup();

        if ($result === false) {
            throw new FtpException('Unable to get parent folder');
        }

        return $this;
    }
    public function nlist($directory = '.', $recursive = false, $filter = 'sort')
    {
        if (!$this->isDir($directory)) {
            throw new FtpException('"'.$directory.'" is not a directory');
        }

        $files = $this->ftp->nlist($directory);

        if ($files === false) {
            throw new FtpException('Unable to list directory');
        }

        $result  = array();
        $dir_len = strlen($directory);
        if (false !== ($kdot = array_search('.', $files))) {
            unset($files[$kdot]);
        }
        if(false !== ($kdot = array_search('..', $files))) {
            unset($files[$kdot]);
        }

        if (!$recursive) {
            foreach ($files as $file) {
                $result[] = $directory.'/'.$file;
            }
            $filter($result);

            return $result;
        }
        $flatten = function (array $arr) use (&$flatten) {

            $flat = [];

            foreach ($arr as $k => $v) {
                if (is_array($v)) {
                    $flat = array_merge($flat, $flatten($v));
                } else {
                    $flat[] = $v;
                }
            }

            return $flat;
        };

        foreach ($files as $file) {
            $file = $directory.'/'.$file;
            if (0 === strpos($file, $directory, $dir_len)) {
                $file = substr($file, $dir_len);
            }

            if ($this->isDir($file)) {
                $result[] = $file;
                $items    = $flatten($this->nlist($file, true, $filter));

                foreach ($items as $item) {
                    $result[] = $item;
                }

            } else {
                $result[] = $file;
            }
        }

        $result = array_unique($result);

        $filter($result);

        return $result;
    }
    public function mkdir($directory, $recursive = false)
    {
        if (!$recursive or $this->isDir($directory)) {
            return $this->ftp->mkdir($directory);
        }

        $result = false;
        $pwd    = $this->ftp->pwd();
        $parts  = explode('/', $directory);

        foreach ($parts as $part) {
            if ($part == '') {
                continue;
	    	}

            if (!@$this->ftp->chdir($part)) {
                $result = $this->ftp->mkdir($part);
                $this->ftp->chdir($part);
            }
        }

        $this->ftp->chdir($pwd);

        return $result;
    }
    public function rmdir($directory, $recursive = true)
    {
        if ($recursive) {
            $files = $this->nlist($directory, false, 'rsort');
            foreach ($files as $file) {
                $this->remove($file, true);
            }
        }
        return $this->ftp->rmdir($directory);
    }
    public function cleanDir($directory)
    {
        if(!$files = $this->nlist($directory)) {
            return $this->isEmpty($directory);
        }
        foreach ($files as $file) {
            $this->remove($file, true);
        }

        return $this->isEmpty($directory);
    }
    public function remove($path, $recursive = false)
    {
        try {
            if (@$this->ftp->delete($path)
            or ($this->isDir($path) and @$this->rmdir($path, $recursive))) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    public function isDir($directory)
    {
        $pwd = $this->ftp->pwd();

        if ($pwd === false) {
            throw new FtpException('Unable to resolve the current directory');
        }

        if (@$this->ftp->chdir($directory)) {
            $this->ftp->chdir($pwd);
            return true;
        }

        $this->ftp->chdir($pwd);

        return false;
    }
    public function isEmpty($directory)
    {
        return $this->count($directory, null, false) === 0 ? true : false;
    }
    public function scanDir($directory = '.', $recursive = false)
    {
        return $this->parseRawList($this->rawlist($directory, $recursive));
    }
    public function dirSize($directory = '.', $recursive = true)
    {
        $items = $this->scanDir($directory, $recursive);
        $size  = 0;

        foreach ($items as $item) {
            $size += (int) $item['size'];
        }

        return $size;
    }
    public function count($directory = '.', $type = null, $recursive = true)
    {
        $items  = (null === $type ? $this->nlist($directory, $recursive)
            : $this->scanDir($directory, $recursive));

        $count = 0;
        foreach ($items as $item) {
            if (null === $type or $item['type'] == $type) {
                $count++;
            }
        }

        return $count;
    }
    public function putFromString($remote_file, $content)
    {
        $handle = fopen('php://temp', 'w');

        fwrite($handle, $content);
        rewind($handle);

        if ($this->ftp->fput($remote_file, $handle, FTP_BINARY)) {
            return $this;
        }

        throw new FtpException('Unable to put the file "'.$remote_file.'"');
    }
    public function putFromPath($local_file)
    {
        $remote_file = basename($local_file);
        $handle      = fopen($local_file, 'r');

        if ($this->ftp->fput($remote_file, $handle, FTP_BINARY)) {
            rewind($handle);
            return $this;
        }

        throw new FtpException(
            'Unable to put the remote file from the local file "'.$local_file.'"'
        );
    }
    public function putAll($source_directory, $target_directory, $mode = FTP_BINARY)
    {
        $d = dir($source_directory);
        while ($file = $d->read()) {
            if ($file != "." && $file != "..") {
                if (is_dir($source_directory.'/'.$file)) {

                    if (!$this->isDir($target_directory.'/'.$file)) {
                        $this->ftp->mkdir($target_directory.'/'.$file);
                    }
                    $this->putAll(
                        $source_directory.'/'.$file, $target_directory.'/'.$file,
                        $mode
                    );
                } else {
                    $this->ftp->put(
                        $target_directory.'/'.$file, $source_directory.'/'.$file,
                        $mode
                    );
                }
            }
        }

        return $this;
    }
    public function rawlist($directory = '.', $recursive = false)
    {
        if (!$this->isDir($directory)) {
            throw new FtpException('"'.$directory.'" is not a directory.');
        }

        $list  = $this->ftp->rawlist($directory);
        $items = array();

        if (!$list) {
            return $items;
        }

        if (false == $recursive) {

            foreach ($list as $path => $item) {
                $chunks = preg_split("/\s+/", $item);
                if (empty($chunks[8]) || $chunks[8] == '.' || $chunks[8] == '..') {
                    continue;
                }

                $path = $directory.'/'.$chunks[8];

                if (isset($chunks[9])) {
                    $nbChunks = count($chunks);

                    for ($i = 9; $i < $nbChunks; $i++) {
                        $path .= ' '.$chunks[$i];
                    }
                }


                if (substr($path, 0, 2) == './') {
                    $path = substr($path, 2);
                }

                $items[ $this->rawToType($item).'#'.$path ] = $item;
            }

            return $items;
        }

        $path = '';

        foreach ($list as $item) {
            $len = strlen($item);

            if (!$len
            || ($item[$len-1] == '.' && $item[$len-2] == ' '
            or $item[$len-1] == '.' && $item[$len-2] == '.' && $item[$len-3] == ' ')
            ){

                continue;
            }

            $chunks = preg_split("/\s+/", $item);
            if (empty($chunks[8]) || $chunks[8] == '.' || $chunks[8] == '..') {
                continue;
            }

            $path = $directory.'/'.$chunks[8];

            if (isset($chunks[9])) {
                $nbChunks = count($chunks);

                for ($i = 9; $i < $nbChunks; $i++) {
                    $path .= ' '.$chunks[$i];
                }
            }

            if (substr($path, 0, 2) == './') {
                $path = substr($path, 2);
            }

            $items[$this->rawToType($item).'#'.$path] = $item;

            if ($item[0] == 'd') {
                $sublist = $this->rawlist($path, true);

                foreach ($sublist as $subpath => $subitem) {
                    $items[$subpath] = $subitem;
                }
            }
        }

        return $items;
    }
    public function parseRawList(array $rawlist)
    {
        $items = array();
        $path  = '';

        foreach ($rawlist as $key => $child) {
            $chunks = preg_split("/\s+/", $child);

            if (isset($chunks[8]) && ($chunks[8] == '.' or $chunks[8] == '..')) {
                continue;
            }

            if (count($chunks) === 1) {
                $len = strlen($chunks[0]);

                if ($len && $chunks[0][$len-1] == ':') {
                    $path = substr($chunks[0], 0, -1);
                }

                continue;
            }

            $item = [
                'permissions' => $chunks[0],
                'number'      => $chunks[1],
                'owner'       => $chunks[2],
                'group'       => $chunks[3],
                'size'        => $chunks[4],
                'month'       => $chunks[5],
                'day'         => $chunks[6],
                'time'        => $chunks[7],
                'name'        => $chunks[8],
                'type'        => $this->rawToType($chunks[0]),
            ];

            if ($item['type'] == 'link') {
                $item['target'] = $chunks[10]; // 9 is "->"
            }
            if (is_int($key) || false === strpos($key, $item['name'])) {
                array_splice($chunks, 0, 8);

                $key = $item['type'].'#'
                    .($path ? $path.'/' : '')
                    .implode(" ", $chunks);

                if ($item['type'] == 'link') {
                    $exp = explode(' ->', $key);
                    $key = rtrim($exp[0]);
                }

                $items[$key] = $item;

            } else {
                $items[$key] = $item;
            }
        }

        return $items;
    }
    public function rawToType($permission)
    {
        if (!is_string($permission)) {
            throw new FtpException('The "$permission" argument must be a string, "'
            .gettype($permission).'" given.');
        }

        if (empty($permission[0])) {
            return 'unknown';
        }

        switch ($permission[0]) {
            case '-':
                return 'file';

            case 'd':
                return 'directory';

            case 'l':
                return 'link';

            default:
                return 'unknown';
        }
    }
    protected function setWrapper(FtpWrapper $wrapper)
    {
        $this->ftp = $wrapper;

        return $this;
    }
}
