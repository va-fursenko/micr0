<?php
/**
 * Created by PhpStorm.
 * User: Виктор
 * Date: 08.03.2016
 * Time: 0:12
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.BaseException.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.ViewParser.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.Language.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.Filter.php');

/*
 * Языковые константы обозначатся тегами {L_ИМЯ_КОНСТАНТЫ}
 */

/** Собственное исключение класса */
class LayoutException extends BaseException{
    # Языковые константы класса
    const L_VIEW_FILE_UNREACHABLE = 'Файл с шаблоном недоступен';
    const L_VIEW_DB_UNREACHABLE   = 'База данных с темплейтами недоступна';
    const L_VIEW_BLOCK_UNKNOWN    = 'Шаблон не найден';
}


/** @todo Вынести замену языковых констант в отдельный метод класса View, чтобы заменять их в уже готовом к выводу тексте страницы */
/** @todo Работа с шаблонами и страницами(слоями) */

/**
 * Класс лэйаута
 */
class Layout
{
    # Подключаем трейты
    use UseDb; # Статическое свойство и методы для работы с объектом Db



    protected $language = '';      # Алиас языка для работы с мультиязычностью

    /*
            //Заменяем языковые константы
            preg_match_all('/\({L_[a-zA-Z_0-9]+\})/', $strContainer, $lang);
            // Получаем массив использованных в данном шаблоне языковых констант
            $lang = Language::getList($this->db(), TPL_DEFAULT_LANGUAGE, $lang[1]);
            // Проходим в цикле и меняем все
            foreach ($lang as $key => $value) {
               $strContainer = str_replace($key, $value, $strContainer);
            }
    */
} 
