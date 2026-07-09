<?php
/**
 * AI Study Hub - Document Text Extractor (Week 5 - AN)
 *
 * Trich noi dung van ban tu cac file PDF / DOCX / PPTX / TXT / MD
 * de lam context cho RAG. Tat ca parser deu viet bang PHP thuan,
 * KHONG can Composer, KHONG can ZipArchive extension
 * (su dung PharData co san trong PHP >= 5.3).
 *
 * - PDF  : doc cac content stream, giai nen FlateDecode bang gzuncompress
 * - DOCX : unzip bang PharData, parse word/document.xml, trich <w:t>
 * - PPTX : unzip bang PharData, parse tung slide*.xml, trich <a:t>
 * - TXT/MD : doc thang
 */

if (!defined('AI_STUDY_HUB')) {
    define('AI_STUDY_HUB', true);
}

if (!function_exists('ai_extract_document_text')) {
    /**
     * Entry point chinh. Tra ve chuoi van ban (da rut gon whitespace).
     * Tra ve '' neu khong trich xuat duoc.
     *
     * @param string $filePath  duong dan tuyet doi den file
     * @param string $extHint   extension (vd: "pdf") - optional, tu dong detect neu trong
     * @param int    $maxChars  gioi han so ky tu tra ve
     * @param array  $debug     neu khac null, se duoc dien thong tin debug
     */
    function ai_extract_document_text(
        string $filePath,
        string $extHint = '',
        int $maxChars = 6000,
        ?array &$debug = null
    ): string {
        if (!is_file($filePath) || !is_readable($filePath)) {
            if ($debug !== null) $debug['reason'] = 'file_not_readable';
            return '';
        }

        $ext = strtolower($extHint);
        if ($ext === '') {
            $ext = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        }

        $raw = '';
        $method = '';

        switch ($ext) {
            case 'pdf':
                $method = 'pdf_native';
                $raw = ai_pdf_extract_text($filePath, $debug);
                break;

            case 'docx':
                $method = 'docx_phardata';
                $raw = ai_docx_extract_text($filePath, $debug);
                break;

            case 'pptx':
                $method = 'pptx_phardata';
                $raw = ai_pptx_extract_text($filePath, $debug);
                break;

            case 'txt':
            case 'md':
            case 'markdown':
                $method = 'text_plain';
                $content = @file_get_contents($filePath);
                $raw = ($content === false) ? '' : $content;
                if ($debug !== null && $raw === '') $debug['reason'] = 'txt_read_failed';
                break;

            default:
                if ($debug !== null) $debug['reason'] = 'unsupported_ext:' . $ext;
                return '';
        }

        if ($raw === '' || $raw === null) {
            if ($debug !== null && !isset($debug['reason'])) $debug['reason'] = 'empty_after_extract';
            return '';
        }

        if (function_exists('to_utf8')) {
            $raw = to_utf8($raw);
        }

        $raw = preg_replace('/\s+/u', ' ', $raw);
        $raw = trim((string) $raw);

        if ($raw === '') {
            if ($debug !== null) $debug['reason'] = 'empty_after_trim';
            return '';
        }

        if ($debug !== null) {
            $debug['method'] = $method;
            $debug['raw_len'] = mb_strlen($raw);
        }

        if (mb_strlen($raw) > $maxChars) {
            $raw = mb_substr($raw, 0, $maxChars) . '... [noi dung da duoc rut gon]';
        }

        return $raw;
    }
}

/* =========================================================
   PDF PARSER (thuan PHP, khong can thu vien ngoai)
   ========================================================= */

if (!function_exists('ai_pdf_extract_text')) {
    function ai_pdf_extract_text(string $path, ?array &$debug = null): string {
        $bytes = @file_get_contents($path);
        if ($bytes === false || $bytes === '') {
            if ($debug !== null) $debug['reason'] = 'pdf_read_failed';
            return '';
        }

        // Kiem tra header %PDF
        if (strncmp($bytes, '%PDF', 4) !== 0) {
            if ($debug !== null) $debug['reason'] = 'pdf_bad_header';
            return '';
        }

        $text = ai_pdf_decode_streams($bytes);

        if ($text === '' && $debug !== null) {
            $debug['reason'] = 'pdf_no_text_or_scanned';
        }

        return $text;
    }
}

