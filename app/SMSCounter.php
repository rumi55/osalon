<?php

namespace App;

class SMSCounter
{
    /**
     * GSM 7BIT ecoding name.
     *
     * @var string
     */
    const GSM_7BIT = 'GSM_7BIT';

    /**
     * GSM 7BIT Extended ecoding name.
     *
     * @var string
     */
    const GSM_7BIT_EX = 'GSM_7BIT_EX';

    /**
     * UTF16 or UNICODE ecoding name.
     *
     * @var string
     */
    const UTF16 = 'UTF16';

    /**
     * Message length for GSM 7 Bit charset.
     *
     * @var int
     */
    const GSM_7BIT_LEN = 160;

    /**
     * Message length for GSM 7 Bit charset with extended characters.
     *
     * @var int
     */
    const GSM_7BIT_EX_LEN = 160;

    /**
     * Message length for UTF16/Unicode charset.
     *
     * @var int
     */
    const UTF16_LEN = 70;

    /**
     * Message length for multipart message in GSM 7 Bit encoding.
     *
     * @var int
     */
    const GSM_7BIT_LEN_MULTIPART = 153;

    /**
     * Message length for multipart message in GSM 7 Bit encoding.
     *
     * @var int
     */
    const GSM_7BIT_EX_LEN_MULTIPART = 153;

    /**
     * Message length for multipart message in GSM 7 Bit encoding.
     *
     * @var int
     */
    const UTF16_LEN_MULTIPART = 67;

    public function getGsm7bitMap()
    {
        return [
            10, 12, 13, 32, 33, 34, 35, 36,
            37, 38, 39, 40, 41, 42, 43, 44,
            45, 46, 47, 48, 49, 50, 51, 52,
            53, 54, 55, 56, 57, 58, 59, 60,
            61, 62, 63, 64, 65, 66, 67, 68,
            69, 70, 71, 72, 73, 74, 75, 76,
            77, 78, 79, 80, 81, 82, 83, 84,
            85, 86, 87, 88, 89, 90, 91, 92,
            93,  94, 95, 97, 98, 99, 100, 101,
            102, 103, 104, 105, 106, 107, 108,
            109, 110, 111, 112, 113, 114, 115,
            116, 117, 118, 119, 120, 121, 122,
            123, 124, 125, 126, 161, 163, 164,
            165, 167, 191, 196, 197, 198, 199,
            201, 209, 214, 216, 220, 223, 224,
            228, 229, 230, 232, 233, 236, 241,
            242, 246, 248, 249, 252, 915, 916,
            920, 923, 926, 928, 931, 934, 936,
            937, 8364,
        ];
    }

    public function getAddedGsm7bitExMap()
    {
        return [12, 91, 92, 93, 94, 123, 124, 125, 126, 8364];
    }

    public function getGsm7bitExMap()
    {
        return array_merge(
            $this->getGsm7bitMap(),
            $this->getAddedGsm7bitExMap()
        );
    }

    /**
     * Detects the encoding, Counts the characters, message length, remaining characters.
     *
     * @return \stdClass Object with params encoding,length, per_message, remaining, messages
     */
    public function count($text)
    {
        $unicodeArray = $this->utf8ToUnicode($text);

        // variable to catch if any ex chars while encoding detection.
        $exChars = [];
        $encoding = $this->detectEncoding($unicodeArray, $exChars);
        $length = count($unicodeArray);

        if ($encoding === self::GSM_7BIT_EX) {
            $lengthExchars = count($exChars);
            // Each exchar in the GSM 7 Bit encoding takes one more space
            // Hence the length increases by one char for each of those Ex chars.
            $length += $lengthExchars;
        }

        // Select the per message length according to encoding and the message length
        switch ($encoding) {
            case self::GSM_7BIT:
                $perMessage = self::GSM_7BIT_LEN;
                if ($length > self::GSM_7BIT_LEN) {
                    $perMessage = self::GSM_7BIT_LEN_MULTIPART;
                }
                break;

            case self::GSM_7BIT_EX:
                $perMessage = self::GSM_7BIT_EX_LEN;
                if ($length > self::GSM_7BIT_EX_LEN) {
                    $perMessage = self::GSM_7BIT_EX_LEN_MULTIPART;
                }
                break;

            default:
                $perMessage = self::UTF16_LEN;
                if ($length > self::UTF16_LEN) {
                    $perMessage = self::UTF16_LEN_MULTIPART;
                }

                break;
        }

        $messages = (int) ceil($length / $perMessage);
        $remaining = ($perMessage * $messages) - $length;

        $returnset = new \stdClass();

        $returnset->encoding = $encoding;
        $returnset->length = $length;
        $returnset->per_message = $perMessage;
        $returnset->remaining = $remaining;
        $returnset->messages = $messages;

        return $returnset;
    }

