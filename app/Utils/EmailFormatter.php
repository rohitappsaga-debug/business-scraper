<?php

namespace App\Utils;

class EmailFormatter
{
    /**
     * Format the email body to ensure professional spacing and readability.
     */
    public static function format(string $emailBody): string
    {
        // 1. Normalize line endings
        $emailBody = str_replace(["\r\n", "\r"], "\n", $emailBody);

        // 2. Split into non-empty lines to analyze
        $lines = explode("\n", $emailBody);
        $lines = array_map(fn ($l) => trim($l), $lines);
        $lines = array_filter($lines, fn ($l) => $l !== '');

        $blocks = [];
        $currentParaLines = [];

        foreach ($lines as $line) {
            $isGreeting = preg_match('/^(Dear|Hi|Hello|Greetings)/i', $line);
            $isClosing = preg_match('/^(Best regards|Sincerely|Regards|Thanks|Cheers|Best)/i', $line);
            $isHeaderFooter = $isGreeting || $isClosing || (str_ends_with($line, ',') && strlen($line) < 50);

            if ($isHeaderFooter) {
                // If we were building a paragraph, finish it first
                if (! empty($currentParaLines)) {
                    $para = implode(' ', $currentParaLines);
                    foreach (self::splitPara($para) as $p) {
                        $blocks[] = $p;
                    }
                    $currentParaLines = [];
                }
                $blocks[] = $line;
            } else {
                $currentParaLines[] = $line;
            }
        }

        // Finish any remaining paragraph
        if (! empty($currentParaLines)) {
            $para = implode(' ', $currentParaLines);
            foreach (self::splitPara($para) as $p) {
                $blocks[] = $p;
            }
        }

        return implode("\n\n", array_map(fn ($b) => trim($b), $blocks));
    }

    /**
     * Split a paragraph into chunks of 2-3 sentences.
     *
     * @return array<string>
     */
    private static function splitPara(string $para): array
    {
        $para = preg_replace('/\s+/', ' ', $para);
        $sentences = preg_split('/(?<=[.?!])\s+(?=[A-Z])/', $para, -1, PREG_SPLIT_NO_EMPTY);

        $chunks = [];
        $currentChunk = [];
        foreach ($sentences as $s) {
            $currentChunk[] = trim($s);
            if (count($currentChunk) >= 2) {
                $chunks[] = implode(' ', $currentChunk);
                $currentChunk = [];
            }
        }
        if (! empty($currentChunk)) {
            $chunks[] = implode(' ', $currentChunk);
        }

        return $chunks;
    }
}
