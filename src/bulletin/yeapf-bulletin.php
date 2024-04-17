<?php
declare(strict_types=1);
namespace YeAPF;

class Bulletin extends \YeAPF\SanitizedKeyData
{
    private $contentType;
    private $charactetSet;

    public function __construct(string $contentType="application/json", $charactetSet="UTF-8") {
        parent::__construct();
        $this->contentType = $contentType;
        $this->charactetSet = $charactetSet;
    }

    public function __invoke(
        int $return_code,
        \OpenSwoole\Http\Request $request ,
        \OpenSwoole\Http\Response $response) {
        // echo "Bulletin\n";
        // echo "Content Type: ".$this->contentType."\n";
        // echo "Charactet Set: ".$this->charactetSet."\n";
        $response->header("Content-Type", $this->contentType.'; charset='.$this->charactetSet);
        $response->status($return_code, $this->reason??'');
        // $response->header("Response-Code", $return_code);
        if (!empty($this->__jsonFile)) {
            $response->header("Content-Disposition", "attachment; filename=\"".$this->__filename??'file.json'."\"");
            $response->end($this->__jsonFile);
        } else {
            if (!empty($this->__json)) {
                if (is_array($this->__json))
                    $response->end(json_encode($this->__json));
                else
                    $response->end($this->__json);
            } else {
                $response->end(json_encode($this->exportData()));
            }
        }
    }

}

