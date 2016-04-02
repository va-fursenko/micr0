<?php
class View_Base_593616de15330c0fb2d55e55410bf994 extends ViewInstance
{
	public static function display($data)
	{
		parent::display($data); 
		$result = '<div class="row log-row">
    <h4 class="log-caption">micro :) \'' . self::getVar('button.text', true) . '\'</h4>
    <img class="log-caption log-loader" id="logLoader" src="img/loader.gif">
    <a id="beginBtn" class="log-caption btn btn-primary" href="javascript:void(0);">Красная кнопка</a>
</div>

';
		if (self::getVar('echo_bool', false)) {
			$result .= '<span>ПРАВДА</span>';
		} else {
			$result .= '<span>ЛОЖЬ</span>';
		}
		$result .= '

<div class="container">
    <table class="table table-striped table-condensed table-bordered table-hover">
        <caption>Таблица с какой-то залупой</caption>
        <thead>
            <tr>
                <th>№</th>
                <th>Город</th>
                <th>Страна</th>
                <th>Население</th>
            </tr>
        </thead>
        <tbody>
            ';
		foreach (self::getVar('some_el.rows', false) as $index => $row) {
			$result .= '<tr>
                <td>' . ($index + 1) . '.</td>
                <td>' . self::getVar('row.city', true, ['row' => $row], 'row.0') . '</td>
                <td>' . self::getVar('row.country', false, ['row' => $row], 'row.1') . '</td>
                <td>' . self::getVar('row.population', false, ['row' => $row], 'row.2') . '</td>
            </tr>';
		}
		$result .= '
        </tbody>
    </table>
</div>

<div class="container">
    ';
		if (self::getVar('place_button', false)) {
			$result .= '<a class="btn btn-success" href="javascript:void(0);">' . self::getVar('button.text', false) . '</a>';
		} else {
			$result .= '<input type="email" class="form-control" value="' . self::getVar('email', false) . '" placeholder="Введи имайлку"/>';
		}
		$result .= '
</div>

        ' . self::getVar('some_data', false) . '

<div>
<!--
    {{?some_flag=1}}
        <span class="label">first label</span>
    {{?some_flag="warning"}}
        <span class="label label-warning">Some warning label</span>
    {{?some_flag=\'place_button\'}}
        <span class="label label-success">Some success label</span>
    {{?some_flag=8}}
        <span class="label label-success">Some success label</span>
    {{!some_flag}}
        <span class="label label-important">DEFAULT LABEL</span>
    {{;some_flag}}
-->
</div>


';
		if (self::getVar('flag3', false)) {
			$result .= 'Если флаг3 истина, то вывести эту бессмыслицу';
		}
		$result .= '

<pre class="log-container" id="logPre">' . self::getVar('lines', false) . '</pre>
';
		return $result;
	}
}