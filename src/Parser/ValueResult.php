<?php
/* @description     Transformation Style Sheets - Revolutionising PHP templating    *
 * @author          Tom Butler tom@r.je                                             *
 * @copyright       2017 Tom Butler <tom@r.je> | https://r.je/                      *
 * @license         http://www.opensource.org/licenses/bsd-license.php  BSD License *
 * @version         1.2                                                             */
namespace Transphporm\Parser;
class ValueResult {
	private $result = [];
	/*
		The next operation to perform. Will be one of the following:
			ARG - A new value e.g,  "a","b"  becomes ["a", "b"]
			CONCAT - Concat onto the current arg e.g "a" + "b" becomes ["ab"]
			NOT - Boolean operation "a" != "b" becomes [true]
			EQUALS - Boolean operation "a" = "b" becomes [false]
	*/
	private $mode = Tokenizer::ARG;

	//Processes $newValue using $mode. Either concats to the current argument, adds a new argument
	//Or usess the two arguments for a boolean comparison
	public function processValue($newValue) {
		$funcs = [
			Tokenizer::ARG => 'arg',
			Tokenizer::CONCAT => 'concat',
			Tokenizer::EQUALS => 'equals',
			Tokenizer::NOT => 'not',
			Tokenizer::SUBTRACT => 'sub',
			Tokenizer::MULTIPLY => 'mult',
			Tokenizer::DIVIDE => 'div',
			Tokenizer::GREATER_THAN => 'greater',
			Tokenizer::LOWER_THAN => 'lower',
			Tokenizer::IN => 'in',
			Tokenizer::CONTAINS => 'contains',
		];

		if ($funcs[$this->mode] === 'concat' && is_numeric($newValue)
			&& is_numeric($this->result[count($this->result)-1]))
			$this->add($newValue);
		else
			$this->{$funcs[$this->mode]}($newValue);
	}

	public function in($value) {
		if (!is_array($value)) throw new \Exception(' `in` can only be used with arrays');
		$this->result[count($this->result)-1] = in_array($this->result[count($this->result)-1], $value);
	}

	public function arg($value) {
		$this->result[] = $value;
	}

	public function concat($value) {
		$this->result[count($this->result)-1] .= $value;
	}

	public function not($value) {
		$this->result[count($this->result)-1] = $this->result[count($this->result)-1] != $value;
	}

	public function equals($value) {
		$this->result[count($this->result)-1] = $this->result[count($this->result)-1] == $value;
	}

	public function greater($value) {
		$this->result[count($this->result)-1] = $this->result[count($this->result)-1] > $value;
	}

	public function lower($value) {
		$this->result[count($this->result)-1] = $this->result[count($this->result)-1] < $value;
	}

	public function add($value) {
		$this->result[count($this->result)-1] += $value;
	}

	public function sub($value) {
		$this->result[count($this->result)-1] -= $value;
	}

	public function mult($value) {
		$this->result[count($this->result)-1] *= $value;
	}

	public function div($value) {
		$this->result[count($this->result)-1] /= $value;
	}

	public function contains($value) {
		$r = count($this->result) - 1;
		$this->result[$r] = strpos($this->result[$r], $value) !== false;
	}

	public function setMode($mode) {
		$this->mode = $mode;
	}

	public function getMode() {
		return $this->mode;
	}

	public function getResult() {
		return $this->result;
	}

	public function pop() {
		return array_pop($this->result);
	}

	private function write($index, $value, $allowNull = false) {
		if ($value !== null || $allowNull === true) {
			$this->result[$index] = $value;
		}
	}

	//Postprocessing - replace values with null where allowed, or override a value at position
	public function postProcess(ValueData $data, $val, $overrideVal, $allowNull) {
		foreach ($this->getResult() as $i => $value) {
			if (is_scalar($value)) {
				$val = ($overrideVal == $val) ? $data->read($value) : $overrideVal;
				$this->write($i, $val, $allowNull);

			}
		}
	}

	public function clear() {
		$this->result = [];
	}
}
