<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Support for external API
 *
 * @package    core_webservice
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

<<<<<<< HEAD
use core_external\util;

defined('MOODLE_INTERNAL') || die;

// Please note that this file and all of the classes and functions listed below will be deprecated from Moodle 4.6.
// This deprecation is delayed to aid plugin developers when maintaining plugins for multiple Moodle versions.
// See MDL-76583 for further information.

// If including this file for unit testing, it _must_ be run in an isolated process to prevent
// any side effect upon other tests.
require_phpunit_isolation();

class_alias(\core_external\external_api::class, 'external_api');
class_alias(\core_external\restricted_context_exception::class, 'restricted_context_exception');
class_alias(\core_external\external_description::class, 'external_description');
class_alias(\core_external\external_value::class, 'external_value');
class_alias(\core_external\external_format_value::class, 'external_format_value');
class_alias(\core_external\external_single_structure::class, 'external_single_structure');
class_alias(\core_external\external_multiple_structure::class, 'external_multiple_structure');
class_alias(\core_external\external_function_parameters::class, 'external_function_parameters');
class_alias(\core_external\util::class, 'external_util');
class_alias(\core_external\external_files::class, 'external_files');
class_alias(\core_external\external_warnings::class, 'external_warnings');
class_alias(\core_external\external_settings::class, 'external_settings');
=======
defined('MOODLE_INTERNAL') || die();

/**
 * Exception indicating user is not allowed to use external function in the current context.
 *
 * @package    core_webservice
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 */
class restricted_context_exception extends moodle_exception {
    /**
     * Constructor
     *
     * @since Moodle 2.0
     */
    function __construct() {
        parent::__construct('restrictedcontextexception', 'error');
    }
}

/**
 * Base class for external api methods.
 *
 * @package    core_webservice
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 */
class external_api {

    /** @var stdClass context where the function calls will be restricted */
    private static $contextrestriction;

    /**
     * Returns detailed function information
     *
     * @param string|object $function name of external function or record from external_function
     * @param int $strictness IGNORE_MISSING means compatible mode, false returned if record not found, debug message if more found;
     *                        MUST_EXIST means throw exception if no record or multiple records found
     * @return stdClass description or false if not found or exception thrown
     * @since Moodle 2.0
     */
    public static function external_function_info($function, $strictness=MUST_EXIST) {
        global $DB, $CFG;

        if (!is_object($function)) {
            if (!$function = $DB->get_record('external_functions', array('name' => $function), '*', $strictness)) {
                return false;
            }
        }

        // First try class autoloading.
        if (!class_exists($function->classname)) {
            // Fallback to explicit include of externallib.php.
            if (empty($function->classpath)) {
                $function->classpath = core_component::get_component_directory($function->component).'/externallib.php';
            } else {
                $function->classpath = $CFG->dirroot.'/'.$function->classpath;
            }
            if (!file_exists($function->classpath)) {
                throw new coding_exception('Cannot find file ' . $function->classpath .
                        ' with external function implementation');
            }
            require_once($function->classpath);
            if (!class_exists($function->classname)) {
                throw new coding_exception('Cannot find external class ' . $function->classname);
            }
        }

        $function->ajax_method = $function->methodname.'_is_allowed_from_ajax';
        $function->parameters_method = $function->methodname.'_parameters';
        $function->returns_method    = $function->methodname.'_returns';
        $function->deprecated_method = $function->methodname.'_is_deprecated';

        // Make sure the implementaion class is ok.
        if (!method_exists($function->classname, $function->methodname)) {
            throw new coding_exception('Missing implementation method ' .
                    $function->classname . '::' . $function->methodname);
        }
        if (!method_exists($function->classname, $function->parameters_method)) {
            throw new coding_exception('Missing parameters description method ' .
                    $function->classname . '::' . $function->parameters_method);
        }
        if (!method_exists($function->classname, $function->returns_method)) {
            throw new coding_exception('Missing returned values description method ' .
                    $function->classname . '::' . $function->returns_method);
        }
        if (method_exists($function->classname, $function->deprecated_method)) {
            if (call_user_func(array($function->classname, $function->deprecated_method)) === true) {
                $function->deprecated = true;
            }
        }
        $function->allowed_from_ajax = false;

        // Fetch the parameters description.
        $function->parameters_desc = call_user_func(array($function->classname, $function->parameters_method));
        if (!($function->parameters_desc instanceof external_function_parameters)) {
            throw new coding_exception($function->classname . '::' . $function->parameters_method .
                    ' did not return a valid external_function_parameters object.');
        }

        // Fetch the return values description.
        $function->returns_desc = call_user_func(array($function->classname, $function->returns_method));
        // Null means void result or result is ignored.
        if (!is_null($function->returns_desc) and !($function->returns_desc instanceof external_description)) {
            throw new coding_exception($function->classname . '::' . $function->returns_method .
                    ' did not return a valid external_description object');
        }

        // Now get the function description.

        // TODO MDL-31115 use localised lang pack descriptions, it would be nice to have
        // easy to understand descriptions in admin UI,
        // on the other hand this is still a bit in a flux and we need to find some new naming
        // conventions for these descriptions in lang packs.
        $function->description = null;
        $servicesfile = core_component::get_component_directory($function->component).'/db/services.php';
        if (file_exists($servicesfile)) {
            $functions = null;
            include($servicesfile);
            if (isset($functions[$function->name]['description'])) {
                $function->description = $functions[$function->name]['description'];
            }
            if (isset($functions[$function->name]['testclientpath'])) {
                $function->testclientpath = $functions[$function->name]['testclientpath'];
            }
            if (isset($functions[$function->name]['type'])) {
                $function->type = $functions[$function->name]['type'];
            }
            if (isset($functions[$function->name]['ajax'])) {
                $function->allowed_from_ajax = $functions[$function->name]['ajax'];
            } else if (method_exists($function->classname, $function->ajax_method)) {
                if (call_user_func(array($function->classname, $function->ajax_method)) === true) {
                    debugging('External function ' . $function->ajax_method . '() function is deprecated.' .
                              'Set ajax=>true in db/service.php instead.', DEBUG_DEVELOPER);
                    $function->allowed_from_ajax = true;
                }
            }
            if (isset($functions[$function->name]['loginrequired'])) {
                $function->loginrequired = $functions[$function->name]['loginrequired'];
            } else {
                $function->loginrequired = true;
            }
        }

        return $function;
    }

