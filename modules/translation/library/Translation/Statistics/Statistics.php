<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Statistics;

/**
 * Class Statistics
 *
 * Creates statistics about a .po file
 */
class Statistics
{
    /**
     * The path from which to create the statistics
     *
     * @var string
     */
    protected $path;

    /**
     * The amount of entries
     *
     * @var int
     */
    protected $entryCount = 0;

    /**
     * The amount of untranslated entries
     *
     * @var int
     */
    protected $untranslatedEntryCount = 0;

    /**
     * The amount of translated entries
     *
     * @var int
     */
    protected $translatedEntryCount = 0;

    /**
     * The amount of fuzzy entries
     *
     * @var int
     */
    protected $fuzzyEntryCount = 0;

    /**
     * The amount of faulty entries
     *
     * @var int
     */
    protected $faultyEntryCount = 0;

    /**
     * Create a new Statistics object
     *
     * @param   string  $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->sortNumbers();
    }

    /**
     * Run msgfmt from the gettext tools and output the gathered statistics
     *
     * @return string
     */
    protected function getStatistics()
    {
        $line = '/usr/bin/msgfmt ' . $this->path . ' --statistics -cf';
        $descriptorSpec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );
        $env = array('LANG' => 'en_GB');
        $process = proc_open(
            $line,
            $descriptorSpec,
            $pipes,
            null,
            $env,
            null
        );

        $info = stream_get_contents($pipes[2]);

        proc_close($process);

        return $info;
    }

    /**
     * Parse the gathered statistics from msgfmt of the gettext tools
     */
    protected function sortNumbers()
    {
        $info = explode('msgfmt: found ', $this->getStatistics());
        $relevant = $info[count($info) - 1];
        preg_match_all('/\d+ [a-z]+/', $relevant , $results);

        foreach ($results[0] as $value) {

            $chunks = explode(' ', $value);
            switch ($chunks[1]) {
                case 'fatal':
                    $this->faultyEntryCount = (int)$chunks[0];
                    break;
                case 'translated':
                    $this->translatedEntryCount = (int)$chunks[0];
                    break;
                case 'fuzzy':
                    $this->fuzzyEntryCount = (int)$chunks[0];
                    break;
                case 'untranslated':
                    $this->untranslatedEntryCount = (int)$chunks[0];
                    break;
            }
        }

        $this->entryCount = $this->faultyEntryCount
            + $this->translatedEntryCount
            + $this->fuzzyEntryCount
            + $this->untranslatedEntryCount;
    }

    /**
     * Return path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Count all Entries
     *
     * @return int
     */
    public function countEntries()
    {
        return $this->entryCount;
    }

    /**
     * Count all untranslated entries
     *
     * @return int
     */
    public function countUntranslatedEntries()
    {
        return $this->untranslatedEntryCount;
    }

    /**
     * Count all translated entries
     *
     * @return int
     */
    public function countTranslatedEntries()
    {
        return $this->translatedEntryCount;
    }

    /**
     * Count all fuzzy entries
     *
     * @return int
     */
    public function countFuzzyEntries()
    {
        return $this->fuzzyEntryCount;
    }

    /**
     * Count all faulty entries
     *
     * @return int
     */
    public function countFaultyEntries()
    {
        return $this->faultyEntryCount;
    }
}