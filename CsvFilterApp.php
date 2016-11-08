<?php
/**
 * Package morrah77/csvfilter
 * Take filters as array [field1 => value1, field2 => value2],
 * take file, parse its contents into array (by chunks, of course),
 * apply filters tooken before,
 * save results into new file
 *
 */

namespace CsvFilter;

class CsvFilterApp {
    public $config;
    public $request;
    public $response;
    public $layout;
    public $headers;
    public $i18n_dir;
    public $language = 'en';
    public $uploadDir;
    public $token;

  public $fields;
  public $sourceFile;
  public $sourceData;

  public $data;
  public $targetFile;

  function __construct($config) {
    $this->config = $config;
    $this->layout = $config['app']['layout'];
    foreach ($config[__CLASS__] as $key => $value) {
        $this->{$key} = $value;
    }
    $this->i18n_dir || $this->i18n_dir = __DIR__ . DIRECTORY_SEPARATOR . 'i18n';
    $this->language = $this->config['app']['language'];
    $this->msgs = include($this->i18n_dir . DIRECTORY_SEPARATOR . $this->language . '.php');
  }

  public function process(){
    session_start();
    $this->handleRequest();
    $this->step = $this->getRequestVariable('step');
    debug($this->step, 'step');
    $this->handleCsrfToken();
    debug($_SESSION['token'], '$_SESSION[\'token\']');
    switch($this->step){
        case '1':
        default:
            $this->render('main.php', [
                'token' => $this->token,
                'step' => 2,
                'msgs' => $this->msgs,
                'action' => '/',
                'tag' => '',
                'sourceFile' => $this->getRequestVariable('sourceFile', ''),
                'targetFile' => $this->getRequestVariable('targetFile', ''),
                'fields' => [],
                'asideLeft' => var_export($_SERVER, true) . '<br/>' . var_export($_REQUEST, true) . '<br/>' . var_export($_FILES, true)
            ]);
            break;
        case '2':
            if($_FILES){
                $responseValues = $this->handleFile();
                $this->render('main.php', array_merge([
                    'token' => $this->token,
                    'step' => 3,
                    'msgs' => $this->msgs,
                    'action' => '/',
                    'tag' => '',
                    'sourceFile' => $this->getRequestVariable('sourceFile', ''),
                    'targetFile' => $this->getRequestVariable('targetFile', ''),
                    'fields' => [],
                    'asideLeft' => var_export($_SERVER, true) . '<br/>' . var_export($_REQUEST, true) . '<br/>' . var_export($_FILES, true)
                ], $responseValues));
            }
            break;
        case '3':
            $this->processFile();
            break;
        case '4':
            $this->processFile();
            break;
    }
    // if(!$this->isAjax()){
    //   $this->render('main.php');
    // }
    //
    // $this->processFile();
    // return true;
  }

  public function handleRequest()
  {
    $this->request['headers'] = getallheaders();
    try{
        $this->request['vars'] = filter_var_array($_REQUEST, FILTER_SANITIZE_ENCODED);
    }
    catch(\Exception $e){
        $this->request['vars'] = array_map(function($item){
            return htmlspecialchars(addslashes($item));
        }, $_REQUEST);
    }
  }

  /**
   * Handles SCRF token using standard session tool
   *
   * Set the CSRF token to saved from session or generate new token and reinitialize app
   *
   * @param type var Description
   * @return return type
   */
  public function handleCsrfToken()
  {
      debug(__METHOD__);
      debug($this->getRequestVariable('token'), 'REQUEST TOKEN');
      debug($_SESSION['token'], 'session token');
      (($this->token = $this->getRequestVariable('token')) && !empty($_SESSION['token']) && $_SESSION['token'] && $_SESSION['token'] == $this->token && $_SESSION['token'] = $this->token)
      || (($this->token =  $_SESSION['token'] = $this->generateCsrftoken()) && $this->step = 1 && $this->request['vars'] = []);
  }

