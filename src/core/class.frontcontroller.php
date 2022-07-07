<?php

/**
 * Frontcontroller class
 *
 */

namespace leantime\core {

    use Exception;
    use leantime\domain\controllers;
    use leantime\domain\repositories;


    class frontcontroller
    {

        /**
         * @access private
         * @var    string - rootpath (is set in index.php)
         */
        private $rootPath;

        /**
         * @access private
         * @var    object - one instance of this object
         */
        private static $instance = null;

        /**
         * @access private
         * @var    string set the first action to fire
         */
        private $firstAction = '';

        /**
         * @access private
         * @var    string - last action that was fired
         */
        private $lastAction;

        /**
         * @access public
         * @var    string - fully parsed action
         */
        public $fullAction;

        /**
         * __construct - Set the rootpath of the server
         *
         * @param $rootPath
         */
        public function __construct($rootPath)
        {
            $this->rootPath = $rootPath;
        }

        /**
         * getInstance - just one instance of the object is allowed (it makes no sense to have more)
         *
         * @access public static
         * @param  $rootPath
         * @return object (instance)
         */
        public static function getInstance($rootPath = null)
        {

            if (is_object(self::$instance) === false) {

                if (is_null($rootPath)) {

                    throw new Exception('No root path');

                }

                self::$instance = new frontcontroller($rootPath);
            }

            return self::$instance;
        }

        /**
         * run - executes the action depending on Request or firstAction
         *
         * @access public
         * @return
         */
        public function run($differentFirstAction = '')
        {

            //Set action-name
            if(isset($_REQUEST['act'])) {

                $this->fullAction = htmlspecialchars($_REQUEST['act']);

            }else{

                if($differentFirstAction == '') {

                    $this->fullAction = $this->firstAction;

                }else{

                    $this->fullAction = $differentFirstAction;

                }

            }

            if($this->fullAction != '') {
                //execute action
                try {

                    $this->executeAction($this->fullAction);

                } catch (Exception $e) {

                    echo $e->getMessage();

                }
            } else {

                header("HTTP/1.0 404 Not Found");

                $this->run("general.error404");

            }
        }

        /**
         * executeAction - includes the class in includes/modules by the Request
         *
         * @access private
         * @param  $completeName
         * @return string|object
         */
        private function executeAction($completeName)
        {

            //actionname.filename

            //actionName is foldername
            $actionName = self::getActionName($completeName);

            //moduleName is filename
            $moduleName = self::getModuleName($completeName);

            //Folder doesn't exist.
            if(is_dir('../src/domain/' . $moduleName) === false || is_file('../src/domain/' . $moduleName . '/controllers/class.' . $actionName . '.php') === false) {

                header("HTTP/1.0 404 Not Found");
                exit();

            }

            //TODO: refactor to be psr 4 compliant
            include_once '../src/domain/' . $moduleName . '/controllers/class.' . $actionName . '.php';

            //Initialize Action
            $classname = "leantime\\domain\\controllers\\".$actionName ;
            $action = new $classname();

            if(is_object($action) === false) {

                header("HTTP/1.0 501 Not Implemented");
                exit();

            }else{// Look at last else

                try {

                    //Everything ok? run action
                    $method= $this->getRequestMethod();

                    if(method_exists($action, $method)) {

                        $params = $this->getRequestParams($method);
                        $action->$method($params);

                    }else {
                        //Use run for all request types.
                        $action->run();
                    }

                }catch (Exception $e) {

                    echo $e->getMessage();

                }

            }

            $this->lastAction = $completeName;

        }

        private function getRequestMethod()
        {

            if(isset($_SERVER['REQUEST_METHOD'])) {
                return strtolower($_SERVER['REQUEST_METHOD']);
            }

            return false;

        }

        private function getRequestParams($method)
        {

            switch ($method) {
            case 'patch':
                parse_str(file_get_contents("php://input"), $patch_vars);
                return $patch_vars;
                    break;
            case 'post':
                return $_POST;
                    break;
            case 'get':
                return $_GET;
                    break;
            case 'delete':
                return $_GET;
                    break;
            default:
                throw(new Exception("Unexpected HTTP Method: ".$method));
                    break;
            }

        }

        /**
         * includeAction - possible to include action from everywhere
         *
         * @access public
         * @param  $completeName
         * @return object
         */
        public function includeAction($completeName)
        {
            $this->executeAction($completeName);
        }

        /**
         * includeAction - possible to include action from everywhere
         *
         * @access public
         * @param  $completeName
         * @return object
         */
        public function getRenderedOutput($completeName)
        {

            ob_start();
            $this->executeAction($completeName);
            $headerOutput = ob_get_clean();
            return $headerOutput;

        }

        /**
         * getActionName - split string to get actionName
         *
         * @access public
         * @param  $completeName
         * @return string
         */
        public static function getActionName($completeName)
        {

            return substr($completeName, strrpos($completeName, ".") + 1);

        }

        /**
         * getModuleName - split string to get modulename
         *
         * @access public
         * @param  $completeName
         * @return string
         */
        public static function getModuleName($completeName)
        {

            return substr($completeName, 0, strrpos($completeName, "."));

        }


        /**
         * getCurrentRoute - gets the current main action
         *
         * @access public
         * @param  $completeName
         * @return string
         */
        public static function getCurrentRoute() {

            if(isset($_REQUEST['act'])) {
                return filter_var($_REQUEST['act'], FILTER_SANITIZE_STRING);
            }

            return '';

        }

    }
}
