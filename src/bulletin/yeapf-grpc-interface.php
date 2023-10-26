<?php
/**
 * A ideia aqui é termos um gerador de arquivo de protocolo (.proto)
 * a partir de uma classe que vai atender com esse protocolo via
 * GRPC. O problema é qu Swoole já inclui um compilador de protocolo
 * e, como devo terminar ORM antes, acho melhor adiar isto.
 * https://openswoole.com/docs/modules/grpc-server
 * https://openswoole.com/docs/grpc/grpc-compiler
 * https://github.com/openswoole/protoc-gen-openswoole-grpc/releases
 * @esteban 2023-05-12
 */
class ProtoGenerator {
    private static array $structure = [];

    public static function addStructure(string $name, array $fields): void {
        self::$structure[$name] = $fields;
    }

    public static function generateProto(string $filename): void {
        $proto = '';

        // Generate message definitions
        foreach (self::$structure as $name => $fields) {
            $proto .= "message $name {\n";
            foreach ($fields as $fieldName => $fieldType) {
                $proto .= "    $fieldType $fieldName = " . ($i+1) . ";\n";
            }
            $proto .= "}\n\n";
        }

        // Generate service definition
        $serviceName = ucfirst(pathinfo($filename, PATHINFO_FILENAME));
        $proto .= "service $serviceName {\n";
        foreach (self::$structure as $name => $fields) {
            $proto .= "    rpc $name($name) returns ($name) {}\n";
        }
        $proto .= "}\n";

        // Save to file
        file_put_contents($filename, $proto);
    }
}
