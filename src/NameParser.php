<?php namespace Xyluz\Street;

use Luchaninov\CsvFileLoader\CsvFileLoader;

/**
 * Accepts different file format, but specifically made for and tested with CSV
 */
class NameParser
{
    public array $parsed;
    public array $titles = [];
    public array $connectors = [];
    public array $initials = [];
    public array $currentHomeOwnerArray = [];

    public function __construct(public string $path = '')
    {
    }

    public function setImportPath(string $path): static{
        $this->path = $path;
        return $this;
    }


    public function run(): array
    {
        $csv = $this->loadFile();

        foreach ($csv as $line){
            $this->parsed[] = $this->extractNameComponents($line['homeowner']);
            $this->resetAllParameters();
        }

        return $this->parsed;
    }

    /**
     * Load the content of the file that has been specified in the filepath.
     *
     * @return array
     */
    public function loadFile(): array
    {
        if(empty($this->path) || !file_exists($this->path)){
            throw new \InvalidArgumentException('File path not set or invalid path provided');
        }

        return (new CsvFileLoader($this->path))->getItemsArray();

    }

    /**
     * Identity necessary components of the given homeowner name, and build the needed results
     *
     * str_replace is necessary because of certain parameters with . in their initials. This way, we level the playing field
     *
     * @param string $homeowner
     * @return array
     */
    public function extractNameComponents(string $homeowner): array
    {
        $this->currentHomeOwnerArray = explode(' ', $this->cleanInput($homeowner));

        $this->extractTitles();
        $this->extractConnectors();
        $this->extractInitials();

        return $this->buildResult();

    }

    /**
     * identities if a string is a title or not,
     * using a strict set of titles
     * TODO: Maybe use regex, or title library? - Going with bruteforce for now
     *
     * @return void
     */
    public function extractTitles(): void
    {
        $titles = ['Mr','Mrs','Ms','Master','Dr','Mister','Prof'];

        foreach($titles as  $title){

            $occurrences = array_count_values($this->currentHomeOwnerArray)[$title] ?? 0;

            for ($i = 0; $i < $occurrences; $i++) {
                $this->titles[] = $title;
                $this->removeValue($title);
            }

        }
    }

    /**
     * Checks if the known collectors are used in a string, and returns the connectors
     * TODO: Possibly can be achieved without having hardcoded connectors? bruteforce again.
     *
     * @return void
     */
    public function extractConnectors(): void
    {
        $knownConnectors = ['and','&'];

        foreach($knownConnectors as $connector){

            if(in_array($connector, $this->currentHomeOwnerArray)){
                $this->connectors[] = $connector;
                $this->removeValue($connector);
            }

        }

    }

    /**
     * Test if initials exists, and set them
     * Set the boundary of the word, check if a letter is standing alone (considered to be an initial) and return the letter (or letters)
     *
     * @return void
     */
    public function extractInitials(): void {

        foreach($this->currentHomeOwnerArray as $value){

            if (preg_match_all('/\b[A-Za-z](?:\.)?\b/', $value, $matches)) {
                $this->initials[] = $matches[0][0];
                foreach($this->initials as $initial){
                    $this->removeValue($initial[0]);
                }

            }

        }

    }

    /**
     * @param string $string
     * @return bool
     */
    private function removeValue(string $string): bool
    {

        foreach($this->currentHomeOwnerArray as $key => $value){

            if($string === $value){
                unset($this->currentHomeOwnerArray[$key]);
            }
        }

        $this->currentHomeOwnerArray = array_values($this->currentHomeOwnerArray);

        return false;

    }

    private function buildResult(): array
    {
        return empty($this->connectors) ? $this->fetchSingleName() : $this->fetchMultipleNames();
    }

    /**
     * Build single result, can be built with specified parameters or global variables
     *
     * @param string|null $title
     * @param null $first_name
     * @param null $last_name
     * @return array
     */
    private function fetchSingleName(
        ?string $title = null, ?string $first_name = null, ?string $last_name = null
    ): array
    {

        $hasTwoNames = count($this->currentHomeOwnerArray) > 1;
        $hasInitial = count($this->initials) > 0;

        $initial = $hasInitial ? $this->initials[0] : null;

        unset($this->initials[0]);
        $this->initials = array_values($this->initials);

        $title = $title ?? $this->titles[0] ?? null;

        $first_name = $first_name ?? ($hasTwoNames ? $this->currentHomeOwnerArray[0] : null);
        $last_name = $last_name ?? ($hasTwoNames ? $this->currentHomeOwnerArray[1] : $this->currentHomeOwnerArray[0]);

        return [
            'title'=> $title,
            'initial'=> $initial,
            'first_name'=> $first_name,
            'last_name'=> $last_name,
        ];

    }


    /**
     * TODO: Too specified to the given input. Maybe it could be improved to capture more input variations.
     * @return array
     */
    private function fetchMultipleNames(): array
    {
        $multiple = [];

        $count = count($this->currentHomeOwnerArray);

        switch ($count) {

            case 1:
                $multiple[] = $this->fetchSingleName($this->titles[0], null, $this->currentHomeOwnerArray[0]);
                if (isset($this->titles[1])) {
                    $multiple[] = $this->fetchSingleName( $this->titles[1], null, $this->currentHomeOwnerArray[0]);
                }
                break;
            case 2:
                $multiple[] = $this->fetchSingleName( $this->titles[0], $this->currentHomeOwnerArray[0], $this->currentHomeOwnerArray[1]);
                if (isset($this->titles[1])) {
                    $multiple[] = $this->fetchSingleName( $this->titles[1], $this->currentHomeOwnerArray[0], $this->currentHomeOwnerArray[1]);
                }
                break;
            case 4:
                $splitNames = array_chunk($this->currentHomeOwnerArray, 2);
                $multiple[] = $this->fetchSingleName( $this->titles[0], $splitNames[0][0], $splitNames[0][1]);
                $multiple[] = $this->fetchSingleName( $this->titles[1], $splitNames[1][0], $splitNames[1][1]);
                break;
            default:
                $multiple = $this->handleCaseAboveFour();
                
        }

        return $multiple;
    }

    private function resetAllParameters(): void
    {
        $this->initials = [];
        $this->currentHomeOwnerArray = [];
        $this->titles = [];
        $this->connectors = [];
    }

    /**
     * It's possible for the user to enter data with double-spacing,
     * this method ensure that all . are removed from Initials and double spaces are converted to
     * single space.
     *
     * @param string $string
     * @return string
     */
    private function cleanInput(string $string): string {
        return preg_replace('/\s+/', ' ', str_replace('.', '', $string));
    }

    /**
     * Handles cases not in the current sample data - probably applicable
     * @return array
     */
    private function handleCaseAboveFour(): array
    {
        $multiple = [];

        $splitNames = array_chunk($this->currentHomeOwnerArray, 2);
        $titleCount = count($this->titles);

        foreach ($splitNames as $index => $names) {
            $title = $titleCount > 0 ? $this->titles[$index % $titleCount] ?? null : null;
            $firstName = $names[0] ?? null;
            $lastName = $names[1] ?? null;

            $multiple[] = $this->fetchSingleName( $title ?? null, $firstName, $lastName);
        }

        return $multiple;
    }


}