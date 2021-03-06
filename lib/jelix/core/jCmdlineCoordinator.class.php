<?php
/**
* @package      jelix
* @subpackage   core
* @author       Christophe Thiriot
* @contributor  Laurent Jouanneau
* @copyright    2008 Christophe Thiriot, 2011-2012 Laurent Jouanneau
* @link         http://www.jelix.org
* @licence      GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

/**
 * The command line version of jCoordinator 
 *
 * This allows us to handle exit code of commands properly
 * @package  jelix
 * @subpackage core
 */
class jCmdlineCoordinator extends jCoordinator {

    function __construct ($configFile, $enableErrorHandler=true) {
        if (PHP_SAPI != 'cli' && strpos(PHP_SAPI, 'cgi') === false) {
            throw new Exception("Error: you're not allowed to execute this script outside a command line shell.");
        }

        if (PHP_SAPI != 'cli') {
            // only php-cgi used from the command line can be used, not the one called by apache
            if (isset($_SERVER['HTTP_HOST']) || isset($_SERVER['REDIRECT_URL'])  || isset($_SERVER['SERVER_PORT'])) {
                throw new Exception("Error: you're not allowed to execute this script with php-cgi outside a command line shell.");
            }
            header('Content-type: text/plain');
            if (!isset($_SERVER['argv'])) {
                $_SERVER['argv'] = array_keys($_GET);
                $_SERVER['argc'] = count($_GET);
            }
            if (!isset($_SERVER['SCRIPT_NAME'])) {
                $_SERVER['SCRIPT_NAME'] = $_SERVER['argv'][0];
            }
            if (!isset($_SERVER['DOCUMENT_ROOT'])) {
                $_SERVER['DOCUMENT_ROOT'] = '';
            }
        }

        jApp::setEnv('cli');
        parent::__construct($configFile, $enableErrorHandler);
    }

    /**
    * main method : launch the execution of the action.
    *
    * This method should be called in a Command line entry point.
    * @param  jRequestCmdline  $request the command line request object
    */
    public function process($request){
        parent::process($request);
        exit($this->response->getExitCode());
    }

    public $allErrorMessages = array();

    /**
     * Handle an error event. Called by error handler and exception handler.
     * @param string  $type    error type : 'error', 'warning', 'notice'
     * @param integer $code    error code
     * @param string  $message error message
     * @param string  $file    the file name where the error appear
     * @param integer $line    the line number where the error appear
     * @param array   $trace   the stack trace
     * @since 1.1
     */
    public function handleError($type, $code, $message, $file, $line, $trace){

        $errorLog = new jLogErrorMessage($type, $code, $message, $file, $line, $trace);

        if ($this->request) {
            // we have config, so we can process "normally"
            $errorLog->setFormat(jApp::config()->error_handling['messageLogFormat']);
            jLog::log($errorLog, $type);
            $this->allErrorMessages[] = $errorLog;

            // if non fatal error, it is finished
            if ($type != 'error')
                return;

            $this->errorMessage = $errorLog;

            while (ob_get_level() && @ob_end_clean());

            if($this->response) {
                $resp = $this->response;
            }
            else {
                require_once(JELIX_LIB_CORE_PATH.'response/jResponseCmdline.class.php');
                $resp = $this->response = new jResponseCmdline();
            }
            $resp->outputErrors();
            jSession::end();
        }
        // for non fatal error appeared during init, let's just store it for loggers later
        elseif ($type != 'error') {
            $this->allErrorMessages[] = $errorLog;
            $this->initErrorMessages[] = $errorLog;
            return;
        }
        else {
            // fatal error appeared during init, let's display a single message
            while (ob_get_level() && @ob_end_clean());
            // log into file
            @error_log($errorLog->getFormatedMessage(),3, jApp::logPath('errors.log'));
            // output text response
            echo 'Error during initialization: '.$message.' ('.$file.' '.$line.")\n";
        }
        exit(1);
    }
}