    /**
     * Detects the encoding of a particular text.
     *
     * @return string (GSM_7BIT|GSM_7BIT_EX|UTF16)
     */
    public function detectEncoding($text, &$exChars)
    {
        if (!is_array($text)) {
            $text = utf8ToUnicode($text);
        }

        $utf16Chars = array_diff($text, $this->getGsm7bitExMap());

        if (count($utf16Chars)) {
            return self::UTF16;
        }

        $exChars = array_intersect($text, $this->getAddedGsm7bitExMap());

        if (count($exChars)) {
            return self::GSM_7BIT_EX;
        }

        return self::GSM_7BIT;
    }

    /**
     * Generates array of unicode points for the utf8 string.
     *
     * @return array
     */
    public function utf8ToUnicode($str)
    {
        $unicode = [];
        $values = [];
        $lookingFor = 1;
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            $thisValue = ord($str[$i]);

            if ($thisValue < 128) {
                $unicode[] = $thisValue;
            }

            if ($thisValue >= 128) {
                if (count($values) == 0) {
                    $lookingFor = ($thisValue < 224) ? 2 : 3;
                }

                $values[] = $thisValue;

                if (count($values) == $lookingFor) {
                    $number = ($lookingFor == 3) ?
                    (($values[0] % 16) * 4096) + (($values[1] % 64) * 64) + ($values[2] % 64) :
                    (($values[0] % 32) * 64) + ($values[1] % 64);

                    $unicode[] = $number;
                    $values = [];
                    $lookingFor = 1;
                }
            }
        }

