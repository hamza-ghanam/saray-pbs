<?php

namespace App\Actions;

use DOMDocument;
use DOMElement;

class LegalListNumbering
{
    public static function apply(string $html, string $rootClass = 'legal'): string
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML(
            '<!doctype html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new \DOMXPath($dom);

        /** @var DOMElement|null $rootOl */
        $rootOl = $xpath->query("//ol[contains(concat(' ', normalize-space(@class), ' '), ' {$rootClass} ')]")->item(0);

        if (!$rootOl) {
            return self::getBodyInnerHTML($dom);
        }

        // Root is always numeric legal numbering
        self::numberOl($dom, $rootOl, [], 'numeric');

        return self::getBodyInnerHTML($dom);
    }

    private static function numberOl(DOMDocument $dom, DOMElement $ol, array $prefix, string $mode = 'numeric'): void
    {
        $type = strtolower(trim($ol->getAttribute('type')));

        // Decide this list mode
        // - type="a" => alpha markers a,b,c (no numeric prefix)
        // - type="i" => roman markers i,ii,iii (no numeric prefix)
        // - otherwise => numeric legal 1, 1.1, 1.1.1
        if ($type === 'a') {
            $mode = 'alpha';
            $prefix = []; // IMPORTANT: alpha list should be a,b,c only
        } elseif ($type === 'i') {
            $mode = 'roman';
            $prefix = []; // IMPORTANT: roman list should be i,ii,iii only
        }

        $index = 0;

        foreach ($ol->childNodes as $node) {
            if (!($node instanceof DOMElement) || strtolower($node->nodeName) !== 'li') {
                continue;
            }

            $index++;

            if ($mode === 'numeric') {
                $current = array_merge($prefix, [$index]);
                $label = implode('.', $current) . '. ';
                self::prependNumberSpan($dom, $node, $label, 'lnum');
            } elseif ($mode === 'alpha') {
                $label = self::alphaLabel($index) . '. ';
                self::prependNumberSpan($dom, $node, $label, 'lnum lnum-alpha');
                $current = $prefix; // alpha list doesn't affect numeric prefix
            } else { // roman
                $label = self::romanLabel($index) . '. ';
                self::prependNumberSpan($dom, $node, $label, 'lnum lnum-roman');
                $current = $prefix;
            }

            // Recurse into nested <ol>
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement && strtolower($child->nodeName) === 'ol') {
                    // For numeric lists: pass current numeric prefix
                    // For alpha/roman lists: they reset prefix anyway
                    self::numberOl($dom, $child, ($mode === 'numeric') ? $current : $prefix, 'numeric');
                }
            }
        }
    }

    private static function prependNumberSpan(DOMDocument $dom, DOMElement $li, string $label, string $class): void
    {
        // Avoid double numbering
        foreach ($li->childNodes as $n) {
            if ($n instanceof DOMElement && strtolower($n->nodeName) === 'span') {
                if (str_contains(' ' . $n->getAttribute('class') . ' ', ' lnum ')) {
                    return;
                }
            }
        }

        // Detect if first meaningful element is a clause title
        $firstElement = null;
        foreach ($li->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $firstElement = $child;
                break;
            }
        }

        if (
            $firstElement &&
            strtolower($firstElement->nodeName) === 'strong' &&
            str_contains(' ' . $firstElement->getAttribute('class') . ' ', ' clause-title ')
        ) {
            $class .= ' lnum-title';
        }

        $span = $dom->createElement('span');
        $span->setAttribute('class', trim($class));
        $span->appendChild($dom->createTextNode($label));

        $li->insertBefore($span, $li->firstChild);
    }

    private static function alphaLabel(int $n): string
    {
        // 1->a, 2->b ... 26->z, 27->aa, 28->ab ...
        $s = '';
        while ($n > 0) {
            $n--;
            $s = chr(97 + ($n % 26)) . $s;
            $n = intdiv($n, 26);
        }
        return $s;
    }

    private static function romanLabel(int $n): string
    {
        // 1->i, 2->ii ... (good enough for clauses)
        $map = [
            1000 => 'm', 900 => 'cm', 500 => 'd', 400 => 'cd',
            100  => 'c', 90  => 'xc', 50  => 'l', 40  => 'xl',
            10   => 'x', 9   => 'ix', 5   => 'v', 4   => 'iv',
            1    => 'i',
        ];

        $res = '';
        foreach ($map as $val => $sym) {
            while ($n >= $val) {
                $res .= $sym;
                $n -= $val;
            }
        }
        return $res;
    }

    private static function getBodyInnerHTML(DOMDocument $dom): string
    {
        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) return '';

        $html = '';
        foreach ($body->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }
        return $html;
    }
}
