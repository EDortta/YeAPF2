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
    private $charactetSet;
    private $__jsonFile;
    private $__binaryFile;
    private $__filename;
    private $__json;
    private $reason;

    private $__desiredOutputFormat = null;

    public function __construct(string $contentType = 'application/json', $charactetSet = 'UTF-8')
    {
        parent::__construct();
        $this->contentType = $contentType;
        $this->charactetSet = $charactetSet;
    }

    private function __setDesiredOutputFormat(string $desiredOutputFormat)
    {
        if (null==$this->__desiredOutputFormat) {
            $this->__desiredOutputFormat = $desiredOutputFormat;
        } else {
            throw new \YeAPF\Exception("Desired output format already set.");
        }        
    }

    public function getDesiredOutputFormat()
    {
        return $this->__desiredOutputFormat;
    }

    public function setJsonFile(string $jsonFile)
    {
        $this->__setDesiredOutputFormat('jsonFile');
        $this->__jsonFile = $jsonFile;
    }

    public function getJsonFile()
    {
        return $this->__jsonFile;
    }

    public function setBinaryFile(string $binaryFile)
    {
        if (!file_exists($binaryFile)) {
            throw new \YeAPF\Exception("File $binaryFile not found");
        }

        if (!is_readable($binaryFile)) {
            throw new \YeAPF\Exception("File $binaryFile is not readable");
        }

        if (!is_file($binaryFile)) {
            throw new \YeAPF\Exception("File $binaryFile is not a file");
        }

        $fileExtension = pathinfo($binaryFile, PATHINFO_EXTENSION);
        switch ($fileExtension) {
            case 'json':
                $contentType = 'application/json';
                break;
            case 'pdf':
                $contentType = 'application/pdf';
                break;
            case 'md':
                $contentType = 'text/markdown';
                break;
            case 'gz':
                $contentType = 'application/x-gzip';
                break;
            case 'html':
                $contentType = 'text/html';
                break;
            case 'xml':
                $contentType = 'application/xml';
                break;
            case 'zip':
                $contentType = 'application/zip';
                break;
            case 'txt':
                $contentType = 'text/plain';
                break;
            default:
                $contentType = 'application/octet-stream';
                break;
        }

        $this->__setDesiredOutputFormat('binaryFile');
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

    public function setJsonString(string $jsonString)
    {
        $this->__setDesiredOutputFormat('jsonString');
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

    public function setCharset(string $charset)
    {
        $this->charactetSet = $charset;
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
        return $this->charactetSet;
    }

    public function getReason()
    {
        return $this->reason;
    }
}

class Http2Bulletin extends \YeAPF\BaseBulletin
{
    public function __construct(string $contentType = 'application/json', $charactetSet = 'UTF-8')
    {
        parent::__construct();
    }

    public function __invoke(
        int $return_code,
        \OpenSwoole\Http\Request $request,
        \OpenSwoole\Http\Response $response
    ) {
        $response->header('Content-Type', $this->contentType . '; charset=' . $this->charactetSet);
        $response->status($return_code, $this->reason ?? '');

        switch ($this->getDesiredOutputFormat()) {
            case 'binaryFile':
                $response->header('Content-Disposition', 'attachment; filename="' . $this->getFilename() ?? 'file.bin' . '"');
                $response->sendfile($this->getBinaryFile());
                break;
            case 'jsonFile':
                $response->header('Content-Disposition', 'attachment; filename="' . $this->getFilename() ?? 'file.json' . '"');
                $response->sendfile($this->getJsonFile());
                break;
            case 'jsonString':
                if (is_array($this->getJsonString()))
                    $response->end(json_encode($this->getJsonString()));
                else
                    $response->end($this->getJsonString());
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
    public function __construct(string $contentType = 'application/json', $charactetSet = 'UTF-8')
    {
        parent::__construct();
    }

    public function __invoke(
        int $return_code
    ) {
        header('Content-Type: '. $this->contentType . '; charset=' . $this->charactetSet);
        header('Response-Code: '. $return_code);


        switch ($this->getDesiredOutputFormat()) {
            case 'jsonFile':
                header('Content-Disposition: attachment; filename="' . $this->getFilename() ?? 'file.json' . '"');
                readfile($this->getJsonFile());
                break;
            case 'binaryFile':
                header('Content-Disposition: attachment; filename="' . $this->
                getFilename() ?? 'file.bin' . '"');
                header('Content-Length: '. filesize($this->getBinaryFile()));
                header('Content-Transfer-Encoding: binary');
                readfile($this->getBinaryFile());
                break;
            case 'jsonString':
                if (is_array($this->getJsonString()))
                    echo json_encode($this->getJsonString());
                else
                    echo $this->getJsonString();
                break;
            default:
                echo json_encode($this->exportData());
                break;
        }
    }
}