  /**
   * Generate CSRF token
   *
   * @param type var Description
   * @return return type
   */
  public function generateCsrftoken()
  {
      $res = md5($this->config['app']['salt'] . (string)time());
      debug($res, 'New token generated');
      return $res;
  }

  /**
   * Get request variable
   *
   * @param mixed $varName
   * @param mixed $defaultValue
   * @return mixed
   */
  public function getRequestVariable($varName, $defaultValue = false)
  {
      return isset($this->request['vars'][$varName]) ? $this->request['vars'][$varName] : $defaultValue;
  }

    /**
     * Check if requested via AJAX
     *
     * @param type var Description
     * @return return type
     */
  public function isAjax()
  {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
  }

  /**
   * Handle received file
   *
   * Save file, read its fields, respond saved file name, read fields, computed target file name
   *
   * @param type var Description
   * @return return type
   */
  public function handleFile($ajax = false)
  {
      $responseValues = [];
      (((int)$_FILES['sourceFile']['error'] == 0)//UPLOAD_ERROR_OK
      && ($responseValues['sourceFile'] = $this->createFileName($_FILES['sourceFile']['name']))
      //&& (debug($responseValues['sourceFile'], '$responseValues[\'sourceFile\']'))
      && ($this->sourceFile =  $this->uploadDir  . DIRECTORY_SEPARATOR .  $responseValues['sourceFile'])
      && (debug($this->sourceFile, '$this->sourceFile'))
      && (copy($_FILES['sourceFile']['tmp_name'],$this->sourceFile))
      && (debug('','Copied!'))
      && ($fileProcessor = new \CsvFilter\Components\FileProcessor(
        $this->sourceFile,
        $responseValues['targetFile'] = $this->targetFile = $this->uploadDir  . DIRECTORY_SEPARATOR . 'copy_' . $responseValues['sourceFile'],
        $this->config['fileProcessor']
      ))
      && ($responseValues['fields'] = $fileProcessor->getFields()));
      debug($responseValues, 'responseValues');
      ($ajax && $this->respondAjax($responseValues));
      return $responseValues;
  }

  public function processFile($ajax = false)
  {
      $this->respondAjax($values);
  }

  /**
   * Create unique file name
   *
   * @param type string $fileName
   * @return string
   */
  public function createFileName($fileName='')
  {
      return time() . $fileName;
  }

  public function render($viewName, $vars) {
      debug(__CLASS__ . ': ' . __METHOD__);
    $content = '';
    ob_start();
    //debug($vars, 'vars');
    foreach($vars as $k => $v){
        ${$k} = $v;
        //debug(${$k}, $k);
    };
    include(realpath((!empty($this->viewsDir) ?( __DIR__ . DIRECTORY_SEPARATOR . $this->viewsDir) : $this->config['app']['viewsDir']) . DIRECTORY_SEPARATOR . $viewName));
    $content = ob_get_contents();
    //debug($content, 'content 1');
    ob_end_clean();
    debug($content, 'content 2');
    ob_start();
    include(realpath($this->config['app']['viewsDir'] . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . $this->layout));
    $this->response['body'][] = ob_get_contents();
    ob_end_clean();
    //debug($this->response['body'], 'body');
    $this->respondHtml();
    debug(__CLASS__ . ': ' . __METHOD__ . ': ' . ' finished');
  }

  public function respondHtml(){
      debug(__CLASS__ . ': ' . __METHOD__ );
    http_response_code(200);
    header('Content-Type: text/html');
    echo(implode('', $this->response['body']));
    debug(__CLASS__ . ': ' . __METHOD__ . ': ' . ' finished');
    exit();
  }

  public function respondAjax($values='')
  {
      http_response_code(200);
      header('Content-Type: application/json');
      echo(json_encode($values));
      exit();
  }

  public function __call($methodName, $args) {
      debug($methodName, var_export($args, true));
  }
}
