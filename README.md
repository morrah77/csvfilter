Simple SCV filter parser with ability to filter passed files.
/**
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
Simple web app CsvFilterApp.php included (not completed)