    /**
     * Call an external function validating all params/returns correctly.
     *
     * Note that an external function may modify the state of the current page, so this wrapper
     * saves and restores tha PAGE and COURSE global variables before/after calling the external function.
     *
     * @param string $function A webservice function name.
     * @param array $args Params array (named params)
     * @param boolean $ajaxonly If true, an extra check will be peformed to see if ajax is required.
     * @return array containing keys for error (bool), exception and data.
     */
    public static function call_external_function($function, $args, $ajaxonly=false) {
        global $PAGE, $COURSE, $CFG, $SITE;

        require_once($CFG->libdir . "/pagelib.php");

        $externalfunctioninfo = static::external_function_info($function);

        $currentpage = $PAGE;
        $currentcourse = $COURSE;
        $response = array();

        try {
            // Taken straight from from setup.php.
            if (!empty($CFG->moodlepageclass)) {
                if (!empty($CFG->moodlepageclassfile)) {
                    require_once($CFG->moodlepageclassfile);
                }
                $classname = $CFG->moodlepageclass;
            } else {
                $classname = 'moodle_page';
            }
            $PAGE = new $classname();
            $COURSE = clone($SITE);

            if ($ajaxonly && !$externalfunctioninfo->allowed_from_ajax) {
                throw new moodle_exception('servicenotavailable', 'webservice');
            }

            // Do not allow access to write or delete webservices as a public user.
            if ($externalfunctioninfo->loginrequired && !WS_SERVER) {
                if (defined('NO_MOODLE_COOKIES') && NO_MOODLE_COOKIES && !PHPUNIT_TEST) {
                    throw new moodle_exception('servicerequireslogin', 'webservice');
                }
                if (!isloggedin()) {
                    throw new moodle_exception('servicerequireslogin', 'webservice');
                } else {
                    require_sesskey();
                }
            }
            // Validate params, this also sorts the params properly, we need the correct order in the next part.
            $callable = array($externalfunctioninfo->classname, 'validate_parameters');
            $params = call_user_func($callable,
                                     $externalfunctioninfo->parameters_desc,
                                     $args);
            $params = array_values($params);

            // Allow any Moodle plugin a chance to override this call. This is a convenient spot to
            // make arbitrary behaviour customisations. The overriding plugin could call the 'real'
            // function first and then modify the results, or it could do a completely separate
            // thing.
            $callbacks = get_plugins_with_function('override_webservice_execution');
            $result = false;
            foreach ($callbacks as $plugintype => $plugins) {
                foreach ($plugins as $plugin => $callback) {
                    $result = $callback($externalfunctioninfo, $params);
                    if ($result !== false) {
                        break 2;
                    }
                }
            }

            // If the function was not overridden, call the real one.
            if ($result === false) {
                $callable = array($externalfunctioninfo->classname, $externalfunctioninfo->methodname);
                $result = call_user_func_array($callable, $params);
            }

            // Validate the return parameters.
            if ($externalfunctioninfo->returns_desc !== null) {
                $callable = array($externalfunctioninfo->classname, 'clean_returnvalue');
                $result = call_user_func($callable, $externalfunctioninfo->returns_desc, $result);
            }

            $response['error'] = false;
            $response['data'] = $result;
        } catch (Throwable $e) {
            $exception = get_exception_info($e);
            unset($exception->a);
            $exception->backtrace = format_backtrace($exception->backtrace, true);
            if (!debugging('', DEBUG_DEVELOPER)) {
                unset($exception->debuginfo);
                unset($exception->backtrace);
            }
            $response['error'] = true;
            $response['exception'] = $exception;
            // Do not process the remaining requests.
        }

        $PAGE = $currentpage;
        $COURSE = $currentcourse;

        return $response;
    }

