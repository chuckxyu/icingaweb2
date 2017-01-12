<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Clicommands;

use Icinga\Module\Translation\Statistics\Statistics;
use Icinga\Module\Translation\Cli\TranslationCommand;
use Icinga\Util\Translator;

class StatisticsCommand extends TranslationCommand
{

    protected $colors = array(
        'untranslated' => 'blue',
        'translated' => 'red',
        'fuzzy' => 'green',
        'faulty' => 'purple'
    );

    protected function getPercentage($number, $maxCount)
    {
        $percentage = $number / $maxCount * 100;
        if ($percentage != 0 && $percentage < 1) {
            return 1;
        }

        return round($percentage);
    }

    /**
     * Calculates the percentages from the statistics
     *
     * @param Statistics $statistics
     *
     * @return array
     */
    protected function calculatePercentages($statistics)
    {
        $maxCount = $statistics->countEntries();

        $percentages = array();
        $percentages['untranslated'] = $this->getPercentage($statistics->countUntranslatedEntries(), $maxCount);
        $percentages['translated'] = $this->getPercentage($statistics->countTranslatedEntries(), $maxCount);
        $percentages['fuzzy'] = $this->getPercentage($statistics->countFuzzyEntries(), $maxCount);
        $percentages['faulty'] = $this->getPercentage($statistics->countFaultyEntries(), $maxCount);

        $percentageSum = array_sum($percentages);
        if ($percentageSum != 100) {
            $difference = 100 - $percentageSum;

            $toAdapt = array_search(max($percentages), $percentages);
            $percentages[$toAdapt] += $difference;
        }

        return $percentages;
    }

    protected function recursiveGlob($pattern, $flags = 0) {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->recursiveGlob($dir.'/'.basename($pattern), $flags));
        }
        return $files;
    }

    protected function getPaths()
    {
        $paths = array();
        $input = $this->params->getAllStandalone();
        $this->app->getModuleManager()->loadEnabledModules();
        $valueGiven = true;

        $allLocales = Translator::getAvailableLocaleCodes();
        if(($key = array_search('en_US', $allLocales)) !== false) {
            unset($allLocales[$key]);
        }

        if (!$input) {
            $input = $allLocales;
            $valueGiven = false;
        }

        foreach ($input as $locale) {
            if (!$valueGiven || in_array($locale, $allLocales)) {
                $paths[] = $this->app->getLocaleDir() . '/' . $locale . '/LC_MESSAGES/icinga.po';
                foreach ($this->app->getModuleManager()->listEnabledModules() as $module) {
                    if (is_dir($this->app->getModuleManager()->getModule($module)->getLocaleDir())) {
                        $paths[] = $this->app->getModuleManager()->getModule($module)->getLocaleDir()
                            . '/' . $locale . '/LC_MESSAGES/' . $module . '.po';
                    }
                }
            } else {
                echo "\n" . $locale . " is an invalid locale code. \n";
            }
        }
        return $paths;
    }

    public function graphsAction()
    {
        foreach ($this->getPaths() as $path) {
            $statistics = new Statistics($path);

            $percentages = $this->calculatePercentages($statistics);

            echo PHP_EOL;

            foreach ($percentages as $key => $value) {
                echo $this->screen->colorize(str_repeat('█', $value), $this->colors[$key]);
            }
                $pathParts = explode('/', $statistics->getPath());

                printf(
                    PHP_EOL . '⤷ %s: %s (%s messages)' . PHP_EOL . PHP_EOL,
                    $pathParts[count($pathParts) - 3],
                    $pathParts[count($pathParts) - 1],
                    $statistics->countEntries()
                );

                printf(
                    "\t %s: %d%% (%d messages)" . PHP_EOL,
                    $this->screen->colorize('Untranslated', 'blue'),
                    $percentages['untranslated'],
                    $statistics->countUntranslatedEntries()
                );

                printf(
                    "\t %s: %d%% (%d messages)" . PHP_EOL,
                    $this->screen->colorize('Translated', 'red'),
                    $percentages['translated'],
                    $statistics->countTranslatedEntries()
                );

                printf(
                    "\t %s: %d%% (%d messages)" . PHP_EOL,
                    $this->screen->colorize('Fuzzy', 'green'),
                    $percentages['fuzzy'],
                    $statistics->countFuzzyEntries()
                );

                printf(
                    "\t %s: %d%% (%d messages)" . PHP_EOL . PHP_EOL,
                    $this->screen->colorize('Faulty', 'purple'),
                    $percentages['faulty'],
                    $statistics->countFaultyEntries()
                );
        }
    }
}
