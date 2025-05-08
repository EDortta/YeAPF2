<?php declare(strict_types=1);

namespace YeAPF;

interface IBulletin
{
    public function getBinaryFile();
    public function setBinaryFile(string $binaryFile);
    public function getCharset();
    public function setCharset(string $charset);
    public function getContentType();
    public function setContentType(string $contentType);
    public function setContent(string $content);
    public function getDesiredOutputFormat();
    public function getFilename();
    public function setFilename(string $filename);
    public function getJsonFile();
    public function setJsonFile(string $jsonFile);
    public function getJsonString();
    public function setJsonString(string $jsonString);
    public function getReason();
    public function setReason(string $reason);
}

class BaseBulletin extends \YeAPF\SanitizedKeyData implements IBulletin
{
    private $contentType;
    private $characterSet;
    private $sessionToken;
    private $__jsonFile;
    private $__binaryFile;
    private $__filename;
    private $__json;
    private $__content;
    private string $__reroutePath = '';
    private bool $__needsReroute = false;
    private $reason;
    private $__desiredOutputFormat = null;

    public function __construct(string $contentType = 'application/json', $characterSet = 'UTF-8')
    {
        parent::__construct();
        $this->contentType = $contentType;
        $this->characterSet = $characterSet;
        $this->sessionToken = null;
    }

    private function __setDesiredOutputFormat(string $desiredOutputFormat)
    {
        if (null == $this->__desiredOutputFormat) {
            $this->__desiredOutputFormat = $desiredOutputFormat;
        } else {
            throw new \YeAPF\YeAPFException('Desired output format already set.');
        }
    }

    public function getDesiredOutputFormat()
    {
        return $this->__desiredOutputFormat;
    }

    public function setSessionToken(string $token)
    {
        $this->sessionToken = $token;
    }

    public function getSessionToken() 
    {
        return $this->sessionToken;    
    }
    public function setJsonFile(string $jsonFile)
    {
        $this->__setDesiredOutputFormat(YeAPF_BULLETIN_OUTPUT_TYPE_JSONFILE);
        $this->__jsonFile = $jsonFile;
    }

    public function getJsonFile()
    {
        return $this->__jsonFile;
    }

    public function setBinaryFile(string $binaryFile)
    {
        
        if (!file_exists($binaryFile)) {
            throw new \YeAPF\YeAPFException("File $binaryFile not found");
        }

        if (!is_readable($binaryFile)) {
            throw new \YeAPF\YeAPFException("File $binaryFile is not readable");
        }

        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
        // https://www.iana.org/assignments/media-types/media-types.xhtml

        $fileExtension = pathinfo($binaryFile, PATHINFO_EXTENSION);
        $contentType = match ($fileExtension) {
            'aac' => 'audio/aac',
            'abw' => 'application/x-abiword',
            'apng' => 'image/apng',
            'arc' => 'application/x-freearc',
            'avif' => 'image/avif',
            'avi' => 'video/x-msvideo',
            'azw' => 'application/vnd.amazon.ebook',
            'bin' => 'application/octet-stream',
            'bmp' => 'image/bmp',
            'bz' => 'application/x-bzip',
            'bz2' => 'application/x-bzip2',
            'cda' => 'application/x-cdf',
            'csh' => 'application/x-csh',
            'css' => 'text/css',
            'csv' => 'text/csv',
            'doc' => 'application/msword',
            'md' => 'text/markdown',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'eot' => 'application/vnd.ms-fontobject',
            'epub' => 'application/epub+zip',
            'gz' => 'application/gzip',
            'gif' => 'image/gif',
            'htm', 'html' => 'text/html',
            'ico' => 'image/vnd.microsoft.icon',
            'ics' => 'text/calendar',
            'jar' => 'application/java-archive',
            'jpeg', 'jpg' => 'image/jpeg',
            'js' => 'text/javascript',
            'json' => 'application/json',
            'jsonld' => 'application/ld+json',
            'mid', 'midi' => 'audio/midi',
            'mjs' => 'text/javascript',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'mpeg' => 'video/mpeg',
            'mpkg' => 'application/vnd.apple.installer+xml',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'oga' => 'audio/ogg',
            'ogv' => 'video/ogg',
            'ogx' => 'application/ogg',
            'opus' => 'audio/opus',
            'otf' => 'font/otf',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'php' => 'application/x-httpd-php',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'rar' => 'application/vnd.rar',
            'rtf' => 'application/rtf',
            'sh' => 'application/x-sh',
            'svg' => 'image/svg+xml',
            'tar' => 'application/x-tar',
            'tif', 'tiff' => 'image/tiff',
            'ts' => 'video/mp2t',
            'ttf' => 'font/ttf',
            'txt' => 'text/plain',
            'vsd' => 'application/vnd.visio',
            'wav' => 'audio/wav',
            'weba' => 'audio/webm',
            'webm' => 'video/webm',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'xhtml' => 'application/xhtml+xml',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml' => 'application/xml',
            'xul' => 'application/vnd.mozilla.xul+xml',
            'zip' => 'application/zip',
            '3gp' => 'video/3gpp',
            '3g2' => 'video/3gpp2',
            '7z' => 'application/x-7z-compressed',
            default => 'application/octet-stream',
        };

        $this->__setDesiredOutputFormat(YeAPF_BULLETIN_OUTPUT_TYPE_BINARYFILE);
        $this->__binaryFile = $binaryFile;

        $this->setContentType($contentType);
        $this->setFilename(basename($binaryFile));
    }

