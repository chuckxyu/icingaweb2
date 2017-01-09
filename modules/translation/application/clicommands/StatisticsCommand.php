<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Clicommands;

use Icinga\Module\Translation\Statistics\Statistics;
use Icinga\Module\Translation\Cli\TranslationCommand;

class StatisticsCommand extends TranslationCommand
{
    /**
     * The statistics to be displayed
     *
     * @var Statistics
     */
    protected $statistics;

    /**
     * The percentages of the statistics to be displayed
     *
     * @var array
     */
    protected $percentages;

    protected function getPercentage($number)
    {
        $percentage = $number / $this->statistics->countEntries() * 100;
        if ($percentage != 0 && $percentage < 1) {
            return 1;
        }

        return round($percentage);
    }

    /**
     * Calculates the percentages from the statistics
     */
    protected function calculatePercentages()
    {
        $this->percentages = array();
        $this->percentages['untranslated'] = $this->getPercentage($this->statistics->countUntranslatedEntries());
        $this->percentages['translated'] = $this->getPercentage($this->statistics->countTranslatedEntries());
        $this->percentages['fuzzy'] = $this->getPercentage($this->statistics->countFuzzyEntries());
        $this->percentages['faulty'] = $this->getPercentage($this->statistics->countFaultyEntries());

        $percentageSum = array_sum($this->percentages);
        if ($percentageSum != 100) {
            $difference = 100 - $percentageSum;

            $toAdapt = array_search(max($this->percentages), $this->percentages);
            $this->percentages[$toAdapt] += $difference;
        }
    }

    public function graphsAction()
    {
        if (!$this->params->getAllStandalone()) {
            //todo display manual
            return;
        }

        foreach ($this->params->getAllStandalone() as $path) {
            $this->statistics = new Statistics($path);

            $this->calculatePercentages();

            echo PHP_EOL;

            foreach ($this->percentages as $key => $value) {
                $color = '';
                switch ($key) {
                    case 'untranslated':
                        $color = 'blue';
                        break;
                    case 'translated':
                        $color = 'red';
                        break;
                    case 'fuzzy':
                        $color = 'green';
                        break;
                    case 'faulty':
                        $color = 'purple';
                        break;
                }
                for ($i = 0; $i < $value; $i++) {
                    echo $this->screen->colorize('█', $color);
                }
            }


            $pathParts = explode('/', $this->statistics->getPath());

            echo PHP_EOL
                . '⤷ '
                . $pathParts[count($pathParts) - 3]
                . ': '
                . $pathParts[count($pathParts) - 1]
                . ' ('
                . $this->statistics->countEntries()
                . ' messages)'
                . PHP_EOL . PHP_EOL;

            echo "\t"
                . $this->screen->colorize('Untranslated', 'blue')
                . ': '
                . $this->percentages['untranslated']
                . '% ('
                . $this->statistics->countUntranslatedEntries()
                . ' messages)'
                . PHP_EOL;

            echo "\t"
                . $this->screen->colorize('Translated', 'red')
                . ': '
                . $this->percentages['translated']
                . '% ('
                . $this->statistics->countTranslatedEntries()
                . ' messages)'
                . PHP_EOL;

            echo "\t"
                . $this->screen->colorize('Fuzzy', 'green')
                . ': '
                . $this->percentages['fuzzy']
                . '% ('
                . $this->statistics->countFuzzyEntries()
                . ' messages)'
                . PHP_EOL;

            echo "\t"
                . $this->screen->colorize('Faulty', 'purple')
                . ': '
                . $this->percentages['faulty']
                . '% ('
                . $this->statistics->countFaultyEntries()
                . ' messages)'
                . PHP_EOL . PHP_EOL;
        }
    }
}