<?php

// Модуль для обработки пользовательских и системных ошибок
//------------------------------------------------------------------------------
// Раздел подключения модулей

    include_once('class.file.php');
    include_once(CORE_DIR . 'system/systemLogsStack.model.php');


// Описание модуля
//------------------------------------------------------------------------------

// Функция обработки пользовательских ошибок
    function errorHandlerCatchError($code, $msg, $file, $line){
        return ErrorHandler::pushError(array(
            'datetime'          => date("Y-m-d H:i:s"),
            'session_id'        => Session::getId(),
            'session_user_id'   => Session::userGet('id', -1),
            'php_error_code'    => ErrorHandler::getStrError($code),
            'php_file_name'     => $file,
            'php_file_line'     => $line,
            'text_message'      => $msg
        ));
    }

/**
 * @todo Избавиться от записи и хранения ошибок в файле из-за синхронного доступа к нему всех пользователей системы
 */
/**
 * Класс работы со стеком ошибок
 * @version   3.2.1
 * @author    Гончаров Станислав stascer@mail.ru
 * @author    Фурсенко Виктор vinjoy@bk.ru
 * @copyright stascer, viktor
 */
class ErrorHandler{
    // Режим отладки
    protected static $_debugMode = true;
    // Файл стека по умолчанию
    protected static $_stackFileName = LOG_STACK_FILE;
    
    /** Добавляет в стек в БД ошибку */
    static function pushError($error){
        $db = Db::getInstance();
        if ($db && $db->isConnected()) {
            Db::blockLogging(true);
            $result = SystemLogsStack::addOne($db, $error);
            Db::blockLogging(false);
        }else{
            $result = self::pushError2File($error, self::getStackFileName());
        }
        return $result;
    }

    /** Запись ошибки в стек и перезагрузка */
    static function setError($text, $file){
        ErrorHandler::pushError(array(
            'datetime'          => date("Y-m-d H:i:s"),
            'session_id'        => Session::getId(),
            'session_user_id'   => Session::userGet('id', -1),
            'php_error_code'    => ErrorHandler::getStrError(E_WARNING),
            'php_file_name'     => $file,
            'php_file_line'     => 0,
            'text_message'      => $text
        ));
        ErrorHandler::go($_SERVER['REQUEST_URI']);
    }

    /** Возвращает текст с непросмотренными ошибками текущего пользователя в формате HTML */
    static function getErrors(){
        Db::blockLogging(true);
        $errors = SystemLogsStack::getSelfList(Db::getInstance());
        $tpl = new TPL(TPL_DIR . 'errors.html');
        $ids = array();
        $content = '';
        foreach ($errors as $error){
            $ids[] = $error['id'];
            $content .= $tpl->parseBlock(
                array(
                    'code' => $error['php_error_code'],
                    'date' => $error['datetime'],
                    'file' => $error['php_file_name'],
                    'line' => $error['php_file_line'],
                    'msg'  => $error['text_message']
                ),
                'error'
            );
        }
        if (count($errors)){
            //SystemLogsStack::delete(Db::getInstance(), $ids);
            SystemLogsStack::markViewed(Db::getInstance(), $ids);
        }
        Db::blockLogging(false);
        return $content;
    }

    /** Проверяет пуст ли стек ошибок */
    static function checkStack($self = true){
        Db::blockLogging(true);
        $result = SystemLogsStack::getCount(Db::getInstance(), $self);
        Db::blockLogging(false);
        return $result;
    }
    
    /** Получает список всех записей в стеке */
    static function getErrorsList($startFrom, $limit, $descOrder = true){
        Db::blockLogging(true);
        $errors = SystemLogsStack::getList(Db::getInstance(), $startFrom, $limit, 'datetime', $descOrder);
        Db::blockLogging(false);
        return $errors;
    }

    /** Переадресация на адрес */
    static function go($url){
        header('Location: ' . $url);
    }