if (!function_exists('ai_pdf_decode_streams')) {
    /**
     * Decode toan bo content stream trong file PDF va trich text.
     */
    function ai_pdf_decode_streams(string $bytes): string {
        $out = '';

        // 1) Tim stream object: "<< ... >> stream ... endstream"
        // Dung regex streaming-friendly
        $pattern = '/<<([^>]*?)>>\s*stream\s*\r?\n(.*?)\r?\nendstream/s';
        if (!preg_match_all($pattern, $bytes, $matches, PREG_SET_ORDER)) {
            // fallback: thu khong co newline sau stream
            $pattern2 = '/<<([^>]*?)>>\s*stream(.*?)endstream/s';
            if (!preg_match_all($pattern2, $bytes, $matches, PREG_SET_ORDER)) {
                return '';
            }
        }

        $found = 0;
        foreach ($matches as $m) {
            $dict = $m[1];
            $stream = $m[2];

            // Chi xu ly cac stream co /Filter FlateDecode (nhieu nhat)
            // Hoac khong co filter (raw text)
            $isFlate = (stripos($dict, '/FlateDecode') !== false);
            $isASCIIHex = (stripos($dict, '/ASCIIHexDecode') !== false);
            $isASCII85  = (stripos($dict, '/ASCII85Decode')  !== false);

            $decoded = null;
            if ($isFlate) {
                // Loai bo trailing newline neu co
                $bin = @gzuncompress($stream);
                if ($bin === false) {
                    $bin = @gzuncompress(substr($stream, 0, -2));
                }
                if ($bin !== false) $decoded = $bin;
            } elseif ($isASCIIHex) {
                $decoded = @ai_pdf_decode_ascii_hex($stream);
            } elseif ($isASCII85) {
                $decoded = @ai_pdf_decode_ascii85($stream);
            } else {
                // Raw
                $decoded = $stream;
            }

            if (!is_string($decoded) || $decoded === '') continue;

            // Bo qua neu stream nay khong phai content (khong co toan tu Td/Tj/TJ)
            if (strpos($decoded, 'Tj') === false
                && strpos($decoded, 'TJ') === false
                && strpos($decoded, "'")   === false) {
                continue;
            }

            $found++;
            $out .= ai_pdf_extract_text_operators($decoded) . ' ';
            if ($found > 5000) break; // safety
        }

        return trim($out);
    }
}

if (!function_exists('ai_pdf_extract_text_operators')) {
    /**
     * Trich text tu cac toan tu PDF: Tj, TJ, '
     *   ( ... ) Tj       -> literal string
     *   [ ... ] TJ       -> array of strings + kerning
     *   ... '            -> move to next line + show string
     */
    function ai_pdf_extract_text_operators(string $stream): string {
        $out = '';

        // Pattern cho TJ (array)
        if (preg_match_all('/\[([^\]]*)\]\s*TJ/s', $stream, $tjMatches)) {
            foreach ($tjMatches[1] as $arr) {
                // Lay cac chuoi trong ngoac don ben trong array
                if (preg_match_all('/\(((?:\\\\.|[^\\\\)])*)\)/s', $arr, $strs)) {
                    foreach ($strs[1] as $s) {
                        $out .= ai_pdf_unescape_string($s);
                    }
                    $out .= ' ';
                }
            }
        }

        // Pattern cho Tj (literal)
        if (preg_match_all('/\(((?:\\\\.|[^\\\\\(\)])*)\)\s*Tj/s', $stream, $tj2)) {
            foreach ($tj2[1] as $s) {
                $out .= ai_pdf_unescape_string($s) . ' ';
            }
        }

        // Pattern cho ' (quote) - tuong tu nhu T* + Tj
        if (preg_match_all('/\(((?:\\\\.|[^\\\\\(\)])*)\)\s*\'/s', $stream, $q)) {
            foreach ($q[1] as $s) {
                $out .= ai_pdf_unescape_string($s) . ' ';
            }
        }

        return trim($out);
    }
}

