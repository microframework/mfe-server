<?php namespace mfe\server\libs\http\server\helpers;

/**
 * Class TWebSocketHelper
 *
 * @package mfe\server\libs\http\server\helpers
 */
trait TWebSocketHelper
{
    /**
     * @param $payload
     * @param string $type
     * @param bool $masked
     *
     * @return string
     */
    protected function encode($payload, $type = 'text', $masked = false)
    {
        $frameHead = [];
        $payloadLength = strlen($payload);

        switch ($type) {
            case 'text':
                $frameHead[0] = 129;
                break;

            case 'close':
                $frameHead[0] = 136;
                break;

            case 'ping':
                $frameHead[0] = 137;
                break;

            case 'pong':
                $frameHead[0] = 138;
                break;
        }

        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            if ($frameHead[2] > 127) {
                return ['type' => '', 'payload' => '', 'error' => 'frame too large (1004)'];
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }

        $array = array_keys($frameHead);
        foreach ($array as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        $mask = [];
        if ($masked === true) {
            $mask = [];
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(mt_rand(0, 255));
            }

            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);

        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }

    /**
     * @param $data
     *
     * @return array|bool
     */
    protected function decode($data)
    {
        $unmaskedPayload = '';
        $decodedData = [];

        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        $isMasked = $secondByteBinary[0] === '1';
        $payloadLength = ord($data[1]) & 127;

        if (!$isMasked) {
            return ['type' => '', 'payload' => '', 'error' => 'protocol error (1002)'];
        }

        switch ($opcode) {
            case 1:
                $decodedData['type'] = 'text';
                break;
            case 2:
                $decodedData['type'] = 'binary';
                break;
            case 8:
                $decodedData['type'] = 'close';
                break;
            case 9:
                $decodedData['type'] = 'ping';
                break;
            case 10:
                $decodedData['type'] = 'pong';
                break;

            default:
                return ['type' => '', 'payload' => '', 'error' => 'unknown opcode (1003)'];
        }

        if ($payloadLength === 126) {
            $mask = substr($data, 4, 4);
            $payloadOffset = 8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } elseif ($payloadLength === 127) {
            $mask = substr($data, 10, 4);
            $payloadOffset = 14;
            $tmp = '';
            for ($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
            unset($tmp);
        } else {
            $mask = substr($data, 2, 4);
            $payloadOffset = 6;
            $dataLength = $payloadLength + $payloadOffset;
        }

        /**
         * We have to check for large frames here. socket_recv cuts at 1024 bytes
         * so if websocket-frame is > 1024 bytes we have to wait until whole
         * data is transfer.
         */
        if (strlen($data) < $dataLength) {
            return false;
        }

        if ($isMasked) {
            for ($payloadPos = $payloadOffset; $payloadPos < $dataLength; $payloadPos++) {
                $payloadMask = $payloadPos - $payloadOffset;

                if (is_array($data) && array_key_exists($payloadPos, $data)) {
                    $unmaskedPayload .= $data[$payloadPos] ^ $mask[$payloadMask % 4];
                }
            }
            $decodedData['payload'] = $unmaskedPayload;
        } else {
            $payloadOffset -= 4;
            $decodedData['payload'] = substr($data, $payloadOffset);
        }

        return $decodedData;
    }
}