    public function getBinaryFile()
    {
        return $this->__binaryFile;
    }

    public function setFilename(string $filename)
    {
        $this->__filename = $filename;
    }

    public function getFilename()
    {
        return $this->__filename;
    }

    public function rerouteTo(string $path) : void
    {
        $this->__reroutePath = $path;
        $this->__needsReroute = true;
        $this->__setDesiredOutputFormat(YeAPF_BULLETIN_REDIRECTION);
    }

    public function getReroutingPath(): string
    {
        return ($this->__needsReroute?$this->__reroutePath:'');
    }

    public function setJsonString(string $jsonString)
    {
        $this->__setDesiredOutputFormat(YeAPF_BULLETIN_OUTPUT_TYPE_JSONSTRING);
        $this->setContentType('application/json');
        $this->__json = $jsonString;
    }

    public function getJsonString()
    {
        return $this->__json;
    }

    public function setContentType(string $contentType)
    {
        $this->contentType = $contentType;
    }

    public function setContent(string $content) 
    {
        $this->__content = $content;
    }

    public function setCharset(string $charset)
    {
        $this->characterSet = $charset;
    }

    public function setReason(string $reason)
    {
        $this->reason = $reason;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function getCharset()
    {
        return $this->characterSet;
    }

    public function getReason()
    {
        return $this->reason;
    }
}

class Http2Bulletin extends \YeAPF\BaseBulletin
{
    public function __construct(string $contentType = 'application/json', $characterSet = 'UTF-8')
    {
        parent::__construct($contentType, $characterSet);
    }

    public function __invoke(
        int $return_code,
        \OpenSwoole\Http\Request $request,
        \OpenSwoole\Http\Response $response
    ) {
        $response->header('Content-Type', $this->getContentType() . '; charset=' . $this->getCharset());
        $response->status($return_code, $this->reason ?? '');

        switch ($this->getDesiredOutputFormat()) {
            case YeAPF_BULLETIN_OUTPUT_TYPE_BINARYFILE:
                $response->header('Content-Disposition', 'attachment; filename="' . $this->getFilename() ?? 'file.bin' . '"');
                $response->sendfile($this->getBinaryFile());
                break;
                
            case YeAPF_BULLETIN_OUTPUT_TYPE_JSON:
                $response->header('Content-Disposition', 'attachment; filename="' . $this->getFilename() ?? 'file.json' . '"');
                $response->sendfile($this->getJsonFile());
                break;

            case YeAPF_BULLETIN_OUTPUT_TYPE_JSONSTRING:
                if (is_array($this->getJsonString()))
                    $response->end(json_encode($this->getJsonString()));
                else
                    $response->end($this->getJsonString());
                break;

            case YeAPF_BULLETIN_REDIRECTION:
                $response->redirect($this->getReroutingPath());
                break;

            default:
                $response->end(json_encode($this->exportData()));
        }

        // if (!empty($this->getJsonFile())) {
        //     $response->header('Content-Disposition', 'attachment; filename="' . $this->getFilename() ?? 'file.json' . '"');
        //     $response->end($this->getJsonFile());
        // } else {
        //     if (!empty($this->getJsonString())) {
        //         if (is_array($this->getJsonString()))
        //             $response->end(json_encode($this->getJsonString()));
        //         else
        //             $response->end($this->getJsonString());
        //     } else {
        //         $response->end(json_encode($this->exportData()));
        //     }
        // }
    }
}

class WebBulletin extends \YeAPF\BaseBulletin
{
    public function __construct(string $contentType = 'application/json', $characterSet = 'UTF-8')
    {
        parent::__construct();
    }

    public function __invoke(
        int $return_code
    ) {
        if ($this->getDesiredOutputFormat() == YeAPF_BULLETIN_REDIRECTION) {
            header('Content-Type: ' . $this->contentType . '; charset=' . $this->characterSet);
            header('Response-Code: ' . $return_code);
            $sessionToken = $this->getSessionToken();
            if (null !== $sessionToken) {
                setcookie('sessionToken', $sessionToken, time() + 3600, '/', '', true, true);
            }
            header('Location: '.$this->getReroutingPath(), true, 302);

        } else {

            switch ($this->getDesiredOutputFormat()) {
                case YeAPF_BULLETIN_OUTPUT_TYPE_JSON:
                    header('Content-Disposition: attachment; filename="' . $this->getFilename() ?? 'file.json' . '"');
                    readfile($this->getJsonFile());
                    break;

                case YeAPF_BULLETIN_OUTPUT_TYPE_BINARYFILE:
                    header('Content-Disposition: attachment; filename="' . $this
                        ->getFilename() ?? 'file.bin' . '"');
                    header('Content-Length: ' . filesize($this->getBinaryFile()));
                    header('Content-Transfer-Encoding: binary');
                    readfile($this->getBinaryFile());
                    break;

                case YeAPF_BULLETIN_OUTPUT_TYPE_JSONSTRING:
                    if (is_array($this->getJsonString()))
                        echo json_encode($this->getJsonString());
                    else
                        echo $this->getJsonString();
                    break;

                default:
                    http_response_code($return_code);
                    header('Content-Type: application/json; charset=UTF-8');
                    echo json_encode($this->exportData());
                    break;
            }
        }
    }
}