if (!function_exists('ai_pdf_unescape_string')) {
    /**
     * Bo escape PDF string:
     *   \\ -> \
     *   \( -> (
     *   \) -> )
     *   \n -> newline
     *   \r -> carriage return
     *   \t -> tab
     *   \ddd -> octal char
     */
    function ai_pdf_unescape_string(string $s): string {
        $out = '';
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $c = $s[$i];
            if ($c !== '\\') { $out .= $c; continue; }
            if ($i + 1 >= $len) break;
            $n = $s[$i + 1];
            if ($n === '\\' || $n === '(' || $n === ')') { $out .= $n; $i++; continue; }
            if ($n === 'n') { $out .= "\n"; $i++; continue; }
            if ($n === 'r') { $out .= "\r"; $i++; continue; }
            if ($n === 't') { $out .= "\t"; $i++; continue; }
            if ($n === 'b') { $out .= "\x08"; $i++; continue; }
            if ($n === 'f') { $out .= "\x0C"; $i++; continue; }
            if ($n >= '0' && $n <= '9') {
                $oct = $n;
                if ($i + 2 < $len && ctype_digit($s[$i + 2])) {
                    $oct .= $s[$i + 2];
                    if ($i + 3 < $len && ctype_digit($s[$i + 3])) {
                        $oct .= $s[$i + 3];
                        $i += 3;
                    } else {
                        $i += 2;
                    }
                } else {
                    $i++;
                }
                $code = octdec($oct);
                if ($code > 0) $out .= chr((int) $code);
                continue;
            }
            // Bo qua backslash mac dinh
            $i++;
        }
        return $out;
    }
}

if (!function_exists('ai_pdf_decode_ascii_hex')) {
    function ai_pdf_decode_ascii_hex(string $s): string {
        $s = preg_replace('/\s+/', '', $s);
        $out = '';
        for ($i = 0, $n = strlen($s); $i < $n; $i += 2) {
            $pair = substr($s, $i, 2);
            if ($pair === '>' ) break;
            $code = hexdec($pair);
            $out .= chr((int) $code);
        }
        return $out;
    }
}

if (!function_exists('ai_pdf_decode_ascii85')) {
    function ai_pdf_decode_ascii85(string $s): string {
        $s = rtrim($s, '~>');
        $out = '';
        $buf = 0;
        $count = 0;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $c = $s[$i];
            if ($c === 'z' && $count === 0) { $out .= str_repeat("\0", 4); continue; }
            if ($c < '!' || $c > 'u') continue;
            $buf = $buf * 85 + (ord($c) - 33);
            $count++;
            if ($count === 5) {
                $out .= chr(($buf >> 24) & 0xFF)
                      . chr(($buf >> 16) & 0xFF)
                      . chr(($buf >> 8)  & 0xFF)
                      . chr($buf & 0xFF);
                $buf = 0; $count = 0;
            }
        }
        if ($count > 0) {
            for ($i = $count; $i < 5; $i++) $buf = $buf * 85 + 84;
            $real = $count - 1;
            for ($i = 0; $i < $real; $i++) {
                $out .= chr(($buf >> (24 - $i * 8)) & 0xFF);
            }
        }
        return $out;
    }
}

/* =========================================================
   DOCX PARSER  (dung PharData vi khong co ZipArchive)
   ========================================================= */