    /**
     * Set context restriction for all following subsequent function calls.
     *
     * @param stdClass $context the context restriction
     * @since Moodle 2.0
     */
    public static function set_context_restriction($context) {
        self::$contextrestriction = $context;
    }

    /**
     * This method has to be called before every operation
     * that takes a longer time to finish!
     *
     * @param int $seconds max expected time the next operation needs
     * @since Moodle 2.0
     */
    public static function set_timeout($seconds=360) {
        $seconds = ($seconds < 300) ? 300 : $seconds;
        core_php_time_limit::raise($seconds);
    }

    /**
     * Validates submitted function parameters, if anything is incorrect
     * invalid_parameter_exception is thrown.
     * This is a simple recursive method which is intended to be called from
     * each implementation method of external API.
     *
     * @param external_description $description description of parameters
     * @param mixed $params the actual parameters
     * @return mixed params with added defaults for optional items, invalid_parameters_exception thrown if any problem found
     * @since Moodle 2.0
     */
    public static function validate_parameters(external_description $description, $params) {
        if ($description instanceof external_value) {
            if (is_array($params) or is_object($params)) {
                throw new invalid_parameter_exception('Scalar type expected, array or object received.');
            }

            if ($description->type == PARAM_BOOL) {
                // special case for PARAM_BOOL - we want true/false instead of the usual 1/0 - we can not be too strict here ;-)
                if (is_bool($params) or $params === 0 or $params === 1 or $params === '0' or $params === '1') {
                    return (bool)$params;
                }
            }
            $debuginfo = 'Invalid external api parameter: the value is "' . $params .
                    '", the server was expecting "' . $description->type . '" type';
            return validate_param($params, $description->type, $description->allownull, $debuginfo);

        } else if ($description instanceof external_single_structure) {
            if (!is_array($params)) {
                throw new invalid_parameter_exception('Only arrays accepted. The bad value is: \''
                        . print_r($params, true) . '\'');
            }
            $result = array();
            foreach ($description->keys as $key=>$subdesc) {
                if (!array_key_exists($key, $params)) {
                    if ($subdesc->required == VALUE_REQUIRED) {
                        throw new invalid_parameter_exception('Missing required key in single structure: '. $key);
                    }
                    if ($subdesc->required == VALUE_DEFAULT) {
                        try {
                            $result[$key] = static::validate_parameters($subdesc, $subdesc->default);
                        } catch (invalid_parameter_exception $e) {
                            //we are only interested by exceptions returned by validate_param() and validate_parameters()
                            //(in order to build the path to the faulty attribut)
                            throw new invalid_parameter_exception($key." => ".$e->getMessage() . ': ' .$e->debuginfo);
                        }
                    }
                } else {
                    try {
                        $result[$key] = static::validate_parameters($subdesc, $params[$key]);
                    } catch (invalid_parameter_exception $e) {
                        //we are only interested by exceptions returned by validate_param() and validate_parameters()
                        //(in order to build the path to the faulty attribut)
                        throw new invalid_parameter_exception($key." => ".$e->getMessage() . ': ' .$e->debuginfo);
                    }
                }
                unset($params[$key]);
            }
            if (!empty($params)) {
                throw new invalid_parameter_exception('Unexpected keys (' . implode(', ', array_keys($params)) . ') detected in parameter array.');
            }
            return $result;

        } else if ($description instanceof external_multiple_structure) {
            if (!is_array($params)) {
                throw new invalid_parameter_exception('Only arrays accepted. The bad value is: \''
                        . print_r($params, true) . '\'');
            }
            $result = array();
            foreach ($params as $param) {
                $result[] = static::validate_parameters($description->content, $param);
            }
            return $result;

        } else {
            throw new invalid_parameter_exception('Invalid external api description');
        }
    }

