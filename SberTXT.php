<?php

class SberTXT
{
	protected $months = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];

	protected $filename;

	public function __construct($filename)
	{
		$this->filename = $filename;

		$this->read();
	}

	public function read()
	{
		$file = mb_convert_encoding(file_get_contents($this->filename), 'utf8', 'cp1251');
		$lines = explode("\r\n", rtrim($file));

		$this->pages = [];
		$page = null;
		foreach ($lines as $line) {
			if (trim($line) === 'С Б Е Р Б А Н К  Р О С С И И') {
				if ($page !== null)
					$this->pages[] = $page;

				$page = [];
			}

			$page[] = $line;
		}

		if ($page !== null)
			$this->pages[] = $page;

		return $this->pages;
	}

	public function getRawRows()
	{
		$lines = [];

		foreach ($this->pages as $page) {
			$page = array_slice($page, 16);
			$page = array_slice($page, 0, -13);

			foreach ($page as $line) {
				$lines[] = mb_substr($line, 20, null, 'utf8');
			}
		}

		return $lines;
	}

	public function getRows()
	{
		$rawRows = $this->getRawRows();

		$lines = [];
		$line = null;

		foreach ($rawRows as $rawLine) {
			$data = $this->lineColumns($rawLine, [6, 8, 7, 23, 4, 16, 14]);
			if (empty($data[0])) {
				$line = $this->joinArrayStrings($line, $data);
			} else {
				if ($line !== null)
					$lines[] = $line;

				$line = $data;
			}
		}

		if ($line !== null)
			$lines[] = $line;

		return $lines;
	}

	public function lineColumns($line, array $columns)
	{
		$data = [];

		$shift = 0;
		foreach ($columns as $width) {
			$data[] = trim(mb_substr($line, $shift, $width, 'utf8'));
			$shift += $width;
		}

		return $data;
	}

	public function joinArrayStrings($array, $joinArray)
	{
		foreach ($array as $key => &$value) {
			$value .= $joinArray[$key];
		}

		return $array;
	}

	public function parseRow($row)
	{
		$fields = [];

		preg_match('/^(\d{2})([А-Я]{3})(\d{2})$/u', $row[1], $match);
		$day = $match[1];
		$month = array_search(mb_strtolower($match[2], 'utf8'), $this->months) + 1;
		$year = '20'.$match[3];
		$fields['processingDate'] = sprintf('%04d-%02d-%02d', $year, $month, $day);

		preg_match('/^(\d{2})([А-Я]{3})$/u', $row[0], $match);
		$day = $match[1];
		$month = array_search(mb_strtolower($match[2], 'utf8'), $this->months) + 1;
		$fields['operationDate'] = sprintf('%04d-%02d-%02d', $year, $month, $day);

		$fields['operation'] = $row[2];
		$fields['name'] = preg_replace('/[ ]+/', ' ', $row[3]);

		if (substr($row[6], -2) === 'CR')
			$fields['sum'] = $row[5];
		else
			$fields['sum'] = -$row[5];

		return $fields;
	}

	public function parse()
	{
		$rows = array_map([$this, 'parseRow'], $this->getRows());

		return $rows;
	}
}
