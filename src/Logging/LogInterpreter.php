<?php

declare(strict_types=1);

namespace ParaTest\Logging;

use ParaTest\Logging\JUnit\Reader;
use ParaTest\Logging\JUnit\TestSuite;

class LogInterpreter extends MetaProvider
{
    /**
     * A collection of Reader objects
     * to aggregate results from.
     *
     * @var array
     */
    protected $readers = [];

    /**
     * Reset the array pointer of the internal
     * readers collection.
     */
    public function rewind()
    {
        reset($this->readers);
    }

    /**
     * Add a new Reader to be included
     * in the final results.
     *
     * @param Reader $reader
     *
     * @return $this
     */
    public function addReader(Reader $reader): self
    {
        $this->readers[] = $reader;

        return $this;
    }

    /**
     * Return all Reader objects associated
     * with the LogInterpreter.
     *
     * @return Reader[]
     */
    public function getReaders(): array
    {
        return $this->readers;
    }

    /**
     * Returns true if total errors and failures
     * equals 0, false otherwise
     * TODO: Remove this comment if we don't care about skipped tests in callers.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        $failures = $this->getTotalFailures();
        $errors = $this->getTotalErrors();

        return $failures === 0 && $errors === 0;
    }

    /**
     * Get all test case objects found within
     * the collection of Reader objects.
     *
     * @return array
     */
    public function getCases(): array
    {
        $cases = [];
        foreach ($this->readers as $reader) {
            foreach ($reader->getSuites() as $suite) {
                $cases = array_merge($cases, $suite->cases);
                foreach ($suite->suites as $nested) {
                    $this->extendEmptyCasesFromSuites($nested->cases, $suite);
                    $cases = array_merge($cases, $nested->cases);
                }
            }
        }

        return $cases;
    }

    /**
     * Fix problem with empty testcase from DataProvider.
     *
     * @param array     $cases
     * @param TestSuite $suite
     */
    protected function extendEmptyCasesFromSuites(array $cases, TestSuite $suite)
    {
        $class = $suite->name;
        $file = $suite->file;

        /** @var TestCase $case */
        foreach ($cases as $case) {
            if (empty($case->class)) {
                $case->class = $class;
            }
            if (empty($case->file)) {
                $case->file = $file;
            }
        }
    }

    /**
     * Flattens all cases into their respective suites.
     *
     * @return array $suites a collection of suites and their cases
     */
    public function flattenCases(): array
    {
        $dict = [];
        foreach ($this->getCases() as $case) {
            if (!isset($dict[$case->file])) {
                $dict[$case->file] = new TestSuite($case->class, 0, 0, 0, 0, 0, 0);
            }
            $dict[$case->file]->cases[] = $case;
            ++$dict[$case->file]->tests;
            $dict[$case->file]->assertions += $case->assertions;
            $dict[$case->file]->failures += \count($case->failures);
            $dict[$case->file]->errors += \count($case->errors);
            $dict[$case->file]->skipped += \count($case->skipped);
            $dict[$case->file]->time += $case->time;
            $dict[$case->file]->file = $case->file;
        }

        return array_values($dict);
    }

    /**
     * Returns a value as either a float or int.
     *
     * @param $property
     *
     * @return float|int
     */
    protected function getNumericValue(string $property)
    {
        return ($property === 'time')
               ? (float) $this->accumulate('getTotalTime')
               : (int) $this->accumulate('getTotal' . ucfirst($property));
    }

    /**
     * Gets messages of a given type and
     * merges them into a single collection.
     *
     * @param $type
     *
     * @return array
     */
    protected function getMessages(string $type): array
    {
        return $this->mergeMessages('get' . ucfirst($type));
    }

    /**
     * Flatten messages into a single collection
     * based on an accessor method.
     *
     * @param $method
     *
     * @return array
     */
    private function mergeMessages(string $method): array
    {
        $messages = [];
        foreach ($this->readers as $reader) {
            $messages = array_merge($messages, $reader->{$method}());
        }

        return $messages;
    }

    /**
     * Reduces a collection of readers down to a single
     * result based on an accessor.
     *
     * @param $method
     *
     * @return mixed
     */
    private function accumulate(string $method)
    {
        return array_reduce($this->readers, function ($result, $reader) use ($method) {
            $result += $reader->$method();

            return $result;
        }, 0);
    }
}
