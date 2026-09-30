<?php

namespace Anchor_Episodes_Index;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reads the Podcasting 2.0 namespace (https://podcastindex.org/namespace/1.0)
 * and the richer iTunes tags out of a feed item.
 *
 * Hosts aimed at independent podcasters - Transistor, Buzzsprout, Captivate,
 * RSS.com - already publish transcripts, chapter markers and per-episode
 * credits in these tags. Nothing was reading them.
 */
class Podcast_Namespace {

    /** Namespace URIs seen in the wild, in addition to the "podcast" prefix. */
    const NAMESPACE_URIS = array(
        'https://podcastindex.org/namespace/1.0',
        'http://podcastindex.org/namespace/1.0',
    );

    /**
     * Transcript formats we can render, best first.
     *
     * JSON carries speaker names and per-segment timing, so it is preferred.
     * text/html is deliberately absent: it is a link to a web page, not a
     * transcript file we can parse.
     */
    const TRANSCRIPT_FORMATS = array(
        'application/json',
        'text/vtt',
        'application/x-subrip',
        'application/srt',
        'text/plain',
    );

    /**
     * Child elements in the podcast namespace, or null.
     *
     * @param \SimpleXMLElement $element
     * @return \SimpleXMLElement|null
     */
    public static function children($element) {
        if (!($element instanceof \SimpleXMLElement)) {
            return null;
        }

        // Nearly every feed uses the "podcast" prefix.
        $children = $element->children('podcast', true);

        if ($children instanceof \SimpleXMLElement && $children->count() > 0) {
            return $children;
        }

        foreach (self::NAMESPACE_URIS as $uri) {
            $children = $element->children($uri);

            if ($children instanceof \SimpleXMLElement && $children->count() > 0) {
                return $children;
            }
        }

        return null;
    }

    /**
     * Attribute value as a string, or '' when absent.
     */
    private static function attr($element, $name) {
        if (!($element instanceof \SimpleXMLElement)) {
            return '';
        }

        $attributes = $element->attributes();

        return isset($attributes[$name]) ? trim((string) $attributes[$name]) : '';
    }

    /**
     * Every <podcast:transcript> on an item.
     *
     * @return array List of array{url,type,rel,language}
     */
    public static function get_transcripts($item) {
        $podcast = self::children($item);

        if (!$podcast || !isset($podcast->transcript)) {
            return array();
        }

        $transcripts = array();

        foreach ($podcast->transcript as $transcript) {
            $url = self::attr($transcript, 'url');

            if ($url === '') {
                continue;
            }

            $transcripts[] = array(
                'url'      => $url,
                'type'     => strtolower(self::attr($transcript, 'type')),
                'rel'      => strtolower(self::attr($transcript, 'rel')),
                'language' => self::attr($transcript, 'language'),
            );
        }

        return $transcripts;
    }

    /**
     * The transcript we would render, or null.
     *
     * Picks by format preference rather than feed order, because hosts list
     * several formats of the same transcript and the order is arbitrary.
     */
    public static function pick_transcript(array $transcripts) {
        foreach (self::TRANSCRIPT_FORMATS as $format) {
            foreach ($transcripts as $transcript) {
                if ($transcript['type'] === $format) {
                    return $transcript;
                }
            }
        }

        // Unknown type but a plausible extension: fall back on the URL.
        foreach ($transcripts as $transcript) {
            $path = strtolower((string) wp_parse_url($transcript['url'], PHP_URL_PATH));

            if (preg_match('/\.(json|vtt|srt|txt)$/', $path)) {
                return $transcript;
            }
        }

        return null;
    }

    /**
     * <podcast:chapters>, or null.
     *
     * @return array|null array{url,type}
     */
    public static function get_chapters($item) {
        $podcast = self::children($item);

        if (!$podcast || !isset($podcast->chapters)) {
            return null;
        }

        $url = self::attr($podcast->chapters, 'url');

        if ($url === '') {
            return null;
        }

        return array(
            'url'  => $url,
            'type' => strtolower(self::attr($podcast->chapters, 'type')),
        );
    }

    /**
     * Every <podcast:person> on an item: hosts, guests, editors and so on.
     *
     * The element's text is the person's name; role, href and img are
     * attributes. Role defaults to "host" per the spec.
     *
     * @return array List of array{name,role,group,href,img}
     */
    public static function get_persons($item) {
        $podcast = self::children($item);

        if (!$podcast || !isset($podcast->person)) {
            return array();
        }

        $persons = array();

        foreach ($podcast->person as $person) {
            $name = trim((string) $person);

            if ($name === '') {
                continue;
            }

            $role = self::attr($person, 'role');

            $persons[] = array(
                'name'  => $name,
                'role'  => $role !== '' ? $role : 'host',
                'group' => self::attr($person, 'group'),
                'href'  => self::attr($person, 'href'),
                'img'   => self::attr($person, 'img'),
            );
        }

        return $persons;
    }

    /**
     * Season and episode numbering, plus episode type.
     *
     * podcast:season / podcast:episode win over the iTunes equivalents where
     * both are present, because they are the newer and better specified tags.
     *
     * @return array array{season,episode_number,episode_type,explicit}
     */
    public static function get_numbering($item) {
        $podcast = self::children($item);
        $itunes  = ($item instanceof \SimpleXMLElement) ? $item->children('itunes', true) : null;

        $season  = '';
        $episode = '';

        if ($podcast && isset($podcast->season)) {
            $season = trim((string) $podcast->season);
        }

        if ($season === '' && $itunes && isset($itunes->season)) {
            $season = trim((string) $itunes->season);
        }

        if ($podcast && isset($podcast->episode)) {
            $episode = trim((string) $podcast->episode);
        }

        if ($episode === '' && $itunes && isset($itunes->episode)) {
            $episode = trim((string) $itunes->episode);
        }

        $type     = ($itunes && isset($itunes->episodeType)) ? strtolower(trim((string) $itunes->episodeType)) : '';
        $explicit = ($itunes && isset($itunes->explicit)) ? strtolower(trim((string) $itunes->explicit)) : '';

        return array(
            'season'         => $season,
            'episode_number' => $episode,
            'episode_type'   => $type,
            'explicit'       => in_array($explicit, array('yes', 'true'), true),
        );
    }
}