    /**
     * Clean response
     * If a response attribute is unknown from the description, we just ignore the attribute.
     * If a response attribute is incorrect, invalid_response_exception is thrown.
     * Note: this function is similar to validate parameters, however it is distinct because
     * parameters validation must be distinct from cleaning return values.
     *
     * @param external_description $description description of the return values
     * @param mixed $response the actual response
     * @return mixed response with added defaults for optional items, invalid_response_exception thrown if any problem found
     * @author 2010 Jerome Mouneyrac
     * @since Moodle 2.0
     */
    public static function clean_returnvalue(external_description $description, $response) {
        if ($description instanceof external_value) {
            if (is_array($response) or is_object($response)) {
                throw new invalid_response_exception('Scalar type expected, array or object received.');
            }

            if ($description->type == PARAM_BOOL) {
                // special case for PARAM_BOOL - we want true/false instead of the usual 1/0 - we can not be too strict here ;-)
                if (is_bool($response) or $response === 0 or $response === 1 or $response === '0' or $response === '1') {
                    return (bool)$response;
                }
            }
            $responsetype = gettype($response);
            $debuginfo = 'Invalid external api response: the value is "' . $response .
                    '" of PHP type "' . $responsetype . '", the server was expecting "' . $description->type . '" type';
            try {
                return validate_param($response, $description->type, $description->allownull, $debuginfo);
            } catch (invalid_parameter_exception $e) {
                //proper exception name, to be recursively catched to build the path to the faulty attribut
                throw new invalid_response_exception($e->debuginfo);
            }

        } else if ($description instanceof external_single_structure) {
            if (!is_array($response) && !is_object($response)) {
                throw new invalid_response_exception('Only arrays/objects accepted. The bad value is: \'' .
                        print_r($response, true) . '\'');
            }

            // Cast objects into arrays.
            if (is_object($response)) {
                $response = (array) $response;
            }

            $result = array();
            foreach ($description->keys as $key=>$subdesc) {
                if (!array_key_exists($key, $response)) {
                    if ($subdesc->required == VALUE_REQUIRED) {
                        throw new invalid_response_exception('Error in response - Missing following required key in a single structure: ' . $key);
                    }
                    if ($subdesc instanceof external_value) {
                        if ($subdesc->required == VALUE_DEFAULT) {
                            try {
                                    $result[$key] = static::clean_returnvalue($subdesc, $subdesc->default);
                            } catch (invalid_response_exception $e) {
                                //build the path to the faulty attribut
                                throw new invalid_response_exception($key." => ".$e->getMessage() . ': ' . $e->debuginfo);
                            }
                        }
                    }
                } else {
                    try {
                        $result[$key] = static::clean_returnvalue($subdesc, $response[$key]);
                    } catch (invalid_response_exception $e) {
                        //build the path to the faulty attribut
                        throw new invalid_response_exception($key." => ".$e->getMessage() . ': ' . $e->debuginfo);
                    }
                }
                unset($response[$key]);
            }

            return $result;

        } else if ($description instanceof external_multiple_structure) {
            if (!is_array($response)) {
                throw new invalid_response_exception('Only arrays accepted. The bad value is: \'' .
                        print_r($response, true) . '\'');
            }
            $result = array();
            foreach ($response as $param) {
                $result[] = static::clean_returnvalue($description->content, $param);
            }
            return $result;

        } else {
            throw new invalid_response_exception('Invalid external api response description');
        }
    }

