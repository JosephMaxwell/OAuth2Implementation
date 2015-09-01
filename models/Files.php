<?php

namespace Models;

class Files
{
    protected $_dataset;

    public function __construct($dataset)
    {
        $this->_dataset = json_decode($dataset, true);
    }

    public function formatData()
    {
        $data = $this->_dataset;
        $output = [];

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $details = [
                    'title' => $item['title'],
                    'owner' => array_shift($item['owners']),
                    'iconLink' => $item['iconLink'],
                ];

                $output[] = $details;
            }
        }

        return $output;
    }
}