        return $unicode;
    }

    /**
     * Unicode equivalent chr() function.
     *
     * @return array characters
     */
    public function utf8Chr($unicode)
    {
        $unicode = intval($unicode);

        $utf8char = chr(240 | ($unicode >> 18));
        $utf8char .= chr(128 | (($unicode >> 12) & 0x3F));
        $utf8char .= chr(128 | (($unicode >> 6) & 0x3F));
        $utf8char .= chr(128 | ($unicode & 0x3F));

        if ($unicode < 128) {
            $utf8char = chr($unicode);
        } elseif ($unicode >= 128 && $unicode < 2048) {
            $utf8char = chr(192 | ($unicode >> 6)).chr(128 | ($unicode & 0x3F));
        } elseif ($unicode >= 2048 && $unicode < 65536) {
            $utf8char = chr(224 | ($unicode >> 12)).chr(128 | (($unicode >> 6) & 0x3F)).chr(128 | ($unicode & 0x3F));
        }

        return $utf8char;
    }

    /**
     * Converts unicode code points array to a utf8 str.
     *
     * @param array $array unicode codepoints array
     *
     * @return string utf8 encoded string
     */
    public function unicodeToUtf8($array)
    {
        $str = '';
        foreach ($array as $a) {
            $str .= $this->utf8Chr($a);
        }

        return $str;
    }

    /**
     * Removes non GSM characters from a string.
     *
     * @return string
     */
    public function removeNonGsmChars($str)
    {
        return $this->replaceNonGsmChars($str, null);
    }

    /**
     * Replaces non GSM characters from a string.
     *
     * @param string $str         String to be replaced
     * @param string $replacement String of characters to be replaced with
     *
     * @return (string|false) if replacement string is more than 1 character
     *                        in length
     */
    public function replaceNonGsmChars($str, $replacement = null)
    {
        $validChars = $this->getGsm7bitExMap();
        $allChars = self::utf8ToUnicode($str);

        if (strlen($replacement) > 1) {
            return false;
        }

        $replacementArray = [];
        $unicodeArray = $this->utf8ToUnicode($replacement);
        $replacementUnicode = array_pop($unicodeArray);

        foreach ($allChars as $key => $char) {
            if (!in_array($char, $validChars)) {
                $replacementArray[] = $key;
            }
        }

        if ($replacement) {
            foreach ($replacementArray as $key) {
                $allChars[$key] = $replacementUnicode;
            }
        }

        if (!$replacement) {
            foreach ($replacementArray as $key) {
                unset($allChars[$key]);
            }
        }

        return $this->unicodeToUtf8($allChars);
    }

    public function sanitizeToGSM($str)
    {
        $str = $this->removeAccents($str);
        $str = $this->removeNonGsmChars($str);

        return $str;
    }

    /**
     * @param string $str Message text
     *
     * @return string Sanitized message text
     */
    public function removeAccents($str)
    {
        if (!preg_match('/[\x80-\xff]/', $str)) {
            return $str;
        }

        $chars = [
          // Decompositions for Latin-1 Supplement
          '??' => 'a', '??' => 'o',
          '??' => 'A', '??' => 'A',
          '??' => 'A', '??' => 'A',
          '??' => 'E',
          '??' => 'E', '??' => 'E',
          '??' => 'I', '??' => 'I',
          '??' => 'I', '??' => 'I',
          '??' => 'D',
          '??' => 'O', '??' => 'O',
          '??' => 'O', '??' => 'O',
          '??' => 'U',
          '??' => 'U', '??' => 'U',
          '??' => 'Y',
          '??' => 'TH',
          '??' => 'a',
          '??' => 'a', '??' => 'a',
          '??' => 'c',
          '??' => 'e', '??' => 'e',
          '??' => 'i',
          '??' => 'i', '??' => 'i',
          '??' => 'd',
          '??' => 'o',
          '??' => 'o', '??' => 'o',
          '??' => 'u',
          '??' => 'u',
          '??' => 'y', '??' => 'th',
          '??' => 'y',
          // Decompositions for Latin Extended-A
          '??' => 'A', '??' => 'a',
          '??' => 'A', '??' => 'a',
          '??' => 'A', '??' => 'a',
          '??' => 'C', '??' => 'c',
          '??' => 'C', '??' => 'c',
          '??' => 'C', '??' => 'c',
          '??' => 'C', '??' => 'c',
          '??' => 'D', '??' => 'd',
          '??' => 'D', '??' => 'd',
          '??' => 'E', '??' => 'e',
          '??' => 'E', '??' => 'e',
          '??' => 'E', '??' => 'e',
          '??' => 'E', '??' => 'e',
          '??' => 'E', '??' => 'e',
          '??' => 'G', '??' => 'g',
          '??' => 'G', '??' => 'g',
          '??' => 'G', '??' => 'g',
          '??' => 'G', '??' => 'g',
          '??' => 'H', '??' => 'h',
          '??' => 'H', '??' => 'h',
          '??' => 'I', '??' => 'i',
          '??' => 'I', '??' => 'i',
          '??' => 'I', '??' => 'i',
          '??' => 'I', '??' => 'i',
          '??' => 'I', '??' => 'i',
          '??' => 'IJ', '??' => 'ij',
          '??' => 'J', '??' => 'j',
          '??' => 'K', '??' => 'k',
          '??' => 'k', '??' => 'L',
          '??' => 'l', '??' => 'L',
          '??' => 'l', '??' => 'L',
          '??' => 'l', '??' => 'L',
          '??' => 'l', '??' => 'L',
          '??' => 'l', '??' => 'N',
          '??' => 'n', '??' => 'N',
          '??' => 'n', '??' => 'N',
          '??' => 'n', '??' => 'n',
          '??' => 'N', '??' => 'n',
          '??' => 'O', '??' => 'o',
          '??' => 'O', '??' => 'o',
          '??' => 'O', '??' => 'o',
          '??' => 'OE', '??' => 'oe',
          '??' => 'R', '??' => 'r',
          '??' => 'R', '??' => 'r',
          '??' => 'R', '??' => 'r',
          '??' => 'S', '??' => 's',
          '??' => 'S', '??' => 's',
          '??' => 'S', '??' => 's',
          '??' => 'S', '??' => 's',
          '??' => 'T', '??' => 't',
          '??' => 'T', '??' => 't',
          '??' => 'T', '??' => 't',
          '??' => 'U', '??' => 'u',
          '??' => 'U', '??' => 'u',
          '??' => 'U', '??' => 'u',
          '??' => 'U', '??' => 'u',
          '??' => 'U', '??' => 'u',
          '??' => 'U', '??' => 'u',
          '??' => 'W', '??' => 'w',
          '??' => 'Y', '??' => 'y',
          '??' => 'Y', '??' => 'Z',
          '??' => 'z', '??' => 'Z',
          '??' => 'z', '??' => 'Z',
          '??' => 'z', '??' => 's',
          // Decompositions for Latin Extended-B
          '??' => 'S', '??' => 's',
          '??' => 'T', '??' => 't',
          // Vowels with diacritic (Vietnamese)
          // unmarked
          '??' => 'O', '??' => 'o',
          '??' => 'U', '??' => 'u',
          // grave accent
          '???' => 'A', '???' => 'a',
          '???' => 'A', '???' => 'a',
          '???' => 'E', '???' => 'e',
          '???' => 'O', '???' => 'o',
          '???' => 'O', '???' => 'o',
          '???' => 'U', '???' => 'u',
          '???' => 'Y', '???' => 'y',
          // hook
          '???' => 'A', '???' => 'a',
          '???' => 'A', '???' => 'a',
          '???' => 'A', '???' => 'a',
          '???' => 'E', '???' => 'e',
          '???' => 'E', '???' => 'e',
          '???' => 'I', '???' => 'i',
          '???' => 'O', '???' => 'o',
          '???' => 'O', '???' => 'o',
          '???' => 'O', '???' => 'o',
          '???' => 'U', '???' => 'u',
          '???' => 'U', '???' => 'u',
          '???' => 'Y', '???' => 'y',
          // tilde
          '???' => 'A', '???' => 'a',
          '???' => 'A', '???' => 'a',
          '???' => 'E', '???' => 'e',
          '???' => 'E', '???' => 'e',
          '???' => 'O', '???' => 'o',
          '???' => 'O', '???' => 'o',
          '???' => 'U', '???' => 'u',
          '???' => 'Y', '???' => 'y',
          // acute accent
          '???' => 'A', '???' => 'a',
          '???' => 'A', '???' => 'a',
          '???' => 'E', '???' => 'e',
          '???' => 'O', '???' => 'o',
          '???' => 'O', '???' => 'o',
          '???' => 'U', '???' => 'u',
          // dot below
          '???' => 'A', '???' => 'a',
          '???' => 'A', '???' => 'a',
          '???' => 'A', '???' => 'a',
          '???' => 'E', '???' => 'e',
          '???' => 'E', '???' => 'e',
          '???' => 'I', '???' => 'i',
          '???' => 'O', '???' => 'o',
          '???' => 'O', '???' => 'o',
          '???' => 'O', '???' => 'o',
          '???' => 'U', '???' => 'u',
          '???' => 'U', '???' => 'u',
          '???' => 'Y', '???' => 'y',
          // Vowels with diacritic (Chinese, Hanyu Pinyin)
          '??' => 'a',
          // macron
          '??' => 'U', '??' => 'u',
          // acute accent
          '??' => 'U', '??' => 'u',
          // caron
          '??' => 'A', '??' => 'a',
          '??' => 'I', '??' => 'i',
          '??' => 'O', '??' => 'o',
          '??' => 'U', '??' => 'u',
          '??' => 'U', '??' => 'u',
          // grave accent
          '??' => 'U', '??' => 'u',
        ];

        $str = strtr($str, $chars);

        return $str;
    }

    /**
     * Truncated to the limit of chars allowed by number of SMS. It will detect
     * the encoding an multipart limits to apply the truncate.
     *
     * @param string $str      Message text
     * @param int    $messages Number of SMS allowed
     *
     * @return string Truncated message
     */
    public function truncate($str, $limitSms)
    {
        $count = $this->count($str);

        if ($count->messages <= $limitSms) {
            return $str;
        }

        if ($count->encoding == 'UTF16') {
            $limit = self::UTF16_LEN;

            if ($limitSms > 2) {
                $limit = self::UTF16_LEN_MULTIPART;
            }
        }

        if ($count->encoding != 'UTF16') {
            $limit = self::GSM_7BIT_LEN;

            if ($limitSms > 2) {
                $limit = self::GSM_7BIT_LEN_MULTIPART;
            }
        }

        do {
            $str = mb_substr($str, 0, $limit * $limitSms);
            $count = $this->count($str);

            $limit = $limit - 1;
        } while ($count->messages > $limitSms);

        return $str;
    }
}