    /**
     * Makes sure user may execute functions in this context.
     *
     * @param stdClass $context
     * @since Moodle 2.0
     */
    public static function validate_context($context) {
        global $CFG, $PAGE;

        if (empty($context)) {
            throw new invalid_parameter_exception('Context does not exist');
        }
        if (empty(self::$contextrestriction)) {
            self::$contextrestriction = context_system::instance();
        }
        $rcontext = self::$contextrestriction;

        if ($rcontext->contextlevel == $context->contextlevel) {
            if ($rcontext->id != $context->id) {
                throw new restricted_context_exception();
            }
        } else if ($rcontext->contextlevel > $context->contextlevel) {
            throw new restricted_context_exception();
        } else {
            $parents = $context->get_parent_context_ids();
            if (!in_array($rcontext->id, $parents)) {
                throw new restricted_context_exception();
            }
        }

        $PAGE->reset_theme_and_output();
        list($unused, $course, $cm) = get_context_info_array($context->id);
        require_login($course, false, $cm, false, true);
        $PAGE->set_context($context);
    }

    /**
     * Get context from passed parameters.
     * The passed array must either contain a contextid or a combination of context level and instance id to fetch the context.
     * For example, the context level can be "course" and instanceid can be courseid.
     *
     * See context_helper::get_all_levels() for a list of valid context levels.
     *
     * @param array $param
     * @since Moodle 2.6
     * @throws invalid_parameter_exception
     * @return context
     */
    protected static function get_context_from_params($param) {
        $levels = context_helper::get_all_levels();
        if (!empty($param['contextid'])) {
            return context::instance_by_id($param['contextid'], IGNORE_MISSING);
        } else if (!empty($param['contextlevel']) && isset($param['instanceid'])) {
            $contextlevel = "context_".$param['contextlevel'];
            if (!array_search($contextlevel, $levels)) {
                throw new invalid_parameter_exception('Invalid context level = '.$param['contextlevel']);
            }
           return $contextlevel::instance($param['instanceid'], IGNORE_MISSING);
        } else {
            // No valid context info was found.
            throw new invalid_parameter_exception('Missing parameters, please provide either context level with instance id or contextid');
        }
    }

    /**
     * Returns a prepared structure to use a context parameters.
     * @return external_single_structure
     */
    protected static function get_context_parameters() {
        $id = new external_value(
            PARAM_INT,
            'Context ID. Either use this value, or level and instanceid.',
            VALUE_DEFAULT,
            0
        );
        $level = new external_value(
            PARAM_ALPHA,
            'Context level. To be used with instanceid.',
            VALUE_DEFAULT,
            ''
        );
        $instanceid = new external_value(
            PARAM_INT,
            'Context instance ID. To be used with level',
            VALUE_DEFAULT,
            0
        );
        return new external_single_structure(array(
            'contextid' => $id,
            'contextlevel' => $level,
            'instanceid' => $instanceid,
        ));
    }

}

/**
 * Common ancestor of all parameter description classes
 *
 * @package    core_webservice
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 */
abstract class external_description {
    /** @var string Description of element */
    public $desc;

    /** @var bool Element value required, null not allowed */
    public $required;

    /** @var mixed Default value */
    public $default;

    /**
     * Contructor
     *
     * @param string $desc
     * @param bool $required
     * @param mixed $default
     * @since Moodle 2.0
     */
    public function __construct($desc, $required, $default) {
        $this->desc = $desc;
        $this->required = $required;
        $this->default = $default;
    }
}

/**
 * Scalar value description class
 *
 * @package    core_webservice
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 */
class external_value extends external_description {

    /** @var mixed Value type PARAM_XX */
    public $type;

    /** @var bool Allow null values */
    public $allownull;

    /**
     * Constructor
     *
     * @param mixed $type
     * @param string $desc
     * @param bool $required
     * @param mixed $default
     * @param bool $allownull
     * @since Moodle 2.0
     */
    public function __construct($type, $desc='', $required=VALUE_REQUIRED,
            $default=null, $allownull=NULL_ALLOWED) {
        parent::__construct($desc, $required, $default);
        $this->type      = $type;
        $this->allownull = $allownull;
    }
}

/**
 * Associative array description class
 *
 * @package    core_webservice
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 */
class external_single_structure extends external_description {

     /** @var array Description of array keys key=>external_description */
    public $keys;

