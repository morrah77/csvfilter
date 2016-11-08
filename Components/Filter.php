<?php

namespace CsvFilter\Components;

/**
 * Apply filter rues to passed data array
 *
 * Use like $fiter = new CsvFilter\Components\Filter( [
 *             'name' => ['string', '!=', 'Vasya'],
 *             'date' => ['datetime', '<', '2016-11-04'],
 *             'status' => ['integer', '=', '1']
 *             ], ['name', 'date', 'status']);
 * $data = [
 *             0 => ['Vasya', '2016-11-04', '1'],
 *             1 => ['Masha', '2016-11-04 00:01:00', '0'],
 *             2 => ['Bobby', '2016-11-05', '1']
 *          ];
 * $filter->applyRules($data);
 * //$data processing follows...
 *
 * @var array  $fields like ['name', 'date', 'status']
 * @var array  $filterRules like [
 *             'name' => ['string', '!=', 'Vasya'],
 *             'date' => ['datetime', '<', '2016-11-04'],
 *             'status' => ['integer', '=', '1']
 *             ]
 * @var array  $data like [
 *             ['Vasya', '2016-01-01 00:00:00', '1'],
 *             ['Masha', '2016-11-01', '0']
 *             ]
 * @var array  $filters like [
 *             0 => ['string', '!=', 'Vasya'],
 *             1 => ['datetime', '<', '2016-11-04'],
 *             2 => ['integer', '=', '1']
 *             ]
 */
class Filter
{
    public $fields;
    public $filterRules;
    public $filters;

    /**
     * Form filter rules from passed arrays
     *
     * When passed $fields treat $filterRules as an associative array and reform it into $filters
     *
     * @param array $filterRules
     * @param array $fields
     */
    public function __construct($filterRules = false, $fields = false)
    {
        ($filterRules && ($this->filterRules = $filterRules));
        if(!$fields){
            $this->filters = &$this->filterRules;
            return;
        }
        ($fields && ($this->fields = (array)$fields));
        $rulesKeys = array_keys($this->filterRules);
        foreach ($this->fields as $key => $value) {
            $this->filters[$key] = $this->filterRules[$rulesKeys[$value]];
        }
    }

    /**
     * Set filters directly
     *
     * Undocumented function long description
     *
     * @param type var Description
     * @return return type
     */
    public function setFilters($filters = false)
    {
        ($filters && ($this->filters = $filters));
    }

  /**
   * Apply rules to data.
   *
   * Change passed $data staff according to rules
   * Assuming big array volume avoid both as eval() as foreach()
   *
   * @param array & $data array of data (by ref)
   */
  public function applyRules(&$data = [])
  {
      $filters = $this->filters;
      return array_walk($data, function (&$item, $key) use (&$data, $filters) {
          $res = true;
          for($i=0; $i<count($filters); $i++){
              $operator = $filters[$i][1];
              $operands = [$data[$key][$i]];
              for($j=2;$j<count($filters[$i]);$j++){
                  $operands[] = $filters[$i][$j];
              }
              $this->convertType($operands, $filters[$i][0]);
              switch($operator){
                  case '=':
                      $res = ($res && ($operands[0] == $operands[1]));
                      break;
                  case '!=':
                      $res = ($data[$filters[$i]] != $operand);
                      break;
                  case '>':
                      $res = ($data[$filters[$i]] > $operand);
                      break;
                  case '<':
                      $res = ($data[$filters[$i]] < $operand);
                      break;
                  case '>=':
                      $res = ($data[$filters[$i]] >= $operand);
                      break;
                  case '<=':
                      $res = ($data[$filters[$i]] <= $operand);
                      break;
                  case 'between':
                      $operands[] = $filters[$i][3];
                      $res = (($data[$filters[$i]] >= $operand) && ($data[$filters[$i]] <= $operand2));
                      break;
                  case 'in':

                      break;
              }
              if(!$res){
                  break;
              }
          }
          (!$res && unset($data[$key]));
      });
  }

  /**
   * undocumented function summary
   *
   * Undocumented function long description
   *
   * @param type var Description
   * @return return type
   */
  public function convertType(&$values=false, $type='string')
  {
      if(is_array($values)){
          foreach ($values as $key => $value) {
              $this->_convertType($values[$key], $type);
          }
          return;
      }
      $this->_convertType($values, $type);
  }

  /**
   * undocumented function summary
   *
   * Undocumented function long description
   *
   * @param type var Description
   * @return return type
   */
  private function _convertType(&$value='', $type='string')
  {
      switch($type){
        case 'string':
        default:
          $this->toString($value);
          break;
        case 'datetime':
          $this->toDateTime($value);
          break;
        case 'int':
        case 'integer':
          $this->toInteger($value);
          break;
      }
  }

  /**
   * Convert value into strings
   *
   * @param array $values (by ref)
   */
  public function toString(&$value = false)
  {
      try{
          $value = (string)$value;
      }
      catch(\Exception $e){
          $value = '';
      }
  }

  /**
   * Convert value into DateTime
   *
   * @param array $values (by ref)
   */
  public function toDateTime(&$value = false)
  {
      try{
          $value = (\DateTime)$value;
      }
      catch(\Exception $e){
          $value = new \DateTime('0');
      }
  }

  /**
   * Convert value into integer
   *
   * @param array $values (by ref)
   */
  public function toInteger(&$value = false)
  {
      try{
          $value = (int)$value;
      }
      catch(\Exception $e){
          $value = 0;
      }
  }
}
