<?php 

/**
 *  CArrayToObject
 *  @desc : Converting an array to instance of CArrayToObject object
 *  @author : Sebastian 'alien' Potasiak
 *  @version : 1.0.3
 *  @update : 2009-04-12
 *  @copyrights : 2009 Sebastian Potasiak
 */
class CArrayToObject {

	private $_variablesCount = 0; // Keeping variables count 
  
	/**
	 *  __construct
	 *  @desc: 'Transporting' variables
	 *  @param: arr [array] - array to 'transport'
	 */
	public function __construct($arr) {
		$this->_variablesCount = count($arr);
		if ($this->_variablesCount > 0) {
			foreach ($arr as $key => $value) {
				if ($key != '_variablesCount') {
					$this->$key = $value; 
					if (is_array($this->$key)) {
						$this->$key = new CArrayToObject($this->$key); 
					}
				}
			}
		}
	}
	
	/**
	 *  __set
	 *  @desc: Adding a number to _variablesCount
	 *  @param: {STANDARD PHP5}
	 */
	public function __set($key, $value) {
		if ($key != '_variablesCount') {
			$this->$key = $value; 
			$this->_variablesCount++; 
		} 
	}
	
	/**
	 *  delete
	 *  @desc: Deleting variable
	 *  @param: key [string|int] - key of variable
	 *  @access: public
	 */
	public function delete($key) { 
		if (isset($this->$key)) { 
			unset($this->$key); 
			$this->_variablesCount--; 
		} 
	}
	
	/**
	 *  varCount
	 *  @desc: Return count of variables
	 *  @access: public
	 *  @return: int
	 */
	public function varCount() {
		return $this->_variablesCount; 
	} 
}
?>