    /**
     * Constructor
     *
     * @param array $keys
     * @param string $desc
     * @param bool $required
     * @param array $default
     * @since Moodle 2.0
     */
    public function __construct(array $keys, $desc='',
            $required=VALUE_REQUIRED, $default=null) {
        parent::__construct($desc, $required, $default);
        $this->keys = $keys;
    }
}

/**
 * Bulk array description class.
 *
 * @package    core_webservice
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 */
class external_multiple_structure extends external_description {

     /** @var external_description content */
    public $content;

    /**
     * Constructor
     *
     * @param external_description $content
     * @param string $desc
     * @param bool $required
     * @param array $default
     * @since Moodle 2.0
     */
    public function __construct(external_description $content, $desc='',
            $required=VALUE_REQUIRED, $default=null) {
        parent::__construct($desc, $required, $default);
        $this->content = $content;
    }
}

/**
 * Description of top level - PHP function parameters.
 *
 * @package    core_webservice
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 */
class external_function_parameters extends external_single_structure {

    /**
     * Constructor - does extra checking to prevent top level optional parameters.
     *
     * @param array $keys
     * @param string $desc
     * @param bool $required
     * @param array $default
     */
    public function __construct(array $keys, $desc='', $required=VALUE_REQUIRED, $default=null) {
        global $CFG;

        if ($CFG->debugdeveloper) {
            foreach ($keys as $key => $value) {
                if ($value instanceof external_value) {
                    if ($value->required == VALUE_OPTIONAL) {
                        debugging('External function parameters: invalid OPTIONAL value specified.', DEBUG_DEVELOPER);
                        break;
                    }
                }
            }
        }
        parent::__construct($keys, $desc, $required, $default);
    }
}
>>>>>>> upstream/MOODLE_38_STABLE

/**
 * Generate a token
 *
 * @param string $tokentype EXTERNAL_TOKEN_EMBEDDED|EXTERNAL_TOKEN_PERMANENT
 * @param stdClass|int $serviceorid service linked to the token
 * @param int $userid user linked to the token
 * @param stdClass|int $contextorid
 * @param int $validuntil date when the token expired
 * @param string $iprestriction allowed ip - if 0 or empty then all ips are allowed
 * @return string generated token
 * @since Moodle 2.0
 */
function external_generate_token($tokentype, $serviceorid, $userid, $contextorid, $validuntil = 0, $iprestriction = '') {
    if (is_numeric($serviceorid)) {
        $service = util::get_service_by_id($serviceorid);
    } else if (is_string($serviceorid)) {
        $service = util::get_service_by_name($serviceorid);
    } else {
        $service = $serviceorid;
    }

    if (!is_object($contextorid)) {
        $context = context::instance_by_id($contextorid, MUST_EXIST);
    } else {
        $context = $contextorid;
    }

<<<<<<< HEAD
    return util::generate_token(
        $tokentype,
        $service,
        $userid,
        $context,
        $validuntil,
        $iprestriction
    );
=======
    $newtoken->contextid = $context->id;
    $newtoken->creatorid = $USER->id;
    $newtoken->timecreated = time();
    $newtoken->validuntil = $validuntil;
    if (!empty($iprestriction)) {
        $newtoken->iprestriction = $iprestriction;
    }
    // Generate the private token, it must be transmitted only via https.
    $newtoken->privatetoken = random_string(64);
    $DB->insert_record('external_tokens', $newtoken);
    return $newtoken->token;
>>>>>>> upstream/MOODLE_38_STABLE
}

/**
 * Create and return a session linked token. Token to be used for html embedded client apps that want to communicate
 * with the Moodle server through web services. The token is linked to the current session for the current page request.
 * It is expected this will be called in the script generating the html page that is embedding the client app and that the
 * returned token will be somehow passed into the client app being embedded in the page.
 *
 * @param string $servicename name of the web service. Service name as defined in db/services.php
 * @param int $context context within which the web service can operate.
 * @return string returns token id.
 * @since Moodle 2.0
 */
function external_create_service_token($servicename, $contextid) {
    global $USER;

    return util::generate_token(
        EXTERNAL_TOKEN_EMBEDDED,
        util::get_service_by_name($servicename),
        $USER->id,
        \context::instance_by_id($contextid)
    );
}

/**
 * Delete all pre-built services (+ related tokens) and external functions information defined in the specified component.
 *
 * @param string $component name of component (moodle, etc.)
 */
