<?php
/**
 * Package morrah77/csvfilter
 * FileProcessor class purposed to process CSV file
 * main operations are:
 * open CSV file
 * close file
 * read  a chunk of data from file
 * get fields of CSV file
 * parse read data
 * flush parsed data into cpecified file
 * get errors arized during the processing
 * use like below:
 * $fileProcessor = new CsvFilter\Components\FileProcessor('path/to/source/file.csv', 'path/to/target/file.csv', ['dataChunkLength => 2048', 'fieldSeparator' => ';', 'rowSeparator' => '\n']);
 * $filter = new CsvFilter\Components\Filter([
 *                'name' => [
 *                  'string',
 *                  '!=',
 *                  'Vasya'
 *                ],
 *                'date' => [
 *                  'datetime',
 *                  '>',
 *                  '2016-11-02'
 *                ],
 *                'status' => [
 *                  'integer',
 *                  '=',
 *                  '1'
 *                ]
 *              ]);
 * $fileProcessor->openFile();
 * while(!$fileProcessor->isProcessFinished()){
 *   $fileProcessor->read()->parse();
 *   $fileProcessor->applyDataProcess([$filter, 'applyFilter']);
 *   $fileProcessor->flush('path/to/out/filr.csv');
 * }
 * $fileProcessor->closeFile;
 * if($errors = $fileProcessor->getErrore=s()){
 *   ...errors processing here...
 * }
 *
 * or simple way (assumed that config.php returns array containing all fileProcessor and Filter settings shown above):
 * $config= include('config.php');
 * $fileProcessor = new CsvFilter\Components\FileProcessor('path/to/source/file.csv', 'path/to/target/file.csv', $config['fileProcessor']);
 * $filter = new CsvFilter\Components\Filter($config['filter']);
 * $rule = [$filter, 'applyRules'];
 * $fileProcessor->process($rule);
 */
 namespace CsvFilter\Components;

 class FileProcessor {
   public $config = [
        'filterClassName' => 'CsvFilter\Components\Filter',
        'filterMethodName' => 'applyRules',
        'dataChunkLength => 65536',
        'fieldSeparator' => ';',
        'rowSeparator' => '\n'
   ];
   public $sourceFileName;
   public $targetFilename;
   private $fileHandler;
   private $dataChunk = '';
   private $processFinished = false;
   private $processStarted = false;
   private $rule;
   public $errors;
   public $fields;
   public $data;

   /**
    * Initialize FileProcessor
    * @param string $fileName
    * @param array $config
    * @return CsvFilter\Components\FileProcessor
    */
   public function __construct($sourceFileName = '', $targetFilename = '', $sonfig = []){
     $this->init();
     return $this;
   }

   public function process() {
     $this->init();
     $this->openFile();
     while(!$this->isProcessFinished()) {
       $this->read()->parse();
       $this->applyDataProcess($this->rule, $this->data);
       $this->flush();
     }
     $this->closeFile();
   }

   public function init($sourceFileName = '', $targetFilename = '', $config = []){

     $this->config = array_merge($this->config, (array)$config);
     $this->clearErrors();
     $this->processStarted = false;
     $this->processFinished = false;
     $this->fields = false;
     $this->fileHandler = false;
     $this->dataChunk = '';
     $this->data = false;
     $filterObject = new $this->config['filterClassName']();
     $this->rule = [];
   }

   /**
    * Open file handler for reading
    * @param string $fileName
    * @return CsvFilter\Components\FileProcessor
    */
   public function openFile($fileName = false) {
     $fileName = (string) $fileName || $this->$sourceFileName;
     try{
       $this->fileHandler = fopen($filename, 'r') || $this->setError('Couldn\'t open file ' . $fileName);
     }
     catch(\Exception $e){
       $this->setError($e->getMessage());
     }
     return $this;
   }

   /**
    * Close file handler
    * @return CsvFilter\Components\FileProcessor
    */
   public function closeFile()
   {
     ($this->fileHandler && fclose($this->fileHandler));
     return $this;
   }

    /**
     * Read chunk of data from opened file
     * on first read invokes _getFields() method
     * @return CsvFilter\Components\FileProcessor
     */
    public function read() {
      $this->dataChunk .= fread($this->fileHandler, $this->dataChunkLength);
      (!$this->processStarted && ($this->processStarted = true) && $this->_getFields());
      ((strlen($this->dataChunk) < $this->config['dataChunkLength']) && $this->processFinished = true);
      return $this;
    }

    /**
     * Return fields
     *
     * on first read build $fields property assuming fieldnames placwd at first row of CSV file
     *
     * @return CsvFilter\Components\FileProcessor
     */
   private function _getFields() {
       debug(__METHOD__);
     (empty($this->fields)
     && (!$this->processStarted())
     && ($this->fields = []))
   || ($this->fields = explode($this->config['fieldSeparator'], substr($this->dataChunk, 0, stripos($this->dataChunk, $this->config['rowSeparator']))));
   debug($this->fields, '$this->fields');
     return $this->fields;
   }

   /**
    * Return fields
    *
    * Use to get just fields, not to read file contetns
    */
   public function getFields()
   {
       (empty($this->fields)
       && ($this->openFile()
       ->read()
       ->closeFile()));
       return $this->fields;
   }

   /**
    * Parse stored read file contents according to config parameters
    *
    * Truncate stored file contents to last unparsed chunk
    *
    * @return CsvFilter\Components\FileProcessor
    */
   public function parse(){
     $lastRowSeparatorPos = strripos($this->dataChunk, $this->config['rowSeparator']);
     $dataTail = substr($this->dataChunk, $lastRowSeparatorPos + strlen($this->config['rowSeparator']) + 1);
     $this->dataChunk = substr($this->dataChunk, 0, $lastRowSeparatorPos);
     $parsed = array_map(function($element) {
       return array_combine(array_keys($this->fields), explode($this->config['fieldSeparator'], $element));
   }, explode($this->config['rowSeparator'], $this->dataChunk));
     $this->data = array_merge($this->data, $parsed);
     array_walk($this->data, function(& $item, $key) {
       if(!$item)
        unset($item);
     });
     $this->dataChunk = $dataTail;
     return $this;
   }

    /**
     * Apply specified Callable to $data
     *
     * @param Callable $rule
     */
    public function applyDataProcess($rule)
    {
      ((is_callable($rule) && call_user_func_array($rule, [$this->data]))
      || ($this->setError(error_get_last())));
    }

   /**
    * Write stored data into file and clear data
    *
    * A little muscle demonstration with chained statements
    * It's diffucultly readable but doesn't take much place at screen
    *
    * @param string $outFileName
    * @return CsvFilter\Components\FileProcessor
    */
   public function flush($outFileName)
   {
     try {
       (($outHandler = fopen($outFileName, 'a'))
       && ($localConfig = $this->config)
       && (fwrite($outHandler, array_reduce($this->data, function($carry, $item) use ($localConfig) {
         $carry .= implode($localConfig['fieldSeparator'], $item) . $localConfig['rowSeparator'];
         return $carry;
       }, '')))
       && (fclose($outHandler))
       && ($this->data = []))
       || ($this->setError(error_get_last()->getMessage()));
     }
     catch(\Exception $e){
       $this->setError($e->getMessage());
     }
     return $this;
   }

   /**
    * Set the processFinished flag
    */
    public function finishProcess()
    {
      $this->processFinished = true;
    }

    /**
     * Get the processFinished flag value
     */
    public function isProcessFinished()
    {
     return $this->processFinished;
    }

    /**
     * Set the processStarted flag
     */
    public function startProcess()
    {
      $this->processStarted = true;
    }

    /**
     * Get the processStarted flag value
     */
    public function isProcessStarted()
    {
      return $this->processStarted;
    }

   /**
    * Add passed error to $errors array
    *
    * @param string $error
    * @return CsvFilter\Components\FileProcessor
    */
   public function setError($error = '')
   {
     $error && $this->errors[] = $error;
     return $this;
   }

   /**
    * Clean $errors array
    * @return CsvFilter\Components\FileProcessor
    */
   public function clearErrors()
   {
     $this->errors = [];
     return $this;
   }
 }