if (!function_exists('ai_docx_extract_text')) {
    function ai_docx_extract_text(string $path, ?array &$debug = null): string {
        if (!class_exists('PharData')) {
            if ($debug !== null) $debug['reason'] = 'phardata_missing';
            return '';
        }
        try {
            $phar = new PharData($path);
        } catch (Throwable $e) {
            if ($debug !== null) $debug['reason'] = 'docx_phar_open_failed:' . $e->getMessage();
            return '';
        }

        // Doc word/document.xml (chinh xac, khong phai document.xml.rels)
        $xml = ai_phar_read_entry($phar, 'word/document.xml');
        if ($xml === '') {
            if ($debug !== null) $debug['reason'] = 'docx_document_xml_empty';
            return '';
        }

        $text = ai_docx_xml_to_text($xml);
        if ($text === '' && $debug !== null) {
            $debug['reason'] = 'docx_no_text_in_xml';
        }
        return $text;
    }
}

if (!function_exists('ai_docx_xml_to_text')) {
    function ai_docx_xml_to_text(string $xml): string {
        $out = '';
        // Trich <w:t>...</w:t>
        if (preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $xml, $m)) {
            foreach ($m[1] as $t) {
                $t = ai_strip_xml_tags_inline($t);
                if ($t !== '') $out .= $t . ' ';
            }
        }
        // Them linebreak theo <w:br/> va ket thuc paragraph
        $out = preg_replace('/(<w:br\s*\/?>|<w:cr\s*\/?>)/i', "\n", $out);
        $out = preg_replace('/<\/w:p>/i', "\n", $out);

        return trim($out);
    }
}

/* =========================================================
   PPTX PARSER
   ========================================================= */

if (!function_exists('ai_pptx_extract_text')) {
    function ai_pptx_extract_text(string $path, ?array &$debug = null): string {
        if (!class_exists('PharData')) {
            if ($debug !== null) $debug['reason'] = 'phardata_missing';
            return '';
        }
        try {
            $phar = new PharData($path);
        } catch (Throwable $e) {
            if ($debug !== null) $debug['reason'] = 'pptx_phar_open_failed:' . $e->getMessage();
            return '';
        }

        $out = '';

        // Liet ke cac entry, sort theo slide1, slide2...
        $slides = [];
        foreach (new RecursiveIteratorIterator($phar) as $entry) {
            $name = $entry->getFilename();
            if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $mm)) {
                $slides[(int) $mm[1]] = $name;
            }
        }
        ksort($slides);

        if (empty($slides)) {
            if ($debug !== null) $debug['reason'] = 'pptx_no_slides';
            return '';
        }

        $idx = 0;
        foreach ($slides as $num => $name) {
            $idx++;
            $xml = ai_phar_read_entry($phar, $name);
            if ($xml === '') continue;

            $out .= "[Slide $num] ";

            // Trich <a:t>...</a:t>
            if (preg_match_all('/<a:t[^>]*>(.*?)<\/a:t>/s', $xml, $m)) {
                foreach ($m[1] as $t) {
                    $t = ai_strip_xml_tags_inline($t);
                    if ($t !== '') $out .= $t . ' ';
                }
            }
            $out .= "\n";
        }

        return trim($out);
    }
}

/* =========================================================
   HELPERS chung
   ========================================================= */

if (!function_exists('ai_phar_read_entry')) {
    /**
     * Doc 1 file ben trong PharData (zip/docx/pptx).
     */
    function ai_phar_read_entry(PharData $phar, string $entryName): string {
        try {
            if (!$phar->offsetExists($entryName)) return '';
            $content = @file_get_contents('phar://' . $phar->getPath() . '/' . $entryName);
            return ($content === false) ? '' : (string) $content;
        } catch (Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('ai_strip_xml_tags_inline')) {
    /**
     * Bo tag XML con ben trong mot node text (vi du <w:t> co the chua <w:tab/>...)
     * va decode entity.
     */
    function ai_strip_xml_tags_inline(string $s): string {
        $s = preg_replace('/<[^>]+>/', '', $s);
        $s = html_entity_decode($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
        return $s;
    }
}