function external_delete_descriptions($component) {
    util::delete_service_descriptions($component);
}

/**
 * Validate text field format against known FORMAT_XXX
 *
 * @param array $format the format to validate
 * @return the validated format
 * @throws coding_exception
 * @since Moodle 2.3
 */
function external_validate_format($format) {
    return util::validate_format($format);
}

/**
 * Format the string to be returned properly as requested by the either the web service server,
 * either by an internally call.
 * The caller can change the format (raw) with the external_settings singleton
 * All web service servers must set this singleton when parsing the $_GET and $_POST.
 *
 * <pre>
 * Options are the same that in {@link format_string()} with some changes:
 *      filter      : Can be set to false to force filters off, else observes {@link external_settings}.
 * </pre>
 *
 * @param string $str The string to be filtered. Should be plain text, expect
 * possibly for multilang tags.
 * @param boolean $striplinks To strip any link in the result text. Moodle 1.8 default changed from false to true! MDL-8713
 * @param context|int $contextorid The id of the context for the string or the context (affects filters).
 * @param array $options options array/object or courseid
 * @return string text
 * @since Moodle 3.0
 */
function external_format_string($str, $context, $striplinks = true, $options = []) {
    if (!$context instanceof context) {
        $context = context::instance_by_id($context);
    }

    return util::format_string($str, $context, $striplinks, $options);
}

/**
 * Format the text to be returned properly as requested by the either the web service server,
 * either by an internally call.
 * The caller can change the format (raw, filter, file, fileurl) with the external_settings singleton
 * All web service servers must set this singleton when parsing the $_GET and $_POST.
 *
 * <pre>
 * Options are the same that in {@link format_text()} with some changes in defaults to provide backwards compatibility:
 *      trusted     :   If true the string won't be cleaned. Default false.
 *      noclean     :   If true the string won't be cleaned only if trusted is also true. Default false.
 *      nocache     :   If true the string will not be cached and will be formatted every call. Default false.
 *      filter      :   Can be set to false to force filters off, else observes {@link external_settings}.
 *      para        :   If true then the returned string will be wrapped in div tags. Default (different from format_text) false.
 *                      Default changed because div tags are not commonly needed.
 *      newlines    :   If true then lines newline breaks will be converted to HTML newline breaks. Default true.
 *      context     :   Not used! Using contextid parameter instead.
 *      overflowdiv :   If set to true the formatted text will be encased in a div with the class no-overflow before being
 *                      returned. Default false.
 *      allowid     :   If true then id attributes will not be removed, even when using htmlpurifier. Default (different from
 *                      format_text) true. Default changed id attributes are commonly needed.
 *      blanktarget :   If true all <a> tags will have target="_blank" added unless target is explicitly specified.
 * </pre>
 *
 * @param string $text The content that may contain ULRs in need of rewriting.
 * @param int $textformat The text format.
 * @param context|int $context This parameter and the next two identify the file area to use.
 * @param string $component
 * @param string $filearea helps identify the file area.
 * @param int $itemid helps identify the file area.
 * @param object/array $options text formatting options
 * @return array text + textformat
 * @since Moodle 2.3
 * @since Moodle 3.2 component, filearea and itemid are optional parameters
 */
function external_format_text($text, $textformat, $context, $component = null, $filearea = null, $itemid = null, $options = null) {
    if (!$context instanceof context) {
        $context = context::instance_by_id($context);
    }

    return util::format_text($text, $textformat, $context, $component, $filearea, $itemid, $options);
}

/**
 * Generate or return an existing token for the current authenticated user.
 * This function is used for creating a valid token for users authenticathing via login/token.php or admin/tool/mobile/launch.php.
 *
 * @param stdClass $service external service object
 * @return stdClass token object
 * @since Moodle 3.2
 */
function external_generate_token_for_current_user($service) {
    return util::generate_token_for_current_user($service);
}

/**
 * Set the last time a token was sent and trigger the \core\event\webservice_token_sent event.
 *
 * This function is used when a token is generated by the user via login/token.php or admin/tool/mobile/launch.php.
 * In order to protect the privatetoken, we remove it from the event params.
 *
 * @param  stdClass $token token object
 * @since  Moodle 3.2
 */
function external_log_token_request($token): void {
    util::log_token_request($token);
}