    /** Вывод пользовательской ошибки в html представлении */
    static function getUserError($type){
       $tpl = new TPL(TPL_DIR.'errors.html');
       $error = '';
       switch ($type){
            // Ошибка на сайте
            case 'page_error':
                $error = $tpl->parseBlock(array(), 'page_error');
                break;

            // Неправильный запрашиваемый адрес
            case 'bad_url':
                $error = $tpl->parseBlock(array(), 'bad_url');
                break;
				
            // Неправильная сессия
            case 'bad_session':
                $error = $tpl->parseBlock(array(), 'bad_session');
                break;

	    // Доступ запрещён
            case 'access_denied':
                $error = $tpl->parseBlock(array(), 'access_denied');
                break;

            // Неправильные данные
            case 'bad_data':
                $error = $tpl->parseBlock(array(), 'bad_data');
                break;

            // Неизвестная запись
            case 'unknown_item':
                $error = $tpl->parseBlock(array(), 'unknown_item');
                break;

            // БД недоступна
            case 'db_unreachable':
                $error = $tpl->parseBlock(array(), 'db_unreachable');
                break;
				
            // Неизвестная команда
            case 'unknown_command':
                $error = $tpl->parseBlock(array(), 'unknown_command');
                break;

            default:
                $error = 'Unknown unknown address O_o<BR>You deserve the admiration of programmers!';
       }
       return $error;
    }

    /** Получение типа ошибки в строковом виде */
    static function getStrError($err_code){
        $err_arr = array(
            E_ERROR 		=> 'E_ERROR',
            E_WARNING		=> 'E_WARNING',
            E_PARSE		=> 'E_PARSE',
            E_NOTICE		=> 'E_NOTICE',
            E_CORE_ERROR	=> 'E_CORE_ERROR',
            E_CORE_WARNING	=> 'E_CORE_WARNING',
            E_COMPILE_ERROR	=> 'E_COMPILE_ERROR',
            E_COMPILE_WARNING	=> 'E_COMPILE_WARNING',
            E_USER_ERROR	=> 'E_USER_ERROR',
            E_USER_WARNING	=> 'E_USER_WARNING',
            E_USER_NOTICE	=> 'E_USER_NOTICE',
            E_ALL		=> 'E_ALL'
        );
        if (isset($err_arr[$err_code])){
            return $err_arr[$err_code];
        }else{
            return $err_code;
        }
    }

    /** Устанавливает обработчик на программные ошибки */
    static function setErrorHandler(){
       set_error_handler('errorHandlerCatchError', E_ALL);
    }
    
    /** Установка режима отладки */
    static function setDebugMode($debugMode){
        self::$_debugMode = $debugMode;
    }
    
    /** Добавляет в файл стека ошибку */
    static function pushError2File($error, $errorStackFile){
        if(is_array($error)){
            $str = "";
            foreach($error as $i => $value){
                $str .= $value . ";";
            }
            $str .= "\n";
            $file = new File($errorStackFile);
            return $file->append($str);
        }else{
            return false;
        }
    }
    
    /** Вывод на экран файла стека ошибок и его очистка */
    static function showStackFile($errorStackFile){
        $content = '';
        $tpl = new TPL(TPL_DIR.'errors.html');
        $file = new File($errorStackFile);
        $listErrors = $file->getStringList();
        $content = '';
        foreach($listErrors as $item){
            $errors = explode(';', $item);
            $content .= $tpl->parseBlock(array(
                    'code' => $errors[1],
                    'date' => $errors[0],
                    'file' => $errors[2],
                    'line' => $errors[4],
                    'msg'  => $errors[3],
                ),
            'error');
        }
        $file->clearFile();
        return $content;
    }

    /** Проверяет пуст ли файл стека с ошибками */
    static function isEmptyStackFile($errorStackFile) {
        $file = new File($errorStackFile);
        return $file->isEmpty();
    }
    
    /** Возвращает файл стека ошибок */
    static function getStackFileName(){
        return self::$_stackFileName;
    }
    
    /** Устанавлвает файл стека ошибок */
    static function  setStaticFileName($stackFileName){
        self::$_stackFileName = $stackFileName;
        return (bool)$stackFileName;
    }
 }



?>
