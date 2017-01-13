<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Clicommands;

use Icinga\Application\Logger;
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
        if($maxCount == 0) {
            return 0;
        } else {
            $percentage = $number / $maxCount * 100;
            if ($percentage != 0 && $percentage < 1) {
                return 1;
            }
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

        if (! $input) {
            $input = $allLocales;
            $valueGiven = false;
        }

        foreach ($input as $locale) {
            if (! $valueGiven || in_array($locale, $allLocales)) {
                $paths[] = implode(
                    DIRECTORY_SEPARATOR,
                    array($this->app->getLocaleDir(), $locale, 'LC_MESSAGES', 'icinga.po')
                );
                foreach ($this->app->getModuleManager()->listEnabledModules() as $module) {
                    $localeDir = $this->app->getModuleManager()->getModule($module)->getLocaleDir();
                    if (is_dir($localeDir)) {
                        $paths[] = implode(
                            DIRECTORY_SEPARATOR,
                            array($localeDir, $locale, 'LC_MESSAGES', $module . '.po')
                        );
                    }
                }
            } else {
                if (! preg_match('@[a-z]{2}_[A-Z]{2}@', $locale)) {
                    Logger::error(
                        sprintf($this->translate('Locale code \'%s\' is not valid. Expected format is: ll_CC'), $locale)
                    );
                    exit(1);
                } else {
                    Logger::warning(
                        sprintf($this->translate('\'%s\' is an unknown locale code.'), $locale)
                    );
                    exit(1);
                }
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
