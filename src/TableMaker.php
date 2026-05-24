<?php

namespace CI4TableMaker;

class TableMaker
{
    private \CodeIgniter\View\Table $ci4TableLib;
    private ?array $template = null;
    private ?array $data = null;
    private ?string $urlBase = null;
    private array $columns = [];
    private array $links = [];
    private string $separator = '&nbsp;';

    public function __construct(\CodeIgniter\View\Table $ci4TableLib)
    {
        $this->ci4TableLib = $ci4TableLib;
    }

    /**
     * Set the table heading
     *
     * @param mixed $heading Can be passed as an array or discreet params
     */
    public function setHeading(...$heading): void
    {
        $this->ci4TableLib->setHeading(...$heading);
    }

    /**
     * Set the table footing
     *
     * @param mixed $footing Can be passed as an array or discreet params
     */
    public function setFooting(...$footing): void
    {
        $this->ci4TableLib->setFooting(...$footing);
    }

    /**
     * Set the table template. The same properties required by the CI4 HTML Table Class.
     *
     * @param array $template
     */
    public function setTemplate(array $template): void
    {
        $this->template = $template;
    }

    /**
     * Set the data to be displayed in the table.
     *
     * @param array|iterable $data
     */
    public function setData(iterable $data): void
    {
        $this->data = [];
        foreach ($data as $row) {
            $this->data[] = (array) $row;
        }
    }

    /**
     * Set the URL base to each link
     *
     * @param string $urlBase The URL
     */
    public function setUrlBase(string $urlBase): void
    {
        $this->urlBase = (substr($urlBase, -1) == '/') ? $urlBase : $urlBase . '/';
    }

    /**
     * Set the columns that should be displayed
     *
     * @param array $columns Columns to be displayed
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * The separator to insert between each link (Default value: "&nbsp;").
     *
     * @param string $separator
     */
    public function setSeparator(string $separator): void
    {
        $this->separator = $separator;
    }

    /**
     * Add a new link that will be inserted into the table.
     * In $path, use {param} or any column name enclosed in braces like {id} as a wildcard.
     *
     * @param string       $path       The path of the link to be inserted.
     * @param string|null  $param      The dynamic parameter key (optional, e.g. "id")
     * @param string       $title      The link description (label)
     * @param array|string $attr       Extra attributes that will be used in the link.
     * @param array|null   $condition  A condition array containing "field", "operator" and "value"
     */
    public function addLink(string $path, ?string $param, string $title, array|string $attr = '', ?array $condition = null): void
    {
        $condition = (
            is_array($condition) &&
            (
                array_key_exists('field', $condition) &&
                array_key_exists('operator', $condition) &&
                array_key_exists('value', $condition)
            )
        )
            ? $condition
            : null;

        $this->links[] = [
            'path'      => $path,
            'param'     => $param,
            'title'     => $title,
            'attr'      => $attr,
            'condition' => $condition,
        ];
    }

    /**
     * Display the table.
     *
     * @param boolean $returnData Set to true to return the data as an array, false to return the table as an HTML string.
     * @param string $indexTitle The title of the column to insert the links into.
     * @return array|string The HTML table as a string or array with data.
     */
    public function display(bool $returnData = false, string $indexTitle = 'actions'): array|string
    {
        $this->insertLinks($indexTitle);
        $this->ci4TableLib->setTemplate($this->template);
        return $returnData ? $this->data : $this->ci4TableLib->generate($this->data);
    }

    /**
     * Convert the object to a string.
     *
     * @return string The HTML table as a string.
     */
    public function __toString(): string
    {
        return $this->display();
    }

    /**
     * Insert links into the $this->data attribute.
     *
     * @param string $indexTitle The title of the column to insert the links into.
     */
    private function insertLinks(string $indexTitle): void
    {
        if (! is_null($this->data)) {
            if (is_array($this->links) && count($this->links) > 0) {
                for ($i = 0; $i < count($this->data); $i++) {
                    $anchors = [];

                    foreach ($this->links as $link) {
                        $passCondition = true;
                        // has condition?
                        if (! is_null($link['condition'])) {
                            $passCondition = $this->checkCondition($this->data[$i], $link['condition']);
                        }

                        if ($passCondition) {
                            $fullPath = ($this->urlBase ?? '') . $link['path'];
                            $fullPath = (substr($fullPath, -1) == '/') ? $fullPath : $fullPath . '/';

                            $path = $fullPath;
                            if (isset($link['param']) && isset($this->data[$i][$link['param']])) {
                                $path = str_replace('{param}', $this->data[$i][$link['param']], $path);
                            }
                            foreach ($this->data[$i] as $key => $value) {
                                if (is_scalar($value) || is_null($value)) {
                                    $path = str_replace('{' . $key . '}', (string) $value, $path);
                                }
                            }
                            $anchors[] = anchor($path, $link['title'], $link['attr'] ?? '');
                        }
                    }
                    $this->data[$i][$indexTitle] = implode($this->separator, $anchors);
                }
            }

            //delete/filter columns that should not display and guarantee their order
            if (is_array($this->columns) && count($this->columns) > 0) {
                if (! in_array($indexTitle, $this->columns)) {
                    $this->columns[] = $indexTitle;
                }
                for ($i = 0; $i < count($this->data); $i++) {
                    $newRow = [];
                    foreach ($this->columns as $column) {
                        if (array_key_exists($column, $this->data[$i])) {
                            $newRow[$column] = $this->data[$i][$column];
                        }
                    }
                    $this->data[$i] = $newRow;
                }
            }
        }
    }

    /**
     * Check condition.
     *
     * @param array $item the data record to check
     * @param array $condition a condition array containing 'field', 'operator' and 'value'
     * @return boolean TRUE if the condition is met, false otherwise.
     */
    private function checkCondition(array $item, array $condition): bool
    {
        $valueField = $item[$condition['field']] ?? null;

        return match ($condition['operator']) {
            '=='   => $valueField ==  $condition['value'],
            '==='  => $valueField === $condition['value'],
            '!='   => $valueField !=  $condition['value'],
            '>'    => $valueField >   $condition['value'],
            '<'    => $valueField <   $condition['value'],
            '>='   => $valueField >=  $condition['value'],
            '<='   => $valueField <=  $condition['value'],
            default => false, // If an invalid operator is encountered
        };
    }
}
